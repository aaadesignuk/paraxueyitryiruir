<?php
$u = auth_user();
$current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$f = flash_get();
$notifications_unread_header = 0;

if ($u && function_exists('notifications_sync_for_user')) {
  notifications_sync_for_user($u);
  $notifications_unread_header = notifications_unread_count((int)$u['user_id']);
}

if (!function_exists('nav_active')) {
  function nav_active($needles, $current) {
    foreach ((array)$needles as $needle) {
      if ($needle !== '' && strpos($current, $needle) !== false) {
        return 'active';
      }
    }
    return '';
  }
}

if (!function_exists('nav_style')) {
  function nav_style($needles, $current) {
    return nav_active($needles, $current)
      ? 'color:#16a34a !important; font-weight:700; border-bottom:2px solid #16a34a; padding-bottom:2px;'
      : '';
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title ?? APP_NAME) ?></title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="app">
  <div class="<?= $u ? 'dashboard' : 'card' ?>">
    <div class="topbar">
      <div class="logo"><?= e(APP_NAME) ?><span>.</span></div>
      <div>
        <?php if ($u): ?>
          <div style="opacity:.75; line-height:1.25;">
            <?php
              $first = trim(explode(' ', (string)$u['full_name'])[0] ?? $u['full_name']);
              echo 'Welcome ' . e($first) . ',';
            ?>
            <br>
            You are logged in as a <?= e(role_label($u['role'])) ?>
          </div>

          <?php if ($u['role'] !== 'A'): ?>
            <a href="/notifications.php"
               class="<?= nav_active(['/notifications.php'], $current) ?>"
               title="Notifications"
               aria-label="Notifications"
               style="margin-right:14px; display:inline-flex; align-items:center; text-decoration:none; vertical-align:middle; <?= nav_style(['/notifications.php'], $current) ?>">
              <span style="position:relative; display:inline-block; line-height:1;">
                <span aria-hidden="true" style="display:inline-block; font-size:18px; line-height:1;">&#128276;</span>
                <?php if ((int)$notifications_unread_header > 0): ?>
                  <span style="position:absolute; left:-8px; bottom:-8px; min-width:16px; height:16px; padding:0 4px; border-radius:999px; background:#ff6b6b; color:#fff; font-size:10px; line-height:16px; text-align:center; font-weight:700; white-space:nowrap; box-sizing:border-box;"><?= (int)$notifications_unread_header ?></span>
                <?php endif; ?>
              </span>
            </a>
          <?php endif; ?>

          <a href="<?= ($u['role'] === 'A') ? '/a/dashboard.php' : '/dashboard.php' ?>"
             class="<?= nav_active(['/dashboard.php', '/a/dashboard.php', '/e/dashboard.php', '/p/dashboard.php'], $current) ?>"
             style="margin-right:14px; <?= nav_style(['/dashboard.php', '/a/dashboard.php', '/e/dashboard.php', '/p/dashboard.php'], $current) ?>">
             Dashboard
          </a>

          <?php if ($u['role'] === 'E'): ?>
            <a href="/e/timesheets.php"
               class="<?= nav_active(['/e/timesheets.php', '/e/timesheet.php'], $current) ?>"
               style="margin-right:14px; <?= nav_style(['/e/timesheets.php', '/e/timesheet.php'], $current) ?>">
               Timesheets
            </a>

            <a href="/e/paralegal_invoices.php"
               class="<?= nav_active(['/e/paralegal_invoices.php', '/e/paralegal_invoice.php'], $current) ?>"
               style="margin-right:14px; <?= nav_style(['/e/paralegal_invoices.php', '/e/paralegal_invoice.php'], $current) ?>">
               Invoices
            </a>
          <?php endif; ?>

          <?php if ($u['role'] === 'P'): ?>
            <a href="/p/timesheets.php"
               class="<?= nav_active(['/p/timesheets.php', '/p/timesheet.php', '/p/time_entry.php'], $current) ?>"
               style="margin-right:14px; <?= nav_style(['/p/timesheets.php', '/p/timesheet.php', '/p/time_entry.php'], $current) ?>">
               Timesheets
            </a>

            <a href="/p/invoices.php"
               class="<?= nav_active(['/p/invoices.php', '/p/invoice.php'], $current) ?>"
               style="margin-right:14px; <?= nav_style(['/p/invoices.php', '/p/invoice.php'], $current) ?>">
               My Invoices
            </a>
          <?php endif; ?>

          <?php if ($u['role'] === 'A'): ?>
            <a href="/a/commission_invoices.php"
               class="<?= nav_active(['/a/commission_invoices.php', '/a/commission_invoice.php'], $current) ?>"
               style="margin-right:14px; <?= nav_style(['/a/commission_invoices.php', '/a/commission_invoice.php'], $current) ?>">
               Commission
            </a>
          <?php endif; ?>

          <a href="/logout.php">Logout</a>
        <?php else: ?>
          <a href="/login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($f): ?>
      <div class="flash flash--<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endif; ?>

    <?php include $content_view; ?>

    <div class="footer">© <?= date('Y') ?> <?= e(APP_NAME) ?></div>
  </div>
</div>
</body>
</html>