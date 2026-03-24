<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$eid = (int)auth_user()['user_id'];

$timesheet_id = (int)($_POST['timesheet_id'] ?? 0);
$job_id = (int)($_POST['job_id'] ?? 0);
$paralegal_id = (int)($_POST['paralegal_id'] ?? 0);
$work_date = (string)($_POST['date'] ?? '');
$reason = trim((string)($_POST['reason'] ?? ''));

$back = '/e/timesheet_day.php?job_id='.$job_id.'&paralegal_id='.$paralegal_id.'&date='.urlencode($work_date);

if ($timesheet_id <= 0 || $job_id <= 0 || $paralegal_id <= 0 || $work_date === '') {
  flash('Invalid request.', 'error');
  redirect('/e/timesheets.php');
  exit;
}

if (mb_strlen($reason) < 3) {
  flash('Query reason is required.', 'error');
  redirect($back);
  exit;
}

// Ensure employer owns job + timesheet belongs to that job/date/paralegal.
$row = db_fetch_one(
  "SELECT t.timesheet_id, t.status, t.work_date, ja.job_id, ja.employer_id, ja.paralegal_id
     FROM timesheets t
     JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
    WHERE t.timesheet_id=?
      AND ja.job_id=?
      AND ja.employer_id=?
      AND ja.paralegal_id=?
      AND t.work_date=?
    LIMIT 1",
  [$timesheet_id, $job_id, $eid, $paralegal_id, $work_date]
);

if (!$row) {
  flash('Timesheet entry not found.', 'error');
  redirect('/e/timesheets.php');
  exit;
}

if (($row['status'] ?? '') !== 'Submitted') {
  flash('Only submitted entries can be queried.', 'error');
  redirect($back);
  exit;
}

$now = date('Y-m-d H:i:s');

// Mark as "Queried" using existing enum value 'Rejected'
db_query(
  "UPDATE timesheets SET status='Rejected', reviewed_at=? WHERE timesheet_id=? LIMIT 1",
  [$now, $timesheet_id]
);

// Log a dispute row (schema-safe, like the day-level code used to do)
$dispute_cols = db_fetch_all("SHOW COLUMNS FROM timesheet_disputes");
$colnames = [];
foreach ($dispute_cols as $c) {
  $colnames[] = (string)$c['Field'];
}
$has = fn($name) => in_array($name, $colnames, true);

$fields = [];
$vals = [];

if ($has('timesheet_id')) { $fields[] = 'timesheet_id'; $vals[] = $timesheet_id; }
if ($has('job_id')) { $fields[] = 'job_id'; $vals[] = $job_id; }
if ($has('assignment_id')) {
  $aid = (int)db_fetch_value("SELECT assignment_id FROM timesheets WHERE timesheet_id=? LIMIT 1", [$timesheet_id]);
  $fields[] = 'assignment_id';
  $vals[] = $aid;
}
if ($has('employer_id')) { $fields[] = 'employer_id'; $vals[] = $eid; }
if ($has('paralegal_id')) { $fields[] = 'paralegal_id'; $vals[] = $paralegal_id; }

if ($has('dispute_text')) { $fields[] = 'dispute_text'; $vals[] = $reason; }
elseif ($has('message')) { $fields[] = 'message'; $vals[] = $reason; }
elseif ($has('comment')) { $fields[] = 'comment'; $vals[] = $reason; }
elseif ($has('notes')) { $fields[] = 'notes'; $vals[] = $reason; }

if ($has('status')) { $fields[] = 'status'; $vals[] = 'Open'; }
if ($has('created_at')) { $fields[] = 'created_at'; $vals[] = $now; }

if (count($fields) >= 2) {
  $sql = "INSERT INTO timesheet_disputes (".implode(',', $fields).") VALUES (".implode(',', array_fill(0, count($fields), '?')).")";
  db_query($sql, $vals);
}

// Notification is generated on the paralegal dashboard from timesheet_queries.

flash('Entry queried.', 'success');
redirect($back);
