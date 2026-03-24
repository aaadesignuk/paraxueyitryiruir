<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Employer Dashboard';
$eid = (int)auth_user()['user_id'];

$firm_name = (string)db_fetch_value("SELECT firm_name FROM employer_profiles WHERE user_id=? LIMIT 1", [$eid]);
if ($firm_name === '') $firm_name = auth_user()['full_name'] ?? 'Employer';

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

$kpi_active_jobs = (int)db_fetch_value("SELECT COUNT(*) FROM jobs WHERE employer_id=? AND status IN ('Open','Active')", [$eid]);
$kpi_timesheets_to_review = (int)db_fetch_value(" 
  SELECT COUNT(*)
  FROM (
    SELECT ja.job_id, ja.paralegal_id, t.work_date
    FROM timesheets t
    JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
    JOIN jobs j ON j.job_id = ja.job_id
    WHERE j.employer_id=?
      AND TRIM(t.status) IN ('Submitted','Rejected')
    GROUP BY ja.job_id, ja.paralegal_id, t.work_date
  ) x
", [$eid]);

$kpi_approved_hours = (float)db_fetch_value(" 
  SELECT COALESCE(SUM(t.hours_worked),0)
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  WHERE j.employer_id=?
    AND t.status='Approved'
    AND t.work_date >= ? AND t.work_date < ?
", [$eid, $date_from, $date_to]);

$kpi_approved_value = (float)db_fetch_value(" 
  SELECT COALESCE(SUM(t.hours_worked * COALESCE(ja.agreed_rate, j.max_rate, 0)),0)
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  WHERE j.employer_id=?
    AND t.status='Approved'
    AND t.work_date >= ? AND t.work_date < ?
", [$eid, $date_from, $date_to]);

$jobs_needing_review = db_fetch_all(" 
  SELECT
    j.job_id,
    j.title,
    j.deadline,
    u.full_name AS paralegal_name,
    MIN(t.work_date) AS oldest_review_date,
    COUNT(*) AS row_count,
    SUM(CASE WHEN t.status='Rejected' THEN 1 ELSE 0 END) AS queried_count,
    SUM(CASE WHEN t.status='Submitted' THEN 1 ELSE 0 END) AS submitted_count
  FROM jobs j
  JOIN job_assignments ja ON ja.job_id = j.job_id
  JOIN users u ON u.user_id = ja.paralegal_id
  JOIN timesheets t ON t.assignment_id = ja.assignment_id
  WHERE j.employer_id=?
    AND t.status IN ('Submitted','Rejected')
  GROUP BY j.job_id, j.title, j.deadline, u.full_name
  ORDER BY oldest_review_date ASC, j.deadline ASC
  LIMIT 12
", [$eid]);

render('employer/dashboard', compact('title','firm_name','period','period_label','kpi_active_jobs','kpi_timesheets_to_review','kpi_approved_hours','kpi_approved_value','jobs_needing_review'));
