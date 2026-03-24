<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Paralegal Dashboard';
$pid = (int)auth_user()['user_id'];
$period = (string)($_GET['period'] ?? 'month');
$allowed = ['month','last_month','year'];
if (!in_array($period, $allowed, true)) $period = 'month';

$today = new DateTimeImmutable('today');
function period_range(DateTimeImmutable $today, string $period): array {
  if ($period === 'year') {
    $start = $today->setDate((int)$today->format('Y'), 1, 1)->setTime(0,0,0);
    $end   = $start->modify('+1 year');
    $label = 'Year total';
  } elseif ($period === 'last_month') {
    $start = $today->modify('first day of last month')->setTime(0,0,0);
    $end   = $today->modify('first day of this month')->setTime(0,0,0);
    $label = 'Last month';
  } else {
    $start = $today->modify('first day of this month')->setTime(0,0,0);
    $end   = $start->modify('+1 month');
    $label = 'This month';
  }
  return [$start->format('Y-m-d'), $end->format('Y-m-d'), $label];
}
[$date_from, $date_to, $period_label] = period_range($today, $period);

$kpi_active_jobs = (int)db_fetch_value("SELECT COUNT(*) FROM job_assignments ja WHERE ja.paralegal_id=? AND ja.status='Active'", [$pid]);
$kpi_invites = (int)db_fetch_value("SELECT COUNT(*) FROM job_invitations ji WHERE ji.paralegal_id=? AND ji.status='Invited'", [$pid]);
$kpi_timesheets_outstanding = (int)db_fetch_value(" 
  SELECT COUNT(*)
  FROM (
    SELECT t.assignment_id, t.work_date
    FROM timesheets t
    JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
    WHERE ja.paralegal_id=?
      AND t.status='Rejected'
    GROUP BY t.assignment_id, t.work_date
  ) q
", [$pid]);

$kpi_approved_hours = (float)db_fetch_value(" 
  SELECT COALESCE(SUM(t.hours_worked),0)
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  WHERE ja.paralegal_id=?
    AND t.status='Approved'
    AND t.work_date >= ? AND t.work_date < ?
", [$pid, $date_from, $date_to]);

$kpi_approved_value = (float)db_fetch_value(" 
  SELECT COALESCE(SUM(t.hours_worked * COALESCE(ja.agreed_rate, j.max_rate, 0)),0)
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  WHERE ja.paralegal_id=?
    AND t.status='Approved'
    AND t.work_date >= ? AND t.work_date < ?
", [$pid, $date_from, $date_to]);

$invites = db_fetch_all(" 
  SELECT ji.invitation_id, ji.created_at, j.job_id, j.title, j.deadline, j.job_type, j.hours_required, j.max_rate, ep.firm_name AS employer_firm
  FROM job_invitations ji
  JOIN jobs j ON j.job_id = ji.job_id
  LEFT JOIN employer_profiles ep ON ep.user_id = ji.employer_id
  WHERE ji.paralegal_id=? AND ji.status='Invited'
  ORDER BY ji.created_at DESC
  LIMIT 12
", [$pid]);

$queried_timesheets = db_fetch_all(" 
  SELECT
    t.timesheet_id,
    t.work_date,
    t.hours_worked,
    j.title AS job_title,
    eu.full_name AS employer_name,
    td.dispute_id,
    td.created_at AS dispute_created_at
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users eu ON eu.user_id = ja.employer_id
  LEFT JOIN timesheet_disputes td ON td.timesheet_id = t.timesheet_id AND td.status = 'Open'
  WHERE ja.paralegal_id=?
    AND t.status='Rejected'
  ORDER BY t.work_date DESC, t.timesheet_id DESC
  LIMIT 12
", [$pid]);

$full_name = (string)(auth_user()['full_name'] ?? 'Paralegal');
$profileComplete = true;

render('paralegal/dashboard', compact('title','full_name','period','period_label','kpi_active_jobs','kpi_invites','kpi_timesheets_outstanding','kpi_approved_hours','kpi_approved_value','invites','queried_timesheets','profileComplete'));
