<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Submitted Timesheets';

$rows = db_fetch_all("
  SELECT t.timesheet_id, t.work_date, t.hours_worked, t.description, t.status,
         ja.assignment_id,
         j.job_id, j.title AS job_title,
         eu.full_name AS employer_name,
         pu.full_name AS paralegal_name
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users eu ON eu.user_id = ja.employer_id
  JOIN users pu ON pu.user_id = ja.paralegal_id
  WHERE t.status='Submitted'
  ORDER BY t.work_date DESC, t.timesheet_id DESC
  LIMIT 200
");

render('admin/timesheets_submitted', compact('title','rows'));
