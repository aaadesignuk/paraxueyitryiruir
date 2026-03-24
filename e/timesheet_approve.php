<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$eid = (int)auth_user()['user_id'];
$timesheet_id = (int)($_POST['timesheet_id'] ?? 0);
$job_id = (int)($_POST['job_id'] ?? 0);

if (!$timesheet_id || !$job_id) {
  flash('Invalid request.', 'error');
  redirect('/e/dashboard.php');
}

// Ensure employer owns job + timesheet belongs to that job
$row = db_fetch_one("
  SELECT t.timesheet_id, t.status, ja.job_id, ja.employer_id
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  WHERE t.timesheet_id=? AND ja.job_id=? AND ja.employer_id=?
  LIMIT 1
", [$timesheet_id, $job_id, $eid]);

if (!$row) {
  flash('Timesheet not found.', 'error');
  redirect('/e/dashboard.php');
}

if (($row['status'] ?? '') !== 'Submitted') {
  flash('Only submitted timesheets can be approved.', 'error');
  redirect('/e/job_view.php?job_id='.$job_id.'&timesheet_id='.$timesheet_id.'#timesheets');
}

db_execute("UPDATE timesheets SET status='Approved', reviewed_at=NOW() WHERE timesheet_id=? LIMIT 1", [$timesheet_id]);


flash('Timesheet approved.', 'success');
redirect('/e/job_view.php?job_id='.$job_id.'&timesheet_id='.$timesheet_id.'#timesheets');
