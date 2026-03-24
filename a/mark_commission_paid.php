<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['A']);

$invoice_id = (int)($_POST['invoice_id'] ?? 0);
if (!$invoice_id) {
  flash('Invalid invoice.', 'error');
  redirect('/a/commission_invoices.php');
}

db_query("
  UPDATE commission_invoices
  SET status='Paid', paid_at=NOW()
  WHERE invoice_id=?
", [$invoice_id]);

flash('Invoice marked as paid.', 'success');
redirect('/a/commission_invoices.php');
