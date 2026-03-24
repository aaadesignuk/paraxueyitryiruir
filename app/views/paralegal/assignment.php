<?php
// app/views/paralegal/assignment.php
// expects: $assignment, $handover, $timesheets, $queried_notes, $draft_timesheet_id

function status_badge(string $status): string {
  $st = strtolower(trim($status));

  // Map internal status to display label
// Map internal status to display label
if ($st === 'rejected') $label = 'Queried';
elseif ($st === 'disputed') $label = 'Disputed';
elseif (str_starts_with($st, 'resolved')) $label = $status; // keep "Resolved (Approved)" etc.
else $label = ($status ?: '-');

  // Colour map
  $bg = 'rgba(255,255,255,.08)';
  $fg = 'rgba(255,255,255,.85)';
  $bd = 'rgba(255,255,255,.10)';

  if ($st === 'approved') {
    $bg = 'rgba(46,204,113,.16)'; $fg = '#b6ffd7'; $bd = 'rgba(46,204,113,.28)';
  } elseif ($st === 'submitted') {
    $bg = 'rgba(241,196,15,.16)'; $fg = '#ffeaa7'; $bd = 'rgba(241,196,15,.28)';
  } elseif ($st === 'rejected') {
    $bg = 'rgba(231,76,60,.16)'; $fg = '#ffb4ad'; $bd = 'rgba(231,76,60,.30)';
  } elseif ($st === 'disputed') {
    // Disputed = red
    $bg = 'rgba(231,76,60,.16)'; $fg = '#ffb4ad'; $bd = 'rgba(231,76,60,.30)';
  } elseif (str_starts_with($st, 'resolved')) {
    // Resolved = blue (regardless of approved/declined)
    $bg = 'rgba(52,152,219,.16)'; $fg = '#bfe6ff'; $bd = 'rgba(52,152,219,.30)';
  } elseif ($st === 'draft') {
    $bg = 'rgba(149,165,166,.16)'; $fg = '#e8eef0'; $bd = 'rgba(149,165,166,.28)';
  }

  return '<span style="display:inline-block;padding:6px 10px;border-radius:999px;'
       . 'background:'.$bg.';color:'.$fg.';border:1px solid '.$bd.';line-height:1;">'
       . e($label)
       . '</span>';
}
?>

<style>
/* highlight row when deep-linking from list pages */
.timesheet-highlight{
  background: rgba(34,139,34,.08);
  border-left: 4px solid #1f7a4d;
}
</style>

<script>
window.addEventListener('DOMContentLoaded', function(){
  const params = new URLSearchParams(window.location.search);
  const tid = params.get('timesheet_id');
  if(tid){
    const row = document.querySelector('[data-timesheet-row="'+tid+'"]');
    if(row) row.classList.add('timesheet-highlight');
    location.hash = 'timesheets';
  }
});
</script>

<div class="section" style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;">
  <div>
    <div class="section-title">Job — <?= e($assignment['title'] ?? 'Job') ?></div>
    <div class="section-hint">Log each work session as its own timesheet. Submitted/approved timesheets are locked; queried ones can be edited and resubmitted.</div>
  </div>

  <div>
    <?php
      // IMPORTANT:
      // If $draft_timesheet_id is ever non-editable (e.g. points at Submitted), the edit page will lock.
      // The safest navigation is to let timesheet_submit.php resolve/create the correct Draft by assignment_id.
      // (Your controller can still pass a draft id when available; this keeps it working either way.)
      $aid = (int)($assignment['assignment_id'] ?? 0);
    ?>
    <?php if ($aid > 0): ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
      
		  
        <?php if (empty($today_locked)): ?>
          <a class="btn" href="/p/timesheet_submit.php?assignment_id=<?= $aid ?>">+ Add Time Entry</a>
        <?php endif; ?>

        <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <?php if (!empty($today_locked)): ?>
            <p class="chip" style="opacity:.85; margin:0; text-align:center;">Todays timesheet has been submitted (locked)</p>
          <?php endif; ?>
          <a class="btn secondary" href="/p/timesheet_day.php?assignment_id=<?= $aid ?>&date=<?= e(date('Y-m-d')) ?>">Today for this job</a>
        </div>
		  
		  
		  
		  
        <?php if (empty($today_locked)): ?>
          <form method="post" action="/p/timesheet_submit.php?assignment_id=<?= $aid ?>&date=<?= e(date('Y-m-d')) ?>" style="display:inline;">
            <input type="hidden" name="action" value="submit_day">
            <input type="hidden" name="work_date" value="<?= e(date('Y-m-d')) ?>">
            <button class="btn secondary" type="submit">Submit Daily Time</button>
          </form>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <a class="btn" href="/p/dashboard.php">Back</a>
    <?php endif; ?>
  </div>
</div>

<div class="section">
  <div class="section-title">Job summary</div>

  <style>
    .job-summary{
      display:grid;
      grid-template-columns: repeat(4, 1fr);
      gap:14px;
    }
    @media(max-width:900px){ .job-summary{ grid-template-columns: 1fr 1fr; } }
    @media(max-width:560px){ .job-summary{ grid-template-columns: 1fr; } }
  </style>

  <div class="job-summary">
    <div>
      <div class="scope-label">Employer</div>
      <div class="scope-value"><?= e($assignment['employer_firm'] ?: ($assignment['employer_name'] ?? 'Employer')) ?></div>
    </div>

    <div>
      <div class="scope-label">Deadline</div>
      <div class="scope-value" style="<?= !empty($assignment['deadline']) ? 'color:#ff6b6b;' : '' ?>">
        <?= e(!empty($assignment['deadline']) ? uk_date($assignment['deadline']) : '-') ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Urgency</div>
      <div class="scope-value">
        <?php if(!empty($assignment['urgency'])): ?>
          <span class="chip"><?= e($assignment['urgency']) ?></span>
        <?php else: ?>
          -
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Mode</div>
      <div class="scope-value"><?= e($assignment['mode'] ?? '-') ?></div>
    </div>

    <div>
      <div class="scope-label">Client ref</div>
      <div class="scope-value"><?= e($assignment['client_ref'] ?? '-') ?></div>
    </div>

    <div>
      <div class="scope-label">Specialism</div>
      <div class="scope-value">
        <?php
          $sp = $assignment['specialism'] ?? '';
          $ss = $assignment['sub_specialism'] ?? '';
          echo e($sp . ($ss ? ' / '.$ss : ''));
        ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Hours required</div>
      <div class="scope-value">
        <?php
          $hrs = $assignment['hours_required'] ?? null;
          $days = $assignment['days_required'] ?? null;
          $jt = $assignment['job_type'] ?? 'Hours';
          if ($jt === 'Days' && $days !== null && $days !== '') {
            echo e(number_format((float)$days, 2)).' days';
          } elseif ($hrs !== null && $hrs !== '') {
            echo e(number_format((float)$hrs, 2)).' hrs';
          } else {
            echo '-';
          }
        ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Offered rate</div>
      <div class="scope-value">
        <?php
          $offered = $assignment['rate_amount'] ?? $assignment['max_rate'] ?? null;
          echo ($offered !== null && $offered !== '') ? '£'.e(number_format((float)$offered, 2)).' / hour' : '-';
        ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Rate</div>
      <div class="scope-value">
        <?php
          $rate = $assignment['agreed_rate'] ?? $assignment['max_rate'] ?? null;
          echo ($rate !== null && $rate !== '') ? '£'.e(number_format((float)$rate, 2)).' / hour' : '-';
        ?>
      </div>
    </div>

    <div>
      <div class="scope-label">Job status</div>
      <div class="scope-value"><?= e($assignment['job_status'] ?? $assignment['status'] ?? '-') ?></div>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Document Handover</div>
  <div class="section-hint">Access case documents via the employer’s secure link. Confirm when you have access.</div>

  <?php
    $h = $handover ?? null;
    $method = $h['method'] ?? 'LINK';
    $shared_link = $h['shared_link'] ?? '';
    $instructions = $h['instructions'] ?? '';
    $access_confirmed = (int)($h['access_confirmed'] ?? 0);
    $access_issue_flag = (int)($h['access_issue_flag'] ?? 0);
  ?>

  <div style="margin:10px 0;">
    <?php if($access_confirmed === 1): ?>
      <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(46,204,113,.15);color:#9cffc5;">Access confirmed ✅</span>
    <?php elseif($access_issue_flag === 1): ?>
      <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(231,76,60,.15);color:#ffb4ad;">Issue reported ⚠️</span>
    <?php else: ?>
      <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);">Awaiting access confirmation</span>
    <?php endif; ?>
  </div>

  <div style="padding:10px 0;">
    <div><strong>Method:</strong> <?= e($method) ?></div>

    <?php if($method === 'LINK' && $shared_link): ?>
      <div style="margin-top:8px;">
        <strong>Link:</strong>
        <a href="<?= e($shared_link) ?>" target="_blank" rel="noopener"><?= e($shared_link) ?></a>
      </div>
    <?php elseif($method === 'FIRM_EMAIL'): ?>
      <div style="margin-top:8px;opacity:.8;">Employer indicated files were sent by the firm’s email.</div>
    <?php elseif($method === 'PERSONAL_EMAIL'): ?>
      <div style="margin-top:8px;opacity:.8;">Employer indicated files were sent by personal email.</div>
    <?php elseif($method === 'NOT_REQUIRED'): ?>
      <div style="margin-top:8px;opacity:.8;">Employer indicated no documents are required.</div>
    <?php else: ?>
      <div style="margin-top:8px;opacity:.8;">No link provided yet.</div>
    <?php endif; ?>

    <?php if($instructions): ?>
      <div style="margin-top:10px;">
        <strong>Instructions:</strong><br>
        <div style="opacity:.85;"><?= nl2br(e($instructions)) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
    <form method="post" action="/p/handover_confirm.php" style="margin:0;">
      <input type="hidden" name="assignment_id" value="<?= (int)($assignment['assignment_id'] ?? 0) ?>">
      <button class="btn" type="submit" <?= $access_confirmed===1 ? 'disabled' : '' ?>>Confirm access</button>
    </form>

    <form method="post" action="/p/handover_issue.php" style="margin:0; min-width: 260px;">
      <input type="hidden" name="assignment_id" value="<?= (int)($assignment['assignment_id'] ?? 0) ?>">
      <input type="text" name="note" placeholder="Report access issue (short note)" style="width:100%; margin-bottom:8px;">
      <button class="btn" type="submit">Report issue</button>
    </form>
  </div>
</div>

<div class="section" id="timesheets">
  <div class="section-title">Timesheets</div>
  <div class="section-hint">Enter total hours per day with a clear description. Draft/queried entries can be edited and resubmitted.</div>

  <?php if (!empty($queried_notes)): ?>
    <div style="margin:12px 0; padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:12px; background: rgba(255,255,255,.03);">
      <div style="font-weight:600; margin-bottom:6px;">Queried notes</div>
      <?php foreach ($queried_notes as $qn): ?>
        <div class="muted-line" style="display:flex; justify-content:space-between; gap:10px; padding:6px 0;">
          <div><?= e($qn['message']) ?></div>
          <div style="white-space:nowrap; opacity:.7;"><?= e(uk_datetime($qn['created_at'] ?? '')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
     <thead>
  <tr>
    <th style="width:120px;" class="ts-nowrap">Work date</th>
    <th style="width:110px;" class="ts-nowrap">Start–End</th>
    <th>Description</th>
    <th class="right ts-nowrap" style="width:85px;">Hours</th>
    <th class="ts-nowrap" style="width:120px;">Status</th>
    <th class="right ts-nowrap" style="width:220px;">Actions</th>
  </tr>
</thead>
      <tbody>
        <?php if (empty($timesheets)): ?>
          <tr><td colspan="6">No timesheets yet.</td></tr>
        <?php else: ?>
 
		  <?php foreach($timesheets as $t):
  $status = (string)($t['status'] ?? '');

  $openDispute   = !empty($t['open_dispute_id']);
  $closedDispute = !empty($t['closed_dispute_id']);

  $canEdit = in_array($status, ['Draft','Rejected'], true) && !$openDispute && !$closedDispute;

            // Start–End is stored on timesheets in the live app (Model A).
            $st = isset($t['start_time']) ? trim((string)$t['start_time']) : '';
            $et = isset($t['end_time']) ? trim((string)$t['end_time']) : '';
            $st = ($st !== '' && $st !== '0' && $st !== '00:00:00') ? substr($st, 0, 5) : '';
            $et = ($et !== '' && $et !== '0' && $et !== '00:00:00') ? substr($et, 0, 5) : '';
            $time_range = ($st !== '' && $et !== '') ? ($st.'–'.$et) : '-';

            // Description: strip any legacy [Work]/[Travel] prefix.
            $desc = (string)($t['description'] ?? '');
            $desc = preg_replace('/^\[(Work|Travel)\]\s*/i', '', $desc);
          ?>
            <tr data-timesheet-row="<?= (int)$t['timesheet_id'] ?>">
              <td><?= e(uk_date($t['work_date'] ?? '')) ?></td>
              <td><?= e($time_range) ?></td>
              <td><?= e($desc ?: '-') ?></td>
              <td class="right"><?= e(number_format((float)($t['hours_worked'] ?? 0), 2)) ?></td>
            
				<?php
$badgeStatus = $status;

if (!empty($t['closed_dispute_id'])) {
  $outcome = trim((string)($t['dispute_resolution'] ?? ''));
  if ($outcome !== '') {
    // Your admin resolve code sets 'Approved' or 'Rejected'
    $badgeStatus = ($outcome === 'Approved') ? 'Resolved (Approved)' : 'Resolved (Declined)';
  } else {
    $badgeStatus = 'Resolved';
  }
} elseif (!empty($t['open_dispute_id'])) {
  $badgeStatus = 'Disputed';
}
?>
<td><?= status_badge($badgeStatus) ?></td>
            
<td class="right ts-min">
  <div class="ts-actions">

	  <?php if ($canEdit): ?>
  <?php $editLabel = ($status === 'Rejected') ? 'Resubmit' : 'Edit'; ?>
  <a class="btn xs" href="/p/timesheet_submit.php?timesheet_id=<?= (int)$t['timesheet_id'] ?>">
    <?= e($editLabel) ?>
  </a>
<?php endif; ?>
	  

    <?php if ($closedDispute): ?>
      <a class="btn xs secondary"
         href="/p/timesheet_dispute_result.php?timesheet_id=<?= (int)$t['timesheet_id'] ?>">
        Results
      </a>

    <?php elseif ($openDispute): ?>
      <a class="btn xs secondary"
         href="/p/timesheet_dispute.php?timesheet_id=<?= (int)$t['timesheet_id'] ?>">
        View Dispute
      </a>

    <?php elseif ($status === 'Rejected'): ?>
      <a class="btn xs secondary"
         href="/p/timesheet_dispute.php?timesheet_id=<?= (int)$t['timesheet_id'] ?>">
        Raise Dispute
      </a>
    <?php endif; ?>

    <?php if (!$canEdit && !$openDispute && !$closedDispute && $status !== 'Rejected'): ?>
      <span style="opacity:.65;">—</span>
    <?php endif; ?>
  </div>
</td>
				
				
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="muted-line" style="margin-top:10px; padding-left:10px;">
    Tip: if a timesheet is queried, open Edit to see the employer’s reason and resubmit.
  </div>
</div>
