<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$paralegal_id = (int)auth_user()['user_id'];
require_once __DIR__.'/../app/services/paralegal_invoicing.php';

$y = (int)($_GET['y'] ?? 0);
$m = (int)($_GET['m'] ?? 0);
if ($y < 2000 || $y > 2100) { $y = 0; }
if ($m < 1 || $m > 12) { $m = 0; }

$sync_y = $y ?: (int)date('Y');
$sync_m = $m ?: (int)date('n');
paralegal_generate_monthly_invoices_for_paralegal($paralegal_id, $sync_y, $sync_m);

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;

$where = "pi.paralegal_id=?";
$params = [$paralegal_id];

if ($y && $m) {
  $start = sprintf('%04d-%02d-01', $y, $m);
  $end = date('Y-m-t', strtotime($start));
  $where .= " AND pi.period_start >= ? AND pi.period_start <= ?";
  $params[] = $start;
  $params[] = $end;
}

$total_row = db_fetch_one(
  "SELECT COUNT(*) AS c FROM paralegal_invoices pi WHERE $where",
  $params
);
$total = (int)($total_row['c'] ?? 0);
$pg = pagination_meta($total, $page, $per_page);

$invoices = db_fetch_all(
  "SELECT pi.invoice_id, pi.employer_id, pi.job_id, pi.period_start, pi.period_end, pi.total_hours, pi.gross_amount, pi.status, pi.created_at, pi.submitted_at, pi.paid_at,
          u.full_name AS employer_name,
          (
            SELECT GROUP_CONCAT(DISTINCT NULLIF(TRIM(pii.client_ref_snapshot), '') ORDER BY pii.client_ref_snapshot SEPARATOR ', ')
            FROM paralegal_invoice_items pii
            WHERE pii.invoice_id = pi.invoice_id
          ) AS client_refs,
          (
            SELECT COUNT(DISTINCT pii.assignment_id)
            FROM paralegal_invoice_items pii
            WHERE pii.invoice_id = pi.invoice_id
          ) AS assignment_count
     FROM paralegal_invoices pi
     JOIN users u ON u.user_id = pi.employer_id
    WHERE $where
    ORDER BY pi.period_start DESC, pi.invoice_id DESC
    LIMIT {$pg['per_page']} OFFSET {$pg['offset']}",
  $params
);

$title = 'My Invoices';
render('paralegal/invoices', compact('title','invoices','y','m','pg'));
