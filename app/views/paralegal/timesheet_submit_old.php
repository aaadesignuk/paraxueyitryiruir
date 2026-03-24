<?php
// expects:
// $assn, $assignment_id, $timesheet_id, $display_status, $query_note, $can_edit
// $work_date_value, $start_value, $end_value, $hours_value, $desc_value, $work_type_value
?>

<style>
.ts-grid-2{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:14px;
}
@media(max-width:900px){ .ts-grid-2{ grid-template-columns:1fr; } }
.ts-note{
  margin-top:12px;
  padding:12px;
  border:1px solid rgba(255,255,255,.10);
  border-radius:12px;
  background:rgba(231,76,60,.08);
}
.ts-note strong{ color:#ffb4ad; }
</style>

<div class="section">
  <div class="section-title"><?= $display_status === 'Queried' ? 'Edit timesheet' : 'Add timesheet' ?></div>

  <div class="muted-line" style="margin-top:6px;">
    Job: <strong><?= e($assn['job_title'] ?? 'Job') ?></strong>
    • Employer: <?= e($assn['employer_name'] ?? '-') ?>
  </div>

  <div style="margin-top:10px;">
    <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.08);">
      Status: <strong><?= e($display_status) ?></strong>
    </span>
  </div>

  <?php if($display_status === 'Queried' && $query_note): ?>
    <div class="ts-note">
      <div><strong>Employer query (required fix):</strong></div>
      <div style="margin-top:6px;opacity:.9;"><?= nl2br(e($query_note)) ?></div>
      <div class="muted-line" style="margin-top:6px;">Update and click <strong>Resubmit</strong>.</div>
    </div>
  <?php endif; ?>
</div>

<div class="section">
  <?php if(!$can_edit): ?>
    <div class="muted-line">This timesheet can’t be edited in its current status.</div>
    <div style="margin-top:14px;">
      <a class="btn btn-light" href="/p/assignment.php?id=<?= (int)$assignment_id ?>#timesheets">Back to job</a>
    </div>
  <?php else: ?>

    <form method="post" action="">
      <input type="hidden" name="timesheet_id" value="<?= (int)$timesheet_id ?>">

      <div class="ts-grid-2">
        <div>
          <label>Work date</label>
          <div class="field">
            <input type="date" name="work_date" value="<?= e($work_date_value) ?>">
          </div>
        </div>

        <div>
          <label>Type</label>
          <div class="field">
            <select name="work_type">
              <option value="Work" <?= ($work_type_value==='Work'?'selected':'') ?>>Work</option>
              <option value="Travel" <?= ($work_type_value==='Travel'?'selected':'') ?>>Travel (hours)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="ts-grid-2" style="margin-top:12px;">
        <div>
          <label>Start time</label>
          <div class="field">
            <input type="time" name="start_time" value="<?= e($start_value) ?>" onchange="tsCalcHours()">
          </div>
        </div>

        <div>
          <label>End time</label>
          <div class="field">
            <input type="time" name="end_time" value="<?= e($end_value) ?>" onchange="tsCalcHours()">
          </div>
        </div>
      </div>

      <label style="margin-top:12px;">Total hours</label>
      <div class="field">
        <input type="number" step="0.25" min="0" name="hours_worked" id="hours_worked" value="<?= e($hours_value) ?>">
        <div class="muted-line" style="margin-top:6px;">Auto-calculated from start/end when provided. You can override if needed.</div>
      </div>

      <label style="margin-top:12px;">Description</label>
      <div class="field">
        <textarea name="description" rows="5" placeholder="What work was completed?"><?= e($desc_value) ?></textarea>
      </div>

      <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <?php if($display_status === 'Queried'): ?>
          <button class="btn" type="submit" name="action" value="resubmit">Resubmit</button>
        <?php else: ?>
          <button class="btn" type="submit" name="action" value="submit">Submit</button>
        <?php endif; ?>
<a class="btn btn-light" href="/p/assignment.php?id=<?= (int)$assignment_id ?>#timesheets">Back to job</a>
      </div>
    </form>

    <script>
      function tsCalcHours(){
        const s = document.querySelector('input[name="start_time"]').value;
        const e = document.querySelector('input[name="end_time"]').value;
        if(!s || !e) return;

        const toMin = (t)=>{
          const [hh,mm] = t.split(':').map(Number);
          return (hh*60)+(mm||0);
        };

        let sm = toMin(s);
        let em = toMin(e);
        if (isNaN(sm) || isNaN(em)) return;

        // allow crossing midnight (end <= start => next day)
        if (em <= sm) em += 1440;

        const mins = em - sm;
        const hrs = Math.round((mins/60)*100)/100; // 2dp
        const el = document.getElementById('hours_worked');

        // Only auto-fill if user hasn't typed something wildly different
        if (!el.value || el.value === "0" || el.value === "0.0" || el.value === "0.00") {
          el.value = hrs.toFixed(2);
        }
      }

      // On load, try to fill if empty
      tsCalcHours();
    </script>

  <?php endif; ?>
</div>
