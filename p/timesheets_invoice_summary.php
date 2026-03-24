<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Timesheet Summary';
$pid = (int)auth_user_id();

$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) {
  flash('Invoice not found.', 'error');
  redirect('/p/invoices.php');
  exit;
}

$inv = db_fetch_one("SELECT * FROM paralegal_invoices WHERE invoice_id=? AND paralegal_id=? LIMIT 1", [$invoice_id, $pid]);
if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/p/invoices.php');
  exit;
}

$employer = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)($inv['employer_id'] ?? 0)]);

$items = db_fetch_all(" 
  SELECT
    pii.item_id,
    pii.assignment_id,
    pii.work_date,
    pii.hours,
    pii.job_title_snapshot
  FROM paralegal_invoice_items pii
  WHERE pii.invoice_id=?
  ORDER BY pii.work_date ASC, pii.item_id ASC
", [$invoice_id]);

$period_start = (string)($inv['period_start'] ?? '');
$period_end = (string)($inv['period_end'] ?? '');
if ($period_start === '' || $period_end === '') {
  if ($items) {
    $period_start = (string)$items[0]['work_date'];
    $period_end = (string)$items[count($items)-1]['work_date'];
  } else {
    flash('No invoice items found for this invoice.', 'error');
    redirect('/p/invoice.php?id='.$invoice_id);
    exit;
  }
}

$start_ts = strtotime($period_start);
$end_ts = strtotime($period_end);
if (!$start_ts || !$end_ts || $start_ts > $end_ts) {
  flash('Invalid invoice period.', 'error');
  redirect('/p/invoice.php?id='.$invoice_id);
  exit;
}

$items_by_date = [];
foreach ($items as $it) {
  $d = (string)$it['work_date'];
  if (!isset($items_by_date[$d])) $items_by_date[$d] = [];
  $items_by_date[$d][] = $it;
}

$weeks = [];
$month_total = 0.0;
for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
  $date = date('Y-m-d', $ts);
  $dow = (int)date('N', $ts);
  $wc_ts = strtotime('-'.($dow-1).' day', $ts);
  $wc = date('Y-m-d', $wc_ts);
  if (!isset($weeks[$wc])) $weeks[$wc] = ['wc' => $wc, 'days' => [], 'total' => 0.0];

  $day_items = $items_by_date[$date] ?? [];
  $hours = 0.0;
  $assignment_ids = [];
  foreach ($day_items as $it) {
    $hours += (float)($it['hours'] ?? 0);
    if (!empty($it['assignment_id'])) $assignment_ids[(int)$it['assignment_id']] = true;
  }
  $hours = round($hours, 2);
  $weeks[$wc]['total'] += $hours;
  $month_total += $hours;

  $day_link = '';
  $assignment_count = count($assignment_ids);
  if ($hours > 0) {
    if ($assignment_count === 1) {
      $assignment_id = (int)array_key_first($assignment_ids);
      $day_link = "/p/timesheet_day.php?assignment_id={$assignment_id}&date=".urlencode($date);
    } else {
      $day_link = "/p/timesheet_day_picker.php?id={$invoice_id}&date=".urlencode($date);
    }
  }

  $weeks[$wc]['days'][] = [
    'date' => $date,
    'day_name' => date('l', $ts),
    'hours' => $hours,
    'link' => $day_link,
  ];
}
foreach ($weeks as &$w) $w['total'] = round((float)$w['total'], 2);
unset($w);
$month_total = round($month_total, 2);

render('paralegal/timesheets_invoice_summary', compact(
  'title', 'inv', 'invoice_id', 'employer', 'period_start', 'period_end', 'weeks', 'month_total'
));
