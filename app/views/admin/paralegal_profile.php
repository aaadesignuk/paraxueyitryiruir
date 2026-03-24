<div class="section">
  <div class="section-title">
    <h1><?= e($paralegal['full_name']) ?></h1>
    <p style="opacity:.8;margin:4px 0 0;"><?= e($paralegal['email']) ?></p>
  </div>

  <div class="row" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
    <a class="btn" href="/a/paralegals.php">&larr; Back to paralegals</a>
    <?php $st = ($paralegal['status'] ?? 'approved'); ?>
    <?php if($st === 'pending'): ?>
      <a class="btn" href="/a/paralegal_approve.php?id=<?= (int)$paralegal['user_id'] ?>&return=profile">Approve</a>
    <?php endif; ?>
    <div style="margin-left:auto; opacity:.8;">Status: <strong><?= e(ucfirst($st)) ?></strong></div>
  </div>

  <div class="card" style="padding:14px; margin-bottom:16px;">
    <div class="row" style="display:flex; gap:18px; row-gap:14px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div style="opacity:.7;font-size:12px;">Specialism</div>
        <div><?= e($paralegal['specialism'] ?? '') ?></div>
      </div>
      <div>
        <div style="opacity:.7;font-size:12px;">Experience</div>
        <div>
         <?php if(($paralegal['experience_type'] ?? 'None') === 'None'): ?>
  None
<?php else: ?>
  <?= e((string)(int)($paralegal['experience_value'] ?? 0)) ?> <?= e($paralegal['experience_type'] ?? '') ?>
<?php endif; ?>
        </div>
      </div>
      <div>
        <div style="opacity:.7;font-size:12px;">Preferred rate</div>
        <div><?= $paralegal['preferred_rate'] !== null ? '£'.e(number_format((float)$paralegal['preferred_rate'],2)) : '' ?></div>
      </div>
      <div>
        <div style="opacity:.7;font-size:12px;">Location preference</div>
        <div><?= e($paralegal['location_preference'] ?? '') ?></div>
      </div>
      <div>
        <div style="opacity:.7;font-size:12px;">Availability</div>
        <div><?= !empty($paralegal['is_available']) ? 'Available' : 'Unavailable' ?></div>
      </div>
      <div>
        <div style="opacity:.7;font-size:12px;">Created</div>
        <div><?= e(uk_datetime($paralegal['created_at'])) ?></div>
      </div>
    </div>
  </div>

  <div class="row" style="display:flex; gap:14px; align-items:stretch; flex-wrap:wrap; margin-top:6px;">
    <div class="card" style="padding:14px; flex:1; min-width:280px; margin-bottom:14px;">
      <h3 style="margin:0 0 10px;">Task Categories & Experience</h3>
      <?php if(!$category_experience): ?>
        <div style="opacity:.7;">No category experience captured.</div>
      <?php else: ?>
        <table class="table" style="border-collapse:separate; border-spacing:0 8px;">
          <tr>
            <th>Category</th>
            <th>Experience</th>
            <th>Updated</th>
          </tr>
          <?php foreach($category_experience as $c): ?>
            <tr>
              <td><?= e($c['category_name']) ?></td>
          <td><?= e((string)(int)($c['experience_value'] ?? 0)) ?> <?= e($c['experience_type'] ?? '') ?></td>
              <td><?= e(uk_datetime($c['updated_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="card" style="padding:14px; flex:1; min-width:280px; margin-bottom:14px;">
      <h3 style="margin:0 0 10px;">Skills</h3>
      <?php if(!$skills && empty($paralegal['skills_free_text'])): ?>
        <div style="opacity:.7;">No skills captured.</div>
      <?php else: ?>
        <?php if($skills): ?>
          <ul style="margin:0 0 10px; padding-left:18px;">
            <?php foreach($skills as $s): ?>
              <li><?= e($s['skill_name']) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if(!empty($paralegal['skills_free_text'])): ?>
          <div style="opacity:.7;font-size:12px; margin-bottom:4px;">Free text skills</div>
          <div><?= e($paralegal['skills_free_text']) ?></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
