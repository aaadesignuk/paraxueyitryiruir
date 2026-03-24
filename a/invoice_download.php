<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) redirect('/a/billing.php');

$inv = db_fetch_one("SELECT * FROM paralegal_invoices WHERE invoice_id=? LIMIT 1", [$invoice_id]);
if (!$inv) { flash('Invoice not found.', 'error'); redirect('/a/billing.php'); }

// Snapshot items are the source of truth for invoices
$items = db_fetch_all(
  "SELECT
      pii.*,
      j.title AS job_title_live
     FROM paralegal_invoice_items pii
     LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
     LEFT JOIN jobs j ON j.job_id = ja.job_id
    WHERE pii.invoice_id=?
    ORDER BY pii.work_date ASC, pii.item_id ASC",
  [$invoice_id]
);

$employer  = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['employer_id']]);
$paralegal = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['paralegal_id']]);

// Profiles for address + bank details
$ep = db_fetch_one("SELECT * FROM employer_profiles WHERE user_id=? LIMIT 1", [(int)$inv['employer_id']]);
$pp = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [(int)$inv['paralegal_id']]);

$is_print = !empty($_GET['print']);

$filename = "invoice-{$invoice_id}.html";
if (!$is_print) {
  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
}

$generated_on = date('d/M/Y');

// Build Paralegal address
$para_addr = [];
if (!empty($pp['base_address1'])) $para_addr[] = $pp['base_address1'];
if (!empty($pp['base_address2'])) $para_addr[] = $pp['base_address2'];

$cityLine = trim(implode(' ', array_filter([
  $pp['base_city'] ?? '',
  $pp['base_state'] ?? '',
  $pp['base_postcode'] ?? ''
])));

if ($cityLine !== '') $para_addr[] = $cityLine;
if (!empty($pp['base_country'])) $para_addr[] = $pp['base_country'];

$para_addr_text = $para_addr ? implode(', ', $para_addr) : '';

// Employer address/location (current schema)
$employer_location = trim((string)($ep['location'] ?? ''));

// Bank details (paralegal)
$hasBank =
  !empty($pp['bank_name']) ||
  !empty($pp['account_name']) ||
  !empty($pp['account_no']) ||
  !empty($pp['sort_code']);

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice #<?= (int)$invoice_id ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; color: #111; }
    .top { display:flex; justify-content: space-between; align-items:flex-start; gap: 20px; flex-wrap:wrap; }
    .box { border: 1px solid #e5e5e5; padding: 12px 14px; border-radius: 8px; min-width:260px; }
    h1 { margin: 0 0 6px 0; font-size: 20px; }
    .muted { opacity: .75; }
    .small { font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 18px; }
    th, td { border: 1px solid #e5e5e5; padding: 10px; text-align: left; vertical-align: top; }
    th { background: #f7f7f7; }
    .right { text-align:right; }
    @media print { body { margin: 0.8cm; } .no-print { display:none; } }
  </style>
</head>
<body>

<?php if ($is_print): ?>
  <div class="no-print" style="margin-bottom:12px;">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>
<?php endif; ?>

<div class="top">
  <div style="flex:1; min-width:260px;">
    <h1>Invoice #<?= (int)$inv['invoice_id'] ?></h1>
    <div class="muted">Status: <?= e($inv['status']) ?></div>
    <div class="muted">Period: <?= e(uk_date($inv['period_start'])) ?> to <?= e(uk_date($inv['period_end'])) ?></div>
    <div class="muted">Generated on: <?= e($generated_on) ?></div>
  </div>

  <div class="box">
    <div><strong>From (Paralegal)</strong></div>
    <div><?= e($paralegal['full_name'] ?? '') ?></div>
    <div class="muted"><?= e($paralegal['email'] ?? '') ?></div>

    <?php if ($para_addr_text !== ''): ?>
      <div class="muted small" style="margin-top:6px;"><?= e($para_addr_text) ?></div>
    <?php endif; ?>

    <?php if ($hasBank): ?>
      <div style="margin-top:10px;">
        <div class="muted"><strong>Bank details</strong></div>
        <?php if (!empty($pp['bank_name'])): ?><div class="small">Bank: <?= e($pp['bank_name']) ?></div><?php endif; ?>
        <?php if (!empty($pp['account_name'])): ?><div class="small">Account name: <?= e($pp['account_name']) ?></div><?php endif; ?>
        <?php if (!empty($pp['account_no'])): ?><div class="small">A/c no: <?= e($pp['account_no']) ?></div><?php endif; ?>
        <?php if (!empty($pp['sort_code'])): ?><div class="small">Sort code: <?= e($pp['sort_code']) ?></div><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="box">
    <div><strong>To (Employer)</strong></div>
    <div><?= e($employer['full_name'] ?? ('User #'.(int)$inv['employer_id'])) ?></div>
    <div class="muted"><?= e($employer['email'] ?? '') ?></div>

    <?php if ($employer_location !== ''): ?>
      <div class="muted small" style="margin-top:6px;"><?= e($employer_location) ?></div>
    <?php endif; ?>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Client ref</th>
      <th>Job</th>
      <th class="right">Hours</th>
      <th class="right">Rate</th>
      <th class="right">Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
      <?php
        $jobTitle = (string)($it['job_title_snapshot'] ?? '');
        if ($jobTitle === '') $jobTitle = (string)($it['job_title_live'] ?? '');
      ?>
      <tr>
        <td><?= e(uk_date($it['work_date'] ?? '')) ?></td>
        <td><?= e($it['client_ref_snapshot'] ?? '') ?></td>
        <td><?= e($jobTitle) ?></td>
        <td class="right"><?= number_format((float)($it['hours'] ?? 0), 2) ?></td>
        <td class="right">£<?= number_format((float)($it['hourly_rate'] ?? 0), 2) ?></td>
        <td class="right">£<?= number_format((float)($it['amount'] ?? 0), 2) ?></td>
      </tr>
    <?php endforeach; ?>

    <tr>
      <td colspan="3"><strong>Total</strong></td>
      <td class="right"><strong><?= number_format((float)$inv['total_hours'], 2) ?></strong></td>
      <td></td>
      <td class="right"><strong>£<?= number_format((float)$inv['gross_amount'], 2) ?></strong></td>
    </tr>
  </tbody>
</table>

</body>
</html>