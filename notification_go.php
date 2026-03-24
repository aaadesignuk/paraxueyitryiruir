<?php
require_once __DIR__.'/app/bootstrap.php';
require_auth();

$u = auth_user();
$uid = (int)$u['user_id'];
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
  redirect('/notifications.php');
}

$msgCol = notifications_message_column();
$linkCol = (function_exists('db_has_column') && db_has_column('notifications', 'link')) ? 'link' : "''";
$row = db_fetch_one("SELECT notification_id, {$msgCol} AS message, {$linkCol} AS link FROM notifications WHERE notification_id=? AND user_id=? LIMIT 1", [$id, $uid]);
if (!$row) {
  flash('Notification not found.', 'error');
  redirect('/notifications.php');
}

notifications_mark_read($uid, $id);
$link = trim((string)($row['link'] ?? ''));
if ($link === '' && function_exists('notifications_guess_link')) {
  $link = notifications_guess_link($u, (string)($row['message'] ?? ''));
}
if ($link === '') $link = '/notifications.php';
redirect($link);
