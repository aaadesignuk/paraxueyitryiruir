<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Profile Details';
$pid = (int)auth_user()['user_id'];

// --- Load/Create profile row ---
$profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
if (!$profile) {
  db_query("INSERT INTO paralegal_profiles (user_id, is_available) VALUES (?, 0)", [$pid]);
  $profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
}

// --- Helpers ---
function norm_time(string $t): string {
  $t = trim($t);
  // Accept HH:MM or empty
  if ($t === '') return '';
  if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) return '';
  return $t;
}

function safe_upload(string $field, int $pid, string $subdir = 'paralegal_docs'): array {
  if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
    return ['ok' => true, 'path' => null, 'error' => null];
  }

  $f = $_FILES[$field];
  if (!isset($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) {
    return ['ok' => true, 'path' => null, 'error' => null];
  }
  if ($f['error'] !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'path' => null, 'error' => 'Upload failed. Please try again.'];
  }

  // 10MB max
  if (!empty($f['size']) && (int)$f['size'] > 10 * 1024 * 1024) {
    return ['ok' => false, 'path' => null, 'error' => 'File is too large (max 10MB).'];
  }

  $orig = (string)($f['name'] ?? '');
  $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
  if (!in_array($ext, $allowed, true)) {
    return ['ok' => false, 'path' => null, 'error' => 'Unsupported file type. Allowed: PDF, DOC, DOCX, JPG, PNG.'];
  }

  // Prevent executable extensions masquerading
  if (preg_match('/\.(php|phtml|phar|cgi|pl|exe|sh)$/i', $orig)) {
    return ['ok' => false, 'path' => null, 'error' => 'Unsupported file name.'];
  }

  $baseDir = realpath(__DIR__ . '/..');
  if (!$baseDir) {
    return ['ok' => false, 'path' => null, 'error' => 'Server path error.'];
  }

  $relDir = '/uploads/' . $subdir . '/' . $pid;
  $absDir = $baseDir . $relDir;
  if (!is_dir($absDir)) {
    @mkdir($absDir, 0775, true);
  }
 
