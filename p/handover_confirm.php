<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$pid = (int)auth_user()['user_id'];

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  flash('Invalid request.','error');
  redirect('/p/dashboard.php');
}

$assignment_id = (int)($_POST['assignment_id'] ?? 0);

$as = db_fetch_one(
  "SELECT assignment_id, job_id
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
   SET access_confirmed=1,
       access_confirmed_by_user_id=?,
       access_confirmed_at=NOW(),
       access_issue_flag=0,
       access_issue_note=NULL,
       access_issue_at=NULL,
       updated_by_user_id=?,
       updated_at=NOW()
   WHERE job_id=?",
  [$pid, $pid, (int)$as['job_id']]
);

flash('Access confirmed.','success');
redirect('/p/assignment.php?id='.$assignment_id);
