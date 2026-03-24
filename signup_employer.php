<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/bootstrap.php';
$title = 'Employer sign up';

if (auth_check()) {
  $u = auth_user();
  if (($u['role'] ?? '') === 'E' && ($u['status'] ?? '') !== 'approved') {
    redirect('/e/pre_approval.php');
  }
  redirect('/e/dashboard.php');
}

$specialisms = db_fetch_all("SELECT DISTINCT specialism FROM specialisms ORDER BY specialism");
$subs_all = db_fetch_all("
  SELECT specialism, sub_specialism
  FROM specialisms
  WHERE sub_specialism IS NOT NULL AND sub_specialism <> ''
  ORDER BY specialism, sub_specialism
");

// Build a quick lookup: which specialisms actually have subs
$subsBySpec = [];
foreach ($subs_all as $row) {
  $sp = (string)($row['specialism'] ?? '');
  if ($sp === '') continue;
  if (!isset($subsBySpec[$sp])) $subsBySpec[$sp] = [];
  $subsBySpec[$sp][] = (string)($row['sub_specialism'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $mobile = trim($_POST['mobile'] ?? '');
  $firm_name = trim($_POST['firm_name'] ?? '');

  // Specialism + Sub-specialism
  $specialism = trim($_POST['specialism'] ?? '');
  $sub_specialism = trim($_POST['sub_specialism'] ?? '');

$location = trim($_POST['location'] ?? '');

  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';

  $terms = !empty($_POST['terms']);

  $errors = [];
  if ($full_name === '') $errors[] = 'Name is required.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
  if ($firm_name === '') $errors[] = 'Firm name is required.';
  if ($specialism === '') $errors[] = 'Specialism is required.';

  // Sub-specialism is only required if the chosen specialism has subs
  $specHasSubs = ($specialism !== '' && !empty($subsBySpec[$specialism]));
  if ($specHasSubs && $sub_specialism === '') $errors[] = 'Sub-specialism is required.';

  if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';
  if (!$terms) $errors[] = 'You must accept the Terms & Conditions.';

  if (db_fetch_value("SELECT COUNT(*) FROM users WHERE email = ?", [$email]) > 0) {
    $errors[] = 'An account with this email already exists.';
  }

  // Validate specialism pair (prevents tampering)
  if ($specialism !== '') {
    if ($specHasSubs) {
      if ($sub_specialism !== '') {
        $ok = db_fetch_value(
          "SELECT COUNT(*) FROM specialisms WHERE specialism = ? AND sub_specialism = ?",
          [$specialism, $sub_specialism]
        );
        if ((int)$ok <= 0) $errors[] = 'Invalid specialism selection.';
      } else {
        $errors[] = 'Sub-specialism is required.';
      }
    } else {
      // No subs exist for this specialism; ignore any posted sub_specialism
      $sub_specialism = '';
      $ok = db_fetch_value("SELECT COUNT(*) FROM specialisms WHERE specialism = ?", [$specialism]);
      if ((int)$ok <= 0) $errors[] = 'Invalid specialism selection.';
    }
  }

  if (!$errors) {
    $pw_hash = password_hash($password, PASSWORD_BCRYPT);
    $now = date('Y-m-d H:i:s');

    db_query(
      "INSERT INTO users (role, full_name, email, password_hash, terms_accepted_at, status)
       VALUES ('E', ?, ?, ?, ?, 'pending')",
      [$full_name, $email, $pw_hash, $now]
    );
    $user_id = (int)db()->lastInsertId();

    // Keep schema stable: we no longer collect location at signup, so store NULL.
    db_query(
  "INSERT INTO employer_profiles (user_id, firm_name, area_of_law, sub_specialism, location, telephone, tasks_required)
   VALUES (?, ?, ?, ?, ?, ?, NULL)",
  [
    $user_id,
    $firm_name,
    ($specialism !== '' ? $specialism : null),
    ($sub_specialism !== '' ? $sub_specialism : null),
    ($location !== '' ? $location : null),
    ($mobile !== '' ? $mobile : null),
  ]
);

    db_query(
      "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
      [$user_id, 'Thanks for signing up. Your employer account is pending approval.']
    );

    auth_login($email, $password);
    redirect('/e/pre_approval.php');
  }

  flash(implode(' ', $errors), 'error');
  render('signup_employer', compact('title', 'specialisms', 'subs_all'));
  exit;
}

render('signup_employer', compact('title', 'specialisms', 'subs_all'));
