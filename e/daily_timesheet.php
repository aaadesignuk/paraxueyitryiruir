<?php
// /e/daily_timesheet.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Daily Timesheet';
$employer_id = (int)auth_user()['user_id'];

$date = trim((string)($_GET['date'] ?? ''));
$paralegal_id = (int)($_GET['paralegal_id'] ?? 0);

if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $paralegal_id <= 0) {
  flash('Invalid daily timesheet link.', 'error');
  redirect('/e/timesheets.php');
}

// Apply deemed approvals before showing.
if (function_exists('timesheets_apply_deemed_approval')) {
  timesheets_apply_deemed_approval();
}

// Verify this paralegal has an assignment under this employer (avoid leakage)
$ok = (int)db_fetch_value(
  "SELECT COUNT(*)
     FROM job_assignments
    WHERE employer_id=? AND paralegal_id=?",
  [$employer_id, $paralegal_id]
);
if ($ok <= 0) {
  flash('Not authorised.', 'error');
  redirect('/e/timesheets.php');
}

$paralegal = db_fetch_one("SELECT full_name FROM users WHERE user_id=? LIMIT 1", [$paralegal_id]);

// Fetch all entries for this day (including Approved/Deemed Approved for context)
$rows = db_fetch_all(
  "SELECT
      t.timesheet_id,
      t.assignment_id,
      t.work_date,
      t.start_time,
      t.end_time,
      t.work_type,
      t.hours_worked,
      t.description,
      t.status,
      j.title AS job_title,
      COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,'')) AS client_ref
   FROM timesheets t
   JOIN job_assignments a ON a.assignment_id = t.assignment_id
   JOIN jobs j ON j.job_id = a.job_id
   WHERE a.employer_id = ?
     AND a.paralegal_id = ?
     AND t.work_date = ?
   ORDER BY
     (t.start_time IS NULL) ASC,
     t.start_time ASC,
     t.timesheet_id ASC",
  [$employer_id, $paralegal_id, $date]
);

$total_hours = 0.0;
foreach ($rows as &$r) {
  $total_hours += (float)($r['hours_worked'] ?? 0);

  if (!empty($r['start_time']) && !empty($r['end_time'])) {
    $r['time_ranges'] = substr((string)$r['start_time'], 0, 5) . '-' . substr((string)$r['end_time'], 0, 5);
  } else {
    $r['time_ranges'] = '-';
  }

  $wt = (string)($r['work_type'] ?? '');
  if ($wt !== 'Work' && $wt !== 'Travel') {
    $d0 = (string)($r['description'] ?? '');
    if (preg_match('/^\[(Work|Travel)\]/i', $d0, $m)) $wt = ucfirst(strtolower($m[1]));
    else $wt = 'Work';
  }
  $r['work_type'] = $wt;

  $desc = (string)($r['description'] ?? '');
  $r['desc_clean'] = trim(preg_replace('/^\[(Work|Travel)\]\s*/i', '', $desc));

  $st = (string)($r['status'] ?? '');
  $r['display_status'] = ($st === 'Rejected') ? 'Queried' : ($st ?: '-');
}
unset($r);

render('employer/daily_timesheet', compact('title','date','paralegal_id','paralegal','rows','total_hours'));