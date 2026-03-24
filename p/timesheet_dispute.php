<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Timesheet Dispute';
$uid = (int)auth_user()['user_id'];

$timesheet_id = (int)($_GET['timesheet_id'] ?? 0);
if ($timesheet_id <= 0) {
  http_response_code(400);
  exit('Invalid request (missing timesheet_id).');
}

// Load timesheet + ownership + employer/job context
$ts = db_fetch_one("
  SELECT t.timesheet_id, t.assignment_id, t.work_date, t.hours_worked, t.description, t.status,
         ja.paralegal_id,
         j.title AS job_title, j.employer_id,
         eu.full_name AS employer_name
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users eu ON eu.user_id = j.employer_id
  WHERE t.timesheet_id = ? AND ja.paralegal_id = ?
  LIMIT 1
", [$timesheet_id, $uid]);

if (!$ts) {
  http_response_code(404);
  exit('Timesheet not found.');
}

// Only queried timesheets can be disputed (Queried == Rejected in DB)
if ((string)($ts['status'] ?? '') !== 'Rejected') {
  flash('Only queried timesheets can be disputed.', 'error');
  redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
  exit;
}

$assignment_id = (int)$ts['assignment_id'];

// Query note (why it was queried)
$query_note = (string)db_fetch_value("
  SELECT reason
  FROM timesheet_queries
  WHERE timesheet_id=?
  ORDER BY created_at DESC
  LIMIT 1
", [$timesheet_id]);
$query_note = trim($query_note);

// Existing open dispute?
$open_dispute = null;
try {
  $open_dispute = db_fetch_one("
    SELECT *
    FROM timesheet_disputes
    WHERE timesheet_id=? AND status='Open'
    ORDER BY created_at DESC
    LIMIT 1
  ", [$timesheet_id]);
} catch (Throwable $e) {
  flash('Disputes table is missing. Please apply the DB change first.', 'error');
  redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($open_dispute) {
    flash('A dispute is already open for this timesheet.', 'error');
    redirect('/p/timesheet_dispute.php?timesheet_id='.(int)$timesheet_id);
    exit;
  }

  $dispute_text = trim((string)($_POST['dispute_text'] ?? ''));
  if (mb_strlen($dispute_text) < 10) {
    flash('Please enter a clear appeal (at least 10 characters).', 'error');
    redirect('/p/timesheet_dispute.php?timesheet_id='.(int)$timesheet_id);
    exit;
  }

  db_execute("
    INSERT INTO timesheet_disputes
      (timesheet_id, employer_id, paralegal_id, assignment_id, dispute_text, status)
    VALUES
      (?, ?, ?, ?, ?, 'Open')
  ", [
    (int)$timesheet_id,
    (int)$ts['employer_id'],
    (int)$uid,
    (int)$assignment_id,
    $dispute_text
  ]);

  flash('Dispute raised to admin.', 'success');
  redirect('/p/assignment.php?id='.(int)$assignment_id.'#timesheets');
  exit;
}

render('paralegal/timesheet_dispute', compact(
  'title','ts','timesheet_id','assignment_id','query_note','open_dispute'
));