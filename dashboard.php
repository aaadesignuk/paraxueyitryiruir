<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

$u = auth_user();
$r = $u['role'] ?? '';

if ($r === ROLE_ADMIN) {
  redirect('/a/dashboard.php');
}

if ($r === ROLE_EMPLOYER) {
  if (($u['status'] ?? '') !== 'approved') {
    redirect('/e/pre_approval.php');
  }
  redirect('/e/dashboard.php');
}

if ($r === ROLE_PARALEGAL) {
  redirect('/p/dashboard.php');
}

http_response_code(500);
echo 'Invalid role';
