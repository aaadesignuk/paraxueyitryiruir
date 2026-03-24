<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

// Backwards compatible: accept old field name commission_rate (updates default)
$default = $_POST['commission_rate_default'] ?? $_POST['commission_rate'] ?? '';
$urgent = $_POST['commission_rate_urgent'] ?? '';
$overnight = $_POST['commission_rate_overnight'] ?? '';
$specialist = $_POST['commission_rate_specialist'] ?? '';

$toSave = [
  'commission_rate_default' => $default,
  'commission_rate_urgent' => $urgent,
  'commission_rate_overnight' => $overnight,
  'commission_rate_specialist' => $specialist,
];

foreach ($toSave as $key => $val) {
  $val = trim((string)$val);
  if ($val === '') continue; // allow leaving blank to keep existing
  if (!is_numeric($val)) {
    flash('Please enter valid commission rates (0–100).', 'error');
    redirect('/a/dashboard.php');
  }
  $f = (float)$val;
  if ($f < 0 || $f > 100) {
    flash('Commission rates must be between 0 and 100.', 'error');
    redirect('/a/dashboard.php');
  }
  setting_set($key, number_format($f, 2, '.', ''));
}

flash('Commission rates updated.', 'success');
redirect('/a/dashboard.php');
