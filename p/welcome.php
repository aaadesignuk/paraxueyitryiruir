<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);
$title = 'Welcome';
render('paralegal/welcome', compact('title'));
