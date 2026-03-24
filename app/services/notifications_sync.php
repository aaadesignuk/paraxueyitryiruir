<?php

function notifications_message_column(): string {
  if (function_exists('db_has_column') && db_has_column('notifications', 'message')) return 'message';
  if (function_exists('db_has_column') && db_has_column('notifications', 'content')) return 'content';
  return 'message';
}

function notifications_insert_once(int $user_id, string $message, ?string $link = null, ?string $created_at = null): bool {
  $msgCol = notifications_message_column();
  $created_at = trim((string)$created_at);
  if ($created_at === '') $created_at = date('Y-m-d H:i:s');

  $existing = db_fetch_value(
    "SELECT notification_id FROM notifications WHERE user_id=? AND {$msgCol}=? LIMIT 1",
    [$user_id, $message]
  );
  if ($existing) return false;

  $fields = ['user_id', $msgCol, 'is_read', 'created_at'];
  $vals = [$user_id, $message, 0, $created_at];

  if (function_exists('db_has_column') && db_has_column('notifications', 'link')) {
    $fields[] = 'link';
    $vals[] = $link;
  }

  $sql = "INSERT INTO notifications (".implode(',', $fields).") VALUES (".implode(',', array_fill(0, count($fields), '?')).")";
  db_query($sql, $vals);
  return true;
}

function notifications_fetch_recent(int $user_id, int $limit = 8): array {
  $msgCol = notifications_message_column();
  $select = "notification_id, {$msgCol} AS message, is_read, created_at";
  if (function_exists('db_has_column') && db_has_column('notifications', 'link')) {
    $select .= ', link';
  } else {
    $select .= ", '' AS link";
  }

  $limit = max(1, min(20, $limit));
  return db_fetch_all(
    "SELECT {$select}
       FROM notifications
      WHERE user_id=?
      ORDER BY is_read ASC, created_at DESC, notification_id DESC
      LIMIT {$limit}",
    [$user_id]
  );
}

function notifications_unread_count(int $user_id): int {
  return (int)db_fetch_value("SELECT COUNT(*) FROM notifications WHERE user_id=? AND COALESCE(is_read,0)=0", [$user_id]);
}

function notifications_sync_for_paralegal(int $paralegal_id): void {
  // 1) New job invitations.
  $invites = db_fetch_all(
    "SELECT ji.invitation_id, ji.created_at, j.title
       FROM job_invitations ji
       JOIN jobs j ON j.job_id = ji.job_id
      WHERE ji.paralegal_id=?
        AND ji.status='Invited'",
    [$paralegal_id]
  );
  foreach ($invites as $r) {
    $message = 'New job available: '.trim((string)$r['title']);
    $link = '/p/invite.php?id='.(int)$r['invitation_id'];
    notifications_insert_once($paralegal_id, $message, $link, (string)($r['created_at'] ?? ''));
  }

  // 2) Confirmed/active assignments.
  $assignments = db_fetch_all(
    "SELECT ja.assignment_id, ja.started_at, j.title
       FROM job_assignments ja
       JOIN jobs j ON j.job_id = ja.job_id
      WHERE ja.paralegal_id=?
        AND ja.status='Active'",
    [$paralegal_id]
  );
  foreach ($assignments as $r) {
    $message = 'Job confirmed: '.trim((string)$r['title']);
    $link = '/p/assignment.php?id='.(int)$r['assignment_id'];
    notifications_insert_once($paralegal_id, $message, $link, (string)($r['started_at'] ?? ''));
  }

  // 3) Timesheet queries needing update.
  if (function_exists('db_has_table') && db_has_table('timesheet_queries')) {
    $queries = db_fetch_all(
      "SELECT q.query_id, q.created_at, t.work_date, ja.assignment_id, j.title
         FROM timesheet_queries q
         JOIN timesheets t ON t.timesheet_id = q.timesheet_id
         JOIN job_assignments ja ON ja.assignment_id = t.assignment_id
         JOIN jobs j ON j.job_id = ja.job_id
        WHERE ja.paralegal_id=?",
      [$paralegal_id]
    );
    foreach ($queries as $r) {
      $date = !empty($r['work_date']) ? uk_date($r['work_date']) : 'a timesheet';
      $message = 'Timesheet queried: '.trim((string)$r['title']).' on '.$date;
      $link = '/p/timesheets.php?status=Queried';
      notifications_insert_once($paralegal_id, $message, $link, (string)($r['created_at'] ?? ''));
    }
  }
}

function notifications_sync_for_employer(int $employer_id): void {
  // 1) Invitation accepted / declined.
  $inviteRows = db_fetch_all(
    "SELECT ji.invitation_id, ji.status, ji.created_at, j.job_id, j.title, u.full_name AS paralegal_name
       FROM job_invitations ji
       JOIN jobs j ON j.job_id = ji.job_id
       JOIN users u ON u.user_id = ji.paralegal_id
      WHERE ji.employer_id=?
        AND ji.status IN ('Accepted','Declined')",
    [$employer_id]
  );
  foreach ($inviteRows as $r) {
    $title = trim((string)$r['title']);
    $name = trim((string)$r['paralegal_name']);
    if (($r['status'] ?? '') === 'Accepted') {
      $message = $name.' accepted the job: '.$title;
    } else {
      $message = $name.' declined the job: '.$title;
    }
    $link = '/e/job_view.php?job_id='.(int)$r['job_id'];
    notifications_insert_once($employer_id, $message, $link, (string)($r['created_at'] ?? ''));
  }

  // 2) Successful placement / active assignment.
  $placements = db_fetch_all(
    "SELECT ja.assignment_id, ja.started_at, j.job_id, j.title, u.full_name AS paralegal_name
       FROM job_assignments ja
       JOIN jobs j ON j.job_id = ja.job_id
       JOIN users u ON u.user_id = ja.paralegal_id
      WHERE ja.employer_id=?
        AND ja.status='Active'",
    [$employer_id]
  );
  foreach ($placements as $r) {
    $message = 'Placement confirmed: '.trim((string)$r['paralegal_name']).' assigned to '.trim((string)$r['title']);
    $link = '/e/job_view.php?job_id='.(int)$r['job_id'];
    notifications_insert_once($employer_id, $message, $link, (string)($r['started_at'] ?? ''));
  }
}


function notifications_guess_link(array $user, string $message): string {
  $role = (string)($user['role'] ?? '');
  $message = trim($message);

  if ($role === ROLE_PARALEGAL) {
    if (stripos($message, 'Timesheet queried:') === 0) return '/p/timesheets.php?status=Queried';
    if (stripos($message, 'Job confirmed:') === 0) return '/p/assignments.php';
    if (stripos($message, 'New job available:') === 0) return '/p/jobs.php';
    return '/p/dashboard.php';
  }

  if ($role === ROLE_EMPLOYER) {
    if (stripos($message, 'Placement confirmed:') === 0) return '/e/jobs.php';
    if (stripos($message, ' accepted the job:') !== false) return '/e/jobs.php';
    if (stripos($message, ' declined the job:') !== false) return '/e/jobs.php';
    return '/e/dashboard.php';
  }

  return '/dashboard.php';
}


function notifications_fetch_all(int $user_id, int $limit = 50): array {
  $msgCol = notifications_message_column();
  $select = "notification_id, {$msgCol} AS message, is_read, created_at";
  if (function_exists('db_has_column') && db_has_column('notifications', 'link')) {
    $select .= ', link';
  } else {
    $select .= ", '' AS link";
  }

  $limit = max(1, min(200, $limit));
  return db_fetch_all(
    "SELECT {$select}
       FROM notifications
      WHERE user_id=?
      ORDER BY created_at DESC, notification_id DESC
      LIMIT {$limit}",
    [$user_id]
  );
}

function notifications_mark_read(int $user_id, int $notification_id): void {
  db_query(
    "UPDATE notifications
        SET is_read=1
      WHERE notification_id=? AND user_id=?",
    [$notification_id, $user_id]
  );
}

function notifications_mark_all_read(int $user_id): void {
  db_query(
    "UPDATE notifications
        SET is_read=1
      WHERE user_id=? AND COALESCE(is_read,0)=0",
    [$user_id]
  );
}

function notifications_sync_for_user(array $user): void {
  $role = (string)($user['role'] ?? '');
  $user_id = (int)($user['user_id'] ?? 0);
  if ($user_id <= 0) return;

  if ($role === ROLE_PARALEGAL) {
    notifications_sync_for_paralegal($user_id);
  } elseif ($role === ROLE_EMPLOYER) {
    notifications_sync_for_employer($user_id);
  }
}
