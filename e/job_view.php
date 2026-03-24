<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$title = 'Job';
$eid = (int)auth_user()['user_id'];
$job_id = (int)($_GET['job_id'] ?? 0);

$job = db_fetch_one("SELECT * FROM jobs WHERE job_id=? AND employer_id=? LIMIT 1", [$job_id, $eid]);
if (!$job) {
  flash('Job not found.', 'error');
  redirect('/e/dashboard.php');
  exit;
}

$handover = db_fetch_one("SELECT * FROM job_handover WHERE job_id=? LIMIT 1", [$job_id]);

$current_assignment = db_fetch_one("
  SELECT
    ja.*,
    u.full_name,
    u.email
  FROM job_assignments ja
  JOIN users u ON u.user_id = ja.paralegal_id
  WHERE ja.job_id=? AND ja.employer_id=?
  ORDER BY
    (ja.status='Active') DESC,
    ja.assignment_id DESC
  LIMIT 1
", [$job_id, $eid]);

$invites = db_fetch_all("
  SELECT
    ji.*,
    u.full_name
  FROM job_invitations ji
  JOIN users u ON u.user_id = ji.paralegal_id
  WHERE ji.job_id=?
  ORDER BY ji.created_at DESC, ji.invitation_id DESC
", [$job_id]);

$has_pending_invite = false;
foreach ($invites as $inv) {
  if (($inv['status'] ?? '') === 'Invited') {
    $has_pending_invite = true;
    break;
  }
}

$assignment_state = 'pending';
if ($current_assignment) {
  $assignment_state = 'assigned';
} elseif ($has_pending_invite) {
  $assignment_state = 'invited';
}

$suggested = [];
if (!$current_assignment && function_exists('match_paralegals_for_job')) {
  $suggested = match_paralegals_for_job($job_id, 6);
}

$rehire = db_fetch_all("
  SELECT DISTINCT
    u.user_id,
    u.full_name,
    pp.specialism,
    pp.preferred_rate,
    pp.is_available
  FROM job_assignments ja
  JOIN users u ON u.user_id = ja.paralegal_id
  LEFT JOIN paralegal_profiles pp ON pp.user_id = u.user_id
  WHERE ja.employer_id=?
  ORDER BY u.full_name ASC
  LIMIT 25
", [$eid]);

/**
 * Approved hours should count both Approved + Deemed Approved
 */
$approved_hours = (float)db_fetch_value("
  SELECT COALESCE(SUM(t.hours_worked),0)
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  WHERE ja.job_id=? AND ja.employer_id=? AND t.status IN ('Approved','Deemed Approved')
", [$job_id, $eid]);

// Remaining scope (hours OR days)
$required_hours = $job['hours_required'] ?? null;
$required_days  = $job['days_required'] ?? null;

$remaining_hours = null;
$remaining_days  = null;

if (($job['job_type'] ?? 'Hours') === 'Days' && $required_days !== null) {
  $remaining_days = max(0, (float)$required_days);
} elseif ($required_hours !== null) {
  $remaining_hours = max(0, (float)$required_hours - $approved_hours);
}

/**
 * Job View Timesheets should show DAILY timesheets, not entry rows.
 * Group by (work_date, paralegal).
 *
 * Pending day status:
 *  - Queried if any Rejected exists that day
 *  - Submitted if any Submitted exists that day
 * Approved day status:
 *  - Deemed Approved if any Deemed Approved exists that day
 *  - else Approved
 */
$daily_timesheets = db_fetch_all("
  SELECT
    t.work_date,
    ja.paralegal_id,
    u.full_name AS paralegal_name,
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
  JOIN users u ON u.user_id = ja.paralegal_id
  LEFT JOIN timesheet_disputes d ON d.timesheet_id = t.timesheet_id AND d.status='Open'
  WHERE ja.job_id=? AND ja.employer_id=?
    AND t.status <> 'Draft'
  GROUP BY t.work_date, ja.paralegal_id, u.full_name
  ORDER BY t.work_date DESC, u.full_name ASC
", [$job_id, $eid]);

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
  $r['is_reviewable'] = ($r['review_entry_count'] > 0);
}
unset($r);

render('employer/job_view', compact(
  'title',
  'job',
  'handover',
  'suggested',
  'invites',
  'rehire',
  'approved_hours',
  'remaining_hours',
  'remaining_days',
  'daily_timesheets',
  'current_assignment',
  'assignment_state'
));