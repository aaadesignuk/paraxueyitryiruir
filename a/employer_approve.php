<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/a/employers.php');

$now = date('Y-m-d H:i:s');
$admin_id = (int)auth_user()['user_id'];

db_query(
  "UPDATE users SET status='approved', approved_at=?, approved_by=? WHERE user_id=? AND role=?",
  [$now, $admin_id, $id, ROLE_EMPLOYER]
);

notify($id, 'Your employer account has been approved. You can now post jobs.');

flash('Employer approved.', 'success');

$ret = (string)($_GET['return'] ?? '');
if ($ret === 'profile') {
  // If you have an employer profile page later, wire it here.
  // redirect('/a/employer_profile.php?id='.$id);
  redirect('/a/employers.php');
}

redirect('/a/employers.php');