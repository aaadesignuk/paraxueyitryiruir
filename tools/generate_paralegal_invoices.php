<?php
// Run manually or via cron to generate paralegal monthly invoices.
// Example: /tools/generate_paralegal_invoices.php?y=2026&m=1

require_once __DIR__ . '/../app/bootstrap.php';

// Optional guard: only allow logged-in Admin (if a session exists).
if (auth_user() && auth_user()['role'] !== ROLE_ADMIN) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

$y = (int)($_GET['y'] ?? date('Y'));
$m = (int)($_GET['m'] ?? (date('n') - 1));
if ($m <= 0) { $m = 12; $y--; }
if ($m < 1 || $m > 12) { $m = (int)date('n'); }

$res = paralegal_generate_monthly_invoices_all($y, $m);

header('Content-Type: text/plain; charset=utf-8');
echo "Paralegal invoice generation for {$y}-".str_pad((string)$m,2,'0',STR_PAD_LEFT)."\n";
echo "Created: ".($res['created'] ?? 0)."\n";
echo "Skipped: ".($res['skipped'] ?? 0)."\n";
