<?php
// /app/views/paralegal/timesheets.php
// expects: $daily, $meta, $notes

$daily = $daily ?? [];
$status_filter = (string)($status_filter ?? '');

$awaiting = [];
$previous = [];

foreach ($daily as $r) {
  $st = (string)($r['day_status'] ?? '');
  if ($st === 'Submitted' || $st === 'Queried') $awaiting[] = $r;
  else $previous[] = $r;
}
?>

<div class="section">
  <div class="section-title">Timesheets</div>
  <div class="section-hint" style="margin-top:6px;">
    Daily timesheets across all your assignments (grouped per job per day).
  </div>

  <?php if (!empty($notes)): ?>
    <div style="margin-top:12px; padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:12px; background: rgba(255,255,255,.03);">
      <div style="font-weight:600; margin-bottom:6px;">Recent updates</div>
      <?php foreach ($notes as $n): ?>
        <div class="muted-line" style="display:flex; justify-content:space-between; gap:10px;">
          <div><?= e($n['message']) ?></div>
          <div style="white-space:nowrap; opacity:.7;"><?= e(uk_datetime($n['created_at'] ?? '')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="section" style="margin-top:14px;">
 <div class="section-title"><?= $status_filter === 'Queried' ? 'Queried timesheets' : 'Awaiting' ?></div>
  <div class="section-hint">
    <?= $status_filter === 'Queried'
      ? 'Days with at least one queried entry that need your update.'
      : 'Days that are submitted (awaiting employer review) or queried (needs your update).' ?>
  </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:140px;">Date</th>
            <th>Job</th>
            <th style="width:220px;">Employer</th>
            <th class="right" style="width:110px;">Hours</th>
            <th style="width:90px;">Entries</th>
            <th style="width:140px;">Status</th>
            <th style="width:140px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($awaiting)): ?>
            <tr><td colspan="7" style="opacity:.8;">No timesheets awaiting action.</td></tr>
          <?php else: ?>
            <?php foreach ($awaiting as $r): ?>
              <?php
                $st = (string)($r['day_status'] ?? '');
                $badgeStyle = 'opacity:.85;';
                if ($st === 'Queried') $badgeStyle .= 'color:#f59e0b;font-weight:800;';
              ?>
              <tr>
                <td><?= e(uk_date($r['work_date'] ?? '')) ?></td>
                <td><?= e($r['job_title'] ?? '-') ?></td>
                <td><?= e($r['employer_name'] ?? '-') ?></td>
                <td class="right"><?= e(number_format((float)($r['total_hours'] ?? 0), 2)) ?></td>
                <td><?= (int)($r['entry_count'] ?? 0) ?></td>
                <td><span style="<?= e($badgeStyle) ?>"><?= e($st) ?></span></td>
                <td class="right">
                  <a class="btn small" href="/p/timesheet_day.php?assignment_id=<?= (int)($r['assignment_id'] ?? 0) ?>&date=<?= urlencode((string)($r['work_date'] ?? '')) ?>">
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

 <?php if ($status_filter !== 'Queried'): ?><div class="section" style="margin-top:14px;">
    <div class="section-title">Previous Timesheets</div>
    <div class="section-hint">Approved (and deemed approved, if used) daily timesheets.</div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:140px;">Date</th>
            <th>Job</th>
            <th style="width:220px;">Employer</th>
            <th class="right" style="width:110px;">Hours</th>
            <th style="width:90px;">Entries</th>
            <th style="width:140px;">Status</th>
            <th style="width:140px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($previous)): ?>
            <tr><td colspan="7" style="opacity:.8;">No previous timesheets yet.</td></tr>
          <?php else: ?>
            <?php foreach ($previous as $r): ?>
              <?php
                $st = (string)($r['day_status'] ?? '');
                $badgeStyle = 'opacity:.85;';
                if ($st === 'Approved' || $st === 'Deemed Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
              ?>
              <tr>
                <td><?= e(uk_date($r['work_date'] ?? '')) ?></td>
                <td><?= e($r['job_title'] ?? '-') ?></td>
                <td><?= e($r['employer_name'] ?? '-') ?></td>
                <td class="right"><?= e(number_format((float)($r['total_hours'] ?? 0), 2)) ?></td>
                <td><?= (int)($r['entry_count'] ?? 0) ?></td>
                <td><span style="<?= e($badgeStyle) ?>"><?= e($st) ?></span></td>
                <td class="right">
                  <a class="btn small" href="/p/timesheet_day.php?assignment_id=<?= (int)($r['assignment_id'] ?? 0) ?>&date=<?= urlencode((string)($r['work_date'] ?? '')) ?>">
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div><?php endif; ?>

  <?php if (($meta['total_pages'] ?? 1) > 1): ?>
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:12px;">
      <div style="opacity:.8;">Page <?= (int)$meta['page'] ?> of <?= (int)$meta['total_pages'] ?></div>
      <div style="display:flex; gap:10px;">
        <?php if(!empty($meta['has_prev'])): ?>
          <a class="btn small" href="?page=<?= (int)$meta['page'] - 1 ?>">← Prev</a>
        <?php endif; ?>
        <?php if(!empty($meta['has_next'])): ?>
          <a class="btn small" href="?page=<?= (int)$meta['page'] + 1 ?>">Next →</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>