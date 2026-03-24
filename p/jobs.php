<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Jobs';
$pid   = (int)auth_user()['user_id'];

// Optional filter from dashboard link: /p/jobs.php?status=Active
$status = (string)($_GET['status'] ?? 'Active');
$allowed = ['Active','Completed','Cancelled','All'];
if (!in_array($status, $allowed, true)) $status = 'Active';

$whereStatus = '';
$params = [$pid];
if ($status !== 'All') {
  $whereStatus = " AND ja.status = ? ";
  $params[] = $status;
}

// Assignments list (jobs for this paralegal)
$assignments = db_fetch_all("
  SELECT
    ja.assignment_id,
    ja.job_id,
    ja.status,
    ja.agreed_rate,
    j.title,
    j.deadline,
    j.hours_required,
    j.max_rate,
    ep.firm_name AS employer_firm
  FROM job_assignments ja
  JOIN jobs j ON j.job_id = ja.job_id
  LEFT JOIN employer_profiles ep ON ep.user_id = ja.employer_id
  WHERE ja.paralegal_id = ?
  {$whereStatus}
  ORDER BY
    (ja.status='Active') DESC,
    j.deadline IS NULL,
    j.deadline ASC,
    ja.assignment_id DESC
", $params);

// Timesheets triage (needs action) — navigation only, opens Job View
$outstanding = db_fetch_all("
  SELECT
    t.timesheet_id,
    t.work_date,
    t.hours_worked,
    t.status,
    ja.assignment_id,
    j.title AS job_title,
    ep.firm_name AS employer_firm
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  LEFT JOIN employer_profiles ep ON ep.user_id = ja.employer_id
  WHERE ja.paralegal_id = ?
    AND t.status IN ('Draft','Rejected')
  ORDER BY t.work_date DESC, t.timesheet_id DESC
  LIMIT 20
", [$pid]);

render('paralegal/jobs', compact('title','status','assignments','outstanding'));
