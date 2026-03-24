<div class="section">
  <div class="section-title">Employer profile</div>
  <div class="section-hint"><?= e($profile_user['full_name']) ?></div>

  <div class="card" style="margin-top:12px;">
    <div><strong>Email:</strong> <?= e($profile_user['email']) ?></div>
    <div><strong>Status:</strong> <?= e($profile_user['status']) ?></div>
    <div><strong>Account:</strong> <?= !empty($profile_user['is_active']) ? 'Active' : 'Inactive' ?></div>
    <div><strong>Created:</strong> <?= e(uk_datetime($profile_user['created_at'])) ?></div>
  <div><strong>Approved at:</strong> <?= !empty($profile_user['approved_at']) ? e(uk_datetime($profile_user['approved_at'])) : '—' ?></div>

	</div>

  <div class="card" style="margin-top:12px;">
    <div><strong>Firm:</strong> <?= e($ep['firm_name'] ?? '') ?></div>
    <div><strong>Area of law:</strong> <?= e($ep['area_of_law'] ?? '') ?></div>
    <div><strong>Sub-specialism:</strong> <?= e($ep['sub_specialism'] ?? '') ?></div>
   <div><strong>Location:</strong> <?= e($ep['location'] ?? '—') ?></div>
	  <div><strong>Telephone:</strong> <?= e($ep['telephone'] ?? '—') ?></div>
    <div><strong>Tasks required:</strong> <?= e($ep['tasks_required'] ?? '') ?></div>
  </div>

  <div style="margin-top:12px;">
    <a class="btn" href="/a/employers.php">&larr; Back to employers</a>
  </div>
</div>
