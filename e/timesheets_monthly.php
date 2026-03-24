<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Monthly Timesheet Summary';
$eid = (int)auth_user()['user_id'];

$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$month_start = $month . '-01';
$month_end = date('Y-m-d', strtotime($month_start . ' +1 month')); // exclusive end

$rows = db_fetch_all("
  SELECT
    j.job_id,
    j.title AS job_title,
    ja.paralegal_id,
    u.full_name AS paralegal_name,

    /* month totals */
    ROUND(SUM(t.hours_worked), 2) AS total_hours,
    COUNT(DISTINCT t.work_date) AS total_days,

    /* submitted */
    ROUND(SUM(CASE WHEN t.status='Submitted' THEN t.hours_worked ELSE 0 END), 2) AS submitted_hours,
    COUNT(DISTINCT CASE WHEN t.status='Submitted' THEN t.work_date END) AS submitted_days,

    /* queried (Rejected) */
    ROUND(SUM(CASE WHEN t.status='Rejected' THEN t.hours_worked ELSE 0 END), 2) AS queried_hours,
    COUNT(DISTINCT CASE WHEN t.status='Rejected' THEN t.work_date END) AS queried_days,

    /* approved (Approved + Deemed Approved) */
    ROUND(SUM(CASE WHEN t.status IN ('Approved','Deemed Approved') THEN t.hours_worked ELSE 0 END), 2) AS approved_hours,
    COUNT(DISTINCT CASE WHEN t.status IN ('Approved','Deemed Approved') THEN t.work_date END) AS approved_days

  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users u ON u.user_id = ja.paralegal_id
  WHERE ja.employer_id=?
    AND t.status <> 'Draft'
    AND t.work_date >= ?
    AND t.work_date < ?
  GROUP BY j.job_id, j.title, ja.paralegal_id, u.full_name
  ORDER BY j.title ASC, u.full_name ASC
", [$eid, $month_start, $month_end]);

render('employer/timesheets_monthly', compact('title', 'month', 'rows'));