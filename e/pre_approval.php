<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (!auth_check()) redirect('/login.php');

$u = auth_user();
if (($u['role'] ?? '') !== 'E') redirect('/');

if (($u['status'] ?? '') === 'approved') {
  redirect('/e/dashboard.php');
}

$title = 'Account pending approval';
render('employer_pre_approval', compact('title', 'u'));
