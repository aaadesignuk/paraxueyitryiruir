<?php
// /p/daily_timesheet.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Daily Timesheet';
$pid = (int)auth_user()['user_id'];

$date = trim((string)($_GET['date'] ?? ''));
if ($date === '') {
  $date = date('Y-m-d');
}

// Basic validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  flash('Invalid date.', 'error');
  redirect('/p/timesheets.php');
}

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$can_submit_day = in_array($date, [$today, $yesterday], true);

// Pull all entry rows for this paralegal on this date (across assignments)
$rows = db_fetch_all(
  "SELECT
      t.timesheet_id,
      t.assignment_id,
      t.work_date,
      t.start_time,
      t.end_time,
      t.work_type,
      t.hours_worked,
      t.description,
      t.status,
      a.job_id,
      j.title AS job_title,
      COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,'')) AS client_ref,
      u.full_name AS employer_name
   FROM timesheets t
   JOIN job_assignments a ON a.assignment_id = t.assignment_id
   JOIN jobs j ON j.job_id = a.job_id
   JOIN users u ON u.user_id = a.employer_id
   WHERE a.paralegal_id = ?
     AND t.work_date = ?
   ORDER BY
     (t.start_time IS NULL) ASC,
     t.start_time ASC,
     t.timesheet_id ASC",
  [$pid, $date]
);

// Normalise and compute totals
$total_hours = 0.0;
$job_groups = [];
foreach ($rows as &$r) {
  $total_hours += (float)($r['hours_worked'] ?? 0);

  $aid = (int)($r['assignment_id'] ?? 0);
  if ($aid > 0) {
    if (!isset($job_groups[$aid])) {
      $job_groups[$aid] = [
        'assignment_id' => $aid,
        'job_title' => (string)($r['job_title'] ?? ''),
        'employer_name' => (string)($r['employer_name'] ?? ''),
        'draft_count' => 0,
        'total_hours' => 0.0,
      ];
    }
    $job_groups[$aid]['total_hours'] += (float)($r['hours_worked'] ?? 0);
    if (((string)($r['status'] ?? '')) === 'Draft') $job_groups[$aid]['draft_count']++;
  }

  if (!empty($r['start_time']) && !empty($r['end_time'])) {
    $r['time_ranges'] = substr((string)$r['start_time'], 0, 5) . '-' . substr((string)$r['end_time'], 0, 5);
  } else {
    $r['time_ranges'] = '-';
  }

  $wt = (string)($r['work_type'] ?? '');
  if ($wt !== 'Work' && $wt !== 'Travel') {
    $d0 = (string)($r['description'] ?? '');
    if (preg_match('/^\[(Work|Travel)\]/i', $d0, $m)) $wt = ucfirst(strtolower($m[1]));
    else $wt = 'Work';
  }
  $r['work_type'] = $wt;

  $desc = (string)($r['description'] ?? '');
  $r['desc_clean'] = trim(preg_replace('/^\[(Work|Travel)\]\s*/i', '', $desc));

  $st = (string)($r['status'] ?? '');
  $r['display_status'] = ($st === 'Rejected') ? 'Queried' : ($st ?: '-');
}
unset($r);

// Convert groups to list (stable order: employer, job)
$job_groups_list = array_values($job_groups);
usort($job_groups_list, function($a, $b){
  $ae = strtolower((string)($a['employer_name'] ?? ''));
  $be = strtolower((string)($b['employer_name'] ?? ''));
  if ($ae !== $be) return $ae <=> $be;
  $aj = strtolower((string)($a['job_title'] ?? ''));
  $bj = strtolower((string)($b['job_title'] ?? ''));
  return $aj <=> $bj;
});

// Quick day-level status summary
$has_submitted = false;
$has_queried = false;
$has_draft = false;
foreach ($rows as $r) {
  $st = (string)($r['status'] ?? '');
  if ($st === 'Submitted') $has_submitted = true;
  if ($st === 'Rejected') $has_queried = true;
  if ($st === 'Draft') $has_draft = true;
}

$day_status = '—';
if ($rows) {
  if ($has_queried) $day_status = 'Queried';
  elseif ($has_submitted) $day_status = 'Submitted';
  elseif ($has_draft) $day_status = 'Draft';
  else $day_status = 'Approved';
}

render('paralegal/daily_timesheet', compact('title','date','rows','total_hours','day_status','job_groups_list','can_submit_day'));