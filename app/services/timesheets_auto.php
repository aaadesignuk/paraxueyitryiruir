<?php

/**
 * Auto-deem approve submitted timesheets after a configurable number of hours.
 *
 * Setting:
 *  - timesheet_deemed_approve_hours (default 72)
 *
 * Eligible:
 *  - timesheets.status='Submitted'
 *  - timesheets.submitted_at is not null
 *  - submitted_at older than threshold
 *
 * Action:
 *  - status -> 'Deemed Approved'
 *  - reviewed_at -> NOW()
 */
function timesheets_apply_deemed_approval(): int {
  // If migrations not applied, do nothing safely.
  if (!function_exists('db_has_column') || !db_has_column('timesheets', 'submitted_at')) {
    return 0;
  }

  $hours = (int)setting_get('timesheet_deemed_approve_hours', '72');
  if ($hours <= 0) $hours = 72;

  db_query("
    UPDATE timesheets
    SET status='Deemed Approved', reviewed_at=NOW()
    WHERE status='Submitted'
      AND submitted_at IS NOT NULL
      AND submitted_at <= (NOW() - INTERVAL ? HOUR)
  ", [$hours]);

  // Your db wrapper doesn't expose rowcount consistently, so return a conservative count for logging/debug.
  // This is not used for logic anywhere.
  $n = (int)db_fetch_value("
    SELECT COUNT(*)
    FROM timesheets
    WHERE status='Deemed Approved'
      AND reviewed_at >= (NOW() - INTERVAL 1 MINUTE)
  ");
  return $n;
}