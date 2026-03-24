<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Paralegal Profile';
$eid = (int)auth_user()['user_id'];
$pid = (int)($_GET['id'] ?? 0);

// Ensure the employer can only view paralegals that are either suggested on one of their tasks or previously invited.
// If you want employers to browse all paralegals, remove this guard.
$allowed = db_fetch_one(
  "SELECT 1
   FROM job_assignments a
   JOIN jobs j ON j.job_id = a.job_id
   WHERE j.employer_id = ? AND a.paralegal_id = ?
   LIMIT 1",
  [$eid, $pid]
);

if (!$allowed) {
  // Also allow viewing when coming from a System Match link (job_id present)
  $job_id = (int)($_GET['job_id'] ?? 0);
  if ($job_id > 0) {
    $own = db_fetch_one("SELECT 1 FROM jobs WHERE job_id=? AND employer_id=?", [$job_id, $eid]);
    if ($own) {
      $allowed = ['1' => 1];
    }
  }
}

if (!$pid || !$allowed) {
  flash('Invalid request.', 'error');
  redirect('/e/dashboard.php');
}

$u = db_fetch_one("SELECT user_id, full_name, email FROM users WHERE user_id=? AND role='P'", [$pid]);
$p = db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=?", [$pid]);

$cats = db_fetch_all(
  "SELECT ce.*, c.name AS category_name
   FROM paralegal_category_experience ce
   JOIN task_categories c ON c.id = ce.category_id
   WHERE ce.paralegal_id = ?
   ORDER BY COALESCE(c.sort_order, 9999), c.id",
  [$pid]
);

render('employer/paralegal_profile', compact('title','u','p','cats'));
