<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$employer_id = auth_user_id();
$invoice_id = (int)($_GET['id'] ?? 0);
if ($invoice_id <= 0) redirect('/e/paralegal_invoices.php');

$inv = db_fetch_one(
  "SELECT pi.*
   FROM paralegal_invoices pi
   WHERE pi.invoice_id=? AND pi.employer_id=?
   LIMIT 1",
  [$invoice_id, $employer_id]
);
if (!$inv) {
  flash('Invoice not found.', 'error');
  redirect('/e/paralegal_invoices.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'mark_paid' && ($inv['status'] ?? '') !== 'Paid') {
    db_query("UPDATE paralegal_invoices SET status='Paid', paid_at=NOW() WHERE invoice_id=?", [$invoice_id]);
    notify((int)$inv['paralegal_id'], "Your invoice #{$invoice_id} has been marked as paid by the employer.");
    flash('Marked as paid.', 'success');
    redirect('/e/paralegal_invoice.php?id='.$invoice_id);
    exit;
  }
}

$items = db_fetch_all(
  "SELECT
      pii.work_date,
      COALESCE(NULLIF(pii.client_ref_snapshot,''), NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS employer_ref,
      COALESCE(NULLIF(t.description,''), '—') AS description,
      pii.hours,
      pii.hourly_rate,
      pii.amount
   FROM paralegal_invoice_items pii
   LEFT JOIN timesheets t ON t.timesheet_id = pii.timesheet_id
   LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
   LEFT JOIN jobs j ON j.job_id = ja.job_id
   WHERE pii.invoice_id=?
   ORDER BY pii.work_date ASC, pii.item_id ASC",
  [$invoice_id]
);

$client_refs = db_fetch_all(
  "SELECT DISTINCT COALESCE(NULLIF(pii.client_ref_snapshot,''), NULLIF(j.employer_client_ref,''), NULLIF(j.client_ref,''), '') AS client_ref
   FROM paralegal_invoice_items pii
   LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
   LEFT JOIN jobs j ON j.job_id = ja.job_id
   WHERE pii.invoice_id = ?
   HAVING client_ref <> ''
   ORDER BY client_ref ASC",
  [$invoice_id]
);

$job_titles = db_fetch_all(
  "SELECT DISTINCT COALESCE(NULLIF(pii.job_title_snapshot,''), NULLIF(j.title,''), '') AS job_title
   FROM paralegal_invoice_items pii
   LEFT JOIN job_assignments ja ON ja.assignment_id = pii.assignment_id
   LEFT JOIN jobs j ON j.job_id = ja.job_id
   WHERE pii.invoice_id = ?
   HAVING job_title <> ''
   ORDER BY job_title ASC",
  [$invoice_id]
);

$inv['client_refs_display'] = !empty($client_refs)
  ? implode(', ', array_map(fn($r) => (string)$r['client_ref'], $client_refs))
  : '';

$inv['jobs_display'] = !empty($job_titles)
  ? implode(', ', array_map(fn($r) => (string)$r['job_title'], $job_titles))
  : '';

$paralegal = db_fetch_one("SELECT full_name, email FROM users WHERE user_id=? LIMIT 1", [(int)$inv['paralegal_id']]);

$title = 'Paralegal Invoice #'.$invoice_id;
render('employer/paralegal_invoice', compact('title','inv','items','paralegal'));