<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Billing & Commission';

// Employer invoices (paralegal invoices) + link to matching commission invoice (same employer + period)
$employer_invoices = db_fetch_all("
  SELECT
    pi.invoice_id,
    pi.employer_id,
    eu.full_name AS employer_name,
    ep.firm_name,
    pi.paralegal_id,
    pu.full_name AS paralegal_name,
    pi.period_start,
    pi.period_end,
    pi.total_hours,
    pi.gross_amount,
    pi.status,
    pi.created_at,
    ci.invoice_id AS commission_invoice_id,
    ci.status AS commission_status
  FROM paralegal_invoices pi
  JOIN users eu ON eu.user_id = pi.employer_id
  JOIN users pu ON pu.user_id = pi.paralegal_id
  LEFT JOIN employer_profiles ep ON ep.user_id = pi.employer_id
  LEFT JOIN commission_invoices ci
    ON ci.employer_id = pi.employer_id
   AND ci.period_start = pi.period_start
   AND ci.period_end = pi.period_end
  ORDER BY pi.created_at DESC, pi.invoice_id DESC
  LIMIT 200
");

// Commission invoices list
$commission_invoices = db_fetch_all("
  SELECT
    ci.*,
    u.full_name AS employer_name,
    ep.firm_name
  FROM commission_invoices ci
  JOIN users u ON u.user_id = ci.employer_id
  LEFT JOIN employer_profiles ep ON ep.user_id = ci.employer_id
  ORDER BY ci.created_at DESC, ci.invoice_id DESC
  LIMIT 200
");

render('admin/billing', compact('title','employer_invoices','commission_invoices'));
