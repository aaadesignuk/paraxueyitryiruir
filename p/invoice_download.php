<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$paralegal_id = (int)auth_user()['user_id'];
$invoice_id = (int)($_GET['id'] ?? 0);
$is_print = !empty($_GET['print']);
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

$employer = db_fetch_one(
  "SELECT u.full_name, ep.firm_name, ep.location
     FROM users u
     LEFT JOIN employer_profiles ep ON ep.user_id = u.user_id
    WHERE u.user_id=?
    LIMIT 1",
  [(int)$inv['employer_id']]
);

$paralegal = db_fetch_one(
  "SELECT u.full_name, pp.*
     FROM users u
     LEFT JOIN paralegal_profiles pp ON pp.user_id = u.user_id
    WHERE u.user_id=?
    LIMIT 1",
  [$paralegal_id]
);

$invoice_date = !empty($inv['submitted_at']) ? substr((string)$inv['submitted_at'], 0, 10)
              : (!empty($inv['created_at']) ? substr((string)$inv['created_at'], 0, 10) : date('Y-m-d'));
$due_date = !empty($inv['due_date']) ? (string)$inv['due_date'] : date('Y-m-d', strtotime($invoice_date . ' +6 days'));

if (!$is_print) {
  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: attachment; filename="paralegal-invoice-'.$invoice_id.'.html"');
}

function block_lines(array $lines): array {
  $out = [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line !== '') $out[] = $line;
  }
  return $out;
}

$to_lines = block_lines([
  $employer['full_name'] ?? '',
  $employer['firm_name'] ?? '',
  $employer['location'] ?? '',
]);

$pay_lines = block_lines([
  $paralegal['full_name'] ?? '',
  $paralegal['base_address1'] ?? '',
  $paralegal['base_address2'] ?? '',
  $paralegal['base_city'] ?? '',
  $paralegal['base_state'] ?? '',
  $paralegal['base_postcode'] ?? '',
  $paralegal['base_country'] ?? '',
]);

$bank_lines = block_lines([
  $paralegal['account_name'] ?? ($paralegal['full_name'] ?? ''),
  $paralegal['bank_name'] ?? '',
  !empty($paralegal['account_no']) ? 'A/C No : '.$paralegal['account_no'] : '',
  !empty($paralegal['sort_code']) ? 'Sort Code: '.$paralegal['sort_code'] : '',
]);
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Paralegal Invoice #<?= (int)$inv['invoice_id'] ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;color:#111;margin:28px 34px;font-size:14px;line-height:1.4;}
    .toolbar{margin-bottom:14px}.toolbar button{padding:8px 12px;border:1px solid #ccc;background:#fff;color:#111;border-radius:6px;cursor:pointer}
    .doc{max-width:760px}.title{font-size:28px;font-weight:700;margin:0 0 18px}.meta-date{margin:0 0 22px}
    .addr strong{font-size:15px}.addr div{margin:1px 0}.period{margin:26px 0 18px;font-weight:600}.topmeta{margin:10px 0 16px;font-size:13px;color:#333}
    table{width:100%;border-collapse:collapse;margin:6px 0 18px} th,td{border:1px solid #d9d9d9;padding:10px 12px;vertical-align:top}
    th{background:#f7f7f7;text-align:left}.right{text-align:right}.total-row td{font-weight:700}
    .payment-wrap{margin-top:30px;max-width:440px}.section-head{font-weight:700;margin:0 0 8px}.bank-head{font-weight:700;margin:18px 0 8px}.due{margin:18px 0 0;font-weight:600}.foot{margin-top:18px}
    @media print{body{margin:18mm}.toolbar{display:none}}
  </style>
</head>
<body>
<?php if ($is_print): ?>
  <div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
<?php endif; ?>
<div class="doc">
  <div class="title">Paralegal Invoice No: <?= (int)$inv['invoice_id'] ?></div>
  <div class="meta-date"><?= e(uk_date($invoice_date)) ?></div>

  <div class="addr">
    <?php foreach ($to_lines as $i => $line): ?>
      <div><?php if ($i === 0): ?><strong><?php endif; ?><?= e($line) ?><?php if ($i === 0): ?></strong><?php endif; ?></div>
    <?php endforeach; ?>
  </div>

  <div class="topmeta">
    <strong>Client Ref:</strong> <?= e(($inv['client_ref'] ?? '') !== '' ? $inv['client_ref'] : '—') ?>
    &nbsp; | &nbsp;
    <strong>Date:</strong> <?= e(uk_date($inv['period_start'])) ?> – <?= e(uk_date($inv['period_end'])) ?>
    &nbsp; | &nbsp;
    <strong>Job:</strong> <?= e(($inv['job_title'] ?? '') !== '' ? $inv['job_title'] : '—') ?>
  </div>

  <div class="period">Invoice for Period - <?= e(uk_date($inv['period_start'])) ?> – <?= e(uk_date($inv['period_end'])) ?></div>

  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Description</th>
        <th class="right" style="width:90px;">Hours</th>
        <th class="right" style="width:110px;">Rate</th>
        <th class="right" style="width:120px;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="5">No approved daily timesheets found for this invoice period.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e(uk_date($it['work_date'] ?? '')) ?></td>
          <td><?= e($it['description'] ?? 'Daily Timesheet') ?></td>
          <td class="right"><?= number_format((float)($it['hours'] ?? 0), 2) ?></td>
          <td class="right"><?= $it['hourly_rate'] !== null ? '£'.number_format((float)$it['hourly_rate'], 2) : 'Mixed' ?></td>
          <td class="right">£<?= number_format((float)($it['amount'] ?? 0), 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr class="total-row">
        <td colspan="2">Total</td>
        <td class="right"><?= number_format((float)($inv['total_hours'] ?? 0), 2) ?></td>
        <td></td>
        <td class="right">£<?= number_format((float)($inv['gross_amount'] ?? 0), 2) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="payment-wrap">
    <div class="section-head">Please make payment to:</div>
    <?php foreach ($pay_lines as $line): ?><div><?= e($line) ?></div><?php endforeach; ?>

    <?php if ($bank_lines): ?>
      <div class="bank-head">Bank details</div>
      <?php foreach ($bank_lines as $line): ?><div><?= e($line) ?></div><?php endforeach; ?>
    <?php endif; ?>

    <div class="due">Payment due <?= e(uk_date($due_date)) ?></div>
    <div class="foot">Monthly summary overleaf</div>
  </div>
</div>
</body>
</html>