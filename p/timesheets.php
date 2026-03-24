<?php
// /p/timesheets.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Timesheets';
$pid   = (int)auth_user()['user_id'];

// Pagination (daily rows)
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$status_filter = (string)($_GET['status'] ?? '');

// Optional filter from dashboard card
$where_extra = "AND t.status <> 'Draft'";
if ($status_filter === 'Queried') {
  $where_extra = "AND t.status = 'Rejected'";
}

// Total daily groups
$totalRow = db_fetch_one("
  SELECT COUNT(*) AS c
  FROM (
    SELECT t.work_date, t.assignment_id
    FROM timesheets t
    JOIN job_assignments a ON a.assignment_id = t.assignment_id
    WHERE a.paralegal_id = ?
      {$where_extra}
    GROUP BY t.work_date, t.assignment_id
  ) x
", [$pid]);

$meta = pagination_meta((int)($totalRow['c'] ?? 0), $page, $per_page);

// Daily grouped rows (per job per day)
$daily = db_fetch_all("
  SELECT
    t.work_date,
    t.assignment_id,
    a.job_id,
    j.title AS job_title,
    u.full_name AS employer_name,

    COUNT(*) AS entry_count,
    ROUND(SUM(t.hours_worked), 2) AS total_hours,

    SUM(CASE WHEN t.status='Draft' THEN 1 ELSE 0 END) AS draft_count,
    SUM(CASE WHEN t.status='Submitted' THEN 1 ELSE 0 END) AS submitted_count,
    SUM(CASE WHEN t.status='Rejected' THEN 1 ELSE 0 END) AS queried_count,
    SUM(CASE WHEN t.status='Approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN t.status='Deemed Approved' THEN 1 ELSE 0 END) AS deemed_count

  FROM timesheets t
  JOIN job_assignments a ON a.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = a.job_id
  JOIN users u ON u.user_id = a.employer_id
  WHERE a.paralegal_id = ?
    {$where_extra}
  GROUP BY t.work_date, t.assignment_id, a.job_id, j.title, u.full_name
  ORDER BY t.work_date DESC, t.assignment_id DESC
  LIMIT {$meta['per_page']} OFFSET {$meta['offset']}
", [$pid]);

foreach ($daily as &$r) {
  $qc = (int)($r['queried_count'] ?? 0);
  $sc = (int)($r['submitted_count'] ?? 0);
  $dc = (int)($r['deemed_count'] ?? 0);

  if ($qc > 0) $r['day_status'] = 'Queried';
  elseif ($sc > 0) $r['day_status'] = 'Submitted';
  else $r['day_status'] = ($dc > 0) ? 'Deemed Approved' : 'Approved';

  $r['total_hours'] = round((float)($r['total_hours'] ?? 0), 2);
  $r['entry_count'] = (int)($r['entry_count'] ?? 0);
}
unset($r);

// Notes (keep existing behaviour if present)
$notes = [];
if (function_exists('timesheet_notes_for_user')) {
  $notes = (array)timesheet_notes_for_user($pid);
}

render('paralegal/timesheets', compact('title','daily','meta','notes','status_filter'));