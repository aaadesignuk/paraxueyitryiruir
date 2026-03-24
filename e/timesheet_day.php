<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Timesheet (Day Review)';
$eid = (int)auth_user()['user_id'];

$job_id = (int)($_GET['job_id'] ?? 0);
$paralegal_id = (int)($_GET['paralegal_id'] ?? 0);
$work_date = (string)($_GET['date'] ?? '');

if ($job_id <= 0 || $paralegal_id <= 0 || $work_date === '') {
  flash('Invalid timesheet link.', 'error');
  redirect('/e/timesheets.php');
  exit;
}

// Confirm job belongs to employer
$job = db_fetch_one("SELECT * FROM jobs WHERE job_id=? AND employer_id=? LIMIT 1", [$job_id, $eid]);
if (!$job) {
  flash('Job not found.', 'error');
  redirect('/e/dashboard.php');
  exit;
}

// Confirm assignment exists for this job + paralegal (and employer)
$assignment = db_fetch_one("
  SELECT ja.*
  FROM job_assignments ja
  WHERE ja.job_id=? AND ja.paralegal_id=? AND ja.employer_id=?
  LIMIT 1
", [$job_id, $paralegal_id, $eid]);

if (!$assignment) {
  flash('Assignment not found.', 'error');
  redirect('/e/job_view.php?job_id='.$job_id);
  exit;
}

$assignment_id = (int)$assignment['assignment_id'];

// Handle actions (Approve) at DAY level
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  // Re-read the day rows so we don't update nothing / wrong day
  $day_rows = db_fetch_all("
    SELECT t.timesheet_id, t.status
    FROM timesheets t
    WHERE t.assignment_id=? AND t.work_date=?
      AND t.status <> 'Draft'
    ORDER BY t.timesheet_id ASC
  ", [$assignment_id, $work_date]);

  if (!$day_rows) {
    flash('No timesheets found for that day.', 'error');
    redirect('/e/timesheets.php');
    exit;
  }

  $now = date('Y-m-d H:i:s');

  if ($action === 'approve') {
    // Approve all SUBMITTED rows for that day.
    // IMPORTANT: Do NOT override queried (Rejected) lines.
    db_query("
      UPDATE timesheets
      SET status='Approved', reviewed_at=?
      WHERE assignment_id=? AND work_date=? AND status='Submitted'
    ", [$now, $assignment_id, $work_date]);

    flash('Day approved.', 'success');
    redirect('/e/timesheet_day.php?job_id='.$job_id.'&paralegal_id='.$paralegal_id.'&date='.urlencode($work_date));
    exit;
  }

  flash('Invalid action.', 'error');
  redirect('/e/timesheet_day.php?job_id='.$job_id.'&paralegal_id='.$paralegal_id.'&date='.urlencode($work_date));
  exit;
}

// Fetch the session rows (timesheets) for this day
$rows = db_fetch_all("
  SELECT
    t.timesheet_id,
    t.work_date,
    t.hours_worked,
    t.description,
    t.work_type,
    t.start_time,
    t.end_time,
    t.status,
    t.submitted_at,
    t.reviewed_at
  FROM timesheets t
  WHERE t.assignment_id=? AND t.work_date=?
    AND t.status <> 'Draft'
  ORDER BY t.start_time IS NULL, t.start_time ASC, t.timesheet_id ASC
", [$assignment_id, $work_date]);

if (!$rows) {
  flash('No timesheets found for that day.', 'error');
  redirect('/e/timesheets.php');
  exit;
}

$paralegal_name = (string)db_fetch_value("SELECT full_name FROM users WHERE user_id=? LIMIT 1", [$paralegal_id]);

// Day totals + derived status (same logic as job_view.php)
$total_hours = 0.0;
$queried_count = 0;
$submitted_count = 0;
$deemed_count = 0;
$has_submitted_rows = false;

foreach ($rows as $r) {
  $total_hours += (float)$r['hours_worked'];
  if ($r['status'] === 'Rejected') $queried_count++;
  if ($r['status'] === 'Submitted') { $submitted_count++; $has_submitted_rows = true; }
  if ($r['status'] === 'Deemed Approved') $deemed_count++;
}

if ($queried_count > 0) $day_status = 'Queried';
elseif ($submitted_count > 0) $day_status = 'Submitted';
else $day_status = ($deemed_count > 0) ? 'Deemed Approved' : 'Approved';

$total_hours = round($total_hours, 2);

// Disputes for these timesheet rows (if table exists / matches)
$timesheet_ids = array_map(fn($r) => (int)$r['timesheet_id'], $rows);
$disputes = [];
if ($timesheet_ids) {
  $in = implode(',', array_fill(0, count($timesheet_ids), '?'));
 
// Safe read: only assumes columns we’ve already seen used in job_view join (timesheet_id, status)
$disputes = db_fetch_all("
  SELECT *
  FROM timesheet_disputes
  WHERE timesheet_id IN ($in)
  ORDER BY created_at DESC, dispute_id DESC
", $timesheet_ids);
}

render('employer/timesheet_day', compact(
  'title',
  'job',
  'job_id',
  'paralegal_id',
  'paralegal_name',
  'work_date',
  'day_status',
  'total_hours',
  'rows',
  'disputes',
  'has_submitted_rows'
));