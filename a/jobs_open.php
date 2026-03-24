<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Open Jobs';

$rows = db_fetch_all("
  SELECT j.job_id, j.title, j.specialism, j.sub_specialism, j.job_type, j.hours_required,
         j.max_rate, j.deadline, j.status, j.created_at,
         u.full_name AS employer_name
  FROM jobs j
  JOIN users u ON u.user_id = j.employer_id
  WHERE j.status='Open'
  ORDER BY j.created_at DESC
");

render('admin/jobs_open', compact('title','rows'));
