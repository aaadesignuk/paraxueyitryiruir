<div class="section">
  <div class="section-title">Employers</div>
  <div class="section-hint">All active and inactive employers.</div>

  <table class="table">
    <tr>
      <th>Name</th>
      <th>Firm</th>
      <th>Email</th>
      <th>Area of law</th>
      <th>Status</th>
      <th>Created</th>
    </tr>

    <?php if(!$rows): ?>
      <tr><td colspan="6">No employers found.</td></tr>
    <?php endif; ?>

    <?php foreach($rows as $r): ?>
      <?php $st = ($r['status'] ?? 'approved'); ?>
      <tr>
<td>
  <a href="/a/employer_profile.php?id=<?= (int)$r['user_id'] ?>">
    <?= e($r['full_name']) ?>
  </a>
</td>
        <td><?= e($r['firm_name'] ?? '') ?></td>
        <td><?= e($r['email']) ?></td>
        <td><?= e($r['area_of_law'] ?? '') ?></td>

        <td>
          <?php if($st === 'pending'): ?>
            Pending
            <a class="btn" style="margin-left:8px" href="/a/employer_approve.php?id=<?= (int)$r['user_id'] ?>">Approve</a>
          <?php elseif($st === 'rejected'): ?>
            Rejected
          <?php else: ?>
            Approved
          <?php endif; ?>

          <div style="opacity:.7;font-size:12px;margin-top:3px;">
            Account: <?= !empty($r['is_active']) ? 'Active' : 'Inactive' ?>
          </div>
        </td>

        <td><?= e(uk_datetime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
