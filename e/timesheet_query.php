<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$eid = (int)auth_user()['user_id'];
$timesheet_id = (int)($_POST['timesheet_id'] ?? 0);
$job_id = (int)($_POST['job_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? ''));

if (!$timesheet_id || !$job_id) {
  flash('Invalid request.', 'error');
  redirect('/e/dashboard.php');
}
if (mb_strlen($reason) < 3) {
  flash('Query reason is required.', 'error');
  redirect('/e/job_view.php?job_id='.$job_id.'&timesheet_id='.$timesheet_id.'#timesheets');
}

// Ensure employer owns job + timesheet belongs to that job
$row = db_fetch_one("
  SELECT t.timesheet_id, t.status, ja.job_id, ja.employer_id, ja.paralegal_id
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
  flash('Only submitted timesheets can be queried.', 'error');
  redirect('/e/job_view.php?job_id='.$job_id.'&timesheet_id='.$timesheet_id.'#timesheets');
}

db_execute(
  "INSERT INTO timesheet_queries (timesheet_id, employer_id, reason, created_at)
   VALUES (?,?,?,NOW())",
  [$timesheet_id, $eid, $reason]
);

// Mark as "Queried" using existing enum value 'Rejected'
db_execute(
  "UPDATE timesheets SET status='Rejected', reviewed_at=NOW() WHERE timesheet_id=? LIMIT 1",
  [$timesheet_id]
);

// Notification is generated on the paralegal dashboard from timesheet_queries.

flash('Query sent to paralegal.', 'success');
redirect('/e/job_view.php?job_id='.$job_id.'&timesheet_id='.$timesheet_id.'#timesheets');
