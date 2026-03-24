<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!function_exists('uk_date')) {
  function uk_date($dt) {
    if (!$dt) return '';
    $ts = is_numeric($dt) ? (int)$dt : strtotime($dt);
    if (!$ts) return '';
    return date('d/M/y', $ts);
  }
}

if (!function_exists('gbp')) {
  function gbp($v) {
    return '£' . number_format((float)$v, 2);
  }
}

$invoice_id = (int)($_GET['id'] ?? 0);
$print = (int)($_GET['print'] ?? 0);

if ($invoice_id <= 0) {
  flash('Invoice not found.', 'error');
  redirect('/a/commission_invoices.php');
}

$inv = db_fetch_one("
  SELECT
    ci.*,
    u.full_name AS employer_name,
    ep.firm_name
  FROM commission_invoices ci
  JOIN users u ON u.user_id = ci.employer_id
  LEFT JOIN employer_profiles ep ON ep.user_id = ci.employer_id
  WHERE ci.invoice_id = ?
  LIMIT 1
", [$invoice_id]);

if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/a/commission_invoices.php');
}

$title = 'Commission Invoice #' . (int)$inv['invoice_id'];

$invoice_no = $inv['invoice_number'] ?? ('CI-'.(int)$inv['invoice_id']);
$employer_name = $inv['employer_name'] ?? ('Employer #'.(int)$inv['employer_id']);
$firm_name = $inv['firm_name'] ?? '—';
$status = $inv['status'] ?? 'Unpaid';

$period_start = $inv['period_start'] ?? null;
$period_end   = $inv['period_end'] ?? null;

$created_at = $inv['created_at'] ?? null;
$paid_at    = $inv['paid_at'] ?? null;

$gross_amount = (float)($inv['gross_amount'] ?? 0);
$commission_amount = (float)($inv['commission_amount'] ?? 0);
$commission_rate = $inv['commission_rate'] ?? null;

$items = [];
try {
  $items = db_fetch_all("
    SELECT
      cii.*,
      u.full_name AS paralegal_name,
      j.title AS job_title
    FROM commission_invoice_items cii
    LEFT JOIN users u ON u.user_id = cii.paralegal_id
    LEFT JOIN job_assignments a ON a.assignment_id = cii.assignment_id
    LEFT JOIN jobs j ON j.job_id = a.job_id
    WHERE cii.invoice_id = ?
    ORDER BY cii.work_date ASC, cii.item_id ASC
  ", [$invoice_id]);
} catch (Throwable $e) {
  $items = [];
}

/**
 * PRINT TEMPLATE OUTPUT (standalone HTML)
 * /a/commission_invoice.php?id=4&print=1
 */
if ($print === 1):
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <style>
    body{font-family:Arial, sans-serif; margin:30px; color:#111;}
    .wrap{max-width:980px; margin:0 auto;}
    .top{display:flex; justify-content:space-between; align-items:flex-start; gap:18px;}
    .brand{font-size:22px; font-weight:700;}
    .muted{color:#666;}
    .box{border:1px solid #ddd; border-radius:10px; padding:14px; margin-top:14px;}
    .grid{display:grid; grid-template-columns:1fr 1fr; gap:10px;}
    table{width:100%; border-collapse:collapse; margin-top:14px;}
    th,td{border-bottom:1px solid #eee; padding:10px; text-align:left; vertical-align:top;}
    th{text-transform:uppercase; font-size:12px; letter-spacing:.04em; color:#555;}
    .tot{font-size:18px; font-weight:700;}
    .right{text-align:right;}
    .no-print{margin-top:14px; display:flex; gap:10px; justify-content:flex-end;}
    .btn{display:inline-block; padding:10px 12px; border:1px solid #ccc; border-radius:10px; background:#f7f7f7; cursor:pointer; text-decoration:none; color:#111;}
    @media print { .no-print{display:none;} }
  </style>
</head>
<body>
<div class="wrap">

  <div class="top">
    <div>
      <div class="brand">Paralete</div>
      <div class="muted">Commission Invoice</div>
    </div>
    <div class="right">
      <div><strong>Invoice #:</strong> <?= h($invoice_no) ?></div>
      <div><strong>Issued:</strong> <?= h(uk_date($created_at)) ?></div>
      <div><strong>Status:</strong> <?= h($status) ?></div>
    </div>
  </div>

  <div class="box grid">
    <div>
      <div class="muted">Billed To</div>
      <div><strong><?= h($employer_name) ?></strong></div>
      <div class="muted" style="margin-top:6px;"><?= h($firm_name) ?></div>
    </div>
    <div class="right">
      <div class="muted">Period</div>
      <div><strong><?= h(uk_date($period_start)) ?> → <?= h(uk_date($period_end)) ?></strong></div>

      <div style="margin-top:10px;">
        <div class="muted">Paid</div>
        <div><strong><?= h(uk_date($paid_at)) ?></strong></div>
      </div>
    </div>
  </div>

  <?php if (!empty($items)): ?>
    <table>
      <thead>
        <tr>
          <th>Paralegal</th>
          <th>Job</th>
          <th>Date</th>
          <th class="right">Hours</th>
          <th class="right">Rate</th>
          <th class="right">Gross</th>
          <th class="right">Commission</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <?php
            $line_amount = (float)($it['line_amount'] ?? 0);
            $line_commission = 0;
            if ($commission_rate !== null && $commission_rate !== '') {
              $line_commission = $line_amount * ((float)$commission_rate / 100);
            }
          ?>
          <tr>
            <td><?= h($it['paralegal_name'] ?? '—') ?></td>
            <td><?= h($it['job_title'] ?? ($it['description'] ?? '—')) ?></td>
            <td><?= h(uk_date($it['work_date'] ?? '')) ?></td>
            <td class="right"><?= h(number_format((float)($it['hours_worked'] ?? 0), 2)) ?></td>
            <td class="right"><?= h(gbp($it['hourly_rate'] ?? 0)) ?></td>
            <td class="right"><?= h(gbp($line_amount)) ?></td>
            <td class="right"><?= h(gbp($line_commission)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th class="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Gross (approved timesheets)</td>
        <td class="right"><?= h(gbp($gross_amount)) ?></td>
      </tr>
      <tr>
        <td><strong>Platform commission<?= $commission_rate !== null ? ' ('.h($commission_rate).'%)' : '' ?></strong></td>
        <td class="right"><strong><?= h(gbp($commission_amount)) ?></strong></td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td class="right"><strong>Total Due</strong></td>
        <td class="right tot"><?= h(gbp($commission_amount)) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="box muted">
    Use “Print / Save as PDF” to download a PDF copy.
  </div>

  <div class="no-print">
    <button class="btn" onclick="window.print()">Print / Save as PDF</button>
    <a class="btn" href="/a/commission_invoice.php?id=<?= (int)$invoice_id ?>">Back</a>
  </div>

</div>
</body>
</html>
<?php
exit;
endif;

/**
 * NORMAL VIEW
 * /a/commission_invoice.php?id=4
 */
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= h($title) ?></title>
  <style>
    body{font-family:Arial, sans-serif; margin:24px; color:#111;}
    .wrap{max-width:1100px; margin:0 auto;}
    .head{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:14px;}
    .muted{color:#666;}
    .btn{display:inline-block; padding:10px 12px; border:1px solid #ccc; border-radius:10px; background:#f7f7f7; cursor:pointer; text-decoration:none; color:#111;}
    table{width:100%; border-collapse:collapse; margin-top:14px;}
    th,td{border-bottom:1px solid #eee; padding:10px; text-align:left; vertical-align:top;}
    th{text-transform:uppercase; font-size:12px; letter-spacing:.04em; color:#555;}
    .right{text-align:right;}
    .card{border:1px solid #ddd; border-radius:10px; padding:14px; margin-top:12px;}
    .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px;}
  </style>
</head>
<body>
<div class="wrap">

  <div class="head">
    <div>
      <h1 style="margin:0;"><?= h($title) ?></h1>
      <div class="muted" style="margin-top:6px;">
        <strong>Employer:</strong> <?= h($employer_name) ?> &nbsp;•&nbsp;
        <strong>Firm:</strong> <?= h($firm_name) ?> &nbsp;•&nbsp;
        <strong>Period:</strong> <?= h(uk_date($period_start)) ?> → <?= h(uk_date($period_end)) ?> &nbsp;•&nbsp;
        <strong>Status:</strong> <?= h($status) ?>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
      <a class="btn" href="/a/commission_invoices.php">← Back to list</a>
      <a class="btn" href="/a/commission_invoice.php?id=<?= (int)$invoice_id ?>&print=1">Download invoice</a>
      <a class="btn" href="/a/commission_invoice.php?id=<?= (int)$invoice_id ?>&print=1" target="_blank" rel="noopener">Print / Save as PDF</a>

      <?php if (($status ?? '') !== 'Paid'): ?>
        <form method="post" action="/a/mark_commission_paid.php" style="margin:0;">
          <input type="hidden" name="invoice_id" value="<?= (int)$invoice_id ?>">
          <button class="btn" type="submit">Mark Paid</button>
        </form>
      <?php else: ?>
        <span style="opacity:.7; align-self:center;">Paid</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="card grid">
    <div>
      <div class="muted">Issued</div>
      <div><strong><?= h(uk_date($created_at)) ?></strong></div>
    </div>
    <div>
      <div class="muted">Paid</div>
      <div><strong><?= h(uk_date($paid_at)) ?></strong></div>
    </div>
    <div>
      <div class="muted">Gross</div>
      <div><strong><?= h(gbp($gross_amount)) ?></strong></div>
    </div>
    <div>
      <div class="muted">Commission<?= $commission_rate !== null ? ' ('.h($commission_rate).'%)' : '' ?></div>
      <div><strong><?= h(gbp($commission_amount)) ?></strong></div>
    </div>
  </div>

  <?php if (!empty($items)): ?>
    <h2 style="margin-top:18px;">Line items</h2>
    <table>
      <thead>
        <tr>
          <th>Paralegal</th>
          <th>Job</th>
          <th>Date</th>
          <th class="right">Hours</th>
          <th class="right">Rate</th>
          <th class="right">Gross</th>
          <th class="right">Commission</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <?php
            $line_amount = (float)($it['line_amount'] ?? 0);
            $line_commission = 0;
            if ($commission_rate !== null && $commission_rate !== '') {
              $line_commission = $line_amount * ((float)$commission_rate / 100);
            }
          ?>
          <tr>
            <td><?= h($it['paralegal_name'] ?? '—') ?></td>
            <td><?= h($it['job_title'] ?? ($it['description'] ?? '—')) ?></td>
            <td><?= h(uk_date($it['work_date'] ?? '')) ?></td>
            <td class="right"><?= h(number_format((float)($it['hours_worked'] ?? 0), 2)) ?></td>
            <td class="right"><?= h(gbp($it['hourly_rate'] ?? 0)) ?></td>
            <td class="right"><?= h(gbp($line_amount)) ?></td>
            <td class="right"><?= h(gbp($line_commission)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>
</body>
</html>