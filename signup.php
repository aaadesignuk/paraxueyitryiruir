<?php require_once __DIR__.'/app/bootstrap.php';
$title='Sign up';

if(auth_check()) {
  redirect('/dashboard.php');
}

render('signup_choice', compact('title'));
