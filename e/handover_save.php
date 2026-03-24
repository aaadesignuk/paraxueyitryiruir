<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$eid = (int)auth_user()['user_id'];

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  flash('Invalid request.','error');
  redirect('/e/dashboard.php');
}

$job_id = (int)($_POST['job_id'] ?? 0);
$method = strtoupper(trim($_POST['method'] ?? 'LINK'));
$shared_link = trim($_POST['shared_link'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');

$job = db_fetch_one("SELECT job_id, employer_id, title FROM jobs WHERE job_id=? AND employer_id=? LIMIT 1", [$job_id, $eid]);
if(!$job){
  flash('Job not found.','error');
  redirect('/e/dashboard.php');
}

$allowed = ['LINK','FIRM_EMAIL','PERSONAL_EMAIL','NOT_REQUIRED'];
if(!in_array($method, $allowed, true)){
  flash('Invalid handover method.','error');
  redirect('/e/job_view.php?job_id='.$job_id);
}

if($method === 'LINK'){
  if($shared_link === '' || !preg_match('#^https?://#i', $shared_link)){
    flash('Please provide a valid link starting with http:// or https://','error');
    redirect('/e/job_view.php?job_id='.$job_id);
  }
} else {
  $shared_link = null;
}

// Upsert (one row per job)
db_query(
  "INSERT INTO job_handover (job_id, method, shared_link, instructions, added_by_user_id, added_at)
   VALUES (?,?,?,?,?,NOW())
   ON DUPLICATE KEY UPDATE
     method=VALUES(method),
     shared_link=VALUES(shared_link),
     instructions=VALUES(instructions),
     updated_by_user_id=VALUES(added_by_user_id),
     updated_at=NOW()",
  [$job_id, $method, $shared_link, ($instructions !== '' ? $instructions : null), $eid]
);

// Notify active assigned paralegal (if any)
$par = db_fetch_one(
  "SELECT ja.paralegal_id
   FROM job_assignments ja
   WHERE ja.job_id=? AND ja.employer_id=? AND ja.status='Active'
   ORDER BY ja.started_at DESC
   LIMIT 1",
  [$job_id, $eid]
);
if($par && !empty($par['paralegal_id'])){
  notify((int)$par['paralegal_id'], "Document sharing details updated for Job #{$job_id} ({$job['title']}).");
}

flash('Sharing details saved.','success');
redirect('/e/job_view.php?job_id='.$job_id);
