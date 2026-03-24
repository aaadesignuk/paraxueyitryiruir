<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Timesheet Disputes';
$show = strtolower(trim((string)($_GET['show'] ?? 'open')));
if (!in_array($show, ['open','resolved','all'], true)) $show = 'open';

$where = '';
if ($show === 'open') $where = "WHERE d.status='Open'";
elseif ($show === 'resolved') $where = "WHERE d.status='Resolved'";

$disputes = [];
try {
  $disputes = db_fetch_all("
    SELECT
      d.dispute_id,
      d.timesheet_id,
      d.assignment_id,
      d.status,
      d.created_at,
      d.resolved_at,
      d.payable_hours,
      d.payable_percent,
      LEFT(d.dispute_text, 220) AS dispute_preview,
      pu.full_name AS paralegal_name,
      eu.full_name AS employer_name,
      t.work_date,
      t.hours_worked,
      t.status AS timesheet_status
    FROM timesheet_disputes d
    JOIN timesheets t ON t.timesheet_id = d.timesheet_id
    JOIN users pu ON pu.user_id = d.paralegal_id
    JOIN users eu ON eu.user_id = d.employer_id
    $where
    ORDER BY COALESCE(d.resolved_at, d.created_at) DESC
    LIMIT 300
  ");
} catch (Throwable $e) {
  $disputes = [];
  flash('Could not load disputes (apply the latest DB migration first).', 'error');
}

render('admin/timesheet_disputes', compact('title','disputes','show'));
