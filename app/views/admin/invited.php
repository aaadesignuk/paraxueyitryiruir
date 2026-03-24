<div class="section">
  <div class="section-title">Pending Invites</div>
  <div class="section-hint">Invitations sent to paralegals that have not yet been accepted or declined.</div>

  <table class="table">
    <tr>
      <th>Job</th>
      <th>Employer</th>
      <th>Paralegal</th>
      <th>Status</th>
      <th>Created</th>
    </tr>

    <?php if(!$rows): ?>
      <tr><td colspan="5">No pending invites.</td></tr>
    <?php endif; ?>

    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= e($r['job_title']) ?></td>
        <td><?= e($r['employer_name']) ?></td>
        <td><?= e($r['paralegal_name']) ?></td>
        <td><?= e($r['status']) ?></td>
        <td><?= e($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>