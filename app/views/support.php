<?php
// app/views/support.php
// expects: $title, $name, $email
?>

<div class="section">
  <div class="section-title">Support</div>
  <div class="section-hint">If something isn’t working as expected, send us a short note and we’ll pick it up.</div>

  <div style="margin-top:14px; line-height:1.7;">
    <div><strong>Email:</strong> <a href="mailto:support@paralete.com">support@paralete.com</a></div>
    <div><strong>Include:</strong> your name, the page link, and what you clicked.</div>
    <?php if (!empty($name) || !empty($email)): ?>
      <div style="margin-top:10px; opacity:.85;">
        <strong>Your details:</strong> <?= e(trim($name)) ?><?= $email ? ' · '.e($email) : '' ?>
      </div>
    <?php endif; ?>
  </div>

  <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="/dashboard.php">Back to dashboard</a>
  </div>
</div>
