<?php
// /app/views/employer/daily_timesheet.php
// expects: $date, $paralegal, $paralegal_id, $rows, $total_hours

$date = (string)($date ?? date('Y-m-d'));
$rows = $rows ?? [];
$total_hours = (float)($total_hours ?? 0);
$paraName = (string)($paralegal['full_name'] ?? ('Paralegal #'.(int)$paralegal_id));
?>

<div class="section">
  <div class="section-title">
    <h1>Daily Timesheet</h1>
    <p><?= e($paraName) ?> • <?= e(uk_date($date)) ?> • Total <?= number_format($total_hours, 2) ?> hours</p>
  </div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:10px 0 14px;">
    <a class="btn secondary" href="/e/timesheets.php" style="width:auto;">Back to timesheets</a>

    <form method="post" action="/e/daily_timesheet_action.php" style="display:inline-flex; gap:8px; align-items:center;">
      <input type="hidden" name="paralegal_id" value="<?= (int)$paralegal_id ?>">
      <input type="hidden" name="date" value="<?= e($date) ?>">
      <button class="btn" type="submit" name="action" value="approve_day" style="background:#16a34a; width:auto;">Approve day</button>
    </form>
    <span style="opacity:.75;">(or approve/query entries below)</span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width:120px;">Time</th>
          <th class="right" style="width:90px;">Hours</th>
          <th style="width:90px;">Type</th>
          <th>Activity</th>
          <th style="width:160px;">Client ref</th>
          <th style="width:220px;">Job</th>
          <th style="width:140px;">Status</th>
          <th style="width:320px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" style="opacity:.8;">No entries for this day.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $status = (string)($r['status'] ?? '');
              $displayStatus = (string)($r['display_status'] ?? ($status ?: '—'));
              $badgeStyle = 'opacity:.85;';
              if ($displayStatus === 'Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
              if ($displayStatus === 'Deemed Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
              if ($displayStatus === 'Queried') $badgeStyle .= 'color:#f59e0b;font-weight:800;';
              if ($displayStatus === 'Submitted') $badgeStyle .= 'color:#eab308;font-weight:800;';
            ?>
            <tr>
              <td><?= e($r['time_ranges'] ?? '-') ?></td>
              <td class="right"><?= e(number_format((float)($r['hours_worked'] ?? 0), 2)) ?></td>
              <td><?= e($r['work_type'] ?? 'Work') ?></td>
              <td><?= e($r['desc_clean'] ?? '') ?></td>
              <td><?= e(($r['client_ref'] ?? '') ?: '—') ?></td>
              <td><?= e($r['job_title'] ?? '') ?></td>
              <td><span style="<?= e($badgeStyle) ?>"><?= e($displayStatus) ?></span></td>
              <td>
                <?php if(in_array($status, ['Submitted','Rejected'], true)): ?>
                  <form method="post" action="/e/timesheet_action.php" style="display:flex;gap:8px;align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="timesheet_id" value="<?= (int)($r['timesheet_id'] ?? 0) ?>">
                    <button class="btn small" type="submit" name="action" value="approve" style="background:#16a34a; width:auto;">Approve</button>
                    <button class="btn btn-outline small" type="button" onclick="toggleQuery(<?= (int)($r['timesheet_id'] ?? 0) ?>)" style="width:auto;">Query</button>
                    <?php if ($status === 'Rejected'): ?>
                      <button class="btn btn-outline small" type="button" onclick="toggleReply(<?= (int)($r['timesheet_id'] ?? 0) ?>)" style="width:auto;">Reply</button>
                    <?php endif; ?>
                  </form>

                  <form method="post" action="/e/timesheet_action.php" id="q<?= (int)($r['timesheet_id'] ?? 0) ?>" style="display:none;margin-top:8px;">
                    <input type="hidden" name="timesheet_id" value="<?= (int)($r['timesheet_id'] ?? 0) ?>">
                    <input type="hidden" name="action" value="query">
                    <div class="field" style="margin:0;">
                      <input type="text" name="note" placeholder="What needs changing?" required>
                    </div>
                    <div style="margin-top:8px;">
                      <button class="btn" type="submit" style="width:auto;">Send Query</button>
                    </div>
                  </form>

                  <form method="post" action="/e/timesheet_action.php" id="r<?= (int)($r['timesheet_id'] ?? 0) ?>" style="display:none;margin-top:8px;">
                    <input type="hidden" name="timesheet_id" value="<?= (int)($r['timesheet_id'] ?? 0) ?>">
                    <input type="hidden" name="action" value="reply">
                    <div class="field" style="margin:0;">
                      <input type="text" name="note" placeholder="Reply to paralegal response" required>
                    </div>
                    <div style="margin-top:8px;">
                      <button class="btn" type="submit" style="width:auto;">Send Reply</button>
                    </div>
                    <div style="opacity:.75; margin-top:6px;">Employer reply is required before the paralegal can raise a dispute.</div>
                  </form>
                <?php else: ?>
                  <span style="opacity:.7;">—</span>
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
            <th colspan="6"></th>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<script>
function toggleQuery(id){
  const el = document.getElementById('q'+id);
  if (!el) return;
  el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
}
function toggleReply(id){
  const el = document.getElementById('r'+id);
  if (!el) return;
  el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
}
</script>