<?php
// /app/views/paralegal/complete_profile.php
// Variables expected: $firstName, $fullName, $skillsCount, $requirements, $profileComplete
?>

<style>
/* Center the checklist page in a column */
.cp-page{
  max-width: 980px;
  margin: 0 auto;
  width: 100%;
}

/* Override the global .card width:min(520px,94vw) ONLY for this page */
.cp-page .card{
  width: 100% !important;
}
</style>

<div class="cp-page">

  <div class="page-head">
    <h1>Complete Profile</h1>
    <p class="muted">
      Use this checklist to finish your profile. Some features may be limited until your profile is complete.
    </p>
  </div>

  <?php if (!$profileComplete): ?>
    <div class="alert alert-warning" style="margin-bottom:16px;">
      <strong>Profile incomplete.</strong>
      Please complete the missing items below.
    </div>
  <?php else: ?>
    <div class="alert alert-success" style="margin-bottom:16px;">
      <strong>Profile complete.</strong>
      You’re all set.
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:16px;">
    <div class="card-body">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
          <div class="muted" style="margin-bottom:4px;">Profile owner</div>
          <div style="font-weight:700;"><?php echo htmlspecialchars($fullName ?: ''); ?></div>
        </div>

        <div style="text-align:right;">
          <div class="muted" style="margin-bottom:4px;">Task skills</div>
          <div style="font-weight:700;"><?php echo (int)$skillsCount; ?> selected</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-wrap">
        <table class="table" style="width:100%;">
          <thead>
            <tr>
              <th style="text-align:left;">Requirement</th>
              <th style="text-align:left; width:140px;">Status</th>
              <th style="text-align:right; width:220px;">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($requirements as $req): ?>
            <?php
              $isComplete = !empty($req['is_complete']);
              $isOptional = !empty($req['optional']);
              $statusText = (string)($req['status_text'] ?? '');
              $meta = (string)($req['meta'] ?? '');
              $actionHref = (string)($req['action_href'] ?? '/p/profile_details.php');
              $actionLabel = (string)($req['action_label'] ?? 'Open');

              $statusStyle = $isComplete
                ? 'color:#1f8f4a; font-weight:700;'
                : ($isOptional ? 'color:rgba(255,255,255,.65);' : 'color:#d98b1f; font-weight:700;');
            ?>
            <tr>
              <td>
                <div style="font-weight:700;"><?php echo htmlspecialchars($req['label']); ?></div>
                <?php if ($meta !== ''): ?>
                  <div class="muted" style="margin-top:3px;"><?php echo htmlspecialchars($meta); ?></div>
                <?php endif; ?>
              </td>
              <td style="<?php echo $statusStyle; ?>">
                <?php echo htmlspecialchars($statusText); ?>
              </td>
              <td style="text-align:right;">
                <a class="btn micro" href="<?php echo htmlspecialchars($actionHref); ?>">
                  <?php echo htmlspecialchars($actionLabel); ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:14px; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
        <a class="btn secondary small" href="/p/profile_details.php">Open profile details</a>
        <a class="btn" href="/p/dashboard.php">Back to dashboard</a>
      </div>
    </div>
  </div>

</div>
