<?php
require_once __DIR__ . '/../app/bootstrap.php';

/**
 * Usage:
 * /tools/generate_commission_invoices.php?month=2026-01&rate=20
 */
$month = $_GET['month'] ?? date('Y-m'); // YYYY-MM
$rate = (float)setting_get('commission_rate_default', '20.00');


$period_start = $month . '-01';
$period_end = date('Y-m-t', strtotime($period_start));

/**
 * Find all employers with approved timesheets in this period
 */
$employers = db_fetch_all("
  SELECT DISTINCT a.employer_id
  FROM timesheets t
  JOIN job_assignments a ON a.assignment_id = t.assignment_id
  WHERE t.status='Approved'
    AND t.work_date BETWEEN ? AND ?
", [$period_start, $period_end]);

foreach ($employers as $e) {
  $employer_id = (int)$e['employer_id'];

  // Avoid duplicate invoices for the same period
  $exists = db_fetch_one("
    SELECT invoice_id FROM commission_invoices
    WHERE employer_id=? AND period_start=? AND period_end=?
  ", [$employer_id, $period_start, $period_end]);

  if ($exists) continue;

  // Create invoice header
  db_query("
    INSERT INTO commission_invoices (employer_id, period_start, period_end, commission_rate, status)
    VALUES (?, ?, ?, ?, 'Unpaid')
  ", [$employer_id, $period_start, $period_end, $rate]);

  $invoice_id = (int)db()->lastInsertId();

  // Build items from approved timesheets
  $items = db_fetch_all("
    SELECT
      t.timesheet_id, t.work_date, t.hours_worked, t.description,
      a.assignment_id, a.paralegal_id, IFNULL(a.agreed_rate,0) AS agreed_rate
    FROM timesheets t
    JOIN job_assignments a ON a.assignment_id = t.assignment_id
    WHERE a.employer_id = ?
      AND t.status='Approved'
      AND t.work_date BETWEEN ? AND ?
  ", [$employer_id, $period_start, $period_end]);

  $gross = 0;

  foreach ($items as $it) {
    $hours = (float)$it['hours_worked'];
    $rateHr = (float)$it['agreed_rate'];
    $line = round($hours * $rateHr, 2);
    $gross += $line;

    db_query("
      INSERT INTO commission_invoice_items
      (invoice_id, timesheet_id, assignment_id, paralegal_id, work_date, hours_worked, hourly_rate, line_amount, description)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
      $invoice_id,
      (int)$it['timesheet_id'],
      (int)$it['assignment_id'],
      (int)$it['paralegal_id'],
      $it['work_date'],
      $hours,
      $rateHr,
      $line,
      $it['description']
    ]);
  }

  $commission = round($gross * ($rate / 100), 2);

  db_query("
    UPDATE commission_invoices
    SET gross_amount=?, commission_amount=?
    WHERE invoice_id=?
  ", [$gross, $commission, $invoice_id]);
}

echo "Commission invoices generated for {$month}\n";
