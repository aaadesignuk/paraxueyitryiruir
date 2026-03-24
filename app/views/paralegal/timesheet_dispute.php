<div class="section">
  <div class="section-title">Queries / Disputes</div>
  <div class="section-hint">
    <strong><?= e($ts['job_title'] ?? '') ?></strong>
    — Employer: <?= e($ts['employer_name'] ?? '') ?><br>
    Timesheet #<?= (int)$timesheet_id ?> · Date: <?= e(uk_date($ts['work_date'] ?? '')) ?> · Hours: <?= e(number_format((float)($ts['hours_worked'] ?? 0), 2)) ?>
  </div>

  <style>
    .qd-grid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap:14px;
      margin-top:14px;
    }
    @media (max-width: 900px){
      .qd-grid{ grid-template-columns:1fr; }
    }
    .qd-card{
      border:1px solid rgba(255,255,255,.08);
      border-radius:14px;
      padding:14px;
      background:rgba(255,255,255,.03);
    }
    .qd-card h3{
      margin:0 0 10px 0;
      font-size:16px;
    }
    .qd-muted{
      opacity:.8;
      font-size:13px;
      margin-top:6px;
    }
    .qd-empty{
      opacity:.7;
    }
  </style>

  <div class="qd-grid">
    <div class="qd-card">
      <h3>Employer / The Query</h3>
      <?php if (!empty($query_note)): ?>
        <div><?= nl2br(e($query_note)) ?></div>
      <?php else: ?>
        <div class="qd-empty">No employer query note recorded.</div>
      <?php endif; ?>
    </div>

    <div class="qd-card">
      <h3>Paralegal Response</h3>

      <?php if (!empty($open_dispute)): ?>
        <div><?= nl2br(e($open_dispute['dispute_text'] ?? '')) ?></div>
        <div class="qd-muted">
          Submitted: <?= e(uk_datetime($open_dispute['created_at'] ?? '')) ?>
        </div>
      <?php else: ?>
        <form method="post" action="">
          <label>Your appeal to admin</label>
          <textarea name="dispute_text" required placeholder="Explain why you disagree with the query (min 10 characters)."></textarea>

          <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn small" type="submit" style="width:auto;">Send dispute</button>

            <a class="btn small secondary" style="width:auto; text-decoration:none;"
               href="/p/assignment.php?id=<?= (int)$assignment_id ?>&timesheet_id=<?= (int)$timesheet_id ?>#timesheets">
              Back
            </a>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <div class="qd-card">
      <h3>Admin</h3>

      <?php if (!empty($open_dispute)): ?>
        <div><strong>Status:</strong> Admin review pending</div>
        <div class="qd-muted">
          Once resolved, the decision will show under <strong>Results</strong> on the assignment timesheets list.
        </div>
      <?php else: ?>
        <div class="qd-empty">No admin dispute open yet.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($open_dispute)): ?>
    <div style="margin-top:16px;">
      <a class="btn small secondary" style="width:auto; text-decoration:none;"
         href="/p/assignment.php?id=<?= (int)$assignment_id ?>&timesheet_id=<?= (int)$timesheet_id ?>#timesheets">
        Back
      </a>
    </div>
  <?php endif; ?>
</div>