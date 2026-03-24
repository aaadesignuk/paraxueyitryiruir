<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Invite';
$pid = (int)auth_user()['user_id'];
$id = (int)($_GET['id'] ?? 0);

$invite = db_fetch_one(
  "SELECT
      ji.invitation_id,
      ji.job_id,
      ji.employer_id,
      ji.status,
      ji.created_at,
      j.title,
      j.description,
      j.deadline,
      j.job_type,
      /* jobs.mode does not exist in DB — compute a compatible alias */
      (CASE WHEN COALESCE(j.on_site,0)=1 THEN 'In person' ELSE 'Remote' END) AS mode,
      /* jobs.urgency does not exist in DB — compute from flags */
      (CASE
        WHEN COALESCE(j.work_247,0)=1 THEN '24/7'
        WHEN COALESCE(j.urgent_work,0)=1 THEN 'Urgent'
        ELSE 'Standard'
      END) AS urgency,
      j.hours_required,
      j.max_rate,
      ep.firm_name AS employer_firm,
      u.full_name AS employer_name
   FROM job_invitations ji
   JOIN jobs j ON j.job_id = ji.job_id
   JOIN users u ON u.user_id = ji.employer_id
   LEFT JOIN employer_profiles ep ON ep.user_id = ji.employer_id
   WHERE ji.invitation_id=? AND ji.paralegal_id=? LIMIT 1",
  [$id, $pid]
);

if (!$invite) {
  flash('Invite not found.', 'error');
  redirect('/p/dashboard.php');
}

render('paralegal/invite', compact('title','invite'));
