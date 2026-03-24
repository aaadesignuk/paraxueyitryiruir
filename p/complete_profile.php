<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Complete Profile';
$pid = (int)auth_user()['user_id'];

// Ensure profile row exists
$profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
if (!$profile) {
  db_query("INSERT INTO paralegal_profiles (user_id,is_available) VALUES (?,1)", [$pid]);
  $profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]) ?: [];
}

// Skills count (current build)
$skillsCount = (int)db_fetch_value(
  "SELECT COUNT(*) FROM paralegal_skills WHERE user_id=?",
  [$pid]
);

$fullName = (string)(auth_user()['full_name'] ?? '');
$firstName = trim(explode(' ', trim($fullName))[0] ?? '');

// Completion checks (current DB schema only)
$hasSpecialism = trim((string)($profile['specialism'] ?? '')) !== '';
$hasBaseLocation =
  trim((string)($profile['base_country'] ?? '')) !== '' &&
  trim((string)($profile['base_city'] ?? '')) !== '';

// If USA then state required
$cc = strtoupper(preg_replace('/[^A-Z]/', '', (string)($profile['base_country'] ?? '')));
$isUSA = in_array($cc, ['USA','US','UNITEDSTATES','UNITEDSTATESOFAMERICA'], true);
if ($isUSA) {
  $hasBaseLocation = $hasBaseLocation && trim((string)($profile['base_state'] ?? '')) !== '';
}
$hasSkills     = $skillsCount > 0;
$hasCV         = trim((string)($profile['cv_path'] ?? '')) !== '';
$hasID         = trim((string)($profile['id_doc_path'] ?? '')) !== '';

$hasAvailability =
  trim((string)($profile['available_from'] ?? '')) !== '' &&
  trim((string)($profile['available_to'] ?? '')) !== '' &&
  trim((string)($profile['weekly_availability'] ?? '')) !== '';

$hasTravelPref = true;
if (db_has_column('paralegal_profiles', 'can_travel_abroad')) {
  $v = $profile['can_travel_abroad'] ?? null;
  // accept 0/1 only
  $hasTravelPref = ($v === 0 || $v === 1 || $v === '0' || $v === '1');
  if ($hasTravelPref && (int)$v === 1) {
    $any = (int)($profile['travel_anywhere'] ?? 0);
    $raw = (string)($profile['travel_countries'] ?? '');
    $arr = [];
    if ($raw !== '') {
      $dec = json_decode($raw, true);
      if (is_array($dec)) $arr = $dec;
    }
    if ($any !== 1 && count($arr) === 0) {
      $hasTravelPref = false;
    }
  }
}

$hasVisa = !empty($profile['visa_path'] ?? '');
$hasUtilityBill = !empty($profile['utility_bill_path'] ?? '');

$requirements = [
  [
    'label' => 'Base location set',
    'is_complete' => $hasBaseLocation,
    'status_text' => $hasBaseLocation ? 'Complete' : 'Missing',
    'action_label' => $hasBaseLocation ? 'View' : 'Set base location',
    'action_href' => '/p/profile_details.php#base-location',
  ],
  [
    'label' => 'Specialism selected',
    'is_complete' => $hasSpecialism,
    'status_text' => $hasSpecialism ? 'Complete' : 'Missing',
    'action_label' => $hasSpecialism ? 'View' : 'Select specialism',
    // Specialism is typically captured during signup; keep as navigation to profile details.
    'action_href' => '/p/profile_details.php',
  ],
  [
    'label' => 'Task skills added',
    'is_complete' => $hasSkills,
    'status_text' => $hasSkills ? 'Complete' : 'Missing',
    'action_label' => $hasSkills ? 'View' : 'Add task skills',
    // Locked principle: checklist actions should link into /p/profile_details.php
    // (anchors are used for availability/travel/documents; task skills currently live on profile details).
    'action_href' => '/p/profile_details.php#taskskills',
    'meta' => $hasSkills ? ($skillsCount . ' selected') : 'None selected',
  ],
  [
    'label' => 'Availability set',
    'is_complete' => $hasAvailability,
    'status_text' => $hasAvailability ? 'Complete' : 'Missing',
    'action_label' => $hasAvailability ? 'View' : 'Set availability',
    'action_href' => '/p/profile_details.php#availability',
  ],
  [
    'label' => 'Travel preference set',
    'is_complete' => $hasTravelPref,
    'status_text' => $hasTravelPref ? 'Complete' : 'Missing',
    'action_label' => $hasTravelPref ? 'View' : 'Set travel preference',
    'action_href' => '/p/profile_details.php#travel',
  ],
  [
    'label' => 'CV uploaded',
    'is_complete' => $hasCV,
    'status_text' => $hasCV ? 'Complete' : 'Missing',
    'action_label' => $hasCV ? 'View' : 'Upload CV',
    'action_href' => '/p/profile_details.php#documents',
  ],
  [
    'label' => 'ID document uploaded',
    'is_complete' => $hasID,
    'status_text' => $hasID ? 'Complete' : 'Missing',
    'action_label' => $hasID ? 'View' : 'Upload ID',
    'action_href' => '/p/profile_details.php#documents',
  ],
  [
    'label' => 'E-visa (if applicable)',
    'is_complete' => $hasVisa,
    'status_text' => $hasVisa ? 'Provided' : 'Not provided',
    'action_label' => $hasVisa ? 'View' : 'Upload e-visa',
    'action_href' => '/p/profile_details.php#documents',
    'optional' => true,
  ],
  [
    'label' => 'Utility bill evidence (if requested)',
    'is_complete' => $hasUtilityBill,
    'status_text' => $hasUtilityBill ? 'Provided' : 'Not provided',
    'action_label' => $hasUtilityBill ? 'View' : 'Upload utility bill',
    'action_href' => '/p/profile_details.php#documents',
    'optional' => true,
  ],
];

$profileComplete = ($hasBaseLocation && $hasSpecialism && $hasSkills && $hasAvailability && $hasTravelPref && $hasCV && $hasID);

render('paralegal/complete_profile', compact(
  'title',
  'firstName',
  'fullName',
  'skillsCount',
  'requirements',
  'profileComplete'
));
