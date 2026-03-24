<?php
function auth_user(){return $_SESSION['user']??null;}
function auth_check(){return auth_user()!==null;}

function auth_login($email,$pw){
  $u=db_fetch_one("SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1",[trim($email)]);
  if(!$u) return false;
  if(empty($u['password_hash'])||!password_verify($pw,$u['password_hash'])) return false;

  $_SESSION['user']=[
    'user_id'=>(int)$u['user_id'],
    'email'=>$u['email'],
    'full_name'=>$u['full_name'],
    'role'=>$u['role'],
    'status'=>($u['status'] ?? 'approved')
  ];
  return true;
}

function auth_refresh_status(){
  if(!auth_check()) return;
  $uid=(int)auth_user()['user_id'];
  $st=db_fetch_value("SELECT status FROM users WHERE user_id=? LIMIT 1",[$uid]);
  if($st){
    $_SESSION['user']['status']=$st;
  }
}

function auth_logout(){unset($_SESSION['user']);}
function require_auth(){if(!auth_check())redirect('/login.php');}
function require_role($roles){require_auth();$u=auth_user();if(!in_array($u['role'],$roles,true)){http_response_code(403);echo 'Forbidden';exit;}}
