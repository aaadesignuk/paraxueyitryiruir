<?php
require_once __DIR__ . '/app/bootstrap.php';

$title = 'Support';

// If logged in, show name/email.
$u = auth_user();
$name = (string)($u['full_name'] ?? '');
$email = (string)($u['email'] ?? '');

render('support', compact('title','name','email'));
