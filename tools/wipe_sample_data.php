<?php
// /tools/wipe_sample_data.php
// Run once, then DELETE this file.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/bootstrap.php';

echo "<pre>";

// ------------------------------
// Helpers
// ------------------------------
function fetch_ids($sql, $params = []) {
  $rows = db_fetch_all($sql, $params);
  $ids = [];
  foreach ($rows as $r) {
    $ids[] = (int)array_values($r)[0];
  }
  return $ids;
}

function in_list_sql($count) {
  return implode(',', array_fill(0, $count, '?'));
}

// ------------------------------
// Identify sample users
// ------------------------------
echo "Finding sample users...\n";

$user_ids = fetch_ids("
  SELECT user_id
  FROM users
  WHERE email LIKE '%sample%@paralete.test%'
");

echo "Sample user IDs: " . json_encode($user_ids) . "\n";

// ------------------------------
// Identify sample jobs
// ------------------------------
echo "Finding sample jobs...\n";

$job_ids = fetch_ids("
  SELECT job_id
  FROM jobs
  WHERE title LIKE '%Test Job%'
     OR title LIKE '%(Sample)%'
     OR title LIKE '%(Open)%'
     OR title LIKE '%Case Bundle Prep%'
     OR title LIKE '%Active Assignment Example%'
");

echo "Sample job IDs: " . json_encode($job_ids) . "\n";

// If nothing found, exit safely
if (!$user_ids && !$job_ids) {
  echo "No sample users or sample jobs found. Nothing to wipe.\n";
  echo "</pre>";
  exit;
}

// ------------------------------
// Gather assignment IDs
// ------------------------------
$assignment_ids = [];
if ($job_ids) {
  $assignment_ids = fetch_ids(
    "SELECT assignment_id FROM job_assignments WHERE job_id IN (" . in_list_sql(count($job_ids)) . ")",
    $job_ids
  );
}
echo "Assignment IDs: " . json_encode($assignment_ids) . "\n";

// ------------------------------
// Delete billing records
// ------------------------------
if ($assignment_ids) {
  echo "Deleting billing records...\n";
  db_query(
    "DELETE FROM billing_records WHERE assignment_id IN (" . in_list_sql(count($assignment_ids)) . ")",
    $assignment_ids
  );
}

// ------------------------------
// Delete timesheets
// ------------------------------
if ($assignment_ids) {
  echo "Deleting timesheets...\n";
  db_query(
    "DELETE FROM timesheets WHERE assignment_id IN (" . in_list_sql(count($assignment_ids)) . ")",
    $assignment_ids
  );
}

// ------------------------------
// Delete job invitations
// ------------------------------
if ($job_ids) {
  echo "Deleting job invitations...\n";
  db_query(
    "DELETE FROM job_invitations WHERE job_id IN (" . in_list_sql(count($job_ids)) . ")",
    $job_ids
  );
}

// ------------------------------
// Delete job assignments
// ------------------------------
if ($assignment_ids) {
  echo "Deleting job assignments...\n";
  db_query(
    "DELETE FROM job_assignments WHERE assignment_id IN (" . in_list_sql(count($assignment_ids)) . ")",
    $assignment_ids
  );
}

// ------------------------------
// Delete notifications for sample users
// ------------------------------
if ($user_ids) {
  echo "Deleting notifications for sample users...\n";

  // Some installs may have "message" or "content". We'll try user_id only.
  db_query(
    "DELETE FROM notifications WHERE user_id IN (" . in_list_sql(count($user_ids)) . ")",
    $user_ids
  );
}

// ------------------------------
// Delete jobs (sample jobs only)
// ------------------------------
if ($job_ids) {
  echo "Deleting sample jobs...\n";
  db_query(
    "DELETE FROM jobs WHERE job_id IN (" . in_list_sql(count($job_ids)) . ")",
    $job_ids
  );
}

// ------------------------------
// Delete profiles (sample users)
// ------------------------------
if ($user_ids) {
  echo "Deleting sample profiles...\n";
  db_query(
    "DELETE FROM paralegal_profiles WHERE user_id IN (" . in_list_sql(count($user_ids)) . ")",
    $user_ids
  );
  db_query(
    "DELETE FROM employer_profiles WHERE user_id IN (" . in_list_sql(count($user_ids)) . ")",
    $user_ids
  );
}

// ------------------------------
// Delete sample users
// ------------------------------
if ($user_ids) {
  echo "Deleting sample users...\n";
  db_query(
    "DELETE FROM users WHERE user_id IN (" . in_list_sql(count($user_ids)) . ")",
    $user_ids
  );
}

echo "\nDONE ✅ Sample data wiped.\n";
echo "\nIMPORTANT: Delete this file now: /tools/wipe_sample_data.php\n";
echo "</pre>";
