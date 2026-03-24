<div class="section">
  <div class="section-title">Invite</div>
  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <div style="margin-top:10px;">
    <div style="font-weight:800; font-size:18px;"><?= e($invite['title'] ?? '') ?></div>
    <div style="opacity:.8; margin-top:4px;">
      Employer: <?= e(($invite['employer_firm'] ?: $invite['employer_name']) ?? 'Employer') ?>
    </div>
  </div>

<div class="section" style="margin-top:14px;">
    <div class="card-body">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <div class="muted">Invited</div>
          <div style="font-weight:700;"><?= e($invite['created_at'] ? uk_date($invite['created_at']) : '-') ?></div>
        </div>
        <div>
          <div class="muted">Deadline</div>
          <div style="font-weight:700;"><?= e($invite['deadline'] ? uk_date($invite['deadline']) : '-') ?></div>
        </div>
        <div>
          <div class="muted">Mode</div>
          <div style="font-weight:700;"><?= e($invite['mode'] ?: '-') ?></div>
        </div>
        <div>
          <div class="muted">Urgency</div>
          <div style="font-weight:700;"><?= e($invite['urgency'] ?: '-') ?></div>
        </div>
      </div>

      <div style="margin-top:14px;">
        <div class="muted">Estimated value</div>
        <div style="font-weight:800; font-size:18px;">
          <?php
            $rate = $invite['max_rate'];
            $hrs  = $invite['hours_required'];
            if ($rate !== null && $hrs !== null && (float)$hrs > 0) {
              echo '£' . e(number_format((float)$rate * (float)$hrs, 2));
            } elseif ($rate !== null) {
              echo '£' . e(number_format((float)$rate, 2)) . '/hr';
            } else {
              echo '-';
            }
          ?>
        </div>
      </div>

      <?php if (!empty($invite['description'])): ?>
        <div style="margin-top:14px;">
          <div class="muted">Job description</div>
          <div style="margin-top:6px; white-space:pre-wrap;"><?= e($invite['description']) ?></div>
        </div>
      <?php endif; ?>

<div style="margin-top:18px;">
        <?php if (($invite['status'] ?? '') === 'Invited'): ?>
    
	<form method="post" action="/p/invite_action.php" style="display:flex; width:100%; gap:10px;">
  <input type="hidden" name="id" value="<?= (int)$invite['invitation_id'] ?>">

  <button
    class="btn"
    type="submit"
    name="action"
    value="accept"
    style="flex:1; width:50%; background:#1f8a3b; border-color:#1f8a3b; color:#fff;"
  >
    Accept invite
  </button>

  <button
    class="btn secondary"
    type="submit"
    name="action"
    value="decline"
    onclick="return confirm('Decline this invite?');"
    style="flex:1; width:50%;"
  >
    Decline
  </button>
</form>
		  
        <?php else: ?>
          <div class="alert" style="margin:0;">
            This invite has already been processed (<?= e($invite['status'] ?? '') ?>).
          </div>
        <?php endif; ?>

<div style="margin-top:12px;">
  <a class="btn btn-light" href="/p/dashboard.php">Back</a>
</div>
		  
      </div>

    </div>
  </div>
</div>
