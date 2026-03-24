<div class="section">
  <div class="section-title">Monthly Timesheet Summary</div>
  <div class="section-hint">Summary of timesheets across all your jobs for the selected month.</div>

  <form method="get" style="margin:12px 0;">
    <label style="display:inline-block; margin-right:8px;">Month</label>
    <input type="month" name="month" value="<?= e($month) ?>">
    <button class="btn btn-sm" type="submit">View</button>
    <a class="btn btn-sm" href="/e/timesheets.php" style="margin-left:8px;">Back to Timesheets</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Job</th>
          <th>Paralegal</th>
          <th style="text-align:right;">Total Hours</th>
          <th style="text-align:right;">Days</th>
          <th style="text-align:right;">Approved (hrs/days)</th>
          <th style="text-align:right;">Submitted (hrs/days)</th>
          <th style="text-align:right;">Queried (hrs/days)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7">No timesheets found for this month.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['job_title']) ?></td>
              <td><?= e($r['paralegal_name']) ?></td>
              <td style="text-align:right;"><?= number_format((float)$r['total_hours'], 2) ?></td>
              <td style="text-align:right;"><?= (int)$r['total_days'] ?></td>
              <td style="text-align:right;">
                <?= number_format((float)$r['approved_hours'], 2) ?> / <?= (int)$r['approved_days'] ?>
              </td>
              <td style="text-align:right;">
                <?= number_format((float)$r['submitted_hours'], 2) ?> / <?= (int)$r['submitted_days'] ?>
              </td>
              <td style="text-align:right;">
                <?= number_format((float)$r['queried_hours'], 2) ?> / <?= (int)$r['queried_days'] ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>