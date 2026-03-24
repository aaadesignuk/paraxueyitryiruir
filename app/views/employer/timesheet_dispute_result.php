<div class="section">
  <div class="section-title">Dispute Results</div>
  <div class="section-hint"><strong><?= e($row['job_title'] ?? '') ?></strong><br>Timesheet #<?= (int)$row['timesheet_id'] ?> · Paralegal: <?= e($row['paralegal_name'] ?? '-') ?> · Date: <?= e(($row['work_date'] ?? null) ? uk_date($row['work_date']) : '-') ?></div>

  <div class="flash flash--info"><strong>Original submission</strong><br><?= nl2br(e($row['description'] ?? '')) ?><br><div style="margin-top:6px; opacity:.8;">Submitted hours: <?= number_format((float)($row['hours_worked'] ?? 0), 2) ?></div></div>
  <?php if (!empty($row['employer_query_reason'])): ?><div class="flash flash--info"><strong>Your query</strong><br><?= nl2br(e($row['employer_query_reason'])) ?></div><?php endif; ?>
  <?php if (!empty($row['para_response'])): ?><div class="flash flash--info"><strong>Paralegal response</strong><br><?= nl2br(e($row['para_response'])) ?></div><?php endif; ?>
  <?php if (!empty($row['employer_reply'])): ?><div class="flash flash--info"><strong>Your reply</strong><br><?= nl2br(e($row['employer_reply'])) ?></div><?php endif; ?>
  <?php if (!empty($row['dispute_text'])): ?><div class="flash flash--info"><strong>Paralegal appeal to admin</strong><br><?= nl2br(e($row['dispute_text'])) ?></div><?php endif; ?>

  <div class="flash flash--success">
    <strong>Admin decision: <?= e($row['resolution'] ?? 'Closed') ?></strong><br>
    Closed: <?= !empty($row['closed_at']) ? e(date('d/m/Y H:i', strtotime((string)$row['closed_at']))) : '—' ?><br>
    Payable hours: <strong><?= ($row['payable_hours'] !== null && $row['payable_hours'] !== '') ? e(number_format((float)$row['payable_hours'], 2)) : e(number_format((float)($row['hours_worked'] ?? 0), 2)) ?></strong><br><br>
    <?= !empty($row['admin_notes']) ? nl2br(e($row['admin_notes'])) : '<span style="opacity:.8;">No admin notes provided.</span>' ?>
  </div>

  <div style="margin-top:16px;"><a class="btn small secondary" style="width:auto; text-decoration:none;" href="/e/job_view.php?job_id=<?= (int)$row['job_id'] ?>#timesheets">Back to timesheets</a></div>
</div>
