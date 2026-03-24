<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Dispute Results';
$uid = (int)auth_user()['user_id'];
$timesheet_id = (int)($_GET['timesheet_id'] ?? 0);
if ($timesheet_id <= 0) { http_response_code(400); exit('Missing timesheet_id'); }

$payable_hours_sql = (function_exists('db_has_column') && db_has_column('timesheet_disputes','payable_hours')) ? 'd.payable_hours,' : 'NULL AS payable_hours,';

$row = db_fetch_one(" 
  SELECT
    t.timesheet_id, t.assignment_id, t.work_date, t.hours_worked, t.description, t.status AS timesheet_status,
    ja.paralegal_id,
    j.title AS job_title, j.employer_id,
    eu.full_name AS employer_name,
    q.reason AS employer_query_reason,
    q.para_response,
    q.employer_reply,
    d.dispute_text,
    d.resolved_action AS resolution,
    d.resolved_note   AS admin_notes,
    d.resolved_at     AS closed_at,
    $payable_hours_sql
    d.created_at AS dispute_created_at
  FROM timesheets t
  JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users eu ON eu.user_id = j.employer_id
  LEFT JOIN timesheet_queries q ON q.timesheet_id = t.timesheet_id
  LEFT JOIN timesheet_disputes d ON d.timesheet_id = t.timesheet_id AND d.status='Resolved'
  WHERE t.timesheet_id=? AND ja.paralegal_id=?
  ORDER BY d.resolved_at DESC, q.created_at DESC
  LIMIT 1
", [$timesheet_id, $uid]);
if (!$row) { http_response_code(404); exit('Not found'); }

render('paralegal/timesheet_dispute_result', compact('title','row'));
