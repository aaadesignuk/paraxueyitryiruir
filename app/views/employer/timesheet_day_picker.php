<?php
$fmt_hours = function($h){
  $s = rtrim(rtrim(number_format((float)$h, 2, '.', ''), '0'), '.');
  return $s === '' ? '0' : $s;
};
?>

<div class="section">
  <div class="section-title">Select job for <?= e(date('d/m/Y', strtotime((string)$date))) ?></div>
  <div class="section-hint">
    This invoice has time recorded against multiple jobs on this date.
  </div>

  <div style="margin:10px 0; display:flex; gap:10px;">
    <a class="btn btn-sm" href="/e/timesheets_invoice_summary.php?id=<?= (int)$invoice_id ?>">Back to Summary</a>
    <a class="btn btn-sm" href="/e/paralegal_invoice.php?id=<?= (int)$invoice_id ?>">Back to Invoice</a>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Job</th>
          <th style="text-align:right;">Hours</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $j): ?>
          <tr>
            <td><?= e($j['job_title']) ?></td>
            <td style="text-align:right;"><?= $fmt_hours($j['hours']) ?></td>
            <td>
              <a class="btn btn-sm"
                 href="/e/timesheet_day.php?job_id=<?= (int)$j['job_id'] ?>&paralegal_id=<?= (int)$paralegal_id ?>&date=<?= urlencode((string)$date) ?>">
                View day
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>