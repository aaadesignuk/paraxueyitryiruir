<?php
// /tools/seed_sample_data.php
// Run once, then DELETE this file.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/bootstrap.php';

echo "<pre>";

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function get_enum_values($table, $column) {
  // Table/column are trusted internal names, but still sanitize to be safe:
  $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
  $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

  $row = db_fetch_one("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
  if (!$row) return [];

  $type = $row['Type'] ?? '';
  if (stripos($type, "enum(") !== 0) return [];

  preg_match_all("/'([^']+)'/", $type, $m);
  return $m[1] ?? [];
}


function pick_status($enumValues, $preferred) {
  foreach ($preferred as $p) {
    if (in_array($p, $enumValues, true)) return $p;
  }
  // fall back to first if any
  return $enumValues[0] ?? null;
}

function ensure_user($email, $role, $full_name) {
  $u = db_fetch_one("SELECT user_id FROM users WHERE email=? LIMIT 1", [$email]);
  if ($u) return (int)$u['user_id'];

  $hash = password_hash("Password123!", PASSWORD_BCRYPT);

  db_query("INSERT INTO users (email, password_hash, full_name, role, is_active, created_at)
            VALUES (?,?,?,?,1,NOW())",
            [$email, $hash, $full_name, $role]);

  return (int)db()->lastInsertId();
}

function ensure_paralegal_profile($uid, $specialism, $rate, $available=1) {
  $p = db_fetch_one("SELECT user_id FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$uid]);
  if ($p) {
    db_query("UPDATE paralegal_profiles SET specialism=?, preferred_rate=?, is_available=? WHERE user_id=?",
      [$specialism, $rate, $available, $uid]);
    return;
  }
  db_query("INSERT INTO paralegal_profiles (user_id, specialism, preferred_rate, is_available)
            VALUES (?,?,?,?)",
            [$uid, $specialism, $rate, $available]);
}

function ensure_employer_profile($uid, $firm) {
  $p = db_fetch_one("SELECT user_id FROM employer_profiles WHERE user_id=? LIMIT 1", [$uid]);
  if ($p) {
    db_query("UPDATE employer_profiles SET firm_name=? WHERE user_id=?", [$firm, $uid]);
    return;
  }
  db_query("INSERT INTO employer_profiles (user_id, firm_name) VALUES (?,?)", [$uid, $firm]);
}

function add_notification($user_id, $message) {
  // Use whatever columns exist - keep simple.
  // If your notifications table has different columns, adjust here.
  $cols = db_fetch_all("SHOW COLUMNS FROM notifications");
  $colNames = array_map(fn($c) => $c['Field'], $cols);

  if (in_array('message', $colNames, true)) {
    db_query("INSERT INTO notifications (user_id, message, created_at) VALUES (?,?,NOW())", [$user_id, $message]);
  } elseif (in_array('content', $colNames, true)) {
    db_query("INSERT INTO notifications (user_id, content, created_at) VALUES (?,?,NOW())", [$user_id, $message]);
  }
}

// ------------------------------------------------------------
// Determine allowed statuses
// ------------------------------------------------------------
$job_statuses = get_enum_values('jobs', 'status');
$invite_statuses = get_enum_values('job_invitations', 'status');
$timesheet_statuses = get_enum_values('timesheets', 'status');
$billing_statuses = get_enum_values('billing_records', 'status');

$JOB_OPEN   = pick_status($job_statuses, ['open','Open','draft','new']);
$JOB_ACTIVE = pick_status($job_statuses, ['active','Active','in_progress','in-progress']);
$JOB_CLOSED = pick_status($job_statuses, ['closed','Closed','complete','completed','archived']);

$INV_PENDING = pick_status($invite_statuses, ['pending','Pending','sent']);
$INV_ACCEPT  = pick_status($invite_statuses, ['accepted','Accepted']);
$INV_DECLINE = pick_status($invite_statuses, ['declined','Declined','rejected','Rejected']);

$TS_SUB  = pick_status($timesheet_statuses, ['submitted','Submitted','pending']);
$TS_APP  = pick_status($timesheet_statuses, ['approved','Approved']);
$TS_REJ  = pick_status($timesheet_statuses, ['rejected','Rejected','declined','Declined']);

$BILL_PEND = pick_status($billing_statuses, ['pending','Pending']);
$BILL_PAID = pick_status($billing_statuses, ['paid','Paid','settled','complete','completed']);

// ------------------------------------------------------------
// Create sample users
// ------------------------------------------------------------
echo "Seeding sample users...\n";

$E1 = ensure_user("employer.sample1@paralete.test", ROLE_EMPLOYER, "Sarah Employer (Sample)");
$E2 = ensure_user("employer.sample2@paralete.test", ROLE_EMPLOYER, "Tom Employer (Sample)");

$P1 = ensure_user("paralegal.sample1@paralete.test", ROLE_PARALEGAL, "James Paralegal (Sample)");
$P2 = ensure_user("paralegal.sample2@paralete.test", ROLE_PARALEGAL, "Amina Paralegal (Sample)");
$P3 = ensure_user("paralegal.sample3@paralete.test", ROLE_PARALEGAL, "Liam Paralegal (Sample)");
$P4 = ensure_user("paralegal.sample4@paralete.test", ROLE_PARALEGAL, "Chloe Paralegal (Sample)");

ensure_employer_profile($E1, "Sample & Co Solicitors");
ensure_employer_profile($E2, "Westminster Legal LLP");

$specs = db_fetch_all("SELECT DISTINCT specialism FROM specialisms ORDER BY specialism LIMIT 5");
$spec = $specs ? ($specs[0]['specialism'] ?? 'Family Law') : 'Family Law';

ensure_paralegal_profile($P1, $spec, 25, 1);
ensure_paralegal_profile($P2, $spec, 30, 1);
ensure_paralegal_profile($P3, $spec, 20, 1);
ensure_paralegal_profile($P4, $spec, 28, 0); // unavailable

echo "Users seeded.\n";

// ------------------------------------------------------------
// Create jobs
// ------------------------------------------------------------
echo "Creating jobs...\n";

$sub = db_fetch_value("SELECT sub_specialism FROM specialisms WHERE specialism=? LIMIT 1", [$spec]) ?: "General";

// --- Schema-aware JOB creator (works even if jobs table is missing newer columns) ---
function table_columns($table) {
  $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
  $rows = db_fetch_all("SHOW COLUMNS FROM `{$table}`");
  return array_map(fn($r) => $r['Field'], $rows);
}

function create_job($employer_id, $title, $spec, $sub, $status, $max_rate, $desc, $job_type='hours', $hours_required=6, $onsite=0) {
  static $cols = null;
  if ($cols === null) $cols = table_columns('jobs');

  // Base columns (most likely exist)
  $data = [
    'employer_id'     => $employer_id,
    'title'           => $title,
    'specialism'      => $spec,
    'sub_specialism'  => $sub,
    'description'     => $desc,
    'max_rate'        => $max_rate,
    'status'          => $status,
  ];

  // Optional columns (only include if they exist in your live schema)
  if (in_array('job_type', $cols, true))       $data['job_type'] = $job_type;
  if (in_array('hours_required', $cols, true)) $data['hours_required'] = $hours_required;
  if (in_array('days_required', $cols, true))  $data['days_required'] = null;
  if (in_array('is_onsite', $cols, true))      $data['is_onsite'] = (int)$onsite;
  if (in_array('deadline', $cols, true))       $data['deadline'] = null;

  // created_at column varies by schema
  if (in_array('created_at', $cols, true)) {
    $data['created_at'] = date('Y-m-d H:i:s');
  }

  $fields = array_keys($data);
  $placeholders = implode(',', array_fill(0, count($fields), '?'));
  $sql = "INSERT INTO jobs (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

  db_query($sql, array_values($data));
  return (int)db()->lastInsertId();
}

// Create sample jobs with mixed "onsite" values (will be ignored if schema doesn't support it)
$j1 = create_job($E1, "Test Job (Open)", $spec, $sub, $GLOBALS['JOB_OPEN'], 35, "Drafting support — sample open job.", 'hours', 6, 0);
$j2 = create_job($E1, "Case Bundle Prep (Open)", $spec, $sub, $GLOBALS['JOB_OPEN'], 28, "Bundling and indexing — sample open job.", 'hours', 4, 1);
$j3 = create_job($E2, "Active Assignment Example", $spec, $sub, ($GLOBALS['JOB_ACTIVE'] ?: $GLOBALS['JOB_OPEN']), 30, "Active assignment with timesheets + billing.", 'hours', 10, 0);

echo "Jobs created: $j1, $j2, $j3\n";


// ------------------------------------------------------------
// Invitations + assignment
// ------------------------------------------------------------
echo "Creating invitations...\n";

function invite($job_id, $employer_id, $paralegal_id, $status) {
  static $cols = null;
  if ($cols === null) $cols = table_columns('job_invitations');

  $data = [
    'job_id'       => $job_id,
    'paralegal_id' => $paralegal_id,
    'status'       => $status,
  ];

  // If employer_id exists, include it (required by your FK)
  if (in_array('employer_id', $cols, true)) {
    $data['employer_id'] = $employer_id;
  }

  // created_at varies by schema
  if (in_array('created_at', $cols, true)) {
    $data['created_at'] = date('Y-m-d H:i:s');
  }

  $fields = array_keys($data);
  $placeholders = implode(',', array_fill(0, count($fields), '?'));
  $sql = "INSERT INTO job_invitations (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

  db_query($sql, array_values($data));
}


if ($GLOBALS['INV_PENDING']) invite($j1, $E1, $GLOBALS['P1'], $GLOBALS['INV_PENDING']);
if ($GLOBALS['INV_ACCEPT'])  invite($j1, $E1, $GLOBALS['P2'], $GLOBALS['INV_ACCEPT']);
if ($GLOBALS['INV_DECLINE']) invite($j1, $E1, $GLOBALS['P3'], $GLOBALS['INV_DECLINE']);

if ($GLOBALS['INV_PENDING']) invite($j2, $E1, $GLOBALS['P2'], $GLOBALS['INV_PENDING']);
if ($GLOBALS['INV_PENDING']) invite($j2, $E1, $GLOBALS['P3'], $GLOBALS['INV_PENDING']);


echo "Invites created.\n";

// Assignment for active job
// Assignment for active job

echo "Creating assignment...\n";

$ja_cols = table_columns('job_assignments');

$ja_data = [
  'job_id'       => $j3,
  'employer_id'  => $E2,
  'paralegal_id' => $P1,
];

if (in_array('status', $ja_cols, true)) {
  $ja_data['status'] = 'active';
}

if (in_array('created_at', $ja_cols, true)) {
  $ja_data['created_at'] = date('Y-m-d H:i:s');
}

$fields = array_keys($ja_data);
$placeholders = implode(',', array_fill(0, count($fields), '?'));
$sql = "INSERT INTO job_assignments (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

db_query($sql, array_values($ja_data));
$assignment_id = (int)db()->lastInsertId();

echo "Assignment created: $assignment_id\n";


// ------------------------------------------------------------
// Timesheets + billing
// ------------------------------------------------------------
echo "Creating timesheets...\n";

function add_timesheet($assignment_id, $work_date, $hours, $status) {
  static $cols = null;
  if ($cols === null) $cols = table_columns('timesheets');

  $data = [
    'assignment_id' => $assignment_id,
  ];

  // Date column differs by schema
  if (in_array('work_date', $cols, true)) {
    $data['work_date'] = $work_date;
  } elseif (in_array('date', $cols, true)) {
    $data['date'] = $work_date;
  }

  // Hours column differs by schema
  if (in_array('hours_worked', $cols, true)) {
    $data['hours_worked'] = $hours;
  } elseif (in_array('hours', $cols, true)) {
    $data['hours'] = $hours;
  } elseif (in_array('total_hours', $cols, true)) {
    $data['total_hours'] = $hours;
  }

  // Notes/description column differs by schema
  if (in_array('notes', $cols, true)) {
    $data['notes'] = 'Sample work entry';
  } elseif (in_array('description', $cols, true)) {
    $data['description'] = 'Sample work entry';
  } elseif (in_array('details', $cols, true)) {
    $data['details'] = 'Sample work entry';
  }

  // Status column may exist
  if (in_array('status', $cols, true) && $status !== null) {
    $data['status'] = $status;
  }

  // created_at column may exist
  if (in_array('created_at', $cols, true)) {
    $data['created_at'] = date('Y-m-d H:i:s');
  }

  $fields = array_keys($data);
  $placeholders = implode(',', array_fill(0, count($fields), '?'));
  $sql = "INSERT INTO timesheets (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

  db_query($sql, array_values($data));
  return (int)db()->lastInsertId();
}


$today = date('Y-m-d');
$yday  = date('Y-m-d', strtotime('-1 day'));

$ts1 = $GLOBALS['TS_SUB'] ? add_timesheet($assignment_id, $today, 2.5, $GLOBALS['TS_SUB']) : null;
$ts2 = $GLOBALS['TS_APP'] ? add_timesheet($assignment_id, $yday, 3.0, $GLOBALS['TS_APP']) : null;
$ts3 = $GLOBALS['TS_REJ'] ? add_timesheet($assignment_id, date('Y-m-d', strtotime('-2 day')), 1.5, $GLOBALS['TS_REJ']) : null;

echo "Timesheets created: " . json_encode([$ts1,$ts2,$ts3]) . "\n";

// Billing record (for approved timesheet)
echo "Creating billing record...\n";

$rate = 30.00;
$approved_hours = 3.0;
$gross = $rate * $approved_hours;
$commission_pct = defined('PLATFORM_COMMISSION_PCT') ? PLATFORM_COMMISSION_PCT : 20;
$commission = round($gross * ($commission_pct/100), 2);
$net = $gross - $commission;

$bill_status = $GLOBALS['BILL_PEND'] ?: ($GLOBALS['billing_statuses'][0] ?? 'pending');

echo "Creating billing record...\n";

$br_cols = table_columns('billing_records');

// Calculate financials (still useful even if schema stores different fields)
$rate = 30.00;
$approved_hours = 3.0;
$gross = $rate * $approved_hours;

$commission_pct = defined('PLATFORM_COMMISSION_PCT') ? PLATFORM_COMMISSION_PCT : 20;
$commission = round($gross * ($commission_pct/100), 2);
$net = $gross - $commission;

$bill_status = $GLOBALS['BILL_PEND'] ?: ($GLOBALS['billing_statuses'][0] ?? null);

// Build data dynamically based on existing columns
$br_data = [
  'assignment_id' => $assignment_id,
];

// hours column name differs
if (in_array('total_hours', $br_cols, true)) {
  $br_data['total_hours'] = $approved_hours;
} elseif (in_array('hours', $br_cols, true)) {
  $br_data['hours'] = $approved_hours;
}

// rate column name differs
if (in_array('hourly_rate', $br_cols, true)) {
  $br_data['hourly_rate'] = $rate;
} elseif (in_array('rate', $br_cols, true)) {
  $br_data['rate'] = $rate;
} elseif (in_array('unit_rate', $br_cols, true)) {
  $br_data['unit_rate'] = $rate;
}

// gross amount column differs
if (in_array('gross_amount', $br_cols, true)) {
  $br_data['gross_amount'] = $gross;
} elseif (in_array('total_amount', $br_cols, true)) {
  $br_data['total_amount'] = $gross;
} elseif (in_array('amount', $br_cols, true)) {
  $br_data['amount'] = $gross;
}

// commission pct / amount columns differ
if (in_array('commission_pct', $br_cols, true)) {
  $br_data['commission_pct'] = $commission_pct;
} elseif (in_array('commission_percent', $br_cols, true)) {
  $br_data['commission_percent'] = $commission_pct;
}

if (in_array('commission_amount', $br_cols, true)) {
  $br_data['commission_amount'] = $commission;
} elseif (in_array('commission', $br_cols, true)) {
  $br_data['commission'] = $commission;
}

// net column differs
if (in_array('net_amount', $br_cols, true)) {
  $br_data['net_amount'] = $net;
} elseif (in_array('net', $br_cols, true)) {
  $br_data['net'] = $net;
}

// status column
if ($bill_status && in_array('status', $br_cols, true)) {
  $br_data['status'] = $bill_status;
}

// created_at column
if (in_array('created_at', $br_cols, true)) {
  $br_data['created_at'] = date('Y-m-d H:i:s');
}

$fields = array_keys($br_data);
$placeholders = implode(',', array_fill(0, count($fields), '?'));
$sql = "INSERT INTO billing_records (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

db_query($sql, array_values($br_data));

$bill_id = (int)db()->lastInsertId();
echo "Billing record created: $bill_id\n";


$bill_id = (int)db()->lastInsertId();
echo "Billing record created: $bill_id\n";

// Notifications (optional)
add_notification($E2, "Sample: Timesheet submitted for approval.");
add_notification($P1, "Sample: Your timesheet was approved.");
add_notification($E1, "Sample: New match available for your job.");

echo "\nDONE ✅ Sample data inserted.\n";
echo "\nSample logins (password: Password123!)\n";
echo "- employer.sample1@paralete.test\n";
echo "- employer.sample2@paralete.test\n";
echo "- paralegal.sample1@paralete.test\n";
echo "- paralegal.sample2@paralete.test\n";
echo "- paralegal.sample3@paralete.test\n";
echo "- paralegal.sample4@paralete.test\n";
echo "</pre>";
