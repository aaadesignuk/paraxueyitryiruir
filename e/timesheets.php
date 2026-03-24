<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Timesheets';
$eid = (int)auth_user()['user_id'];



/**
 * Global employer daily timesheets
 * Same logic as job_view but without job_id filter
 */

$daily_timesheets = db_fetch_all("
  SELECT
    t.work_date,
    ja.paralegal_id,
    u.full_name AS paralegal_name,
    j.job_id,
    j.title AS job_title,
    COUNT(*) AS entry_count,
    ROUND(SUM(t.hours_worked), 2) AS total_hours,
    SUM(CASE WHEN t.status='Rejected' THEN 1 ELSE 0 END) AS queried_count,
    SUM(CASE WHEN t.status='Rejected' THEN t.hours_worked ELSE 0 END) AS queried_hours,
    SUM(CASE WHEN t.status='Submitted' THEN 1 ELSE 0 END) AS submitted_count,
    SUM(CASE WHEN t.status='Submitted' THEN t.hours_worked ELSE 0 END) AS submitted_hours,
    SUM(CASE WHEN t.status='Approved' THEN 1 ELSE 0 END) AS approved_count,
    SUM(CASE WHEN t.status='Deemed Approved' THEN 1 ELSE 0 END) AS deemed_count,
    SUM(CASE WHEN d.status='Open' THEN 1 ELSE 0 END) AS open_dispute_count
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users u ON u.user_id = ja.paralegal_id
  LEFT JOIN timesheet_disputes d 
    ON d.timesheet_id = t.timesheet_id AND d.status='Open'
  WHERE ja.employer_id=?
    AND t.status <> 'Draft'
  GROUP BY t.work_date, ja.paralegal_id, u.full_name, j.job_id, j.title
  ORDER BY t.work_date DESC, u.full_name ASC
", [$eid]);

foreach ($daily_timesheets as &$r) {
  $qc = (int)($r['queried_count'] ?? 0);
  $sc = (int)($r['submitted_count'] ?? 0);
  $dc = (int)($r['deemed_count'] ?? 0);

  if ($qc > 0) $r['day_status'] = 'Queried';
  elseif ($sc > 0) $r['day_status'] = 'Submitted';
  else $r['day_status'] = ($dc > 0) ? 'Deemed Approved' : 'Approved';

  $r['total_hours'] = round((float)($r['total_hours'] ?? 0), 2);
  $r['entry_count'] = (int)($r['entry_count'] ?? 0);
  $r['open_dispute_count'] = (int)($r['open_dispute_count'] ?? 0);
  $r['review_entry_count'] = (int)($sc + $qc);
  $r['review_hours'] = round((float)($r['submitted_hours'] ?? 0) + (float)($r['queried_hours'] ?? 0), 2);
}
unset($r);

render('employer/timesheets', compact(
  'title',
  'daily_timesheets'
));