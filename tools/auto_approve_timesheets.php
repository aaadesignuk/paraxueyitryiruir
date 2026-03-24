<?php
require_once __DIR__ . '/../app/bootstrap.php';

$hours = 72;
$cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));

/**
 * Auto-approve all Submitted timesheets older than 72 hours.
 * We use reviewed_at as "decision time".
 */
$sql = "
  UPDATE timesheets
  SET status = 'Approved',
      reviewed_at = NOW()
  WHERE status = 'Submitted'
    AND reviewed_at IS NULL
    AND CONCAT(work_date, ' 00:00:00') <= ?
";

db_query($sql, [$cutoff]);

echo "Auto-approved timesheets older than {$hours} hours.\n";
