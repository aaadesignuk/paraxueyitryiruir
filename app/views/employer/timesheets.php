<?php
// /app/views/employer/timesheets.php
// Daily list view
// expects: $daily_timesheets

$daily_timesheets = $daily_timesheets ?? [];

$awaiting = [];
$previous = [];

foreach ($daily_timesheets as $r) {
  $st = (string)($r['day_status'] ?? '');
  if ($st === 'Submitted' || $st === 'Queried') {
    $awaiting[] = $r;
  } else {
    $previous[] = $r;
  }
}
?>

<div class="section">
  <div class="section-title">Timesheets</div>
  <div class="section-hint">
    All daily timesheets across your jobs.
  </div>

  <div class="section" style="margin-top:14px;">
    <div class="section-title">Timesheets to Review</div>
    <div class="section-hint">Submitted or queried entries that still need employer action.</div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Job</th>
            <th>Paralegal</th>
            <th>Total Hours</th>
            <th>Entries</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($awaiting)): ?>
            <tr><td colspan="7" style="opacity:.8;">No timesheets awaiting action.</td></tr>
          <?php else: ?>
            <?php foreach ($awaiting as $r): ?>
              <tr>
                <td><?= e(date('d/m/Y', strtotime((string)$r['work_date']))) ?></td>
                <td><?= e($r['job_title']) ?></td>
                <td><?= e($r['paralegal_name']) ?></td>
           <td><?= number_format((float)($r['total_hours'] ?? 0), 2) ?></td>
<td><?= (int)($r['entry_count'] ?? 0) ?></td>
                <td><span class="badge"><?= e($r['day_status']) ?></span></td>
                <td>
                  <a class="btn btn-sm" href="/e/timesheet_day.php?job_id=<?= (int)$r['job_id'] ?>&paralegal_id=<?= (int)$r['paralegal_id'] ?>&date=<?= urlencode((string)$r['work_date']) ?>">Approve/Query</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section" style="margin-top:14px;">
    <div class="section-title">Previous Timesheets</div>
    <div class="section-hint">Approved (and deemed approved, if used) daily timesheets.</div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Job</th>
            <th>Paralegal</th>
            <th>Total Hours</th>
            <th>Entries</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($previous)): ?>
            <tr><td colspan="7" style="opacity:.8;">No previous timesheets yet.</td></tr>
          <?php else: ?>
            <?php foreach ($previous as $r): ?>
              <tr>
                <td><?= e(date('d/m/Y', strtotime((string)$r['work_date']))) ?></td>
                <td><?= e($r['job_title']) ?></td>
                <td><?= e($r['paralegal_name']) ?></td>
          <td><?= number_format((float)($r['total_hours'] ?? 0), 2) ?></td>
<td><?= (int)($r['entry_count'] ?? 0) ?></td>
                <td><span class="badge"><?= e($r['day_status']) ?></span></td>
                <td>
                  <a class="btn btn-sm" href="/e/timesheet_day.php?job_id=<?= (int)$r['job_id'] ?>&paralegal_id=<?= (int)$r['paralegal_id'] ?>&date=<?= urlencode((string)$r['work_date']) ?>">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>