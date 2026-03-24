<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$pid = (int)auth_user()['user_id'];

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  flash('Invalid request.','error');
  redirect('/p/dashboard.php');
}

$assignment_id = (int)($_POST['assignment_id'] ?? 0);
$note = trim($_POST['note'] ?? '');

if($note === ''){
  flash('Please add a short note describing the access issue.','error');
  redirect('/p/assignment.php?id='.$assignment_id);
}

$as = db_fetch_one(
  "SELECT assignment_id, job_id, employer_id
   FROM job_assignments
   WHERE assignment_id=? AND paralegal_id=? LIMIT 1",
  [$assignment_id, $pid]
);

if(!$as){
  flash('Assignment not found.','error');
  redirect('/p/dashboard.php');
}

// Ensure row exists
db_query("INSERT IGNORE INTO job_handover (job_id, method, added_at) VALUES (?, 'LINK', NOW())", [(int)$as['job_id']]);

db_query(
  "UPDATE job_handover
   SET access_issue_flag=1,
       access_issue_note=?,
       access_issue_at=NOW(),
       updated_by_user_id=?,
       updated_at=NOW()
   WHERE job_id=?",
  [$note, $pid, (int)$as['job_id']]
);

// Notify employer (and optionally admins) that access issue was reported
notify((int)$as['employer_id'], "Paralegal reported a document access issue for Job #".(int)$as['job_id'].": ".$note);

flash('Issue reported to the employer.','info');
redirect('/p/assignment.php?id='.$assignment_id);
