<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role(['E']);

$title = 'Select Timesheet Day';
$eid = (int)auth_user_id();

$invoice_id = (int)($_GET['id'] ?? 0);
$date = (string)($_GET['date'] ?? '');

if ($invoice_id <= 0 || $date === '') {
  flash('Invalid link.', 'error');
  redirect('/e/paralegal_invoices.php');
  exit;
}

$inv = db_fetch_one("
  SELECT *
  FROM paralegal_invoices
  WHERE invoice_id=? AND employer_id=?
  LIMIT 1
", [$invoice_id, $eid]);

if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/e/paralegal_invoices.php');
  exit;
}

$paralegal_id = (int)($inv['paralegal_id'] ?? 0);

$items = db_fetch_all("
  SELECT
    pii.item_id,
    pii.assignment_id,
    pii.work_date,
    pii.hours,
    ja.job_id,
    j.title AS job_title
  FROM paralegal_invoice_items pii
  LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
  LEFT JOIN jobs j ON j.job_id = ja.job_id
  WHERE pii.invoice_id=?
    AND pii.work_date=?
  ORDER BY j.title ASC, pii.item_id ASC
", [$invoice_id, $date]);

if (!$items) {
  flash('No invoice items found for that date.', 'error');
  redirect('/e/timesheets_invoice_summary.php?id='.$invoice_id);
  exit;
}

// Group by job_id
$jobs = []; // job_id => ['job_id','job_title','hours']
foreach ($items as $it) {
  $jid = (int)($it['job_id'] ?? 0);
  $jt = (string)($it['job_title'] ?? ($it['job_title_snapshot'] ?? 'Job'));
  if (!isset($jobs[$jid])) {
    $jobs[$jid] = ['job_id' => $jid, 'job_title' => $jt, 'hours' => 0.0];
  }
  $jobs[$jid]['hours'] += (float)($it['hours'] ?? 0);
}

foreach ($jobs as &$j) $j['hours'] = round((float)$j['hours'], 2);
unset($j);

render('employer/timesheet_day_picker', compact(
  'title',
  'inv',
  'invoice_id',
  'date',
  'paralegal_id',
  'jobs'
));