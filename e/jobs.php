<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Jobs';
$eid   = (int)auth_user()['user_id'];

// Optional filter from dashboard: /e/jobs.php?status=Open,Active
$status = (string)($_GET['status'] ?? 'Open,Active');

$allowed = ['Open','Active','Completed','Cancelled','In Progress'];
$statuses = array_filter(array_map('trim', explode(',', $status)));
$statuses = array_values(array_intersect($statuses, $allowed));

$whereStatus = '';
$params = [$eid];

if (!empty($statuses)) {
  $in = implode(',', array_fill(0, count($statuses), '?'));
  $whereStatus = " AND j.status IN ($in) ";
  foreach ($statuses as $s) $params[] = $s;
}

// Jobs list (for this employer)
$jobs = db_fetch_all("
  SELECT
    j.job_id,
    j.title,
    j.status,
    j.created_at,
    j.deadline,
    j.hours_required,
    j.max_rate,
    (SELECT COUNT(*) FROM job_assignments a WHERE a.job_id=j.job_id AND a.status='Active') AS active_assignments
  FROM jobs j
  WHERE j.employer_id = ?
  {$whereStatus}
  ORDER BY
    (j.status IN ('Open','Active','In Progress')) DESC,
    j.deadline IS NULL,
    j.deadline ASC,
    j.job_id DESC
", $params);

render('employer/jobs', compact('title','jobs','status'));