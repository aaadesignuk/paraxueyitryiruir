<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['E']);

$employer_id = auth_user_id();
$invoice_id = (int)($_GET['id'] ?? 0);

$inv = db_fetch_one(
  "SELECT
      pi.invoice_id,
      pi.employer_id,
      pi.paralegal_id,
      pi.job_id,
      pi.period_start,
      pi.period_end,
      pi.total_hours,
      pi.gross_amount,
      pi.status,
      pi.created_at,
      pi.paid_at,
      j.title AS job_title,
      COALESCE(NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref,
      u.full_name AS paralegal_name
   FROM paralegal_invoices pi
   LEFT JOIN jobs j ON j.job_id = pi.job_id
   LEFT JOIN users u ON u.user_id = pi.paralegal_id
   WHERE pi.invoice_id=? AND pi.employer_id=?
   LIMIT 1",
  [$invoice_id, $employer_id]
);

if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/e/paralegal_invoices.php');
  exit;
}

$commission_rate = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);
$commission_amount = round(((float)$inv['gross_amount']) * ($commission_rate / 100), 2);

$inv['commission_rate'] = $commission_rate;
$inv['commission_amount'] = $commission_amount;

$items = db_fetch_all(
  "SELECT
      pii.work_date,
      pii.client_ref_snapshot AS client_ref,
      pii.job_title_snapshot AS job_title,
      u.full_name AS paralegal_name,
      '' AS description,
      pii.hours AS hours_worked,
      pii.hourly_rate,
      pii.amount AS line_amount
   FROM paralegal_invoice_items pii
   JOIN paralegal_invoices pi ON pi.invoice_id = pii.invoice_id
   JOIN users u ON u.user_id = pi.paralegal_id
   WHERE pii.invoice_id = ?
     AND pi.employer_id = ?
   ORDER BY pii.work_date ASC, pii.item_id ASC",
  [$invoice_id, $employer_id]
);

$title = 'Commission Invoice';
render('employer/invoice', compact('title', 'inv', 'items'));