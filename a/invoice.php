<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) redirect('/a/billing.php');

$inv = db_fetch_one("SELECT * FROM paralegal_invoices WHERE invoice_id=? LIMIT 1", [$invoice_id]);
if(!$inv){ flash('Invoice not found.', 'error'); redirect('/a/billing.php'); }

$items = db_fetch_all(
  "SELECT pii.*, j.title AS job_title
     FROM paralegal_invoice_items pii
     JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
     JOIN jobs j ON j.job_id = ja.job_id
    WHERE pii.invoice_id=?
    ORDER BY pii.item_id ASC",
  [$invoice_id]
);

$employer = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['employer_id']]);
$paralegal = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['paralegal_id']]);

$title = 'Invoice #'.$invoice_id;
render('admin/invoice', compact('title','inv','items','employer','paralegal'));
