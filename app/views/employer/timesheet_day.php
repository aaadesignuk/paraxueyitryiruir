<div class="section">
  <div class="section-title">Timesheet Day Review</div>
<div class="section-hint" style="font-size:16px; line-height:1.5;">
  <div style="font-size:20px; font-weight:600; margin-bottom:6px;">
    <?= e($job['title'] ?? '') ?>
  </div>

  <div style="margin-bottom:4px;">
    <strong>Paralegal:</strong> <?= e($paralegal_name) ?>
  </div>

  <div>
    <strong>Date:</strong> <?= e(date('d/m/Y', strtotime((string)$work_date))) ?>
	  · <strong>Status:</strong><span class="badge" style="font-size:14px; padding:4px 8px;"><?= e($day_status) ?>
</span>
   · <strong>Total:</strong> <span style="font-size:17px;"><?= number_format((float)$total_hours, 2) ?></span> hours
  </div>
</div>

  <div class="table-responsive">
<style>
  /* Page-local tweaks */
   .btn-approve-daily{
    background:#1f8a3b;
    border-color:#1f8a3b;
  }
  .btn-approve-daily:hover{
    filter:brightness(1.05);
  }

  tr.query-box{
    display:none;
  }

  tr.query-box td{
    background:transparent;
    padding-top:0;
    border-top:0;
  }

  .query-panel{
    background:#f6f8fa;
    border:1px solid #d9e2ec;
    border-radius:10px;
    padding:14px 16px;
    margin:4px 0 10px 0;
  }

  .query-panel-label{
    display:block;
    margin:0 0 8px 0;
    font-size:14px;
    font-weight:600;
    color:#334e68;
  }

  .query-panel textarea{
    width:100%;
    min-height:110px;
    resize:vertical;
    box-sizing:border-box;
    border:1px solid #cbd5e1;
    border-radius:8px;
    padding:10px 12px;
    font:inherit;
    background:#fff;
  }

  .query-panel-actions{
    display:flex;
    gap:8px;
    justify-content:flex-end;
    margin-top:10px;
  }
</style>

    <table class="table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Work Type</th>
          <th>Description</th>
          <th style="text-align:right;">Hours</th>
          <th>Status</th>
          <th style="width:260px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
 
<tr>
  <td>
    <?php
      $st = $r['start_time'] ?? '';
      $et = $r['end_time'] ?? '';
      echo e(($st && $et) ? (substr($st,0,5).'–'.substr($et,0,5)) : '—');
    ?>
  </td>
  <td><?= e($r['work_type'] ?? 'Work') ?></td>
  <td><?= nl2br(e($r['description'] ?? '')) ?></td>
  <td style="text-align:right;"><?= number_format((float)$r['hours_worked'], 2) ?></td>
  <td>
    <?php
      $s = (string)($r['status'] ?? '');
      if ($s === 'Rejected') $s = 'Queried';
    ?>
    <span class="badge"><?= e($s) ?></span>
  </td>

  <td style="text-align:right;">
    <?php if (($r['status'] ?? '') === 'Submitted'): ?>
      <button class="btn btn-sm btn-warning" type="button" data-query-toggle>Query</button>
    <?php else: ?>
      <span class="muted">—</span>
    <?php endif; ?>
  </td>
</tr>

<?php if (($r['status'] ?? '') === 'Submitted'): ?>
  <tr class="query-box" data-query-box>
    <td colspan="5" style="padding-top:0;">
      <form method="post" action="/e/timesheet_entry_query.php" class="query-form" style="margin:0;">
        <input type="hidden" name="timesheet_id" value="<?= (int)$r['timesheet_id'] ?>">
        <input type="hidden" name="job_id" value="<?= (int)($job_id ?? 0) ?>">
        <input type="hidden" name="paralegal_id" value="<?= (int)($paralegal_id ?? 0) ?>">
        <input type="hidden" name="date" value="<?= e((string)$work_date) ?>">

        <div style="padding:12px 0 14px 0;">
          <label class="muted" style="display:block; margin:0 0 6px 0;">Query details</label>
          <textarea class="input" name="reason" placeholder="Describe what needs changing..."></textarea>
          <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:8px;">
            <button class="btn btn-sm btn-warning" type="submit">Submit Query</button>
            <button class="btn btn-sm" type="button" data-query-cancel>Cancel</button>
          </div>
        </div>
      </form>
    </td>
    <td></td>
  </tr>
<?php endif; ?>

<?php if (($r['status'] ?? '') === 'Submitted'): ?>
  <tr class="query-box" data-query-box>
    <td colspan="5" style="padding-top:0;">
      <form method="post" action="/e/timesheet_entry_query.php" class="query-form" style="margin:0;">
        <input type="hidden" name="timesheet_id" value="<?= (int)$r['timesheet_id'] ?>">
        <input type="hidden" name="job_id" value="<?= (int)($job_id ?? 0) ?>">
        <input type="hidden" name="paralegal_id" value="<?= (int)($paralegal_id ?? 0) ?>">
        <input type="hidden" name="date" value="<?= e((string)$work_date) ?>">

        <div style="padding:12px 0 14px 0;">
          <label class="muted" style="display:block; margin:0 0 6px 0;">Query details</label>
          <textarea class="input" name="reason" placeholder="Describe what needs changing..."></textarea>
          <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:8px;">
            <button class="btn btn-sm btn-warning" type="submit">Submit Query</button>
            <button class="btn btn-sm" type="button" data-query-cancel>Cancel</button>
          </div>
        </div>
      </form>
    </td>
    <td></td>
  </tr>
<?php endif; ?>		  
		  
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($disputes)): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-title">Queries / Disputes</div>
      <?php foreach ($disputes as $d): ?>
        <div class="flash flash--info" style="margin-bottom:10px;">
          <strong>Status:</strong> <?= e($d['status'] ?? '') ?>
          <?php if (!empty($d['created_at'])): ?>
            · <strong>Created:</strong> <?= e(date('d/m/Y H:i', strtotime((string)$d['created_at']))) ?>
          <?php endif; ?>
          <div style="margin-top:6px;">
            <?php
              $txt = $d['dispute_text'] ?? $d['message'] ?? $d['comment'] ?? $d['notes'] ?? '';
              echo nl2br(e((string)$txt));
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php if (!empty($has_submitted_rows)): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-title">Actions</div>

      <div class="flash flash--info" style="margin-bottom:12px;">
        <strong>Approval is per day.</strong> If you need changes, use <em>Query</em> next to the specific entry.
      </div>

      <form method="post" style="display:flex; gap:10px; align-items:center;">
        <button class="btn btn-sm btn-success btn-approve-daily" type="submit" name="action" value="approve">
          Approve Daily Timesheet
        </button>
        <a class="btn btn-sm" href="/e/timesheets.php">Back</a>
      </form>
    </div>
  <?php else: ?>
    <div style="margin-top:12px;">
      <a class="btn btn-sm" href="/e/timesheets.php">Back</a>
    </div>
  <?php endif; ?>
</div>

<script>
  (function(){
    function closest(el, sel){
      while(el && el !== document){
        if (el.matches && el.matches(sel)) return el;
        el = el.parentNode;
      }
      return null;
    }

    document.addEventListener('click', function(e){
      var t = e.target;

      if (t && t.hasAttribute('data-query-toggle')){
        var row = closest(t, 'tr');
        if (!row) return;
        var next = row.nextElementSibling;
        if (next && next.hasAttribute('data-query-box')){
          next.style.display = 'table-row';
          var ta = next.querySelector('textarea');
          if (ta) setTimeout(function(){ ta.focus(); }, 0);
        }
        t.style.display = 'none';
      }

      if (t && t.hasAttribute('data-query-cancel')){
        var form = closest(t, 'form');
        if (!form) return;
        var queryRow = closest(form, 'tr');
        if (!queryRow) return;

        var ta2 = form.querySelector('textarea');
        if (ta2) ta2.value = '';

        queryRow.style.display = 'none';

        var mainRow = queryRow.previousElementSibling;
        if (mainRow){
          var openBtn = mainRow.querySelector('[data-query-toggle]');
          if (openBtn) openBtn.style.display = 'inline-flex';
        }
      }
    });
  })();
</script>