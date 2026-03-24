<div class="section">
  <div class="section-title">Open Jobs</div>
  <div class="section-hint">Jobs currently accepting paralegal matches.</div>

  <table class="table">
    <tr>
      <th>Job</th>
      <th>Employer</th>
      <th>Specialism</th>
      <th>Type</th>
      <th>Hours</th>
      <th>Max rate</th>
      <th>Deadline</th>
      <th>Created</th>
    </tr>

    <?php if(!$rows): ?>
      <tr><td colspan="8">No open jobs.</td></tr>
    <?php endif; ?>

    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['title']) ?></td>
        <td><?= e($r['employer_name']) ?></td>
        <td><?= e($r['specialism'] ?? '') ?><?= $r['sub_specialism'] ? ' / '.e($r['sub_specialism']) : '' ?></td>
        <td><?= e($r['job_type']) ?></td>
        <td><?= e($r['hours_required'] ?? '') ?></td>
        <td><?= $r['max_rate'] !== null ? '£'.e(number_format((float)$r['max_rate'],2)) : '' ?></td>
        <td><?= e($r['deadline'] ?? '') ?></td>
        <td><?= e($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>