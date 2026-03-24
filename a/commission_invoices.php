<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Invoices';

$rows = db_fetch_all("
  SELECT
    pi.invoice_id,
    pi.employer_id,
    pi.paralegal_id,
    pi.period_start,
    pi.period_end,
    pi.total_hours,
    pi.gross_amount,
    pi.status,
    pi.created_at,
    pi.paid_at,
    eu.full_name AS employer_name,
    ep.firm_name,
    pu.full_name AS paralegal_name,
    (
      SELECT GROUP_CONCAT(DISTINCT NULLIF(TRIM(pii.client_ref_snapshot), '') ORDER BY pii.client_ref_snapshot SEPARATOR ', ')
      FROM paralegal_invoice_items pii
      WHERE pii.invoice_id = pi.invoice_id
    ) AS client_refs
  FROM paralegal_invoices pi
  JOIN users eu ON eu.user_id = pi.employer_id
  JOIN users pu ON pu.user_id = pi.paralegal_id
  LEFT JOIN employer_profiles ep ON ep.user_id = pi.employer_id
  ORDER BY pi.period_start DESC, pi.invoice_id DESC
");

$commission_rate_default = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);
render('admin/commission_invoices', compact('title','rows','commission_rate_default'));
