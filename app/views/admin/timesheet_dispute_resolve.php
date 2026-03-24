<div class="section">
  <div class="section-title">Resolve Dispute #<?= (int)$d['dispute_id'] ?></div>
  <div class="section-hint">
    <strong><?= e($d['job_title'] ?? '') ?></strong><br>
    Employer: <?= e($d['employer_name'] ?? '-') ?> · Paralegal: <?= e($d['paralegal_name'] ?? '-') ?><br>
    Timesheet #<?= (int)$d['timesheet_id'] ?> · Date: <?= e(uk_date($d['work_date'] ?? '')) ?> · Original hours: <?= e(number_format((float)($d['hours_worked'] ?? 0), 2)) ?>
  </div>

  <div class="section" style="margin-top:14px;">
    <div class="section-title" style="font-size:16px;">Timeline</div>
    <div class="flash flash--info">
      <strong>Original submission</strong><br>
      <?= nl2br(e($d['description'] ?? '')) ?><br>
      <div style="margin-top:6px; opacity:.8;">Hours submitted: <?= number_format((float)($d['hours_worked'] ?? 0), 2) ?></div>
    </div>
    <?php if (!empty($query['reason'])): ?>
      <div class="flash flash--info"><strong>Employer query</strong><br><?= nl2br(e($query['reason'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($query['para_response'])): ?>
      <div class="flash flash--info"><strong>Paralegal response</strong><br><?= nl2br(e($query['para_response'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($query['employer_reply'])): ?>
      <div class="flash flash--info"><strong>Employer reply</strong><br><?= nl2br(e($query['employer_reply'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($d['dispute_text'])): ?>
      <div class="flash flash--info"><strong>Paralegal appeal note</strong><br><?= nl2br(e($d['dispute_text'])) ?></div>
    <?php endif; ?>
  </div>

  <form method="post" action="" style="margin-top:18px;">
    <?php if (!empty($has_payable_hours)): ?>
      <label>Payable hours (used for partial approval)</label>
      <input type="number" name="payable_hours" step="0.01" min="0" max="<?= e(number_format((float)($d['hours_worked'] ?? 0), 2, '.', '')) ?>" value="<?= e(number_format((float)($d['hours_worked'] ?? 0), 2, '.', '')) ?>">
      <div class="section-hint" style="margin-top:6px;">Only change this when approving partial hours.</div>
    <?php endif; ?>

    <label style="margin-top:12px;">Reasons / final note</label>
    <textarea name="admin_notes" placeholder="Set out the final decision and reasons"><?= e($d['resolved_note'] ?? '') ?></textarea>

    <div style="margin-top:16px; display:flex; gap:12px; flex-wrap:wrap;">
      <?php if (($d['status'] ?? '') !== 'Resolved'): ?>
        <button class="btn small" type="submit" name="action" value="approve_full" style="width:auto;">Approve in full</button>
        <button class="btn small" type="submit" name="action" value="approve_partial" style="width:auto;">Approve partial hours</button>
        <button class="btn small secondary" type="submit" name="action" value="reject_timesheet" style="width:auto;">Reject / pay 0</button>
      <?php endif; ?>
      <a class="btn small secondary" href="/a/timesheet_disputes.php" style="width:auto; text-decoration:none;">Back</a>
    </div>
  </form>
</div>
