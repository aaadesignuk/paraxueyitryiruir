<?php require_once __DIR__.'/app/bootstrap.php'; $title='Login';
if(auth_check()) redirect('/dashboard.php');
if($_SERVER['REQUEST_METHOD']==='POST'){ if(auth_login($_POST['email']??'',$_POST['password']??'')) redirect('/dashboard.php'); flash('Invalid credentials.','error'); redirect('/login.php');}
render('login', compact('title'));
