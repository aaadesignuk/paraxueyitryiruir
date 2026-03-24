<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role(['E']);

$employer_id = auth_user_id();
$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) redirect('/e/paralegal_invoices.php');

$inv = db_fetch_one(
  "SELECT
      pi.invoice_id,
      pi.employer_id,
      pi.paralegal_id,
      pi.job_id,
      pi.period_start,
      pi.period_end,
      pi.total_hours,
      pi.gross_amount,
      pi.status,
      pi.created_at,
      pi.paid_at,
      j.title AS job_title,
      COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref,
      u.full_name AS paralegal_name
   FROM paralegal_invoices pi
   LEFT JOIN jobs j ON j.job_id = pi.job_id
   LEFT JOIN users u ON u.user_id = pi.paralegal_id
   WHERE pi.invoice_id=? AND pi.employer_id=?
   LIMIT 1",
  [$invoice_id, $employer_id]
);

if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/e/paralegal_invoices.php');
  exit;
}

$commission_rate = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);
$commission_amount = round(((float)$inv['gross_amount']) * ($commission_rate / 100), 2);

$items = db_fetch_all(
  "SELECT
      pii.work_date,
      pii.client_ref_snapshot AS client_ref,
      pii.job_title_snapshot AS job_title,
      u.full_name AS paralegal_name,
      pii.hours AS hours_worked,
      pii.hourly_rate,
      pii.amount AS line_amount
   FROM paralegal_invoice_items pii
   JOIN paralegal_invoices pi ON pi.invoice_id = pii.invoice_id
   JOIN users u ON u.user_id = pi.paralegal_id
   WHERE pii.invoice_id = ?
     AND pi.employer_id = ?
   ORDER BY pii.work_date ASC, pii.item_id ASC",
  [$invoice_id, $employer_id]
);

$employer = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [$employer_id]);
$ep = db_fetch_one("SELECT * FROM employer_profiles WHERE user_id=? LIMIT 1", [$employer_id]);
$employer_location = trim((string)($ep['location'] ?? ''));

$is_print = !empty($_GET['print']);
$filename = "commission-invoice-{$invoice_id}.html";

if (!$is_print) {
  header('Content-Type: text/html; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
}

$generated_on = date('d/M/Y');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Commission Invoice #<?= (int)$invoice_id ?></title>
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
    <h1>Commission Invoice #<?= (int)$inv['invoice_id'] ?></h1>
    <div class="muted">Status: <?= e($inv['status'] ?? '') ?></div>
    <div class="muted">Period: <?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?></div>
    <div class="muted">Generated on: <?= e($generated_on) ?></div>
    <div class="muted">Paralegal: <?= e($inv['paralegal_name'] ?? '') ?></div>
    <div class="muted">Job: <?= e($inv['job_title'] ?? '') ?></div>
    <div class="muted">Client ref: <?= e($inv['client_ref'] ?? '') ?></div>
  </div>

  <div class="box">
    <div><strong>From</strong></div>
    <div>Paralete</div>
    <div class="muted small">Platform commission invoice</div>
  </div>

  <div class="box">
    <div><strong>To (Employer)</strong></div>
    <div><?= e($employer['full_name'] ?? '') ?></div>
    <div class="muted"><?= e($employer['email'] ?? '') ?></div>
    <?php if ($employer_location !== ''): ?>
      <div class="muted small" style="margin-top:6px;"><?= e($employer_location) ?></div>
    <?php endif; ?>
  </div>

  <div class="box">
    <div><strong>Gross</strong></div>
    <div>£<?= number_format((float)($inv['gross_amount'] ?? 0), 2) ?></div>
    <div class="muted">Commission <?= rtrim(rtrim(number_format($commission_rate, 2), '0'), '.') ?>%</div>
    <div><strong>£<?= number_format($commission_amount, 2) ?></strong></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Client ref</th>
      <th>Job</th>
      <th>Paralegal</th>
      <th class="right">Hours</th>
      <th class="right">Rate</th>
      <th class="right">Line</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="7">No invoice lines found.</td></tr>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e(uk_date($it['work_date'] ?? '')) ?></td>
        <td><?= e($it['client_ref'] ?? '') ?></td>
        <td><?= e($it['job_title'] ?? '') ?></td>
        <td><?= e($it['paralegal_name'] ?? '') ?></td>
        <td class="right"><?= number_format((float)($it['hours_worked'] ?? 0), 2) ?></td>
        <td class="right">£<?= number_format((float)($it['hourly_rate'] ?? 0), 2) ?></td>
        <td class="right">£<?= number_format((float)($it['line_amount'] ?? 0), 2) ?></td>
      </tr>
    <?php endforeach; ?>

    <tr>
      <td colspan="6" class="right"><strong>Total Commission Due</strong></td>
      <td class="right"><strong>£<?= number_format($commission_amount, 2) ?></strong></td>
    </tr>
  </tbody>
</table>

</body>
</html>