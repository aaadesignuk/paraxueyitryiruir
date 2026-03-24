<div class="section">
  <div class="section-title">Paralegals</div>
  <div class="section-hint">All registered paralegals.</div>

  <table class="table">
<tr>
  <th>Name</th>
  <th>Email</th>
  <th>Specialism</th>
  <th>Experience</th>
  <th>Preferred rate</th>
  <th>Status</th>
  <th>Created</th>
</tr>

    <?php if(!$rows): ?>
      <tr><td colspan="7">No paralegals found.</td></tr>
    <?php endif; ?>

    <?php foreach($rows as $r): ?>
      <?php $st = ($r['status'] ?? 'approved'); ?>
      <tr>
        <td><a href="/a/paralegal_profile.php?id=<?= (int)$r['user_id'] ?>"><?= e($r['full_name']) ?></a></td>
        <td><?= e($r['email']) ?></td>
        <td><?= e($r['specialism'] ?? '') ?></td>
        <td>
          <?php if(($r['experience_type'] ?? 'None') === 'None'): ?>None<?php else: ?>
       <?= e((string)(int)($r['experience_value'] ?? 0)) ?> <?= e($r['experience_type'] ?? '') ?>
          <?php endif; ?>
        </td>
        <td><?= $r['preferred_rate'] !== null ? '£'.e(number_format((float)$r['preferred_rate'],2)) : '' ?></td>
               <td>
          <?php if($st === 'pending'): ?>
            Pending
            <a class="btn" style="margin-left:8px" href="/a/paralegal_approve.php?id=<?= (int)$r['user_id'] ?>">Approve</a>
          <?php elseif($st === 'rejected'): ?>
            Rejected
          <?php else: ?>
            Approved
          <?php endif; ?>
          <div style="opacity:.7;font-size:12px;margin-top:3px;">Account: <?= !empty($r['is_active']) ? 'Active' : 'Inactive' ?></div>
        </td>
        <td><?= e(uk_datetime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if(!empty($pg) && ($pg['total_pages'] ?? 1) > 1): ?>
    <div class="row" style="gap:10px; align-items:center; margin-top:12px;">
      <?php if($pg['has_prev']): ?>
        <a class="btn" href="/a/paralegals.php?page=<?= (int)($pg['page']-1) ?>">&larr; Prev</a>
      <?php else: ?>
        <span class="btn" style="opacity:.5; pointer-events:none;">&larr; Prev</span>
      <?php endif; ?>

      <div style="opacity:.8;">Page <strong><?= (int)$pg['page'] ?></strong> of <strong><?= (int)$pg['total_pages'] ?></strong> (<?= (int)$pg['total'] ?> total)</div>

      <?php if($pg['has_next']): ?>
        <a class="btn" href="/a/paralegals.php?page=<?= (int)($pg['page']+1) ?>">Next &rarr;</a>
      <?php else: ?>
        <span class="btn" style="opacity:.5; pointer-events:none;">Next &rarr;</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
