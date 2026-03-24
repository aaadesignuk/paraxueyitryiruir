<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$pid = (int)auth_user()['user_id'];
$id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
$action = (string)($_POST['action'] ?? ($_GET['action'] ?? ''));

$inv = db_fetch_one(
  "SELECT * FROM job_invitations WHERE invitation_id=? AND paralegal_id=? LIMIT 1",
  [$id, $pid]
);

if(!$inv){
  flash('Invite not found.','error');
  redirect('/p/dashboard.php');
}

if($inv['status'] !== 'Invited'){
  flash('Already processed.','info');
  redirect('/p/dashboard.php');
}

if($action === 'accept'){
  // Require CV before accepting (client feedback)
  if (function_exists('db_has_column') && db_has_column('paralegal_profiles', 'cv_path')) {
    $cv = db_fetch_value("SELECT cv_path FROM paralegal_profiles WHERE user_id=? LIMIT 1", [$pid]);
    if (empty($cv)) {
      flash('Please upload your CV before accepting a task.', 'error');
      redirect('/p/profile_details.php#documents');
    }
  }

  db_query("UPDATE job_invitations SET status='Accepted' WHERE invitation_id=?", [$id]);

  $existing = db_fetch_one(
    "SELECT assignment_id FROM job_assignments WHERE job_id=? AND employer_id=? AND paralegal_id=? LIMIT 1",
    [(int)$inv['job_id'], (int)$inv['employer_id'], $pid]
  );

  if(!$existing){
    db_query(
      "INSERT INTO job_assignments (job_id,employer_id,paralegal_id,status,started_at) VALUES (?,?,?,'Active',NOW())",
      [(int)$inv['job_id'], (int)$inv['employer_id'], $pid]
    );
    $existing = db_fetch_one(
      "SELECT assignment_id FROM job_assignments WHERE job_id=? AND employer_id=? AND paralegal_id=? ORDER BY assignment_id DESC LIMIT 1",
      [(int)$inv['job_id'], (int)$inv['employer_id'], $pid]
    );
  }

  // Update job status now that it has been accepted/assigned
  db_query("UPDATE jobs SET status='In Progress' WHERE job_id=? AND employer_id=? LIMIT 1", [(int)$inv['job_id'], (int)$inv['employer_id']]);

  // Ensure a handover row exists (External link sharing + logging)
  db_query("INSERT IGNORE INTO job_handover (job_id, method, added_at) VALUES (?, 'LINK', NOW())", [(int)$inv['job_id']]);

  // Notification is generated on the employer dashboard from job_invitations/job_assignments.
  flash('Accepted.', 'success');

  $aid = (int)($existing['assignment_id'] ?? 0);
  if ($aid > 0) {
    redirect('/p/assignment.php?id='.$aid);
  }

} elseif($action === 'decline'){

  db_query("UPDATE job_invitations SET status='Declined' WHERE invitation_id=?", [$id]);
  // Notification is generated on the employer dashboard from job_invitations.
  flash('Declined.', 'info');

} else {
  flash('Invalid action.', 'error');
}

redirect('/p/dashboard.php');
