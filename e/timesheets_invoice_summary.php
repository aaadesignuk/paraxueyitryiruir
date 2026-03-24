<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role(['E']);

$title = 'Timesheet Summary';
$eid = (int)auth_user_id();

$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) {
  flash('Invoice not found.', 'error');
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
$paralegal = db_fetch_one("
  SELECT full_name, email
  FROM users
  WHERE user_id=?
  LIMIT 1
", [$paralegal_id]);

$selected_client_ref = trim((string)($_GET['client_ref'] ?? ''));

$items = db_fetch_all("
  SELECT
    pii.item_id,
    pii.assignment_id,
    pii.work_date,
    pii.hours,
    COALESCE(NULLIF(pii.client_ref_snapshot,''), NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref_display,
    COALESCE(NULLIF(pii.description_snapshot,''), NULLIF(t.description,''), '—') AS description_display
  FROM paralegal_invoice_items pii
  LEFT JOIN timesheets t ON t.timesheet_id = pii.timesheet_id
  LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
  LEFT JOIN jobs j ON j.job_id = ja.job_id
  WHERE pii.invoice_id=?
  ORDER BY pii.work_date ASC, pii.item_id ASC
", [$invoice_id]);

$client_refs = [];
foreach ($items as $it) {
  $ref = trim((string)($it['client_ref_display'] ?? ''));
  if ($ref !== '') {
    $client_refs[$ref] = $ref;
  }
}
ksort($client_refs, SORT_NATURAL | SORT_FLAG_CASE);

if ($selected_client_ref !== '' && !isset($client_refs[$selected_client_ref])) {
  $selected_client_ref = '';
}

$period_start = (string)($inv['period_start'] ?? '');
$period_end   = (string)($inv['period_end'] ?? '');

if ($period_start === '' || $period_end === '') {
  if ($items) {
    $period_start = (string)$items[0]['work_date'];
    $period_end   = (string)$items[count($items)-1]['work_date'];
  } else {
    flash('No invoice items found for this invoice.', 'error');
    redirect('/e/paralegal_invoice.php?id='.$invoice_id);
    exit;
  }
}

$start_ts = strtotime($period_start);
$end_ts   = strtotime($period_end);

if (!$start_ts || !$end_ts || $start_ts > $end_ts) {
  flash('Invalid invoice period.', 'error');
  redirect('/e/paralegal_invoice.php?id='.$invoice_id);
  exit;
}

$items_by_date = [];
foreach ($items as $it) {
  $d = (string)$it['work_date'];
  if (!isset($items_by_date[$d])) {
    $items_by_date[$d] = [];
  }
  $items_by_date[$d][] = $it;
}

$weeks = [];
$month_total = 0.0;

for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
  $date = date('Y-m-d', $ts);
  $dow  = (int)date('N', $ts);
  $wc_ts = strtotime('-'.($dow - 1).' day', $ts);
  $wc    = date('Y-m-d', $wc_ts);

  if (!isset($weeks[$wc])) {
    $weeks[$wc] = [
      'wc' => $wc,
      'days' => [],
      'total' => 0.0
    ];
  }

  $source_items = $items_by_date[$date] ?? [];
  $hours = 0.0;
  $line_items = [];

  foreach ($source_items as $it) {
    $client_ref_value = trim((string)($it['client_ref_display'] ?? ''));

    if ($selected_client_ref !== '' && $client_ref_value !== $selected_client_ref) {
      continue;
    }

    $item_hours = (float)($it['hours'] ?? 0);
    $hours += $item_hours;

    $line_items[] = [
      'client_ref'  => $client_ref_value,
      'description' => (string)($it['description_display'] ?? '—'),
      'hours'       => round($item_hours, 2),
    ];
  }

  $hours = round($hours, 2);

  if ($hours <= 0 && empty($line_items)) {
    continue;
  }

  $weeks[$wc]['total'] += $hours;
  $month_total += $hours;

  $weeks[$wc]['days'][] = [
    'date'       => $date,
    'day_name'   => date('l', $ts),
    'hours'      => $hours,
    'line_items' => $line_items,
  ];
}

foreach ($weeks as $wc_key => &$w) {
  $w['total'] = round((float)$w['total'], 2);
  if (empty($w['days'])) {
    unset($weeks[$wc_key]);
  }
}
unset($w);

$month_total = round($month_total, 2);

render('employer/timesheets_invoice_summary', compact(
  'title',
  'inv',
  'invoice_id',
  'paralegal',
  'paralegal_id',
  'period_start',
  'period_end',
  'weeks',
  'month_total',
  'client_refs',
  'selected_client_ref'
));