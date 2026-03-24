<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Assignment';
$pid = (int)auth_user()['user_id'];
$id  = (int)($_GET['id'] ?? 0);

// Optional job columns (avoid schema assumptions)
$jCols = [];
foreach (['client_ref','specialism','sub_specialism','rate_amount','rate_type','job_type','days_required'] as $c) {
  if (function_exists('db_has_column') && db_has_column('jobs', $c)) $jCols[] = $c;
}
$jSelect = '';
foreach ($jCols as $c) { $jSelect .= ", j.{$c}"; }

$assignment = db_fetch_one(
  "SELECT
      ja.*,
      j.title,
      j.deadline,
      j.status AS job_status,
      (CASE
        WHEN COALESCE(j.work_247,0)=1 THEN '24/7'
        WHEN COALESCE(j.urgent_work,0)=1 THEN 'Urgent'
        ELSE 'Standard'
      END) AS urgency,
      (CASE WHEN COALESCE(j.on_site,0)=1 THEN 'In person' ELSE 'Remote' END) AS mode,
         j.max_rate,
      j.hours_required
      {$jSelect},
      u.full_name AS employer_name,
      ep.firm_name AS employer_firm
   FROM job_assignments ja
   JOIN jobs j ON j.job_id = ja.job_id
   JOIN users u ON u.user_id = j.employer_id
   LEFT JOIN employer_profiles ep ON ep.user_id = j.employer_id
   WHERE ja.assignment_id=? AND ja.paralegal_id=? LIMIT 1",
  [$id, $pid]
);

if(!$assignment){
  flash('Assignment not found.','error');
  redirect('/p/dashboard.php');
  exit;
}

$handover = db_fetch_one("SELECT * FROM job_handover WHERE job_id=? LIMIT 1", [(int)$assignment['job_id']]);

// Model A session fields
$has_start = function_exists('db_has_column') ? db_has_column('timesheets','start_time') : false;
$has_end   = function_exists('db_has_column') ? db_has_column('timesheets','end_time') : false;

$type_col = null;
foreach (['work_type','type','entry_type'] as $c) {
  if (function_exists('db_has_column') && db_has_column('timesheets', $c)) { $type_col = $c; break; }
}

$select_type = $type_col ? "t.{$type_col} AS work_type" : "NULL AS work_type";
$select_start = $has_start ? "t.start_time" : "NULL AS start_time";
$select_end   = $has_end   ? "t.end_time"   : "NULL AS end_time";

$timesheets = [];
try {
  $timesheets = db_fetch_all("
    SELECT
      t.*,
      {$select_type},
      {$select_start},
      {$select_end},

      od.dispute_id AS open_dispute_id,

      cd.dispute_id AS closed_dispute_id,
      cd.resolution AS dispute_resolution,
      cd.admin_notes AS dispute_admin_notes,
      cd.closed_at AS dispute_closed_at

    FROM timesheets t

    /* open dispute (if any) */
    LEFT JOIN (
      SELECT timesheet_id, MAX(dispute_id) AS dispute_id
      FROM timesheet_disputes
      WHERE status='Open'
      GROUP BY timesheet_id
    ) od ON od.timesheet_id = t.timesheet_id

 /* latest resolved dispute (if any) */
LEFT JOIN (
  SELECT
    d1.timesheet_id,
    d1.dispute_id,
    d1.resolved_action AS resolution,
    d1.resolved_note   AS admin_notes,
    d1.resolved_at     AS closed_at
  FROM timesheet_disputes d1
  JOIN (
    SELECT timesheet_id, MAX(resolved_at) AS mc
    FROM timesheet_disputes
    WHERE status='Resolved'
    GROUP BY timesheet_id
  ) m ON m.timesheet_id=d1.timesheet_id AND m.mc=d1.resolved_at
  WHERE d1.status='Resolved'
) cd ON cd.timesheet_id = t.timesheet_id

    WHERE t.assignment_id=?
    ORDER BY t.work_date DESC, t.timesheet_id DESC
  ", [$id]);
} catch (Throwable $e) {
  // If disputes table doesn't exist, fall back to original query safely
  $timesheets = db_fetch_all("
    SELECT
      t.*,
      {$select_type},
      {$select_start},
      {$select_end}
    FROM timesheets t
    WHERE t.assignment_id=?
    ORDER BY t.work_date DESC, t.timesheet_id DESC
  ", [$id]);
}

$timesheet_query_reason = [];

if (!empty($timesheets)) {
  $ids = array_values(array_unique(array_filter(array_map(
    static fn($r) => (int)($r['timesheet_id'] ?? 0),
    $timesheets
  ))));

  if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $rows = db_fetch_all("
      SELECT q.timesheet_id, q.reason
      FROM timesheet_queries q
      JOIN (
        SELECT timesheet_id, MAX(created_at) AS mc
        FROM timesheet_queries
        WHERE timesheet_id IN ($placeholders)
        GROUP BY timesheet_id
      ) m ON m.timesheet_id=q.timesheet_id AND m.mc=q.created_at
    ", $ids);

    foreach ($rows as $r) {
      $timesheet_query_reason[(int)$r['timesheet_id']] = trim((string)($r['reason'] ?? ''));
    }
  }
}

$draft_timesheet_id = 0; // Truth model: no auto-drafts

// Queried notes are stored as notifications.

/* and end with 
$queried_notes = [];
try {
  $queried_notes = db_fetch_all(
    "SELECT message, created_at
     FROM notifications
     WHERE user_id = ? AND message LIKE 'Timesheet queried:%'
     ORDER BY created_at DESC
     LIMIT 5",
    [$pid]
  );
} catch (Throwable $e) {
  $queried_notes = [];
}
*/ 

render('paralegal/assignment', compact(
  'title',
  'assignment',
  'handover',
  'timesheets',
  'timesheet_query_reason',
  'draft_timesheet_id'
));
