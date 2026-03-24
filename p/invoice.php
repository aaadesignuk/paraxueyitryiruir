<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

require_once __DIR__.'/../app/services/paralegal_invoicing.php';

$paralegal_id = (int)auth_user()['user_id'];
$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) redirect('/p/invoices.php');

$inv = db_fetch_one(
  "SELECT pi.*, j.title AS job_title, COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref
     FROM paralegal_invoices pi
     LEFT JOIN jobs j ON j.job_id = pi.job_id
    WHERE pi.invoice_id=? AND pi.paralegal_id=?
    LIMIT 1",
  [$invoice_id, $paralegal_id]
);
if (!$inv) { flash('Invoice not found.', 'error'); redirect('/p/invoices.php'); }

if (($inv['status'] ?? '') === 'Draft') {
  $yy = (int)date('Y', strtotime($inv['period_start'] ?? ''));
  $mm = (int)date('n', strtotime($inv['period_start'] ?? ''));
  if ($yy > 0 && $mm > 0) {
    paralegal_generate_monthly_invoices_for_paralegal($paralegal_id, $yy, $mm);
    $inv = db_fetch_one(
      "SELECT pi.*, j.title AS job_title, COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref
         FROM paralegal_invoices pi
         LEFT JOIN jobs j ON j.job_id = pi.job_id
        WHERE pi.invoice_id=? AND pi.paralegal_id=?
        LIMIT 1",
      [$invoice_id, $paralegal_id]
    ) ?: $inv;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'submit' && ($inv['status'] ?? '') === 'Draft') {
    $unresolved_dates = db_fetch_all(
      "SELECT DISTINCT t.work_date
         FROM timesheets t
         JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
        WHERE ja.paralegal_id=?
          AND ja.employer_id=?
          AND ja.job_id=?
          AND t.work_date BETWEEN ? AND ?
          AND t.status IN ('Draft','Submitted','Rejected')
        ORDER BY t.work_date ASC",
      [$paralegal_id, (int)$inv['employer_id'], (int)($inv['job_id'] ?? 0), (string)$inv['period_start'], (string)$inv['period_end']]
    );

    if (!empty($unresolved_dates)) {
      $dates = array_map(fn($r) => date('d/m/Y', strtotime((string)$r['work_date'])), $unresolved_dates);
      $preview = array_slice($dates, 0, 10);
      $more = count($dates) > 10 ? ' (+' . (count($dates) - 10) . ' more)' : '';
      flash('Invoice cannot be submitted yet. Unresolved timesheets exist for: '.implode(', ', $preview).$more, 'error');
      redirect('/p/invoice.php?id='.$invoice_id);
      exit;
    }

    db_query("UPDATE paralegal_invoices SET status='Submitted', submitted_at=NOW() WHERE invoice_id=? AND status='Draft'", [$invoice_id]);
    notify((int)$inv['employer_id'], "A paralegal invoice has been submitted for {$inv['period_start']} to {$inv['period_end']}. (Invoice #{$invoice_id})");
    flash('Invoice submitted to employer.', 'success');
    redirect('/p/invoice.php?id='.$invoice_id);
    exit;
  }
}

$items = db_fetch_all(
  "SELECT
      pii.work_date,
      'Daily Timesheet' AS description,
      SUM(COALESCE(pii.hours,0)) AS hours,
      CASE
        WHEN MIN(COALESCE(pii.hourly_rate,0)) = MAX(COALESCE(pii.hourly_rate,0))
          THEN MIN(pii.hourly_rate)
        ELSE NULL
      END AS hourly_rate,
      SUM(COALESCE(pii.amount,0)) AS amount
   FROM paralegal_invoice_items pii
   WHERE pii.invoice_id=?
   GROUP BY pii.work_date
   ORDER BY pii.work_date ASC",
  [$invoice_id]
);

$employer = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['employer_id']]);

$title = 'Invoice #'.$invoice_id;
render('paralegal/invoice', compact('title','inv','items','employer'));