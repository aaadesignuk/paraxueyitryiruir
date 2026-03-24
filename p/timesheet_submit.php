<?php
// /p/timesheet_submit.php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Time Entry';
$uid = (int)auth_user()['user_id'];

$timesheet_id  = (int)($_GET['timesheet_id'] ?? 0);
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

if ($timesheet_id <= 0 && $assignment_id <= 0) {
  http_response_code(400);
  exit('Invalid request (missing timesheet_id or assignment_id).');
}

function calc_hours_from_times(string $start, string $end): ?float {
  $start = trim($start);
  $end   = trim($end);
  if ($start === '' || $end === '') return null;

  $st = strtotime("1970-01-01 {$start}:00");
  $et = strtotime("1970-01-01 {$end}:00");
  if ($st === false || $et === false) return null;

  if ($et <= $st) $et += 86400; // allow crossing midnight
  $minutes = (int)round(($et - $st) / 60);
  if ($minutes <= 0) return null;
  return $minutes / 60;
}

function infer_type_from_desc(string $desc): string {
  if (preg_match('/^\[(Work|Travel)\]/i', trim($desc), $m)) {
    $t = ucfirst(strtolower($m[1]));
    return in_array($t, ['Work','Travel'], true) ? $t : 'Work';
  }
  return 'Work';
}

function clean_desc_prefix(string $desc): string {
  return preg_replace('/^\[(Work|Travel)\]\s*/i', '', (string)$desc);
}

// Detect optional fields on timesheets
$has_start = function_exists('db_has_column') ? db_has_column('timesheets','start_time') : false;
$has_end   = function_exists('db_has_column') ? db_has_column('timesheets','end_time') : false;
$has_submitted_at = function_exists('db_has_column') ? db_has_column('timesheets','submitted_at') : false;
$has_reviewed_at  = function_exists('db_has_column') ? db_has_column('timesheets','reviewed_at') : false;

$type_col = null;
foreach (['work_type','type','entry_type'] as $c) {
  if (function_exists('db_has_column') && db_has_column('timesheets', $c)) { $type_col = $c; break; }
}

// Load existing entry (edit/view)
$timesheet = null;
if ($timesheet_id > 0) {
  $timesheet = db_fetch_one("
    SELECT t.*
    FROM timesheets t
    JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
    WHERE t.timesheet_id = ? AND ja.paralegal_id = ?
    LIMIT 1
  ", [$timesheet_id, $uid]);

  if (!$timesheet) {
    http_response_code(404);
    exit('Time entry not found.');
  }

  $assignment_id = (int)$timesheet['assignment_id'];
}

// Validate assignment belongs to paralegal + load header info
$assn = db_fetch_one("
  SELECT ja.*, j.title AS job_title, j.employer_id, eu.full_name AS employer_name
  FROM job_assignments ja
  JOIN jobs j ON j.job_id = ja.job_id
  JOIN users eu ON eu.user_id = j.employer_id
  WHERE ja.assignment_id = ? AND ja.paralegal_id = ?
  LIMIT 1
", [$assignment_id, $uid]);

if (!$assn) {
  http_response_code(404);
  exit('Assignment not found.');
}

$status = $timesheet ? (string)($timesheet['status'] ?? 'New') : 'New';
$display_status = ($status === 'Rejected') ? 'Queried' : ($timesheet ? $status : 'Draft');

// Keep originals locked after first submission. Queried items can be responded to or disputed, but not edited in place.
$can_edit = (!$timesheet) || $status === 'Draft';

// Flow flags
$is_new_entry    = !$timesheet;
$is_draft_edit   = $timesheet && $status === 'Draft';
$is_queried_edit = false;

// Date guard: only today or yesterday for brand-new entries
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$allowed_dates = [$today, $yesterday];

// Query note (if queried)
$query_note = '';
if ($timesheet && $status === 'Rejected') {
  $query_note = (string)db_fetch_value("
    SELECT reason
    FROM timesheet_queries
    WHERE timesheet_id=?
    ORDER BY created_at DESC
    LIMIT 1
  ", [(int)$timesheet_id]);
  $query_note = trim($query_note);
}

// Query conversation (for gating disputes)
$query_thread = null;
if ($timesheet && $status === 'Rejected') {
  if (function_exists('db_has_table') && db_has_table('timesheet_queries')) {
    $query_thread = db_fetch_one("
      SELECT *
      FROM timesheet_queries
      WHERE timesheet_id=?
      ORDER BY created_at DESC
      LIMIT 1
    ", [(int)$timesheet_id]);
  }
}

// Existing open dispute (only if queried)
$open_dispute = null;
if ($timesheet && $status === 'Rejected') {
  try {
    $open_dispute = db_fetch_one("
      SELECT *
      FROM timesheet_disputes
      WHERE timesheet_id=? AND status='Open'
      ORDER BY created_at DESC
      LIMIT 1
    ", [(int)$timesheet_id]);
  } catch (Throwable $e) {
    $open_dispute = null;
  }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');

  // ----- Paralegal respond to query -----
  if ($action === 'respond_query') {
    if (!$timesheet) {
      flash('Time entry not found.', 'error');
      redirect('/p/assignment.php?id=' . (int)$assignment_id . '#timesheets');
      exit;
    }
    if ((string)($timesheet['status'] ?? '') !== 'Rejected') {
      flash('Only queried entries can be responded to.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
      exit;
    }
    if (!function_exists('db_has_table') || !db_has_table('timesheet_queries')) {
      flash('Queries table is missing. Please apply the DB change first.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
      exit;
    }

    $resp = trim((string)($_POST['para_response'] ?? ''));
    if (mb_strlen($resp) < 5) {
      flash('Please add a short response (at least 5 characters).', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'#query');
      exit;
    }

    db_execute("
      UPDATE timesheet_queries
      SET para_response=?, para_responded_at=NOW()
      WHERE timesheet_id=?
      ORDER BY created_at DESC
      LIMIT 1
    ", [$resp, (int)$timesheet_id]);

    $empId = (int)($assn['employer_id'] ?? 0);
    if ($empId > 0) {
      notify($empId, "Paralegal responded to your time entry query (Entry #".(int)$timesheet_id.").");
    }

    flash('Response sent to employer.', 'success');
    redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
    exit;
  }

  // ----- Raise dispute -----
  if ($action === 'raise_dispute') {
    if (!$timesheet) {
      flash('Time entry not found for dispute.', 'error');
      redirect('/p/assignment.php?id=' . (int)$assignment_id . '#timesheets');
      exit;
    }
    if ((string)($timesheet['status'] ?? '') !== 'Rejected') {
      flash('Only queried entries can be disputed.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
      exit;
    }
    if (!function_exists('db_has_table') || !db_has_table('timesheet_queries')) {
      flash('Queries table is missing. Please apply the DB change first.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
      exit;
    }

    $q = db_fetch_one("
      SELECT para_response, para_responded_at, employer_reply, employer_replied_at
      FROM timesheet_queries
      WHERE timesheet_id=?
      ORDER BY created_at DESC
      LIMIT 1
    ", [(int)$timesheet_id]);

    $para_ok = $q && trim((string)($q['para_response'] ?? '')) !== '' && !empty($q['para_responded_at']);
    $emp_ok  = $q && trim((string)($q['employer_reply'] ?? '')) !== '' && !empty($q['employer_replied_at']);

    if (!$para_ok) {
      flash('You must respond to the employer query before raising a dispute.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'#query');
      exit;
    }
    if (!$emp_ok) {
      flash('The employer must reply to your response before you can raise a dispute.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'#query');
      exit;
    }

    $dispute_text = trim((string)($_POST['dispute_text'] ?? ''));
    if (mb_strlen($dispute_text) < 10) {
      flash('Please enter a clear appeal to admin (at least 10 characters).', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'&dispute=1#dispute');
      exit;
    }

    try {
      $open = (int)db_fetch_value("
        SELECT COUNT(*)
        FROM timesheet_disputes
        WHERE timesheet_id=? AND status='Open'
      ", [(int)$timesheet_id]);
    } catch (Throwable $e) {
      flash('Disputes table is missing. Please apply the DB change first.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'&dispute=1#dispute');
      exit;
    }

    if ($open > 0) {
      flash('A dispute is already open for this entry.', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id.'&dispute=1#dispute');
      exit;
    }

    $employer_id_for_dispute = (int)($assn['employer_id'] ?? 0);
    if ($employer_id_for_dispute <= 0) {
      flash('Unable to raise dispute (missing employer).', 'error');
      redirect('/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id);
      exit;
    }

    db_execute("
      INSERT INTO timesheet_disputes
        (timesheet_id, employer_id, paralegal_id, assignment_id, dispute_text, status)
      VALUES
        (?, ?, ?, ?, ?, 'Open')
    ", [
      (int)$timesheet_id,
      $employer_id_for_dispute,
      (int)$uid,
      (int)$assignment_id,
      $dispute_text
    ]);

    flash('Dispute raised to admin.', 'success');
    redirect('/p/assignment.php?id=' . (int)$assignment_id . '&timesheet_id='.(int)$timesheet_id.'#timesheets');
    exit;
  }

  // ----- Delete draft entry -----
  if ($action === 'delete_entry') {
    $del_id = (int)($_POST['timesheet_id'] ?? 0);
    $wd = trim((string)($_POST['work_date'] ?? $today));
    if (!in_array($wd, $allowed_dates, true)) $wd = $today;

    if ($del_id <= 0) {
      flash('Invalid entry.', 'error');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($wd));
      exit;
    }

    $row = db_fetch_one("
      SELECT t.*
      FROM timesheets t
      JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
      WHERE t.timesheet_id=? AND ja.paralegal_id=?
      LIMIT 1
    ", [$del_id, $uid]);

    if (!$row || (string)($row['status'] ?? '') !== 'Draft') {
      flash('Only draft entries can be deleted.', 'error');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($wd));
      exit;
    }

    db_execute("DELETE FROM timesheets WHERE timesheet_id=? LIMIT 1", [$del_id]);
    flash('Draft entry deleted.', 'success');
    redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($wd));
    exit;
  }

  // ----- Submit daily timesheet (all draft entries for day) -----
  if ($action === 'submit_day') {
    $work_date = trim((string)($_POST['work_date'] ?? $today));

    if (!in_array($work_date, $allowed_dates, true)) {
      flash('You can only submit entries for today or yesterday.', 'error');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id);
      exit;
    }

    $cnt = (int)db_fetch_value("
      SELECT COUNT(*)
      FROM timesheets
      WHERE assignment_id=? AND work_date=? AND status='Draft'
    ", [$assignment_id, $work_date]);

    if ($cnt <= 0) {
      flash('No draft entries to submit for this day.', 'error');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;
    }

    $set = "status='Submitted'";
    if ($has_submitted_at) $set .= ", submitted_at=NOW()";
    if ($has_reviewed_at)  $set .= ", reviewed_at=NULL";

    db_execute("
      UPDATE timesheets
      SET {$set}
      WHERE assignment_id=? AND work_date=? AND status='Draft'
    ", [$assignment_id, $work_date]);

    notify($uid, "Daily timesheet submitted for Assignment #{$assignment_id} ({$work_date}).");
    flash('Daily timesheet submitted.', 'success');
    redirect('/p/assignment.php?id=' . (int)$assignment_id . '#timesheets');
    exit;
  }

  // ----- Save entry (draft) / resubmit queried entry -----
  if ($action === 'save_entry') {

    // Existing record must be editable
    if ($timesheet && !$can_edit) {
      flash('This entry can’t be edited in its current status.', 'error');
      redirect('/p/assignment.php?id=' . (int)$assignment_id . '#timesheets');
      exit;
    }

    $work_date  = trim((string)($_POST['work_date'] ?? $today));
    $work_type  = trim((string)($_POST['work_type'] ?? 'Work'));
    $start_time = trim((string)($_POST['start_time'] ?? ''));
    $end_time   = trim((string)($_POST['end_time'] ?? ''));
    $desc       = trim((string)($_POST['description'] ?? ''));

    // Date rules:
    // - new entry: today/yesterday only
    // - draft edit: editable normally
    // - queried edit: keep original stored historical date
    if ($is_new_entry) {
      if (!in_array($work_date, $allowed_dates, true)) {
        flash('You can only add entries for today or yesterday.', 'error');
        redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id);
        exit;
      }
    } elseif ($is_queried_edit) {
      $work_date = (string)($timesheet['work_date'] ?? $work_date);
    }

    // STRICT LOCK AFTER SUBMISSION:
    // If ANY entry for this assignment+date has been submitted (or later), the paralegal
    // cannot add NEW entries for that day. They may only edit existing queried entries.
    if (!$timesheet) {
      $non_draft = (int)db_fetch_value(
        "SELECT COUNT(*) FROM timesheets WHERE assignment_id=? AND work_date=? AND status <> 'Draft'",
        [$assignment_id, $work_date]
      );
      if ($non_draft > 0) {
        flash('This day has already been submitted. You can\'t add new entries for this day. Only queried entries can be edited/resubmitted.', 'error');
        redirect('/p/timesheet_day.php?assignment_id='.(int)$assignment_id.'&date='.urlencode($work_date));
        exit;
      }
    }

    if (!in_array($work_type, ['Work','Travel'], true)) $work_type = 'Work';

    // Start/end REQUIRED now
    if ($start_time === '' || $end_time === '') {
      flash('Start and end time are required.', 'error');
      redirect($timesheet ? '/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id : '/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;
    }

    $hours = calc_hours_from_times($start_time, $end_time);
    if ($hours === null || $hours <= 0) {
      flash('End time must be after start time.', 'error');
      redirect($timesheet ? '/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id : '/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;
    }

    if (mb_strlen($desc) < 3) {
      flash('Please enter a clear description of the work done.', 'error');
      redirect($timesheet ? '/p/timesheet_submit.php?timesheet_id='.(int)$timesheet_id : '/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;
    }

    $desc_clean = clean_desc_prefix($desc);
    $desc_to_store = ($type_col ? $desc_clean : '['.$work_type.'] '.$desc_clean);

    $set_cols = "work_date=?, description=?, hours_worked=?";
    $args = [$work_date, $desc_to_store, (float)$hours];

    if ($type_col) { $set_cols .= ", {$type_col}=?"; $args[] = $work_type; }
    if ($has_start) { $set_cols .= ", start_time=?"; $args[] = $start_time; }
    if ($has_end)   { $set_cols .= ", end_time=?";   $args[] = $end_time; }

    if ($timesheet) {
      $args[] = (int)$timesheet_id;
      db_execute("UPDATE timesheets SET {$set_cols} WHERE timesheet_id=? LIMIT 1", $args);

      // If it was queried, resubmitting puts it back to Submitted (employer re-reviews)
      if ((string)($timesheet['status'] ?? '') === 'Rejected') {
        $set = "status='Submitted'";
        if ($has_submitted_at) $set .= ", submitted_at=NOW()";
        if ($has_reviewed_at)  $set .= ", reviewed_at=NULL";
        db_execute("UPDATE timesheets SET {$set} WHERE timesheet_id=? LIMIT 1", [(int)$timesheet_id]);
        flash('Entry updated and resubmitted.', 'success');
        redirect('/p/assignment.php?id='.(int)$assignment_id.'#timesheets');
        exit;
      }

      flash('Draft entry updated.', 'success');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;

    } else {
      // Create new row as Draft (NOT Submitted)
      $cols = "assignment_id, work_date, description, hours_worked, status";
      $vals = "?, ?, ?, ?, 'Draft'";
      $iargs = [$assignment_id, $work_date, $desc_to_store, (float)$hours];

      if ($type_col) { $cols .= ", {$type_col}"; $vals .= ", ?"; $iargs[] = $work_type; }
      if ($has_start) { $cols .= ", start_time"; $vals .= ", ?"; $iargs[] = $start_time; }
      if ($has_end)   { $cols .= ", end_time";   $vals .= ", ?"; $iargs[] = $end_time; }

      db_execute("INSERT INTO timesheets ({$cols}) VALUES ({$vals})", $iargs);

      flash('Draft time entry saved.', 'success');
      redirect('/p/timesheet_submit.php?assignment_id='.(int)$assignment_id.'&date='.e($work_date));
      exit;
    }
  }

  // Fallback
  flash('Invalid action.', 'error');
  redirect('/p/assignment.php?id=' . (int)$assignment_id . '#timesheets');
  exit;
}

// Work date selection (GET): allow today/yesterday for new entries.
// Existing entries use their stored work_date.
$work_date_value = trim((string)($_GET['date'] ?? ''));
if ($timesheet) {
  $work_date_value = (string)($timesheet['work_date'] ?? $today);
} else {
  if ($work_date_value === '' || !in_array($work_date_value, $allowed_dates, true)) $work_date_value = $today;
}

// Pre-fill form fields
$desc_value  = $timesheet ? clean_desc_prefix((string)($timesheet['description'] ?? '')) : '';
$hours_value = (string)($timesheet['hours_worked'] ?? '');

$start_value = '';
$end_value   = '';
if ($timesheet) {
  if ($has_start && !empty($timesheet['start_time'])) $start_value = substr((string)$timesheet['start_time'], 0, 5);
  if ($has_end && !empty($timesheet['end_time']))     $end_value   = substr((string)$timesheet['end_time'], 0, 5);
}

$work_type_value = 'Work';
if ($timesheet) {
  if ($type_col && isset($timesheet[$type_col])) $work_type_value = (string)$timesheet[$type_col];
  if (!$type_col) $work_type_value = infer_type_from_desc((string)($timesheet['description'] ?? ''));
}
if (!in_array($work_type_value, ['Work','Travel'], true)) $work_type_value = 'Work';

// Draft entries list for the selected day (assignment_id + work_date)
$draft_entries = db_fetch_all("
  SELECT *
  FROM timesheets
  WHERE assignment_id=? AND work_date=? AND status='Draft'
  ORDER BY
    CASE WHEN start_time IS NULL THEN 1 ELSE 0 END,
    start_time ASC,
    timesheet_id ASC
", [$assignment_id, $work_date_value]);

render('paralegal/timesheet_submit', compact(
  'title',
  'assn',
  'assignment_id',
  'timesheet_id',
  'status',
  'display_status',
  'query_note',
  'query_thread',
  'open_dispute',
  'can_edit',
  'is_new_entry',
  'is_draft_edit',
  'is_queried_edit',
  'work_date_value',
  'start_value',
  'end_value',
  'hours_value',
  'desc_value',
  'work_type_value',
  'draft_entries',
  'today',
  'yesterday'
));