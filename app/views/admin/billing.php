<?php
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('uk_dt')) {
  function uk_dt($dt) {
    if (!$dt) return '';
    $ts = is_numeric($dt) ? (int)$dt : strtotime($dt);
    if (!$ts) return '';
    return date('d/M/y H:i', $ts);
  }
}
if (!function_exists('uk_d')) {
  function uk_d($dt) {
    if (!$dt) return '';
    $ts = is_numeric($dt) ? (int)$dt : strtotime($dt);
    if (!$ts) return '';
    return date('d/M/y', $ts);
  }
}
?>

<div class="section" id="billing">
  <div class="section-title">Billing & Commission</div>
  <div class="section-hint">Employer invoices (paralegal invoices) + commission invoices generated from approved timesheets.</div>

  <?php
    $tot_employer_hours = 0.0;
    $tot_employer_gross = 0.0;
    foreach(($employer_invoices ?? []) as $r){
      $tot_employer_hours += (float)($r['total_hours'] ?? 0);
      $tot_employer_gross += (float)($r['gross_amount'] ?? 0);
    }
    $tot_commission_gross = 0.0;
    $tot_commission_amount = 0.0;
    foreach(($commission_invoices ?? []) as $r){
      $tot_commission_gross += (float)($r['gross_amount'] ?? 0);
      $tot_commission_amount += (float)($r['commission_amount'] ?? 0);
    }
  ?>

  <h2 style="margin-top:18px;">Paralegal Invoices</h2>
  <div class="section-hint">What the employer owes the paralegal (paid directly to the paralegal).</div>

  <div class="section-hint" style="margin-top:6px;">
    Totals: <strong><?= e(number_format($tot_employer_hours, 2)) ?></strong> hrs •
    <strong>£<?= e(number_format($tot_employer_gross, 2)) ?></strong>
  </div>

  <div class="table-wrap">
  <table class="table">
    <tr>
      <th>Created</th>
      <th>Employer</th>
      <th>Firm</th>
      <th>Paralegal</th>
      <th>Period</th>
      <th>Hours</th>
      <th>Gross</th>
      <th>Status</th>
      <th></th>
    </tr>

    <?php if (empty($employer_invoices)): ?>
      <tr><td colspan="9">No employer invoices yet.</td></tr>
    <?php endif; ?>

    <?php foreach ($employer_invoices as $inv): ?>
      <tr>
        <td><?= e(uk_dt($inv['created_at'] ?? '')) ?></td>
        <td><?= e($inv['employer_name'] ?? '') ?></td>
        <td><?= e($inv['firm_name'] ?? '') ?></td>
        <td><?= e($inv['paralegal_name'] ?? '') ?></td>
        <td><?= e(uk_d($inv['period_start'] ?? '')) ?> → <?= e(uk_d($inv['period_end'] ?? '')) ?></td>
        <td><?= e(number_format((float)($inv['total_hours'] ?? 0), 2)) ?></td>
        <td>£<?= e(number_format((float)($inv['gross_amount'] ?? 0), 2)) ?></td>
        <td><?= e($inv['status'] ?? '') ?></td>
        <td style="white-space:nowrap;">
          <a class="btn micro" href="/a/invoice.php?id=<?= (int)$inv['invoice_id'] ?>">View</a>

          <?php if (!empty($inv['commission_invoice_id'])): ?>
            <a class="btn micro" href="/a/commission_invoice.php?id=<?= (int)$inv['commission_invoice_id'] ?>" style="margin-left:6px;">
              Commission
            </a>
          <?php else: ?>
            <span style="opacity:.6; margin-left:8px;">No commission invoice</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  </div>