<?php
// /app/views/paralegal/daily_timesheet.php
// expects: $date, $rows, $total_hours, $day_status, $job_groups_list, $can_submit_day

$date = (string)($date ?? date('Y-m-d'));
$rows = $rows ?? [];
$total_hours = (float)($total_hours ?? 0);
$day_status = (string)($day_status ?? '—');
$job_groups_list = $job_groups_list ?? [];
$can_submit_day = (bool)($can_submit_day ?? false);

$prev = date('Y-m-d', strtotime($date.' -1 day'));
$next = date('Y-m-d', strtotime($date.' +1 day'));

$badgeStyle = 'opacity:.85;';
if ($day_status === 'Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
if ($day_status === 'Submitted') $badgeStyle .= 'color:#eab308;font-weight:800;';
if ($day_status === 'Queried') $badgeStyle .= 'color:#f59e0b;font-weight:800;';
if ($day_status === 'Draft') $badgeStyle .= 'opacity:.75;';
?>

<div class="section">
  <div class="section-title">Daily Timesheet</div>
  <div class="section-hint" style="margin-top:6px;">
    <strong><?= e(uk_date($date)) ?></strong>
    &nbsp;•&nbsp;
    Status: <span style="<?= e($badgeStyle) ?>"><?= e($day_status) ?></span>
    &nbsp;•&nbsp;
    Total: <strong><?= number_format($total_hours, 2) ?></strong> hours
  </div>

  <div class="muted-line" style="margin-top:10px;">
    Add a new entry by opening the relevant job and clicking <strong>+ Add timesheet</strong>. Each work session is submitted individually.
  </div>

  <?php if (!empty($job_groups_list)): ?>
    <div class="section" style="margin-top:14px;">
      <div class="section-title">Jobs worked this day</div>
      <div class="section-hint">
        Submit drafts per job to avoid submitting time for the wrong employer.
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Job</th>
              <th style="width:220px;">Employer</th>
              <th class="right" style="width:120px;">Hours</th>
              <th class="right" style="width:140px;">Draft entries</th>
              <th style="width:190px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($job_groups_list as $g): ?>
              <?php
                $aid = (int)($g['assignment_id'] ?? 0);
                $drafts = (int)($g['draft_count'] ?? 0);
                $hours = (float)($g['total_hours'] ?? 0);
              ?>
              <tr>
                <td><?= e($g['job_title'] ?? '—') ?></td>
                <td><?= e($g['employer_name'] ?? '—') ?></td>
                <td class="right"><?= e(number_format($hours, 2)) ?></td>
                <td class="right"><?= (int)$drafts ?></td>
                <td class="right" style="display:flex; gap:8px; justify-content:flex-end;">
                  <a class="btn btn-sm secondary" href="/p/timesheet_day.php?assignment_id=<?= $aid ?>&date=<?= e($date) ?>">Open day</a>

                  <?php if ($can_submit_day && $drafts > 0): ?>
                    <form method="post" action="/p/timesheet_submit.php?assignment_id=<?= $aid ?>&date=<?= e($date) ?>" style="display:inline;">
                      <input type="hidden" name="action" value="submit_day">
                      <input type="hidden" name="work_date" value="<?= e($date) ?>">
                      <button class="btn btn-sm" type="submit">Submit Jobs Daily Time</button>
                    </form>
                  <?php else: ?>
                    <span style="opacity:.6;">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!$can_submit_day): ?>
        <div class="section-hint" style="margin-top:10px;">Submissions are only available for today or yesterday.</div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:12px;">
    <a class="btn" href="/p/daily_timesheet.php?date=<?= e($prev) ?>" style="width:auto;">← Prev day</a>
    <a class="btn" href="/p/daily_timesheet.php?date=<?= e($next) ?>" style="width:auto;">Next day →</a>
    <a class="btn secondary" href="/p/timesheets.php" style="width:auto;">Back to timesheets</a>
  </div>

  <div class="table-wrap" style="margin-top:14px;">
    <table class="table">
      <thead>
        <tr>
          <th style="width:120px;">Time</th>
          <th class="right" style="width:90px;">Hours</th>
          <th style="width:90px;">Type</th>
          <th>Activity</th>
          <th style="width:160px;">Client ref</th>
          <th style="width:200px;">Job</th>
          <th style="width:180px;">Employer</th>
          <th style="width:120px;">Status</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" style="opacity:.8;">No entries for this day yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $st = (string)($r['display_status'] ?? '');
              $bs = 'opacity:.85;';
              if ($st === 'Approved') $bs .= 'color:#16a34a;font-weight:800;';
              if ($st === 'Submitted') $bs .= 'color:#eab308;font-weight:800;';
              if ($st === 'Queried') $bs .= 'color:#f59e0b;font-weight:800;';
              if ($st === 'Draft') $bs .= 'opacity:.75;';
            ?>
            <tr>
              <td><?= e($r['time_ranges'] ?? '-') ?></td>
              <td class="right"><?= e(number_format((float)($r['hours_worked'] ?? 0), 2)) ?></td>
              <td><?= e($r['work_type'] ?? 'Work') ?></td>
              <td><?= e($r['desc_clean'] ?? '') ?></td>
              <td><?= e(($r['client_ref'] ?? '') ?: '—') ?></td>
              <td><?= e($r['job_title'] ?? '—') ?></td>
              <td><?= e($r['employer_name'] ?? '—') ?></td>
              <td><span style="<?= e($bs) ?>"><?= e($st ?: '—') ?></span></td>
              <td class="right">
                <?php if (!empty($r['assignment_id']) && !empty($r['timesheet_id'])): ?>
                  <a class="btn small" href="/p/assignment.php?id=<?= (int)$r['assignment_id'] ?>&timesheet_id=<?= (int)$r['timesheet_id'] ?>#timesheets">Open</a>
                <?php else: ?>
                  <span style="opacity:.6;">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
        <tfoot>
          <tr>
            <th>Daily total</th>
            <th class="right"><?= number_format($total_hours, 2) ?></th>
            <th colspan="7"></th>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>