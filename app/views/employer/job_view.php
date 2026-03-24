<?php
// expects:
// $job, $handover, $suggested, $invites, $rehire, $approved_hours, $remaining_hours, $remaining_days, $daily_timesheets
// $current_assignment, $assignment_state

function status_chip_daily($s){
  $s = strtolower((string)$s);
  if ($s === 'approved') return '<span class="chip" style="border-color:rgba(34,139,34,.55);">Approved</span>';
  if ($s === 'deemed approved') return '<span class="chip" style="border-color:rgba(34,139,34,.55);">Deemed Approved</span>';
  if ($s === 'submitted') return '<span class="chip">Submitted</span>';
  if ($s === 'queried') return '<span class="chip" style="border-color:rgba(255,165,0,.5);">Queried</span>';
  return '<span class="chip" style="border-color:rgba(255,255,255,.18);">'.e(ucfirst($s)).'</span>';
}
?>

<style>
.assignment-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
  gap:12px;
  margin-top:12px;
}
.assignment-card{
  padding:14px;
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  background:rgba(255,255,255,.03);
}
.assignment-label{
  opacity:.72;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.assignment-value{
  margin-top:6px;
  font-size:18px;
  font-weight:700;
}
.assignment-note{
  margin-top:8px;
  opacity:.86;
  line-height:1.5;
}
.people-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
  gap:12px;
  margin-top:12px;
}
.people-card{
  padding:14px;
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  background:rgba(255,255,255,.03);
}
.people-name{
  font-size:16px;
  font-weight:700;
}
.people-meta{
  margin-top:6px;
  opacity:.86;
  line-height:1.55;
}
.people-actions{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-top:12px;
}
.table-wrap{
  overflow-x:auto;
}
.ts-grid{ overflow-x: hidden; }
.ts-grid table{ table-layout: fixed; width:100%; }
.ts-grid .table th, .ts-grid .table td{ padding:6px 8px; }
.ts-grid .table th{ white-space:nowrap; }
.nowrap{ white-space:nowrap; }
.warn{ color:#f59e0b; font-weight:800; }
.clip{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; max-width:100%; }
</style>

<div class="section">
  <div class="section-title"><?= e($job['title']) ?></div>
  <div class="muted-line">
    <?= e($job['specialism'] ?? '-') ?><?= !empty($job['sub_specialism']) ? ' / '.e($job['sub_specialism']) : '' ?>
    • Status: <strong><?= e($job['status'] ?? '-') ?></strong>
  </div>

  <div class="job-scope" style="grid-template-columns:repeat(4,1fr); margin-top:14px;">
    <div class="scope-item">
      <div class="scope-label">Client ref</div>
      <div class="scope-value"><?= e($job['client_ref'] ?? '-') ?></div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Specialism</div>
      <div class="scope-value"><?= e(($job['specialism'] ?? '').(($job['sub_specialism'] ?? '') ? ' / '.$job['sub_specialism'] : '')) ?></div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Hours required</div>
      <div class="scope-value">
        <?php
          $hrs = $job['hours_required'] ?? null;
          $days = $job['days_required'] ?? null;
          if (($job['job_type'] ?? 'Hours') === 'Days' && $days !== null && $days !== '') {
            echo e(number_format((float)$days, 2)).' days';
          } elseif ($hrs !== null && $hrs !== '') {
            echo e(number_format((float)$hrs, 2)).' hrs';
          } else {
            echo '-';
          }
        ?>
      </div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Offered rate</div>
      <div class="scope-value">
        <?php
          $offered = $job['rate_amount'] ?? $job['max_rate'] ?? null;
          echo $offered !== null ? '£'.e(number_format((float)$offered, 2)).'/hr' : '-';
        ?>
      </div>
    </div>
  </div>

  <div class="job-scope" style="grid-template-columns:repeat(4,1fr); margin-top:14px;">
    <div class="scope-item">
      <div class="scope-label">Approved hours</div>
      <div class="scope-value"><?= e(number_format((float)$approved_hours, 2)) ?> hrs</div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Remaining</div>
      <div class="scope-value">
        <?php if ($remaining_days !== null): ?>
          <?= e(number_format((float)$remaining_days, 2)) ?> days
        <?php elseif ($remaining_hours !== null): ?>
          <?= e(number_format((float)$remaining_hours, 2)) ?> hrs
        <?php else: ?>
          —
        <?php endif; ?>
      </div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Deadline</div>
      <div class="scope-value"><?= e(!empty($job['deadline']) ? uk_date($job['deadline']) : '-') ?></div>
    </div>

    <div class="scope-item">
      <div class="scope-label">Created</div>
      <div class="scope-value"><?= e(!empty($job['created_at']) ? uk_datetime($job['created_at']) : '-') ?></div>
    </div>
  </div>

  <?php if(!empty($job['description'])): ?>
    <p style="margin-top:16px;"><?= nl2br(e($job['description'])) ?></p>
  <?php endif; ?>
</div>

<div class="section">
  <div class="section-title">Assignment Status</div>

  <div class="assignment-grid">
    <?php if ($assignment_state === 'assigned' && !empty($current_assignment)): ?>
      <div class="assignment-card">
        <div class="assignment-label">Current status</div>
        <div class="assignment-value">Assigned</div>
        <div class="assignment-note">
          Assigned to
          <a href="/e/paralegal_profile.php?id=<?= (int)$current_assignment['paralegal_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">
            <?= e($current_assignment['full_name'] ?? 'Paralegal') ?>
          </a>
          <?php if (!empty($current_assignment['started_at'])): ?>
            on <?= e(uk_datetime($current_assignment['started_at'])) ?>
          <?php endif; ?>.
        </div>
      </div>
    <?php elseif ($assignment_state === 'invited'): ?>
      <div class="assignment-card">
        <div class="assignment-label">Current status</div>
        <div class="assignment-value">Invite sent</div>
        <div class="assignment-note">
          Waiting for paralegal response. You can review profiles below and invite another paralegal if needed.
        </div>
      </div>
    <?php else: ?>
      <div class="assignment-card">
        <div class="assignment-label">Current status</div>
        <div class="assignment-value">Pending assignment</div>
        <div class="assignment-note">
          No paralegal has accepted this job yet. Review the suggested paralegals below and send an invite.
        </div>
      </div>
    <?php endif; ?>

    <div class="assignment-card">
      <div class="assignment-label">Work mode</div>
      <div class="assignment-value"><?= e($job['work_mode'] ?? '-') ?></div>
      <div class="assignment-note">
        <?php
          $bits = [];
          if (!empty($job['job_country'])) $bits[] = $job['job_country'];
          if (!empty($job['job_city'])) $bits[] = $job['job_city'];
          echo !empty($bits) ? e(implode(' / ', $bits)) : 'Location not specified.';
        ?>
      </div>
    </div>
  </div>
</div>

<?php if ($assignment_state !== 'assigned'): ?>
  <div class="section">
    <div class="section-title">Suggested Paralegals</div>
    <div class="section-hint">After job creation, this is your next step. Review a profile, then invite the paralegal you want.</div>

    <?php if (empty($suggested)): ?>
      <div style="opacity:.85;">No suggested matches are available right now.</div>
    <?php else: ?>
      <div class="people-grid">
        <?php foreach ($suggested as $p): ?>
          <div class="people-card">
            <div class="people-name">
              <a href="/e/paralegal_profile.php?id=<?= (int)$p['user_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">
                <?= e($p['full_name'] ?? '-') ?>
              </a>
            </div>

            <div class="people-meta">
              <div><strong>Specialism:</strong> <?= e($p['specialism'] ?? ($job['specialism'] ?? '-')) ?></div>
              <div>
                <strong>Matched rate:</strong>
                <?= isset($p['matched_rate']) && $p['matched_rate'] !== null ? '£'.e(number_format((float)$p['matched_rate'], 2)).'/hr' : '—' ?>
              </div>
              <div><strong>Availability:</strong> <?= !empty($p['is_available']) ? 'Available' : 'Unavailable' ?></div>
              <div><strong>Location:</strong> <?= e($p['location_preference'] ?? '-') ?></div>
              <div><strong>Task matches:</strong> <?= (int)($p['matched_tasks'] ?? 0) ?></div>
            </div>

            <div class="people-actions">
              <a class="btn micro" href="/e/paralegal_profile.php?id=<?= (int)$p['user_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">View Profile</a>
              <a class="btn micro" href="/e/invite_paralegal.php?job_id=<?= (int)$job['job_id'] ?>&paralegal_id=<?= (int)$p['user_id'] ?>">Invite</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="section">
  <div class="section-title">Invites</div>
  <div class="section-hint">Invited paralegals can be opened from here to review their full profile.</div>

  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <tr>
        <th>Paralegal</th>
        <th style="width:140px;">Status</th>
        <th style="width:180px;">Invited</th>
        <th style="width:130px;"></th>
      </tr>

      <?php if (empty($invites)): ?>
        <tr><td colspan="4">No invites sent yet.</td></tr>
      <?php else: ?>
        <?php foreach ($invites as $inv): ?>
          <tr>
            <td>
              <a href="/e/paralegal_profile.php?id=<?= (int)$inv['paralegal_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">
                <?= e($inv['full_name'] ?? '-') ?>
              </a>
            </td>
            <td><?= e($inv['status'] ?? '-') ?></td>
            <td><?= e(!empty($inv['created_at']) ? uk_datetime($inv['created_at']) : '-') ?></td>
            <td>
              <a class="btn micro" href="/e/paralegal_profile.php?id=<?= (int)$inv['paralegal_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">Profile</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php if (!empty($rehire) && $assignment_state !== 'assigned'): ?>
  <div class="section">
    <div class="section-title">Rehire Previous Paralegals</div>
    <div class="section-hint">Quick access to paralegals you have used before.</div>

    <div class="people-grid">
      <?php foreach ($rehire as $p): ?>
        <div class="people-card">
          <div class="people-name">
            <a href="/e/paralegal_profile.php?id=<?= (int)$p['user_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">
              <?= e($p['full_name'] ?? '-') ?>
            </a>
          </div>

          <div class="people-meta">
            <div><strong>Specialism:</strong> <?= e($p['specialism'] ?? '-') ?></div>
            <div>
              <strong>Standard rate:</strong>
              <?= isset($p['preferred_rate']) && $p['preferred_rate'] !== null ? '£'.e(number_format((float)$p['preferred_rate'], 2)).'/hr' : '—' ?>
            </div>
            <div><strong>Availability:</strong> <?= !empty($p['is_available']) ? 'Available' : 'Unavailable' ?></div>
          </div>

          <div class="people-actions">
            <a class="btn micro" href="/e/paralegal_profile.php?id=<?= (int)$p['user_id'] ?>&job_id=<?= (int)$job['job_id'] ?>">View Profile</a>
            <a class="btn micro" href="/e/invite_paralegal.php?job_id=<?= (int)$job['job_id'] ?>&paralegal_id=<?= (int)$p['user_id'] ?>">Invite</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- TIMESHEETS (Daily) -->
<div class="section" id="timesheets">
  <div class="section-title">Timesheets</div>
  <div class="section-hint">Daily timesheets grouped by date + paralegal. Open a day to review entries; approve a full day if everything is correct.</div>

  <?php
    $review_rows = [];
    $previous_rows = [];
    foreach (($daily_timesheets ?? []) as $d) {
      if (!empty($d['is_reviewable'])) $review_rows[] = $d;
      else $previous_rows[] = $d;
    }

    $render_timesheet_rows = function(array $rows, bool $is_review_section = true) use ($job) {
      if (empty($rows)) {
        $msg = $is_review_section ? 'No timesheets awaiting action.' : 'No previous timesheets yet.';
        echo '<tr><td colspan="6" style="opacity:.8;">'.e($msg).'</td></tr>';
        return;
      }
      foreach ($rows as $d) {
        $date = (string)($d['work_date'] ?? '');
        $pid = (int)($d['paralegal_id'] ?? 0);
        $status = (string)($d['day_status'] ?? '');
        $openDisputes = (int)($d['open_dispute_count'] ?? 0);
        ?>
        <tr>
          <td class="nowrap"><?= e(uk_date($date)) ?></td>
          <td><span class="clip"><?= e($d['paralegal_name'] ?? '') ?></span></td>
          <?php
            $hoursValue = $is_review_section ? (float)($d['review_hours'] ?? 0) : (float)($d['total_hours'] ?? 0);
            $entriesValue = $is_review_section ? (int)($d['review_entry_count'] ?? 0) : (int)($d['entry_count'] ?? 0);
          ?>
          <td class="right"><?= e(number_format($hoursValue, 2)) ?></td>
          <td class="right"><?= $entriesValue ?></td>
          <td>
            <?= status_chip_daily($status) ?>
            <?php if ($openDisputes > 0): ?>
              <span class="warn" title="There is at least one open dispute on this day"> • Dispute</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
              <a class="btn small" href="/e/timesheet_day.php?job_id=<?= (int)($job['job_id'] ?? 0) ?>&paralegal_id=<?= (int)$pid ?>&date=<?= e($date) ?>" style="width:auto;"><?= $is_review_section ? 'Approve/Query' : 'View' ?></a>
            </div>
          </td>
        </tr>
        <?php
      }
    };
  ?>

  <div class="section" style="margin-top:14px; padding:0; border:0; background:none; box-shadow:none;">
    <div class="section-title">Timesheets to Review</div>
    <div class="section-hint">Submitted or queried entries that still need employer action.</div>

    <div class="table-wrap ts-grid" style="margin-top:10px;">
      <table class="table">
        <thead>
          <tr>
            <th style="width:120px;">Date</th>
            <th style="width:180px;">Paralegal</th>
            <th class="right" style="width:90px;">Hours</th>
            <th class="right" style="width:80px;">Entries</th>
            <th style="width:140px;">Status</th>
            <th style="width:220px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $render_timesheet_rows($review_rows, true); ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section" style="margin-top:14px; padding:0; border:0; background:none; box-shadow:none;">
    <div class="section-title">Previous Timesheets</div>
    <div class="section-hint">Approved (and deemed approved, if used) daily timesheets.</div>

    <div class="table-wrap ts-grid" style="margin-top:10px;">
      <table class="table">
        <thead>
          <tr>
            <th style="width:120px;">Date</th>
            <th style="width:180px;">Paralegal</th>
            <th class="right" style="width:90px;">Hours</th>
            <th class="right" style="width:80px;">Entries</th>
            <th style="width:140px;">Status</th>
            <th style="width:220px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $render_timesheet_rows($previous_rows, false); ?>
        </tbody>
      </table>
    </div>
  </div>
</div>