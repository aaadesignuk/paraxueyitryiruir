<?php

/**
 * Paralegal monthly invoicing
 *
 * Locked rules (agreed):
 * - One invoice per (employer_id, paralegal_id, month).
 * - Only Approved / Deemed Approved timesheets are invoiceable.
 * - One invoice line per Approved timesheet (NOT per assignment, NOT per entry).
 * - Draft invoices can be rebuilt (idempotent) at any time.
 * - Submitted/Paid invoices are frozen and must NOT be rebuilt automatically.
 * - Timesheets included in an invoice are locked via timesheets.paralegal_invoice_id.
 *
 * Notes:
 * - Rate is frozen per assignment (job_assignments.agreed_rate) at generation time by copying into invoice_items.hourly_rate.
 * - Employer private client ref is snapshotted onto invoice items (client_ref_snapshot) if the column exists.
 */

function month_period_bounds(int $year, int $month): array {
  $start = sprintf('%04d-%02d-01', $year, $month);
  $end = date('Y-m-t', strtotime($start));
  return [$start, $end];
}

/**
 * Upsert commission invoice for an employer+period, derived from paralegal invoice totals.
 *
 * Rules:
 * - Commission is derived from paralegal_invoices.gross_amount (source of truth).
 * - If a commission invoice exists and status='Paid', do NOT modify it.
 * - Otherwise, insert/update with status='Unpaid'.
 */
function commission_upsert_for_period(int $employer_id, string $period_start, string $period_end): void {
  $gross = (float)db_fetch_value(
    "SELECT COALESCE(SUM(gross_amount),0)
       FROM paralegal_invoices
      WHERE employer_id=? AND period_start=? AND period_end=?",
    [$employer_id, $period_start, $period_end]
  );
  $gross = round($gross, 2);
  if ($gross <= 0) return;

  $pct = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);
  $pct = round($pct, 4);
  $comm = round($gross * ($pct / 100), 2);

  $existing = db_fetch_one(
    "SELECT invoice_id, status
       FROM commission_invoices
      WHERE employer_id=? AND period_start=? AND period_end=?
      LIMIT 1",
    [$employer_id, $period_start, $period_end]
  );

  if ($existing) {
    if (($existing['status'] ?? '') === 'Paid') {
      return;
    }
    db_query(
      "UPDATE commission_invoices
          SET gross_amount=?, commission_rate=?, commission_amount=?
        WHERE invoice_id=?",
      [$gross, $pct, $comm, (int)$existing['invoice_id']]
    );
    return;
  }

  db_query(
    "INSERT INTO commission_invoices
       (employer_id, period_start, period_end, gross_amount, commission_rate, commission_amount, status, created_at)
     VALUES
       (?,?,?,?,?,?, 'Unpaid', NOW())",
    [$employer_id, $period_start, $period_end, $gross, $pct, $comm]
  );
}

function paralegal_generate_monthly_invoices_for_paralegal(int $paralegal_id, int $year, int $month): array {
  [$start, $end] = month_period_bounds($year, $month);

  $rows = db_fetch_all(
    "SELECT
        t.timesheet_id,
        t.assignment_id,
        t.work_date,
        t.hours_worked,
        t.paralegal_invoice_id,
        ja.employer_id,
        ja.paralegal_id,
        ja.agreed_rate,
        j.title AS job_title,
        COALESCE(j.employer_client_ref, '') AS employer_client_ref
      FROM timesheets t
      JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
      JOIN jobs j ON j.job_id = ja.job_id
      WHERE ja.paralegal_id = ?
        AND t.status IN ('Approved','Deemed Approved')
        AND t.work_date BETWEEN ? AND ?
      ORDER BY ja.employer_id, t.work_date, t.timesheet_id",
    [$paralegal_id, $start, $end]
  );

  if (!$rows) {
    return ['created' => 0, 'rebuilt' => 0, 'skipped' => 0];
  }

  $byEmployer = [];
  foreach ($rows as $r) {
    $eid = (int)$r['employer_id'];
    if (!isset($byEmployer[$eid])) $byEmployer[$eid] = [];
    $byEmployer[$eid][] = $r;
  }

  $created = 0;
  $rebuilt = 0;
  $skipped = 0;

  $pdo = db();
  $pdo->beginTransaction();
  try {
    foreach ($byEmployer as $employer_id => $timesheets) {

      $existing = db_fetch_one(
        "SELECT invoice_id, status FROM paralegal_invoices
         WHERE employer_id=? AND paralegal_id=? AND period_start=? AND period_end=?
         LIMIT 1",
        [(int)$employer_id, (int)$paralegal_id, $start, $end]
      );

      $invoice_id = 0;
      $status = null;
      if ($existing) {
        $invoice_id = (int)$existing['invoice_id'];
        $status = (string)$existing['status'];
        if ($status !== 'Draft') {
          $skipped++;
          continue;
        }

        db_query("DELETE FROM paralegal_invoice_items WHERE invoice_id=?", [$invoice_id]);
        db_query("UPDATE timesheets SET paralegal_invoice_id=NULL, paralegal_invoiced_at=NULL WHERE paralegal_invoice_id=?", [$invoice_id]);
      }

      $items = [];
      $all_timesheet_ids = [];
      $total_hours = 0.0;
      $gross = 0.0;

      foreach ($timesheets as $t) {
        $rate = (float)($t['agreed_rate'] ?? 0);
        $hours = (float)($t['hours_worked'] ?? 0);
        if ($hours <= 0) continue;
        if ($rate <= 0) {
          $skipped++;
          continue;
        }

        $amount = round($hours * $rate, 2);
        $items[] = [
          'timesheet_id' => (int)$t['timesheet_id'],
          'assignment_id' => (int)$t['assignment_id'],
          'work_date' => $t['work_date'],
          'hours' => round($hours, 2),
          'rate' => round($rate, 2),
          'amount' => $amount,
          'job_title' => (string)($t['job_title'] ?? ''),
          'client_ref' => (string)($t['employer_client_ref'] ?? ''),
        ];

        $total_hours += $hours;
        $gross += $amount;
        $all_timesheet_ids[] = (int)$t['timesheet_id'];
      }

      if (!$items) {
        continue;
      }

      if (!$invoice_id) {
        db_query(
          "INSERT INTO paralegal_invoices (employer_id, paralegal_id, period_start, period_end, total_hours, gross_amount, status, created_at)
           VALUES (?,?,?,?,?,?,'Draft', NOW())",
          [(int)$employer_id, (int)$paralegal_id, $start, $end, round($total_hours, 2), round($gross, 2)]
        );
        $invoice_id = (int)$pdo->lastInsertId();
        $created++;
      } else {
        db_query(
          "UPDATE paralegal_invoices
           SET total_hours=?, gross_amount=?
           WHERE invoice_id=?",
          [round($total_hours, 2), round($gross, 2), $invoice_id]
        );
        $rebuilt++;
      }

      foreach ($items as $it) {
        db_query(
          "INSERT INTO paralegal_invoice_items
             (invoice_id, assignment_id, timesheet_id, work_date, hours, hourly_rate, amount, job_title_snapshot, client_ref_snapshot)
           VALUES
             (?,?,?,?,?,?,?,?,?)",
          [
            $invoice_id,
            (int)$it['assignment_id'],
            (int)$it['timesheet_id'],
            $it['work_date'],
            $it['hours'],
            $it['rate'],
            $it['amount'],
            $it['job_title'],
            $it['client_ref'],
          ]
        );
      }

      if ($all_timesheet_ids) {
        $placeholders = implode(',', array_fill(0, count($all_timesheet_ids), '?'));
        $params = array_merge([$invoice_id], $all_timesheet_ids);
        db_query("UPDATE timesheets SET paralegal_invoice_id=?, paralegal_invoiced_at=NOW() WHERE timesheet_id IN ($placeholders)", $params);
      }

      commission_upsert_for_period((int)$employer_id, $start, $end);
    }

    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }

  return ['created' => $created, 'rebuilt' => $rebuilt, 'skipped' => $skipped];
}

function paralegal_generate_monthly_invoices_all(int $year, int $month): array {
  [$start, $end] = month_period_bounds($year, $month);

  $paralegals = db_fetch_all(
    "SELECT DISTINCT ja.paralegal_id
     FROM job_assignments ja
     JOIN timesheets t ON t.assignment_id = ja.assignment_id
     WHERE t.status IN ('Approved','Deemed Approved')
       AND t.work_date BETWEEN ? AND ?",
    [$start, $end]
  );

  $total = ['created' => 0, 'rebuilt' => 0, 'skipped' => 0];
  foreach ($paralegals as $p) {
    $r = paralegal_generate_monthly_invoices_for_paralegal((int)$p['paralegal_id'], $year, $month);
    $total['created'] += $r['created'];
    $total['rebuilt'] += $r['rebuilt'];
    $total['skipped'] += $r['skipped'];
  }

  return $total;
}