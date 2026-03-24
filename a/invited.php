<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Invited';

$rows = db_fetch_all("
  SELECT i.invitation_id, i.status, i.created_at,
         j.job_id, j.title AS job_title,
         eu.full_name AS employer_name,
         pu.full_name AS paralegal_name
  FROM job_invitations i
  JOIN jobs j ON j.job_id = i.job_id
  JOIN users eu ON eu.user_id = i.employer_id
  JOIN users pu ON pu.user_id = i.paralegal_id
  WHERE i.status='Invited'
  ORDER BY i.created_at DESC
  LIMIT 200
");

render('admin/invited', compact('title','rows'));
