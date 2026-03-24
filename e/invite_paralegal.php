<?php
// /e/invite_paralegal.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$eid = (int)auth_user()['user_id'];
$job_id = (int)($_GET['job_id'] ?? 0);
$pid = (int)($_GET['paralegal_id'] ?? 0);

if ($job_id <= 0 || $pid <= 0) {
  flash('Invalid invite request.', 'error');
  redirect('/e/jobs.php');
}

$job = db_fetch_one("SELECT job_id, employer_id FROM jobs WHERE job_id=? LIMIT 1", [$job_id]);
if (!$job || (int)$job['employer_id'] !== $eid) {
  flash('You cannot invite for this job.', 'error');
  redirect('/e/jobs.php');
}

$existing_assignment = db_fetch_one(
  "SELECT assignment_id
   FROM job_assignments
   WHERE job_id=? AND employer_id=? AND paralegal_id=?
   LIMIT 1",
  [$job_id, $eid, $pid]
);

if ($existing_assignment) {
  flash('This paralegal is already assigned to the job.', 'info');
  redirect('/e/job_view.php?job_id=' . $job_id);
}

$existing = db_fetch_one(
  "SELECT invitation_id, status
   FROM job_invitations
   WHERE job_id=? AND employer_id=? AND paralegal_id=?
   LIMIT 1",
  [$job_id, $eid, $pid]
);

if ($existing) {
  if (empty($existing['status'])) {
    db_query("UPDATE job_invitations SET status='Invited' WHERE invitation_id=?", [(int)$existing['invitation_id']]);
    flash('Invitation updated. The job will be assigned when the paralegal accepts.', 'success');
  } else {
    flash('Invite already exists (' . $existing['status'] . ').', 'info');
  }
  redirect('/e/job_view.php?job_id=' . $job_id);
}

db_query(
  "INSERT INTO job_invitations (job_id, employer_id, paralegal_id, status, created_at)
   VALUES (?,?,?,?,NOW())",
  [$job_id, $eid, $pid, 'Invited']
);

flash('Invitation sent. The job will be assigned when the paralegal accepts.', 'success');
redirect('/e/job_view.php?job_id=' . $job_id);