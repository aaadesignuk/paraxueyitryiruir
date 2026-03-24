<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/app/bootstrap.php';
$title = 'Paralegal sign up';

if (auth_check()) {
  redirect('/p/welcome.php');
}

/**
 * Upload handler for paralegal docs.
 * Stores file under: /uploads/paralegal_docs/{user_id}/
 * Returns the WEB path to store in DB, or null on no file / error.
 */
function handle_paralegal_doc_upload(string $field, int $user_id, array &$errors): ?string
{
  if (empty($_FILES[$field]) || !isset($_FILES[$field]['error'])) {
    return null;
  }

  if ($_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return null; // optional
  }

  if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    $errors[] = ucfirst($field) . ' upload failed (error code: ' . (int)$_FILES[$field]['error'] . ').';
    return null;
  }

  $orig = $_FILES[$field]['name'] ?? '';
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

  // Allowlist
  $allowed = ['pdf', 'doc', 'docx'];
  if (!in_array($ext, $allowed, true)) {
    $errors[] = ucfirst($field) . ' must be a PDF, DOC, or DOCX.';
    return null;
  }

  $publicRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
  if ($publicRoot === '') {
    $errors[] = 'Upload failed (server misconfig: DOCUMENT_ROOT not set).';
    return null;
  }

  $dirFs = $publicRoot . "/uploads/paralegal_docs/{$user_id}";
  if (!is_dir($dirFs)) {
    if (!mkdir($dirFs, 0775, true)) {
      $errors[] = 'Could not create upload folder.';
      return null;
    }
  }

  $safeName = $field . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $destFs   = $dirFs . '/' . $safeName;

  if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destFs)) {
    $errors[] = 'Could not save uploaded ' . $field . '.';
    return null;
  }

  return "/uploads/paralegal_docs/{$user_id}/{$safeName}";
}

$specialisms = db_fetch_all("SELECT DISTINCT specialism FROM specialisms ORDER BY specialism");
$subs_all = db_fetch_all("
  SELECT specialism, sub_specialism
  FROM specialisms
  WHERE sub_specialism IS NOT NULL AND sub_specialism <> ''
  ORDER BY specialism, sub_specialism
");

// Build lookup: which specialisms have subs
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
  $password = $_POST['password'] ?? '';

  $specialism = trim($_POST['specialism'] ?? '');
  $sub_specialism = trim($_POST['sub_specialism'] ?? '');
  $specialism2 = trim($_POST['specialism2'] ?? '');
  $sub_specialism2 = trim($_POST['sub_specialism2'] ?? '');

  // IMPORTANT: Days & Months (not Years)
// Experience (required)
$experience_type = trim($_POST['experience_type'] ?? '');
$experience_value = trim($_POST['experience_value'] ?? '');

  $preferred_rate = trim($_POST['preferred_rate'] ?? '');
  $location_preference = trim($_POST['location_preference'] ?? '');

  // Base location (required)
  $base_country = trim($_POST['base_country'] ?? '');
  $base_country_other = trim($_POST['base_country_other'] ?? '');
  if ($base_country === 'Other') $base_country = $base_country_other;
  $base_state = trim($_POST['base_state'] ?? '');
  $base_city = trim($_POST['base_city'] ?? '');
  $base_postcode = trim($_POST['base_postcode'] ?? '');
  $base_address1 = trim($_POST['base_address1'] ?? '');
  $base_address2 = trim($_POST['base_address2'] ?? '');

  $is_available = (($_POST['is_available'] ?? '1') === '1') ? 1 : 0;

  // Terms + authorised confirmation (split)
  $terms = isset($_POST['terms']);
  $authorised_only = isset($_POST['authorised_only']);

  $errors = [];
  if ($full_name === '') $errors[] = 'Name is required.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
  if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($specialism === '') $errors[] = 'Specialism 1 is required.';
  if ($base_country === '') $errors[] = 'Country is required.';
// City required except when US + State selected
$cityRequired = !($base_country === 'United States' && $base_state !== '');
if ($cityRequired && $base_city === '') $errors[] = 'City is required.';

  if ($base_country === 'United States' && $base_state === '') $errors[] = 'State is required for United States.';

  // Sub 1 required only if specialism 1 has subs
  $spec1HasSubs = ($specialism !== '' && !empty($subsBySpec[$specialism]));
  if ($spec1HasSubs && $sub_specialism === '') $errors[] = 'Sub-specialism 1 is required.';
  if (!$spec1HasSubs) $sub_specialism = '';

  // Specialism 2 rules:
  // - if specialism2 chosen and it has subs => require sub2
  // - if specialism2 chosen and it has no subs => sub2 must be blank (ignored)
  // - if specialism2 blank => sub2 must be blank
  if ($specialism2 === '' && $sub_specialism2 !== '') {
    $errors[] = 'Please select Specialism 2 first.';
  }

  if ($specialism2 !== '') {
    $spec2HasSubs = !empty($subsBySpec[$specialism2]);
    if ($spec2HasSubs && $sub_specialism2 === '') {
      $errors[] = 'If selecting Specialism 2, please select its sub-specialism.';
    }
    if (!$spec2HasSubs) {
      $sub_specialism2 = '';
    }
  } else {
    $sub_specialism2 = '';
  }

  if (!$terms) $errors[] = 'You must accept the terms.';
  if (!$authorised_only) $errors[] = 'You must confirm you will only assist authorised lawyers.';

 if (!in_array($experience_type, ['Days', 'Months', 'Years'], true)) {
  $errors[] = 'Please select Days, Months, or Years for experience.';
}

if ($experience_value === '' || !is_numeric($experience_value) || (float)$experience_value <= 0) {
  $errors[] = 'Please enter a valid experience amount.';
}

  if ($preferred_rate !== '' && !is_numeric($preferred_rate)) {
    $errors[] = 'Preferred rate must be a number.';
  }

  if (db_fetch_value("SELECT COUNT(*) FROM users WHERE email = ?", [$email]) > 0) {
    $errors[] = 'An account with this email already exists.';
  }

  // Validate specialism pairs (prevents tampering)
  if ($specialism !== '') {
    if ($spec1HasSubs) {
      $ok1 = db_fetch_value("SELECT COUNT(*) FROM specialisms WHERE specialism = ? AND sub_specialism = ?", [$specialism, $sub_specialism]);
      if ((int)$ok1 <= 0) $errors[] = 'Invalid specialism selection.';
    } else {
      $ok1 = db_fetch_value("SELECT COUNT(*) FROM specialisms WHERE specialism = ?", [$specialism]);
      if ((int)$ok1 <= 0) $errors[] = 'Invalid specialism selection.';
    }
  }

  if ($specialism2 !== '') {
    $spec2HasSubs = !empty($subsBySpec[$specialism2]);
    if ($spec2HasSubs && $sub_specialism2 !== '') {
      $ok2 = db_fetch_value("SELECT COUNT(*) FROM specialisms WHERE specialism = ? AND sub_specialism = ?", [$specialism2, $sub_specialism2]);
      if ((int)$ok2 <= 0) $errors[] = 'Invalid specialism selection.';
    } else {
      $ok2 = db_fetch_value("SELECT COUNT(*) FROM specialisms WHERE specialism = ?", [$specialism2]);
      if ((int)$ok2 <= 0) $errors[] = 'Invalid specialism selection.';
    }
  }

  if (!$errors) {
    $pw_hash = password_hash($password, PASSWORD_BCRYPT);
    $now = date('Y-m-d H:i:s');

    db_query(
      "INSERT INTO users (role, full_name, email, password_hash, terms_accepted_at, status)
       VALUES ('P', ?, ?, ?, ?, 'pending')",
      [$full_name, $email, $pw_hash, $now]
    );
    $user_id = (int)db()->lastInsertId();

    // Store up to 2 specialisms in a single field (pipe-separated)
    $specs = [];
    if ($specialism !== '' && (!$spec1HasSubs || $sub_specialism !== '')) {
      $specs[] = $spec1HasSubs ? ($specialism . ' — ' . $sub_specialism) : $specialism;
    }
    if ($specialism2 !== '' && $specialism2 !== $specialism) {
      $spec2HasSubs = !empty($subsBySpec[$specialism2]);
      $specs[] = $spec2HasSubs ? ($specialism2 . ' — ' . $sub_specialism2) : $specialism2;
    }
    $stored_specialism = $specs ? implode(' | ', $specs) : null;

    // Also persist into users.Specialism if that column exists (so profile details can show it)
    if (db_has_column('users', 'Specialism')) {
      db_query("UPDATE users SET Specialism=? WHERE user_id=?", [$stored_specialism, $user_id]);
    }

$ev = (float)$experience_value;
    $pr = ($preferred_rate === '') ? null : (float)$preferred_rate;

db_query(
  "INSERT INTO paralegal_profiles
    (user_id, specialism, sub_specialism, specialism2, sub_specialism2,
     experience_type, experience_value, preferred_rate, location_preference, is_available)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
  [
    $user_id,
    ($specialism !== '' ? $specialism : null),
    ($sub_specialism !== '' ? $sub_specialism : null),
    ($specialism2 !== '' ? $specialism2 : null),
    ($sub_specialism2 !== '' ? $sub_specialism2 : null),
    $experience_type,
    $ev,
    $pr,
    ($location_preference !== '' ? $location_preference : null),
    $is_available
  ]
);


    // Base location (persist if columns exist)
    $locSet = [];
    $locParams = [];
    if (db_has_column('paralegal_profiles','base_country')) { $locSet[]='base_country=?'; $locParams[] = ($base_country !== '' ? $base_country : null); }
    if (db_has_column('paralegal_profiles','base_state')) { $locSet[]='base_state=?'; $locParams[] = ($base_state !== '' ? $base_state : null); }
    if (db_has_column('paralegal_profiles','base_city')) { $locSet[]='base_city=?'; $locParams[] = ($base_city !== '' ? $base_city : null); }
    if (db_has_column('paralegal_profiles','base_postcode')) { $locSet[]='base_postcode=?'; $locParams[] = ($base_postcode !== '' ? $base_postcode : null); }
    if (db_has_column('paralegal_profiles','base_address1')) { $locSet[]='base_address1=?'; $locParams[] = ($base_address1 !== '' ? $base_address1 : null); }
    if (db_has_column('paralegal_profiles','base_address2')) { $locSet[]='base_address2=?'; $locParams[] = ($base_address2 !== '' ? $base_address2 : null); }
    if ($locSet) {
      $locParams[] = $user_id;
      db_query("UPDATE paralegal_profiles SET ".implode(',', $locSet)." WHERE user_id=?", $locParams);
    }

    // CV upload on signup (optional)
    $cv_path = handle_paralegal_doc_upload('cv', $user_id, $errors);
    if (!$errors && $cv_path) {
      db_query("UPDATE paralegal_profiles SET cv_path = ? WHERE user_id = ?", [$cv_path, $user_id]);
    }

    if ($errors) {
      flash(implode(' ', $errors), 'error');
    }

    db_query(
      "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
      [$user_id, 'Welcome to Paralete. Next step: complete your Task Skills in your profile to improve matching.']
    );

    auth_login($email, $password);
    redirect('/p/welcome.php');
  }

  flash(implode(' ', $errors), 'error');
  render('signup_paralegal', compact('title', 'specialisms', 'subs_all'));
  exit;
}

render('signup_paralegal', compact('title', 'specialisms', 'subs_all'));
