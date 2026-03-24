<?php
/* VIEW ONLY */
?>

<div class="section">
  <div class="section-title">Time Entry</div>

  <div class="section-hint">
    <strong><?= e($assn['job_title'] ?? '') ?></strong>
    <?php if (!empty($assn['employer_name'])): ?>
      — Employer: <?= e($assn['employer_name']) ?>
    <?php endif; ?>
    <br>
    Status: <strong><?= e($display_status ?? '') ?></strong>
    <?php if (!empty($timesheet_id)): ?>
      &nbsp;·&nbsp; Entry #<?= (int)$timesheet_id ?>
    <?php endif; ?>
    <br>
    <?php if (!empty($is_new_entry)): ?>
      <span class="muted">You can add entries for <strong>today</strong> or <strong>yesterday</strong> only.</span>
    <?php elseif (!empty($status) && $status === 'Rejected'): ?>
      <span class="muted">The original submitted entry is locked. Respond below or raise a dispute if needed.</span>
    <?php else: ?>
      <span class="muted">You are editing an existing draft entry.</span>
    <?php endif; ?>
  </div>

  <?php if (!empty($query_note)): ?>
    <div class="flash flash--info">
      <strong>Query from employer</strong><br>
      <?= nl2br(e($query_note)) ?>
    </div>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const st = document.querySelector('input[name="start_time"]');
      const et = document.querySelector('input[name="end_time"]');
      const hw = document.getElementById('hours_worked');
      function toMinutes(t){ if (!t) return null; const parts = t.split(':'); if (parts.length < 2) return null; const h = parseInt(parts[0], 10); const m = parseInt(parts[1], 10); if (isNaN(h) || isNaN(m)) return null; return (h * 60) + m; }
      function calc(){ if (!st || !et || !hw) return; const a = toMinutes(st.value); const b = toMinutes(et.value); if (a === null || b === null) { hw.value = ''; return; } let diff = b - a; if (diff <= 0) diff += (24 * 60); hw.value = (diff / 60).toFixed(2); }
      if (st) st.addEventListener('change', calc);
      if (et) et.addEventListener('change', calc);
      calc();
    });
  </script>

  <form method="post" action="">
    <input type="hidden" name="action" value="save_entry">

    <div class="grid-2">
      <div>
        <label>Work date</label>
        <div class="field">
          <input type="date" name="work_date" value="<?= e($work_date_value ?? '') ?>" <?php if (!empty($is_new_entry)): ?>min="<?= e($yesterday ?? '') ?>" max="<?= e($today ?? '') ?>"<?php endif; ?> <?= !empty($can_edit) ? '' : 'disabled' ?> required>
        </div>
      </div>

      <div>
        <label>Type</label>
        <div class="field">
          <select name="work_type" <?= !empty($can_edit) ? '' : 'disabled' ?>>
            <option value="Work" <?= (($work_type_value ?? '') === 'Work') ? 'selected' : '' ?>>Work</option>
            <option value="Travel" <?= (($work_type_value ?? '') === 'Travel') ? 'selected' : '' ?>>Travel</option>
          </select>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div>
        <label>Start time</label>
        <div class="field"><input type="time" name="start_time" value="<?= e($start_value ?? '') ?>" <?= !empty($can_edit) ? '' : 'disabled' ?> required></div>
      </div>
      <div>
        <label>End time</label>
        <div class="field"><input type="time" name="end_time" value="<?= e($end_value ?? '') ?>" <?= !empty($can_edit) ? '' : 'disabled' ?> required></div>
      </div>
    </div>

    <div class="grid-2">
      <div>
        <label>Hours worked</label>
        <div class="field">
          <input id="hours_worked" type="number" step="0.01" min="0" name="hours_worked" value="<?= e($hours_value ?? '') ?>" readonly <?= !empty($can_edit) ? '' : 'disabled' ?> style="font-size:22px; font-weight:700; background:#f0fdf4; border:2px solid #16a34a; color:#166534;">
        </div>
        <div class="section-hint" style="margin-top:10px;">Hours are calculated automatically from start and end time.</div>
      </div>
      <div>
        <label>&nbsp;</label>
        <div class="section-hint" style="margin-top:10px;">If the work crossed midnight, start/end time still works.</div>
      </div>
    </div>

    <label>Description</label>
    <textarea name="description" placeholder="What did you do?" <?= !empty($can_edit) ? '' : 'disabled' ?> required><?= e($desc_value ?? '') ?></textarea>

    <?php if (!empty($can_edit)): ?>
      <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn" type="submit">Save Time Entry</button>
        <?php if (empty($timesheet_id)): ?>
          <button class="btn secondary" type="submit" name="action" value="submit_day" formaction="">Submit Daily Timesheet</button>
        <?php else: ?>
          <a class="btn secondary" href="/p/timesheet_submit.php?assignment_id=<?= (int)$assignment_id ?>&date=<?= e($work_date_value ?? '') ?>">Back to day</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="section-hint" style="margin-top:16px;">This entry is read-only because it has already been submitted.</div>
    <?php endif; ?>
  </form>

  <?php if (empty($timesheet_id)): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-title">Draft entries for <?= e(date('d/m/Y', strtotime((string)($work_date_value ?? '')))) ?></div>
      <?php if (empty($draft_entries)): ?>
        <div class="section-hint">No draft entries yet for this day.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table"><thead><tr><th style="width:140px;">Time</th><th>Description</th><th style="width:90px; text-align:right;">Hours</th><th style="width:190px; text-align:right;"></th></tr></thead><tbody>
          <?php foreach ($draft_entries as $r): ?><tr><td><?php $st = $r['start_time'] ?? ''; $et = $r['end_time'] ?? ''; echo e(($st && $et) ? (substr((string)$st,0,5).'–'.substr((string)$et,0,5)) : '—'); ?></td><td><?= nl2br(e(clean_desc_prefix((string)($r['description'] ?? '')))) ?></td><td style="text-align:right;"><?= number_format((float)($r['hours_worked'] ?? 0), 2) ?></td><td style="text-align:right; display:flex; gap:8px; justify-content:flex-end;"><a class="btn btn-sm" href="/p/timesheet_submit.php?timesheet_id=<?= (int)$r['timesheet_id'] ?>">Edit</a><form method="post" action="" onsubmit="return confirm('Delete this draft entry?');" style="display:inline;"><input type="hidden" name="action" value="delete_entry"><input type="hidden" name="timesheet_id" value="<?= (int)$r['timesheet_id'] ?>"><input type="hidden" name="work_date" value="<?= e($work_date_value ?? '') ?>"><button class="btn btn-sm secondary" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <form method="post" action="" style="margin-top:12px;"><input type="hidden" name="action" value="submit_day"><input type="hidden" name="work_date" value="<?= e($work_date_value ?? '') ?>"><button class="btn" type="submit">Submit Daily Timesheet</button></form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php $is_queried = (!empty($timesheet_id) && (($status ?? '') === 'Rejected')); ?>
<?php if ($is_queried): ?>
  <div class="section" id="dispute">
    <div class="section-title">Query / Dispute</div>
    <div class="section-hint">The original entry stays locked. Use the response and dispute boxes below.</div>
    <?php if (!empty($query_thread)): ?>
      <div class="flash flash--info" id="query"><strong>Employer query</strong><br><?= nl2br(e($query_thread['reason'] ?? '')) ?>
        <?php if (!empty($query_thread['para_response'])): ?><hr><strong>Your response</strong><br><?= nl2br(e($query_thread['para_response'])) ?><?php endif; ?>
        <?php if (!empty($query_thread['employer_reply'])): ?><hr><strong>Employer reply</strong><br><?= nl2br(e($query_thread['employer_reply'])) ?><?php endif; ?>
      </div>
      <?php if (empty($query_thread['para_response'])): ?>
        <form method="post" action="" style="margin-top:12px;"><input type="hidden" name="action" value="respond_query"><label>Your response</label><textarea name="para_response" required placeholder="Reply to the employer query"></textarea><div style="margin-top:12px;"><button class="btn" type="submit">Send response</button></div></form>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($open_dispute)): ?>
      <div class="flash flash--info" style="margin-top:12px;"><strong>Open dispute</strong><br><?= nl2br(e($open_dispute['dispute_text'] ?? '')) ?></div>
    <?php elseif (!empty($query_thread['para_response']) && !empty($query_thread['employer_reply'])): ?>
      <form method="post" action="" style="margin-top:12px;"><input type="hidden" name="action" value="raise_dispute"><label>Your appeal to admin</label><textarea name="dispute_text" required placeholder="Explain why this should be escalated to admin"></textarea><div style="margin-top:12px;"><button class="btn" type="submit">Raise dispute</button></div></form>
    <?php endif; ?>
  </div>
<?php endif; ?>
