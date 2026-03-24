<?php
// /e/daily_timesheet_action.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$employer_id = (int)auth_user()['user_id'];
$action = (string)($_POST['action'] ?? '');
$date = trim((string)($_POST['date'] ?? ''));
$paralegal_id = (int)($_POST['paralegal_id'] ?? 0);

if ($action !== 'approve_day' || $paralegal_id <= 0 || $date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  flash('Invalid request.', 'error');
  redirect('/e/timesheets.php');
}

// Apply deemed approvals before taking action.
if (function_exists('timesheets_apply_deemed_approval')) {
  timesheets_apply_deemed_approval();
}

// Option 1 rule: cannot approve a day that contains queried lines.
$has_queried = (int)db_fetch_value(
  "SELECT COUNT(*)
     FROM timesheets t
     JOIN job_assignments a ON a.assignment_id = t.assignment_id
    WHERE a.employer_id = ?
      AND a.paralegal_id = ?
      AND t.work_date = ?
      AND t.status = 'Rejected'",
  [$employer_id, $paralegal_id, $date]
);
if ($has_queried > 0) {
  flash('This day has queried entries. Resolve queried entries first, then approve the day.', 'error');
  redirect('/e/daily_timesheet.php?date='.urlencode($date).'&paralegal_id='.(int)$paralegal_id);
  exit;
}

// Approve all Submitted entries for this (date, paralegal) where employer matches.
$ids = db_fetch_all(
  "SELECT t.timesheet_id
     FROM timesheets t
     JOIN job_assignments a ON a.assignment_id = t.assignment_id
    WHERE a.employer_id = ?
      AND a.paralegal_id = ?
      AND t.work_date = ?
      AND t.status = 'Submitted'",
  [$employer_id, $paralegal_id, $date]
);

if (empty($ids)) {
  flash('Nothing to approve for that day.', 'error');
  redirect('/e/timesheets.php');
}

$id_list = array_map(fn($r) => (int)$r['timesheet_id'], $ids);
$placeholders = implode(',', array_fill(0, count($id_list), '?'));
$params = $id_list;

db_query(
  "UPDATE timesheets
      SET status='Approved', reviewed_at=NOW()
    WHERE timesheet_id IN ($placeholders)",
  $params
);

// Notify paralegal once (keep it simple)
notify($paralegal_id, 'Your daily timesheet for '.uk_date($date).' has been approved.');

flash('Day approved.', 'success');
redirect('/e/daily_timesheet.php?date='.urlencode($date).'&paralegal_id='.(int)$paralegal_id);