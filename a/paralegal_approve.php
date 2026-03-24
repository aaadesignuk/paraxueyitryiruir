<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/a/paralegals.php');

$now = date('Y-m-d H:i:s');
$admin_id = (int)auth_user()['user_id'];

db_query(
  "UPDATE users SET status='approved', approved_at=?, approved_by=? WHERE user_id=? AND role=?",
  [$now, $admin_id, $id, ROLE_PARALEGAL]
);

notify($id, 'Your paralegal account has been approved. You can now access available work.');

flash('Paralegal approved.', 'success');

$ret = (string)($_GET['return'] ?? '');
if ($ret === 'profile') {
  redirect('/a/paralegal_profile.php?id='.$id);
}
redirect('/a/paralegals.php');