<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Admin Dashboard';

// Standard/default commission rate only (used for billing totals)
$commission_rate_default = (float)setting_get('commission_rate_default', '20.00');

$metrics = [
  'employers' => (int)db_fetch_value("SELECT COUNT(*) FROM users WHERE role=? AND is_active=1", [ROLE_EMPLOYER]),
  'paralegals' => (int)db_fetch_value("SELECT COUNT(*) FROM users WHERE role=? AND is_active=1", [ROLE_PARALEGAL]),

  // DB enum values are Title Case (e.g. Open/Invited/Submitted/Pending)
  'open_jobs' => (int)db_fetch_value("SELECT COUNT(*) FROM jobs WHERE status='Open'"),
  'pending_invites' => (int)db_fetch_value("SELECT COUNT(*) FROM job_invitations WHERE status='Invited'"),
  'submitted_timesheets' => (int)db_fetch_value("SELECT COUNT(*) FROM timesheets WHERE status='Submitted'"),

  // Internally some environments use Rejected as "Queried" (legacy)...
  'queried_timesheets' => (int)db_fetch_value("SELECT COUNT(*) FROM timesheets WHERE status IN ('Queried','Rejected')"),

  'overdue_submitted_timesheets' => (int)db_fetch_value(
    "SELECT COUNT(*) FROM timesheets WHERE status='Submitted' AND DATEDIFF(NOW(), work_date) > 7"
  ),

  // Commission standard rate only
  'commission_rate_default' => $commission_rate_default,

  'unpaid_commission_invoices' => (int)db_fetch_value("SELECT COUNT(*) FROM commission_invoices WHERE status='Unpaid'"),
  'total_commission_invoiced' => (float)db_fetch_value("SELECT COALESCE(SUM(commission_amount),0) FROM commission_invoices"),
];

// Open disputes (safe if table not created yet)
$metrics['open_disputes'] = 0;
try {
  $metrics['open_disputes'] = (int)db_fetch_value("SELECT COUNT(*) FROM timesheet_disputes WHERE status='Open'");
} catch (Throwable $e) {
  $metrics['open_disputes'] = 0;
}

$recent_jobs = db_fetch_all("
  SELECT j.job_id, j.title, j.status, j.created_at, u.full_name AS employer_name
  FROM jobs j
  JOIN users u ON u.user_id = j.employer_id
  ORDER BY j.created_at DESC
  LIMIT 10
");

$pending_timesheets = db_fetch_all("
  SELECT t.timesheet_id, t.work_date, t.hours_worked, t.status,
         pu.full_name AS paralegal_name,
         eu.full_name AS employer_name
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN users pu ON pu.user_id = ja.paralegal_id
  JOIN users eu ON eu.user_id = ja.employer_id
  WHERE t.status='Submitted'
  ORDER BY t.work_date DESC
  LIMIT 10
");

render('admin/dashboard', compact('title','metrics','recent_jobs','pending_timesheets'));