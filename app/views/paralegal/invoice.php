<div class="section">
  <div class="section-title">
    <h1>Invoice #<?= (int)$inv['invoice_id'] ?></h1>
    <p>
      <strong>Employer:</strong> <?= e($employer['full_name'] ?? ('User #'.(int)$inv['employer_id'])) ?>
      &nbsp;•&nbsp;
      <strong>Period:</strong> <?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?>
      &nbsp;•&nbsp;
      <strong>Status:</strong> <?= e($inv['status']) ?>
    </p>
    <p>
      <strong>Client Ref:</strong> <?= e(($inv['client_ref'] ?? '') !== '' ? $inv['client_ref'] : '—') ?>
      &nbsp;•&nbsp;
      <strong>Job:</strong> <?= e(($inv['job_title'] ?? '') !== '' ? $inv['job_title'] : '—') ?>
    </p>
  </div>

  <style>
    .invoice-action-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
    .invoice-action-row .btn,
    .invoice-action-row button.btn{width:auto;min-width:180px;flex:1 1 180px;display:inline-flex;justify-content:center;white-space:nowrap}
    .invoice-status-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-weight:700}
    .invoice-status-draft{background:#f3f4f6;color:#374151}
    .invoice-status-submitted{background:#dbeafe;color:#1d4ed8}
    .invoice-status-paid{background:#dcfce7;color:#166534}
    @media (max-width: 900px){
      .invoice-action-row .btn,
      .invoice-action-row button.btn{flex:1 1 calc(50% - 10px)}
    }
    @media (max-width: 640px){
      .invoice-action-row .btn,
      .invoice-action-row button.btn{flex:1 1 100%}
    }
  </style>

  <?php
    $st = (string)($inv['status'] ?? '');
    $statusClass = 'invoice-status-draft';
    if ($st === 'Submitted') $statusClass = 'invoice-status-submitted';
    if ($st === 'Paid') $statusClass = 'invoice-status-paid';
  ?>

  <div style="margin-bottom:12px;">
    <span class="invoice-status-badge <?= e($statusClass) ?>"><?= e($st) ?></span>
  </div>

  <div class="invoice-action-row">
    <a class="btn" href="/p/invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>" target="_blank">Download invoice</a>
    <a class="btn secondary" href="/p/timesheets_invoice_summary.php?id=<?= (int)$inv['invoice_id'] ?>">Monthly summary</a>
    <a class="btn secondary" href="/p/invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>&print=1" target="_blank">Print / Save as PDF</a>
    <a class="btn secondary" href="/p/invoices.php">← Back to invoices</a>
  </div>

  <?php if (($inv['status'] ?? '') === 'Draft'): ?>
    <form method="post" style="margin-bottom:12px;">
      <input type="hidden" name="action" value="submit">
      <button class="btn" type="submit" style="width:auto;">Submit to employer</button>
      <span style="margin-left:10px; opacity:0.8;">Once submitted, the employer can mark it as paid after they pay you directly.</span>
    </form>
  <?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Description</th>
        <th style="text-align:right;">Hours</th>
        <th style="text-align:right;">Rate</th>
        <th style="text-align:right;">Amount</th>
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
          <td style="text-align:right;"><?= number_format((float)($it['hours'] ?? 0), 2) ?></td>
          <td style="text-align:right;"><?= $it['hourly_rate'] !== null ? '£'.number_format((float)$it['hourly_rate'], 2) : 'Mixed' ?></td>
          <td style="text-align:right;">£<?= number_format((float)($it['amount'] ?? 0), 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="2">Total</th>
        <th style="text-align:right;"><?= number_format((float)($inv['total_hours'] ?? 0), 2) ?></th>
        <th></th>
        <th style="text-align:right;">£<?= number_format((float)($inv['gross_amount'] ?? 0), 2) ?></th>
      </tr>
    </tfoot>
  </table>
</div>