<?php

/**
 * Paralegal monthly invoicing
 *
 * Locked rules:
 * - One invoice per (employer_id, paralegal_id, month).
 * - Only Approved / Deemed Approved timesheets are invoiceable.
 * - One invoice line per assignment + work_date (daily line).
 * - Draft invoices can be rebuilt idempotently.
 * - Submitted/Paid invoices are frozen.
 * - Existing duplicate draft invoices for the same employer/paralegal/month are consolidated.
 */

function month_period_bounds(int $year, int $month): array {
  $start = sprintf('%04d-%02d-01', $year, $month);
  $end = date('Y-m-t', strtotime($start));
  return [$start, $end];
}

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
        ja.employer_id,
        ja.paralegal_id,
        ja.job_id,
        ja.agreed_rate,
        j.title AS job_title,
        COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS employer_client_ref
      FROM timesheets t
      JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
      JOIN jobs j ON j.job_id = ja.job_id
      WHERE ja.paralegal_id = ?
        AND t.status IN ('Approved','Deemed Approved')
        AND t.work_date BETWEEN ? AND ?
      ORDER BY ja.employer_id, t.work_date, t.assignment_id, t.timesheet_id",
    [$paralegal_id, $start, $end]
  );

  if (!$rows) {
    return ['created' => 0, 'rebuilt' => 0, 'skipped' => 0];
  }

  $byEmployer = [];
  foreach ($rows as $r) {
    $eid = (int)$r['employer_id'];
    if (!isset($byEmployer[$eid])) {
      $byEmployer[$eid] = [
        'employer_id' => $eid,
        'job_id' => (int)($r['job_id'] ?? 0), // representative only for legacy pages
        'groups' => [],
      ];
    }

    $groupKey = (int)$r['assignment_id'] . '|' . (string)$r['work_date'];
    if (!isset($byEmployer[$eid]['groups'][$groupKey])) {
      $byEmployer[$eid]['groups'][$groupKey] = [
        'assignment_id' => (int)$r['assignment_id'],
        'work_date' => (string)$r['work_date'],
        'job_title' => (string)($r['job_title'] ?? ''),
        'client_ref' => (string)($r['employer_client_ref'] ?? ''),
        'rate' => (float)($r['agreed_rate'] ?? 0),
        'hours' => 0.0,
        'timesheet_ids' => [],
      ];
    }

    $byEmployer[$eid]['groups'][$groupKey]['hours'] += (float)($r['hours_worked'] ?? 0);
    $byEmployer[$eid]['groups'][$groupKey]['timesheet_ids'][] = (int)$r['timesheet_id'];
  }

  $created = 0;
  $rebuilt = 0;
  $skipped = 0;

  $pdo = db();
  $pdo->beginTransaction();
  try {
    foreach ($byEmployer as $pack) {
      $employer_id = (int)$pack['employer_id'];
      $job_id = (int)$pack['job_id'];
      $groups = $pack['groups'];

      $existing_all = db_fetch_all(
        "SELECT invoice_id, status
           FROM paralegal_invoices
          WHERE employer_id=? AND paralegal_id=? AND period_start=? AND period_end=?
          ORDER BY invoice_id ASC",
        [$employer_id, $paralegal_id, $start, $end]
      );

      $invoice_id = 0;
      $locked_exists = false;
      $draft_ids = [];
      foreach ($existing_all as $ex) {
        $status = (string)($ex['status'] ?? '');
        if ($status === 'Draft') {
          $draft_ids[] = (int)$ex['invoice_id'];
        } else {
          $locked_exists = true;
        }
      }

      if ($locked_exists) {
        $skipped++;
        continue;
      }

      if ($draft_ids) {
        $invoice_id = (int)array_shift($draft_ids);
        foreach ($draft_ids as $dup_id) {
          db_query("DELETE FROM paralegal_invoice_items WHERE invoice_id=?", [(int)$dup_id]);
          db_query("UPDATE timesheets SET paralegal_invoice_id=NULL, paralegal_invoiced_at=NULL WHERE paralegal_invoice_id=?", [(int)$dup_id]);
          db_query("DELETE FROM paralegal_invoices WHERE invoice_id=? AND status='Draft'", [(int)$dup_id]);
        }

        db_query("DELETE FROM paralegal_invoice_items WHERE invoice_id=?", [$invoice_id]);
        db_query(
          "UPDATE timesheets
              SET paralegal_invoice_id=NULL, paralegal_invoiced_at=NULL
            WHERE paralegal_invoice_id=?",
          [$invoice_id]
        );
      }

      uasort($groups, function ($a, $b) {
        if ($a['work_date'] !== $b['work_date']) {
          return strcmp($a['work_date'], $b['work_date']);
        }
        return ((int)$a['assignment_id']) <=> ((int)$b['assignment_id']);
      });

      $items = [];
      $all_timesheet_ids = [];
      $total_hours = 0.0;
      $gross = 0.0;

      foreach ($groups as $g) {
        $hours = round((float)$g['hours'], 2);
        $rate = round((float)$g['rate'], 2);
        if ($hours <= 0) continue;
        if ($rate <= 0) {
          $skipped++;
          continue;
        }

        $amount = round($hours * $rate, 2);
        $rep_timesheet_id = (int)min($g['timesheet_ids']);

        $items[] = [
          'timesheet_id' => $rep_timesheet_id,
          'assignment_id' => (int)$g['assignment_id'],
          'work_date' => (string)$g['work_date'],
          'hours' => $hours,
          'rate' => $rate,
          'amount' => $amount,
          'job_title' => (string)$g['job_title'],
          'client_ref' => (string)$g['client_ref'],
        ];

        foreach ($g['timesheet_ids'] as $tid) {
          $all_timesheet_ids[] = (int)$tid;
        }

        $total_hours += $hours;
        $gross += $amount;
      }

      if (!$items) {
        continue;
      }

      $total_hours = round($total_hours, 2);
      $gross = round($gross, 2);
      $all_timesheet_ids = array_values(array_unique($all_timesheet_ids));

      if (!$invoice_id) {
        try {
          db_query(
            "INSERT INTO paralegal_invoices
               (employer_id, paralegal_id, job_id, period_start, period_end, total_hours, gross_amount, status, created_at)
             VALUES
               (?,?,?,?,?,?,?,'Draft', NOW())",
            [$employer_id, $paralegal_id, $job_id, $start, $end, $total_hours, $gross]
          );
          $invoice_id = (int)$pdo->lastInsertId();
          $created++;
        } catch (Throwable $e) {
          $msg = (string)$e->getMessage();
          if (strpos($msg, 'Duplicate entry') === false) {
            throw $e;
          }

          $existing = db_fetch_one(
            "SELECT invoice_id, status
               FROM paralegal_invoices
              WHERE employer_id=? AND paralegal_id=? AND period_start=? AND period_end=?
              ORDER BY invoice_id ASC
              LIMIT 1",
            [$employer_id, $paralegal_id, $start, $end]
          );

          if (!$existing || (string)($existing['status'] ?? '') !== 'Draft') {
            throw $e;
          }

          $invoice_id = (int)$existing['invoice_id'];
          db_query("DELETE FROM paralegal_invoice_items WHERE invoice_id=?", [$invoice_id]);
          db_query(
            "UPDATE timesheets
                SET paralegal_invoice_id=NULL, paralegal_invoiced_at=NULL
              WHERE paralegal_invoice_id=?",
            [$invoice_id]
          );
          $rebuilt++;
        }
      } else {
        db_query(
          "UPDATE paralegal_invoices
              SET total_hours=?, gross_amount=?, job_id=?
            WHERE invoice_id=?",
          [$total_hours, $gross, $job_id, $invoice_id]
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
        db_query(
          "UPDATE timesheets
              SET paralegal_invoice_id=?, paralegal_invoiced_at=NOW()
            WHERE timesheet_id IN ($placeholders)",
          $params
        );
      }

      commission_upsert_for_period($employer_id, $start, $end);
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
