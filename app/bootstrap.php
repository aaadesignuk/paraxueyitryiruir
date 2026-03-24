<?php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime'=>0,
  'path'=>'/',
  'domain'=>'app.paralete.com',
  'secure'=>$secure,
  'httponly'=>true,
  'samesite'=>'Lax'
]);
session_start();

require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/services/matching.php';
require_once __DIR__.'/services/billing.php';
require_once __DIR__.'/services/paralegal_invoicing.php';
require_once __DIR__.'/services/timesheets_auto.php'; // ✅ NEW
require_once __DIR__.'/services/notifications_sync.php';

// --- Approval gate (pending paralegals) ---
if (function_exists('auth_refresh_status')) {
  auth_refresh_status();
}

if (auth_check()) {
  $u = auth_user();
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';

  // --- Approval gate (pending employers) ---
  if (($u['role'] ?? '') === ROLE_EMPLOYER && (($u['status'] ?? 'pending') !== 'approved')) {
    $allowed = [
      '/e/pre_approval.php',
      '/logout.php',
    ];

    $ok = false;
    foreach ($allowed as $a) {
      if (stripos($path, $a) === 0) { $ok = true; break; }
    }

    if (!$ok) {
      redirect('/e/pre_approval.php');
    }
  }

  // --- Approval gate (pending paralegals) ---
  if (($u['role'] ?? '') === ROLE_PARALEGAL && (($u['status'] ?? 'pending') !== 'approved')) {
    $allowed = [
      '/p/welcome.php',
      '/p/complete_profile.php',
      '/p/profile.php',
      '/p/profile_details.php',
      '/p/complete_profile_details.php',
      '/logout.php',
    ];

    $ok = false;
    foreach ($allowed as $a) {
      if (stripos($path, $a) === 0) { $ok = true; break; }
    }

    if (!$ok) {
      redirect('/p/welcome.php');
    }
  }
}