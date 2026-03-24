<div class="section">
  <div class="section-title">Submitted Timesheets</div>
  <div class="section-hint">Timesheets awaiting employer review.</div>

  <table class="table">
    <tr>
      <th>Date</th>
      <th>Employer</th>
      <th>Paralegal</th>
      <th>Job</th>
      <th>Hours</th>
      <th>Description</th>
      <th>Status</th>
    </tr>

    <?php if(!$rows): ?>
      <tr><td colspan="7">No submitted timesheets.</td></tr>
    <?php endif; ?>

    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e(uk_date($r['work_date'] ?? '')) ?></td>
        <td><?= e($r['employer_name']) ?></td>
        <td><?= e($r['paralegal_name']) ?></td>
        <td><?= e($r['job_title']) ?></td>
        <td><?= e($r['hours_worked']) ?></td>
        <td><?= e($r['description'] ?? '') ?></td>
        <?php
          $st = (string)($r['status'] ?? '');
          $badgeStyle = 'opacity:.85;';
          if ($st === 'Approved') $badgeStyle .= 'color:#16a34a;font-weight:800;';
          if ($st === 'Rejected' || $st === 'Queried') $badgeStyle .= 'color:#f59e0b;font-weight:800;';
        ?>
        <td><span style="<?= e($badgeStyle) ?>"><?= e($st) ?></span></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
