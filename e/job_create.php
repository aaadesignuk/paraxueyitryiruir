<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Create Job';
$eid = (int)auth_user()['user_id'];

function table_columns($table) {
  $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
  $rows = db_fetch_all("SHOW COLUMNS FROM `{$table}`");
  return array_map(fn($r) => $r['Field'], $rows);
}

$job_cols = table_columns('jobs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $title_ = trim($_POST['title'] ?? '');
  $client_ref = trim($_POST['client_ref'] ?? '');
  $client_ref = ($client_ref !== '' ? $client_ref : null);

  $sp   = trim($_POST['specialism'] ?? '');
  $sub  = trim($_POST['sub_specialism'] ?? '');
  $desc = trim($_POST['description'] ?? '');

  $job_type = (($_POST['job_type'] ?? 'Hours') === 'Days') ? 'Days' : 'Hours';
  $hours_required = ($_POST['hours_required'] ?? '') !== '' ? (float)$_POST['hours_required'] : null;
  $days_required  = ($_POST['days_required'] ?? '') !== '' ? (float)$_POST['days_required'] : null;
  $deadline = trim($_POST['deadline'] ?? '');
  $deadline = $deadline !== '' ? $deadline : null;
  $max = ($_POST['rate_amount'] ?? '') !== '' ? (float)$_POST['rate_amount'] : null;

  // ===== Work Location =====
  $work_mode = trim($_POST['work_mode'] ?? '');
  $job_country = trim($_POST['job_country'] ?? '');
  $job_country_other = trim($_POST['job_country_other'] ?? '');
  $job_city = trim($_POST['job_city'] ?? '');

  if ($job_country === 'Other' && $job_country_other !== '') {
    $job_country = $job_country_other;
  }

  // ===== Travel =====
  $travel_required = !empty($_POST['travel_required']) ? 1 : 0;
  $travel_country  = trim($_POST['travel_country'] ?? '');
  $travel_city     = trim($_POST['travel_city'] ?? '');
  $travel_days     = ($_POST['travel_days'] ?? '') !== '' ? (int)$_POST['travel_days'] : null;
  $travel_budget   = ($_POST['travel_budget'] ?? '') !== '' ? (float)$_POST['travel_budget'] : null;

  $errors = [];

  if ($title_ === '') $errors[] = 'Title is required.';
  if ($sp === '') $errors[] = 'Specialism is required.';
  if ($work_mode === '') $errors[] = 'Work mode is required.';

  if (in_array($work_mode, ['On-site','Hybrid'], true) && $job_country === '') {
    $errors[] = 'Country is required for on-site or hybrid work.';
  }

  if (!$errors) {

    $data = [
      'employer_id'    => $eid,
      'title'          => $title_,
      'specialism'     => $sp,
      'sub_specialism' => ($sub !== '' ? $sub : null),
      'description'    => $desc,
      'max_rate'       => $max,

      // Work Location
      'work_mode'      => $work_mode,
      'job_country'    => ($job_country !== '' ? $job_country : null),
      'job_city'       => ($job_city !== '' ? $job_city : null),

      // Travel
      'travel_required'=> $travel_required,
      'travel_country' => ($travel_country !== '' ? $travel_country : null),
      'travel_city'    => ($travel_city !== '' ? $travel_city : null),
      'travel_days'    => $travel_days,
      'travel_budget'  => $travel_budget,
    ];

    // Optional schema-safe fields
    if (in_array('client_ref', $job_cols, true))     $data['client_ref'] = $client_ref;
    if (in_array('job_type', $job_cols, true))       $data['job_type'] = $job_type;
    if (in_array('hours_required', $job_cols, true)) $data['hours_required'] = $hours_required;
    if (in_array('days_required', $job_cols, true))  $data['days_required'] = $days_required;
    if (in_array('deadline', $job_cols, true))       $data['deadline'] = $deadline;
    if (in_array('status', $job_cols, true))         $data['status'] = 'Open';
    if (in_array('created_at', $job_cols, true))     $data['created_at'] = date('Y-m-d H:i:s');

    $fields = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = "INSERT INTO jobs (`" . implode('`,`', $fields) . "`) VALUES ({$placeholders})";

    db_query($sql, array_values($data));
    $jid = (int)db()->lastInsertId();

    // Save task requirements
    $activity_ids = $_POST['activities'] ?? [];
    if (is_array($activity_ids)) {
      foreach ($activity_ids as $aid) {
        $aid = (int)$aid;
        if ($aid > 0) {
          db_query(
            "INSERT IGNORE INTO job_task_requirements (job_id, activity_id, required)
             VALUES (?, ?, 1)",
            [$jid, $aid]
          );
        }
      }
    }

    flash('Job created successfully. You can now review paralegals and send an invite.', 'success');
    redirect('/e/job_view.php?job_id=' . $jid);
  }

  flash(implode(' ', $errors), 'error');
}

$specialisms = array_map(
  fn($r) => $r['specialism'],
  db_fetch_all("SELECT DISTINCT specialism FROM specialisms ORDER BY specialism")
);

$task_categories = db_fetch_all("SELECT id, name FROM task_categories ORDER BY COALESCE(sort_order, 9999), id");
$task_activities = db_fetch_all("SELECT id, category_id, name FROM task_activities WHERE is_active=1 ORDER BY category_id, COALESCE(sort_order, 9999), id");

render('employer/job_create', compact('title', 'specialisms', 'task_categories', 'task_activities'));