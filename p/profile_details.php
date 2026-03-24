<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Profile details';
$pid = (int)auth_user()['user_id'];

$profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
if (!$profile) {
  db_query("INSERT INTO paralegal_profiles (user_id,is_available) VALUES (?,1)", [$pid]);
  $profile = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
}

function normalize_date($v) {
  $v = trim((string)$v);
  if ($v === '') return null;
  $ts = strtotime($v);
  if ($ts === false) return null;
  return date('Y-m-d', $ts);
}

function normalize_time($v) {
  $v = trim((string)$v);
  if ($v === '') return '';
  // Accept HH:MM
  if (preg_match('/^\d{2}:\d{2}$/', $v)) return $v;
  $ts = strtotime($v);
  if ($ts === false) return '';
  return date('H:i', $ts);
}

$days = [
  'mon' => 'Monday',
  'tue' => 'Tuesday',
  'wed' => 'Wednesday',
  'thu' => 'Thursday',
  'fri' => 'Friday',
  'sat' => 'Saturday',
  'sun' => 'Sunday',
];

$availability = [];
$existing_av_json = (string)($profile['weekly_availability'] ?? '');

if ($existing_av_json !== '') {
  $decoded = json_decode($existing_av_json, true);
  if (is_array($decoded)) $availability = $decoded;
}
foreach ($days as $k => $label) {
  if (!isset($availability[$k]) || !is_array($availability[$k])) {
    $availability[$k] = ['start' => '', 'end' => ''];
  } else {
    $availability[$k]['start'] = (string)($availability[$k]['start'] ?? '');
    $availability[$k]['end']   = (string)($availability[$k]['end'] ?? '');
  }
}

// ---------- Task skills (load) ----------

// Load categories (alphabetical) + activities (by sort_order)
$cats = db_fetch_all("
  SELECT id, name
  FROM task_categories
  WHERE is_active=1
  ORDER BY name ASC
");

$skillsByCategory = [];
foreach ($cats as $c) {
  $cid = (int)$c['id'];
  $cname = (string)$c['name'];

  $acts = db_fetch_all("
    SELECT id, name
    FROM task_activities
    WHERE category_id=? AND is_active=1
    ORDER BY sort_order ASC, id ASC
  ", [$cid]);

  $skillsByCategory[$cname] = [];
  foreach ($acts as $a) {
    $skillsByCategory[$cname][] = [
      'id'   => (int)$a['id'],     // task_activities.id
      'name' => (string)$a['name']
    ];
  }
}

// Selected skills (storing task_activities.id in paralegal_skills.skill_id)
$selectedSkillIds = array_map(
  'intval',
  array_column(
    db_fetch_all("SELECT skill_id FROM paralegal_skills WHERE user_id=?", [$pid]),
    'skill_id'
  )
);

// ---------- Specialisms (load) ----------
$specRows = db_fetch_all("SELECT specialism, sub_specialism FROM specialisms ORDER BY specialism, sub_specialism");
$specialismOptions = [];
$subsBySpecialism = [];
foreach ($specRows as $r) {
  $sp = trim((string)($r['specialism'] ?? ''));
  $ss = trim((string)($r['sub_specialism'] ?? ''));
  if ($sp === '') continue;
  $specialismOptions[$sp] = true;
  if (!isset($subsBySpecialism[$sp])) $subsBySpecialism[$sp] = [];
  if ($ss !== '') $subsBySpecialism[$sp][] = $ss;
}
$specialismOptions = array_values(array_keys($specialismOptions));
sort($specialismOptions);
foreach ($subsBySpecialism as $sp => $subs) {
  $subs = array_values(array_unique(array_filter($subs)));
  sort($subs);
  $subsBySpecialism[$sp] = $subs;
}
// ---------- /Specialisms (load) ----------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
	  // Bank details
  $bank_name     = trim((string)($_POST['bank_name'] ?? ''));
  $account_name  = trim((string)($_POST['account_name'] ?? ''));
  $account_no    = trim((string)($_POST['account_no'] ?? ''));
  $sort_code     = trim((string)($_POST['sort_code'] ?? ''));

  $work_mode = $_POST['work_mode'] ?? 'remote';
  if (!in_array($work_mode, ['remote','in_person','either'], true)) $work_mode = 'remote';

  $weekend_available = isset($_POST['weekend_available']) ? 1 : 0;
  $night_available   = isset($_POST['night_available']) ? 1 : 0;

  $available_from = normalize_date($_POST['available_from'] ?? '');
  $available_to   = normalize_date($_POST['available_to'] ?? '');

  // Weekly availability
  $newAvailability = [];
  foreach ($days as $k => $label) {
    $start = normalize_time($_POST['availability'][$k]['start'] ?? '');
    $end   = normalize_time($_POST['availability'][$k]['end'] ?? '');
    $newAvailability[$k] = ['start' => $start, 'end' => $end];
  }

  // Base location
  $base_country  = trim((string)($_POST['base_country'] ?? ''));
  $base_city     = trim((string)($_POST['base_city'] ?? ''));
  $base_state    = trim((string)($_POST['base_state'] ?? ''));
  $base_postcode = trim((string)($_POST['base_postcode'] ?? ''));
  $base_address1 = trim((string)($_POST['base_address1'] ?? ''));
  $base_address2 = trim((string)($_POST['base_address2'] ?? ''));

  // Specialism (1 + 2)
  $specialism = trim((string)($_POST['specialism'] ?? ''));
  $sub_specialism = trim((string)($_POST['sub_specialism'] ?? ''));
  $specialism2 = trim((string)($_POST['specialism2'] ?? ''));
  $sub_specialism2 = trim((string)($_POST['sub_specialism2'] ?? ''));

  // Language skills (simple comma-separated text)
  $languages = trim((string)($_POST['languages'] ?? ''));

  // Travel preference (checkbox + countries)
  $can_travel_abroad = isset($_POST['can_travel_abroad']) ? 1 : 0;
  $travel_anywhere = isset($_POST['travel_anywhere']) ? 1 : 0;
  $travel_countries = $_POST['travel_countries'] ?? [];
  if (!is_array($travel_countries)) $travel_countries = [];
  $travel_countries = array_values(array_unique(array_filter(array_map('trim', $travel_countries))));
  $travel_countries_json = $travel_countries ? json_encode($travel_countries) : null;

  // ---------------- Validation ----------------
  $errors = [];

  if ($base_country === '') $errors[] = 'Country is required.';

  $cc = strtoupper(preg_replace('/[^A-Z]/', '', $base_country));
  $isUSA = in_array($cc, ['USA','US','UNITEDSTATES','UNITEDSTATESOFAMERICA'], true);

  // City required except when USA + State selected
  $cityRequired = !($isUSA && $base_state !== '');
  if ($cityRequired && $base_city === '') $errors[] = 'City is required.';

  if ($isUSA && $base_state === '') $errors[] = 'State is required when Country is USA.';

  if ($specialism === '') $errors[] = 'Specialism is required.';

  // Specialism 1: sub required only if subs exist
  $spec1HasSubs = ($specialism !== '' && !empty($subsBySpecialism[$specialism]));
  if (!$spec1HasSubs) {
    $sub_specialism = '';
  } else {
    if ($sub_specialism === '') $errors[] = 'Sub-specialism is required for the selected specialism.';
  }

  // Specialism 2 (optional): same rules
  if ($specialism2 === '') {
    $sub_specialism2 = '';
  } else {
    // prevent duplicates (quietly drop)
    if ($specialism2 === $specialism) {
      $specialism2 = '';
      $sub_specialism2 = '';
    } else {
      $spec2HasSubs = !empty($subsBySpecialism[$specialism2]);
      if (!$spec2HasSubs) {
        $sub_specialism2 = '';
      } else {
        if ($sub_specialism2 === '') $errors[] = 'Sub-specialism 2 is required for the selected specialism.';
      }
    }
  }

  if ($can_travel_abroad === 1) {
    if ($travel_anywhere !== 1 && count($travel_countries) === 0) {
      $errors[] = 'Select at least one country, or choose Travel anywhere.';
    }
  } else {
    // If not travelling internationally, clear dependent fields
    $travel_anywhere = 0;
    $travel_countries_json = null;
  }

  // Validate specialism pairs (prevents tampering)
  if ($specialism !== '') {
    if ($spec1HasSubs) {
      $ok1 = db_fetch_value(
        "SELECT COUNT(*) FROM specialisms WHERE specialism=? AND sub_specialism=?",
        [$specialism, $sub_specialism]
      );
      if ((int)$ok1 <= 0) $errors[] = 'Invalid specialism selection.';
    } else {
      $ok1 = db_fetch_value(
        "SELECT COUNT(*) FROM specialisms WHERE specialism=?",
        [$specialism]
      );
      if ((int)$ok1 <= 0) $errors[] = 'Invalid specialism selection.';
    }
  }

  if ($specialism2 !== '') {
    $spec2HasSubs = !empty($subsBySpecialism[$specialism2]);
    if ($spec2HasSubs) {
      $ok2 = db_fetch_value(
        "SELECT COUNT(*) FROM specialisms WHERE specialism=? AND sub_specialism=?",
        [$specialism2, $sub_specialism2]
      );
      if ((int)$ok2 <= 0) $errors[] = 'Invalid specialism 2 selection.';
    } else {
      $ok2 = db_fetch_value(
        "SELECT COUNT(*) FROM specialisms WHERE specialism=?",
        [$specialism2]
      );
      if ((int)$ok2 <= 0) $errors[] = 'Invalid specialism 2 selection.';
    }
  }

  if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect('/p/profile_details.php#base-location');
  }

  // ---------- Task skills (save) ----------
  $skillIds = $_POST['skill_ids'] ?? [];
  if (!is_array($skillIds)) $skillIds = [];
  $skillIds = array_values(array_unique(array_filter(array_map('intval', $skillIds))));

  if ($skillIds) {
    // Keep only valid, active task activity IDs
    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
    $validIds = db_fetch_all("
      SELECT id
      FROM task_activities
      WHERE is_active=1 AND id IN ($placeholders)
    ", $skillIds);

    $skillIds = array_map('intval', array_column($validIds, 'id'));
  }

  db_query("DELETE FROM paralegal_skills WHERE user_id=?", [$pid]);

  if ($skillIds) {
    $values = [];
    $params = [];
    foreach ($skillIds as $sid) {
      $values[] = "(?,?)";
      $params[] = $pid;
      $params[] = $sid; // task_activities.id
    }
    db_query(
      "INSERT INTO paralegal_skills (user_id, skill_id) VALUES ".implode(',', $values),
      $params
    );
  }
  // ---------- /Task skills (save) ----------

  // Uploads
  $uploadErrors = [];
  $savedPaths = []; // column => relative path

  $uploadMap = [
    'cv_file'      => ['col' => 'cv_path',            'label' => 'CV'],
    'id_file'      => ['col' => 'id_doc_path',        'label' => 'Passport / ID'],
    'visa_file'    => ['col' => 'visa_path',          'label' => 'E-visa'],
    'utility_file' => ['col' => 'utility_bill_path', 'label' => 'Utility bill evidence'],
  ];

  $baseDir = realpath(__DIR__.'/..');
  $destRoot = $baseDir ? ($baseDir.'/uploads/paralegal_docs/'.$pid) : null;

  if ($destRoot && !is_dir($destRoot)) {
    @mkdir($destRoot, 0775, true);
  }

  foreach ($uploadMap as $field => $meta) {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) continue;
    if ((int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;

    $err = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
    if ($err !== UPLOAD_ERR_OK) {
      $uploadErrors[] = $meta['label']." upload failed.";
      continue;
    }

    if (!$destRoot || !is_dir($destRoot)) {
      $uploadErrors[] = "Upload folder is not writable on the server.";
      break;
    }

    $tmp = (string)$_FILES[$field]['tmp_name'];
    $orig = (string)$_FILES[$field]['name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if ($ext === '' || !preg_match('/^[a-z0-9]{1,6}$/', $ext)) $ext = 'dat';

    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
    if ($safeBase === '') $safeBase = 'file';

    $fname = $safeBase.'_'.date('Ymd_His').'.'.$ext;
    $destAbs = $destRoot.'/'.$fname;

    if (!move_uploaded_file($tmp, $destAbs)) {
      $uploadErrors[] = $meta['label']." upload could not be saved.";
      continue;
    }

    $rel = '/uploads/paralegal_docs/'.$pid.'/'.$fname;
    $savedPaths[$meta['col']] = $rel;
  }

  if ($uploadErrors) {
    flash(implode(' ', $uploadErrors), 'error');
    redirect('/p/profile_details.php');
  }

  // Build an UPDATE with only columns that exist
  $set = [];
  $params = [];

	  // Bank details (only update if columns exist)
  if (db_has_column('paralegal_profiles', 'bank_name')) {
    $set[] = "bank_name=?";
    $params[] = ($bank_name !== '' ? $bank_name : null);
  }
  if (db_has_column('paralegal_profiles', 'account_name')) {
    $set[] = "account_name=?";
    $params[] = ($account_name !== '' ? $account_name : null);
  }
  if (db_has_column('paralegal_profiles', 'account_no')) {
    $set[] = "account_no=?";
    $params[] = ($account_no !== '' ? $account_no : null);
  }
  if (db_has_column('paralegal_profiles', 'sort_code')) {
    $set[] = "sort_code=?";
    $params[] = ($sort_code !== '' ? $sort_code : null);
  }
	
  if (db_has_column('paralegal_profiles', 'work_mode')) {
    $set[] = "work_mode=?";
    $params[] = $work_mode;
  }

  if (db_has_column('paralegal_profiles', 'weekend_available')) {
    $set[] = "weekend_available=?";
    $params[] = $weekend_available;
  }

  if (db_has_column('paralegal_profiles', 'night_available')) {
    $set[] = "night_available=?";
    $params[] = $night_available;
  }

  if (db_has_column('paralegal_profiles', 'available_from')) {
    $set[] = "available_from=?";
    $params[] = $available_from;
  }

  if (db_has_column('paralegal_profiles', 'available_to')) {
    $set[] = "available_to=?";
    $params[] = $available_to;
  }

  // Base location
  if (db_has_column('paralegal_profiles', 'base_country')) {
    $set[] = "base_country=?";
    $params[] = ($base_country !== '' ? $base_country : null);
  }
  if (db_has_column('paralegal_profiles', 'base_city')) {
    $set[] = "base_city=?";
    $params[] = ($base_city !== '' ? $base_city : null);
  }
  if (db_has_column('paralegal_profiles', 'base_state')) {
    $set[] = "base_state=?";
    $params[] = ($base_state !== '' ? $base_state : null);
  }
  if (db_has_column('paralegal_profiles', 'base_postcode')) {
    $set[] = "base_postcode=?";
    $params[] = ($base_postcode !== '' ? $base_postcode : null);
  }
  if (db_has_column('paralegal_profiles', 'base_address1')) {
    $set[] = "base_address1=?";
    $params[] = ($base_address1 !== '' ? $base_address1 : null);
  }
  if (db_has_column('paralegal_profiles', 'base_address2')) {
    $set[] = "base_address2=?";
    $params[] = ($base_address2 !== '' ? $base_address2 : null);
  }

  // Specialism 1 + 2
  if (db_has_column('paralegal_profiles', 'specialism')) {
    $set[] = "specialism=?";
    $params[] = ($specialism !== '' ? $specialism : null);
  }
  if (db_has_column('paralegal_profiles', 'sub_specialism')) {
    $set[] = "sub_specialism=?";
    $params[] = ($sub_specialism !== '' ? $sub_specialism : null);
  }
  if (db_has_column('paralegal_profiles', 'specialism2')) {
    $set[] = "specialism2=?";
    $params[] = ($specialism2 !== '' ? $specialism2 : null);
  }
  if (db_has_column('paralegal_profiles', 'sub_specialism2')) {
    $set[] = "sub_specialism2=?";
    $params[] = ($sub_specialism2 !== '' ? $sub_specialism2 : null);
  }

  // Weekly availability + languages (columns exist in your schema)
  $set[] = "weekly_availability=?";
  $params[] = json_encode($newAvailability);

  $set[] = "languages=?";
  $params[] = ($languages !== '' ? $languages : null);

  if (db_has_column('paralegal_profiles', 'can_travel_abroad')) {
    $set[] = "can_travel_abroad=?";
    $params[] = $can_travel_abroad;
  }

  if (db_has_column('paralegal_profiles', 'travel_anywhere')) {
    $set[] = "travel_anywhere=?";
    $params[] = (int)$travel_anywhere;
  }

  if (db_has_column('paralegal_profiles', 'travel_countries')) {
    $set[] = "travel_countries=?";
    $params[] = $travel_countries_json;
  }

  foreach ($savedPaths as $col => $path) {
    if (db_has_column('paralegal_profiles', $col)) {
      $set[] = "{$col}=?";
      $params[] = $path;
    }
  }

  if ($set) {
    $params[] = $pid;
    db_query("UPDATE paralegal_profiles SET ".implode(',', $set)." WHERE user_id=?", $params);
  }

  flash('Profile details saved.', 'success');
  redirect('/p/profile_details.php');
}

render('paralegal/profile_details', compact(
  'title','profile','days','availability',
  'skillsByCategory','selectedSkillIds',
  'specialismOptions','subsBySpecialism'
));
