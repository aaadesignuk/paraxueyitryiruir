<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['E']);
require_once __DIR__ . '/../app/services/paralegal_invoicing.php';

$employer_id = auth_user_id();
$year  = (int)($_GET['year'] ?? 0);
$month = (int)($_GET['month'] ?? 0);
$client_ref = trim((string)($_GET['client_ref'] ?? ''));

$sync_year = ($year > 0 ? $year : (int)date('Y'));
$sync_month = ($month > 0 && $month <= 12 ? $month : (int)date('n'));
$sync_rows = db_fetch_all(
  "SELECT DISTINCT ja.paralegal_id
     FROM job_assignments ja
     JOIN timesheets t ON t.assignment_id = ja.assignment_id
    WHERE ja.employer_id = ?
      AND t.status IN ('Approved','Deemed Approved')
      AND YEAR(t.work_date) = ?
      AND MONTH(t.work_date) = ?",
  [$employer_id, $sync_year, $sync_month]
);
foreach ($sync_rows as $sr) {
  paralegal_generate_monthly_invoices_for_paralegal((int)$sr['paralegal_id'], $sync_year, $sync_month);
}

$where = "pi.employer_id = ?";
$params = [$employer_id];

if ($year > 0) {
  $where .= " AND YEAR(pi.period_start) = ?";
  $params[] = $year;
}
if ($month > 0 && $month <= 12) {
  $where .= " AND MONTH(pi.period_start) = ?";
  $params[] = $month;
}
if ($client_ref !== '') {
  $where .= " AND EXISTS (
    SELECT 1 FROM paralegal_invoice_items pii
    WHERE pii.invoice_id = pi.invoice_id
      AND pii.client_ref_snapshot = ?
  )";
  $params[] = $client_ref;
}

$invoices = db_fetch_all(" 
  SELECT
    pi.*,
    u.full_name AS paralegal_name,
    (
      SELECT GROUP_CONCAT(DISTINCT NULLIF(TRIM(pii.client_ref_snapshot), '') ORDER BY pii.client_ref_snapshot SEPARATOR ', ')
      FROM paralegal_invoice_items pii
      WHERE pii.invoice_id = pi.invoice_id
    ) AS client_refs
  FROM paralegal_invoices pi
  JOIN users u ON u.user_id = pi.paralegal_id
  WHERE $where
  ORDER BY pi.period_start DESC, pi.invoice_id DESC
", $params);

$client_refs = db_fetch_all(" 
  SELECT DISTINCT pii.client_ref_snapshot AS client_ref
  FROM paralegal_invoice_items pii
  JOIN paralegal_invoices pi ON pi.invoice_id = pii.invoice_id
  WHERE pi.employer_id = ?
    AND NULLIF(TRIM(pii.client_ref_snapshot), '') IS NOT NULL
  ORDER BY pii.client_ref_snapshot ASC
", [$employer_id]);

$commission_rate_default = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);

$title = 'Paralegal Invoices';
render('employer/paralegal_invoices', compact('title', 'year', 'month', 'client_ref', 'client_refs', 'invoices', 'commission_rate_default'));
