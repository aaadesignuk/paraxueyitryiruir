<?php
// /app/views/paralegal/timesheet_day.php
// expects: $assn, $assignment_id, $date, $entries, $total_hours, $day_status, $can_submit_day
?>

<div class="section">
  <div class="section-title">Daily Timesheet</div>

  <div class="section-hint">
    <strong><?= e($assn['job_title'] ?? '') ?></strong><br>
    Employer: <?= e($assn['employer_name'] ?? '') ?><br>
    Date: <?= e(uk_date($date ?? '')) ?> · Status: <strong><?= e($day_status ?? '') ?></strong> · Daily total: <strong><?= e(number_format((float)$total_hours, 2)) ?></strong> hours
  </div>

  <div style="margin:12px 0; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn btn-sm" href="/p/timesheets.php">Back to Timesheets</a>
    <a class="btn btn-sm secondary" href="/p/assignment.php?id=<?= (int)($assignment_id ?? 0) ?>">Back to Job</a>

    <?php if (!empty($can_submit_day)): ?>
      <?php if (($day_status ?? '') === 'Draft'): ?>
        <a class="btn btn-sm" href="/p/timesheet_submit.php?assignment_id=<?= (int)($assignment_id ?? 0) ?>&date=<?= e($date ?? '') ?>">Add / Edit Time Entries</a>
      <?php else: ?>
        <span class="section-hint" style="margin:0;">Day submitted/locked. Only queried entries can be edited.</span>
      <?php endif; ?>

      <?php if (((int)($draft ?? 0)) > 0): ?>
        <form method="post" action="/p/timesheet_submit.php?assignment_id=<?= (int)($assignment_id ?? 0) ?>&date=<?= e($date ?? '') ?>" style="display:inline;">
          <input type="hidden" name="action" value="submit_day">
          <input type="hidden" name="work_date" value="<?= e($date ?? '') ?>">
          <button class="btn btn-sm secondary" type="submit">Submit Daily Time</button>
        </form>
      <?php else: ?>
        <span class="section-hint" style="margin:0;">No draft entries to submit.</span>
      <?php endif; ?>
    <?php else: ?>
      <a class="btn btn-sm" href="/p/timesheet_submit.php?assignment_id=<?= (int)($assignment_id ?? 0) ?>&date=<?= e($date ?? '') ?>">Edit entries for this day</a>
      <div class="section-hint" style="margin:0;">
        Submissions are only available for today or yesterday.
      </div>
    <?php endif; ?>
  </div>

  <style>
    .query-inline{
      margin-top:8px;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid rgba(245,158,11,.35);
      background:rgba(245,158,11,.08);
    }
    .query-inline-title{
      font-weight:800;
      color:#f59e0b;
      margin-bottom:4px;
    }
    .query-inline-meta{
      margin-top:6px;
      font-size:12px;
      opacity:.8;
    }
  </style>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th style="width:140px;">Time</th>
          <th style="width:120px;">Duration</th>
          <th>Activity</th>
          <th style="width:140px;">Status</th>
          <th style="width:110px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($entries)): ?>
          <tr><td colspan="5" style="opacity:.8;">No entries for this day.</td></tr>
        <?php else: ?>
          <?php foreach ($entries as $r): ?>
            <?php
              $st = (string)($r['status_display'] ?? '');
              $badgeStyle = 'opacity:.85;';
              if ($st === 'Queried') $badgeStyle .= 'color:#f59e0b;font-weight:800;';
              if ($st === 'Approved' || $st === 'Deemed Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
              if ($st === 'Draft') $badgeStyle .= 'color:#64748b;font-weight:800;';

              $rawStatus = (string)($r['status'] ?? '');
              $canEditRow = in_array($rawStatus, ['Draft','Rejected'], true);

              $queryReason = trim((string)($r['query_reason'] ?? ''));
              $queryCreatedAt = (string)($r['query_created_at'] ?? '');
            ?>
            <tr>
              <td><?= e($r['time_range'] ?? '—') ?></td>
              <td><?= e($r['duration_display'] ?? '—') ?></td>
              <td>
                <?= nl2br(e($r['description_clean'] ?? '')) ?>

                <?php if ($st === 'Queried' && $queryReason !== ''): ?>
                  <div class="query-inline">
                    <div class="query-inline-title">Employer query</div>
                    <div><?= nl2br(e($queryReason)) ?></div>

                    <?php if ($queryCreatedAt !== ''): ?>
                      <div class="query-inline-meta">
                        Queried on <?= e(uk_datetime($queryCreatedAt)) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><span style="<?= e($badgeStyle) ?>"><?= e($st) ?></span></td>
              <td style="text-align:right;">
                <?php if ($canEditRow): ?>
                  <a class="btn btn-sm" href="/p/timesheet_submit.php?timesheet_id=<?= (int)($r['timesheet_id'] ?? 0) ?>">Edit</a>
                <?php else: ?>
                  <span style="opacity:.6;">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>