<?php
require_once __DIR__.'/app/bootstrap.php';
require_auth();

$title = 'Notifications';
$u = auth_user();
$uid = (int)$u['user_id'];

notifications_sync_for_user($u);

if (isset($_GET['mark_all']) && $_GET['mark_all'] === '1') {
  notifications_mark_all_read($uid);
  flash('All notifications marked as read.', 'success');
  redirect('/notifications.php');
}

$notifications = notifications_fetch_all($uid, 100);
$unread_count = notifications_unread_count($uid);

render('notifications', compact('title', 'notifications', 'unread_count'));
