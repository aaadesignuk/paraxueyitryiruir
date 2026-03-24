<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Resolve Dispute';
$admin_id = (int)auth_user()['user_id'];
$dispute_id = (int)($_GET['id'] ?? 0);
if ($dispute_id <= 0) { http_response_code(400); exit('Missing dispute id.'); }

$has_payable_hours = function_exists('db_has_column') && db_has_column('timesheet_disputes','payable_hours');
$has_payable_percent = function_exists('db_has_column') && db_has_column('timesheet_disputes','payable_percent');

$d = db_fetch_one(" 
  SELECT d.*, pu.full_name AS paralegal_name, eu.full_name AS employer_name,
         t.work_date, t.hours_worked, t.description, t.status AS timesheet_status,
         ja.assignment_id, j.job_id, j.title AS job_title
  FROM timesheet_disputes d
  JOIN timesheets t ON t.timesheet_id = d.timesheet_id
  JOIN job_assignments ja ON ja.assignment_id = d.assignment_id
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users pu ON pu.user_id = d.paralegal_id
  JOIN users eu ON eu.user_id = d.employer_id
  WHERE d.dispute_id = ?
  LIMIT 1
", [$dispute_id]);
if (!$d) { http_response_code(404); exit('Dispute not found.'); }

$query = db_fetch_one(" 
  SELECT reason, para_response, employer_reply, created_at, para_responded_at, employer_replied_at
  FROM timesheet_queries
  WHERE timesheet_id=?
  ORDER BY created_at DESC
  LIMIT 1
", [(int)$d['timesheet_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $admin_notes = trim((string)($_POST['admin_notes'] ?? ''));
  $payable_hours = trim((string)($_POST['payable_hours'] ?? ''));
  $original_hours = (float)($d['hours_worked'] ?? 0);
  $resolved_action = 'Rejected';
  $final_hours = 0.0;

  if ($action === 'approve_full') {
    $resolved_action = 'Approved';
    $final_hours = $original_hours;
    db_execute("UPDATE timesheets SET status='Approved', reviewed_at=NOW() WHERE timesheet_id=? LIMIT 1", [(int)$d['timesheet_id']]);
  } elseif ($action === 'approve_partial') {
    $resolved_action = 'Part Approved';
    $final_hours = max(0, min($original_hours, (float)$payable_hours));
    db_execute("UPDATE timesheets SET status='Approved', reviewed_at=NOW(), hours_worked=? WHERE timesheet_id=? LIMIT 1", [$final_hours, (int)$d['timesheet_id']]);
  } elseif ($action === 'reject_timesheet') {
    $resolved_action = 'Rejected';
    $final_hours = 0.0;
    db_execute("UPDATE timesheets SET status='Rejected', reviewed_at=NOW() WHERE timesheet_id=? LIMIT 1", [(int)$d['timesheet_id']]);
  } else {
    flash('Invalid action.', 'error');
    redirect('/a/timesheet_dispute_resolve.php?id='.(int)$dispute_id);
    exit;
  }

  $sets = ["status='Resolved'", "resolved_action=?", "resolved_note=?", "resolved_at=NOW()", "resolved_by=?"];
  $params = [$resolved_action, $admin_notes, $admin_id];
  if ($has_payable_hours) { $sets[] = 'payable_hours=?'; $params[] = $final_hours; }
  if ($has_payable_percent) {
    $pct = $original_hours > 0 ? round(($final_hours / $original_hours) * 100, 2) : 0;
    $sets[] = 'payable_percent=?';
    $params[] = $pct;
  }
  $params[] = (int)$dispute_id;
  db_execute("UPDATE timesheet_disputes SET ".implode(', ', $sets)." WHERE dispute_id=? LIMIT 1", $params);

  flash('Dispute resolved.', 'success');
  redirect('/a/timesheet_disputes.php');
  exit;
}

render('admin/timesheet_dispute_resolve', compact('title','d','query','has_payable_hours'));
