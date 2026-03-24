<?php require_once __DIR__.'/../app/bootstrap.php';
$email=$_GET['email']??'admin@paralete.test'; $pw=$_GET['password']??'Admin123!';
$hash=password_hash($pw,PASSWORD_BCRYPT);
db_query("UPDATE users SET password_hash=?, role=?, is_active=1 WHERE email=? LIMIT 1",[$hash,ROLE_ADMIN,$email]);
echo "<pre>Updated $email\nPassword $pw\nRole ".ROLE_ADMIN."\nDelete this file.</pre>";
