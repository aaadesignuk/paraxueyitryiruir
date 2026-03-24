<?php
// /p/timesheet_day.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Daily Timesheet';
$pid   = (int)auth_user()['user_id'];

$assignment_id = (int)($_GET['assignment_id'] ?? 0);
$date = trim((string)($_GET['date'] ?? ''));

if ($assignment_id <= 0 || $date === '') {
  http_response_code(400);
  exit('Invalid request.');
}

// Validate assignment belongs to paralegal + load header info
$assn = db_fetch_one("
  SELECT a.*, j.title AS job_title, u.full_name AS employer_name
  FROM job_assignments a
  JOIN jobs j ON j.job_id = a.job_id
  JOIN users u ON u.user_id = a.employer_id
  WHERE a.assignment_id=? AND a.paralegal_id=?
  LIMIT 1
", [$assignment_id, $pid]);

if (!$assn) {
  http_response_code(404);
  exit('Assignment not found.');
}

// Allow actions only for today/yesterday (matches time entry rules)
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$can_submit_day = in_array($date, [$today, $yesterday], true);

// Entries for the day (include Draft so this page is actionable)
$entries = db_fetch_all("
  SELECT *
  FROM timesheets
  WHERE assignment_id=? AND work_date=?
  ORDER BY
    CASE WHEN start_time IS NULL THEN 1 ELSE 0 END,
    start_time ASC,
    timesheet_id ASC
", [$assignment_id, $date]);

// Load latest employer query per timesheet entry so it can be shown inline on the day page.
// First prefer timesheet_queries. If none exists, fall back to timesheet_disputes.
$query_map = [];

if (!empty($entries)) {
  $timesheet_ids = array_map(
    static fn(array $row): int => (int)$row['timesheet_id'],
    $entries
  );

  $placeholders = implode(',', array_fill(0, count($timesheet_ids), '?'));

  $latest_queries = db_fetch_all("
    SELECT q1.timesheet_id, q1.reason, q1.created_at
    FROM timesheet_queries q1
    INNER JOIN (
      SELECT timesheet_id, MAX(query_id) AS max_query_id
      FROM timesheet_queries
      WHERE timesheet_id IN ($placeholders)
      GROUP BY timesheet_id
    ) q2
      ON q2.timesheet_id = q1.timesheet_id
     AND q2.max_query_id = q1.query_id
  ", $timesheet_ids);

  foreach ($latest_queries as $query_row) {
    $query_map[(int)$query_row['timesheet_id']] = [
      'reason'     => (string)($query_row['reason'] ?? ''),
      'created_at' => (string)($query_row['created_at'] ?? ''),
    ];
  }

  $missing_ids = [];
  foreach ($timesheet_ids as $timesheet_id) {
    if (!isset($query_map[$timesheet_id])) {
      $missing_ids[] = (int)$timesheet_id;
    }
  }

  if (!empty($missing_ids)) {
    $missing_placeholders = implode(',', array_fill(0, count($missing_ids), '?'));

    $latest_disputes = db_fetch_all("
      SELECT d1.timesheet_id, d1.dispute_text, d1.created_at
      FROM timesheet_disputes d1
      INNER JOIN (
        SELECT timesheet_id, MAX(dispute_id) AS max_dispute_id
        FROM timesheet_disputes
        WHERE timesheet_id IN ($missing_placeholders)
        GROUP BY timesheet_id
      ) d2
        ON d2.timesheet_id = d1.timesheet_id
       AND d2.max_dispute_id = d1.dispute_id
    ", $missing_ids);

    foreach ($latest_disputes as $dispute_row) {
      $query_map[(int)$dispute_row['timesheet_id']] = [
        'reason'     => (string)($dispute_row['dispute_text'] ?? ''),
        'created_at' => (string)($dispute_row['created_at'] ?? ''),
      ];
    }
  }
}

$total_hours = 0.0;
$queried = 0;
$submitted = 0;
$deemed = 0;
$draft = 0;

foreach ($entries as &$entry) {
  $total_hours += (float)($entry['hours_worked'] ?? 0);

  $status = (string)($entry['status'] ?? '');

  if ($status === 'Rejected') {
    $queried++;
  } elseif ($status === 'Draft') {
    $draft++;
  } elseif ($status === 'Submitted') {
    $submitted++;
  } elseif ($status === 'Deemed Approved') {
    $deemed++;
  }

  $start_time = (string)($entry['start_time'] ?? '');
  $end_time   = (string)($entry['end_time'] ?? '');

  if ($start_time !== '' && $end_time !== '') {
    $entry['time_range'] = substr($start_time, 0, 5) . '–' . substr($end_time, 0, 5);
  } else {
    $entry['time_range'] = '—';
  }

  $minutes = (int)round(((float)($entry['hours_worked'] ?? 0)) * 60);
  $hours_part = intdiv($minutes, 60);
  $minutes_part = $minutes % 60;

  if ($hours_part > 0 && $minutes_part > 0) {
    $entry['duration_display'] = "{$hours_part}h {$minutes_part}m";
  } elseif ($hours_part > 0) {
    $entry['duration_display'] = "{$hours_part}h";
  } else {
    $entry['duration_display'] = "{$minutes_part}m";
  }

  $description = trim((string)($entry['description'] ?? ''));
  $description = preg_replace('/^\[(Work|Travel)\]\s*/i', '', $description);
  $entry['description_clean'] = $description;

  $entry['status_display'] = ($status === 'Rejected') ? 'Queried' : ($status ?: '');

  $timesheet_id = (int)($entry['timesheet_id'] ?? 0);
  $entry['query_reason'] = $query_map[$timesheet_id]['reason'] ?? '';
  $entry['query_created_at'] = $query_map[$timesheet_id]['created_at'] ?? '';
}
unset($entry);

if ($queried > 0) {
  $day_status = 'Queried';
} elseif ($draft > 0) {
  $day_status = 'Draft';
} elseif ($submitted > 0) {
  $day_status = 'Submitted';
} else {
  $day_status = ($deemed > 0) ? 'Deemed Approved' : 'Approved';
}

$total_hours = round($total_hours, 2);

render('paralegal/timesheet_day', compact(
  'title',
  'assn',
  'assignment_id',
  'date',
  'entries',
  'total_hours',
  'day_status',
  'can_submit_day',
  'draft'
));