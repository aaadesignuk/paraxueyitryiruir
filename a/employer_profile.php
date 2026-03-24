<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/a/employers.php'); }

$profile_user = db_fetch_one("
  SELECT user_id, role, full_name, email, is_active, status, created_at, approved_at, approved_by
  FROM users
  WHERE user_id=? AND role=?
  LIMIT 1
", [$id, ROLE_EMPLOYER]);

if (!$profile_user) { redirect('/a/employers.php'); }

$ep = db_fetch_one("SELECT * FROM employer_profiles WHERE user_id=? LIMIT 1", [$id]);

$title = 'Employer Profile';

render('admin/employer_profile', compact('title','profile_user','ep'));
