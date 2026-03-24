<?php
$fmt_hours = function($h){
  $s = rtrim(rtrim(number_format((float)$h, 2, '.', ''), '0'), '.');
  return $s === '' ? '0' : $s;
};
$range_title = e(uk_date($period_start)).' – '.e(uk_date($period_end)).' – Timesheet Summary';
?>

<div class="section">
  <div class="section-title"><?= $range_title ?></div>
  <div class="section-hint">
    Invoice #<?= (int)$invoice_id ?> · Employer: <strong><?= e($employer['full_name'] ?? '') ?></strong><br>
    This summary is based strictly on invoice items for audit consistency.
  </div>

  <div style="margin:10px 0; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn btn-sm" href="/p/invoice.php?id=<?= (int)$invoice_id ?>">Back to Invoice</a>
    <a class="btn btn-sm" href="/p/invoice_download.php?id=<?= (int)$invoice_id ?>&print=1" target="_blank">Print Invoice</a>
  </div>

  <?php foreach ($weeks as $w): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-title">WC <?= e(date('d.m.y', strtotime((string)$w['wc']))) ?></div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Day</th>
              <th>Date</th>
              <th style="text-align:right;">Time (Hours)</th>
              <th>Open Timesheet</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($w['days'] as $d): ?>
              <tr>
                <td><?= e($d['day_name']) ?></td>
                <td><?= e(date('d.m.y', strtotime((string)$d['date']))) ?></td>
                <td style="text-align:right;"><?= $fmt_hours($d['hours']) ?></td>
                <td style="text-align:center;">
                  <?php if (!empty($d['link'])): ?>
                    <a class="btn btn-sm" href="<?= e($d['link']) ?>" style="width:auto; display:inline-flex; white-space:nowrap;">Open</a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr>
              <td colspan="2"><strong>Total</strong></td>
              <td style="text-align:right;"><strong><?= $fmt_hours($w['total']) ?></strong></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="section" style="margin-top:18px;">
    <div class="section-title">Total period</div>
    <div style="font-size:16px;"><strong><?= $fmt_hours($month_total) ?></strong></div>
  </div>
</div>
