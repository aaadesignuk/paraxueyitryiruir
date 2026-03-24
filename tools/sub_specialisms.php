<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$specialism = trim($_GET['specialism'] ?? '');
if ($specialism === '') {
  echo json_encode([]);
  exit;
}

$rows = db_fetch_all(
  "SELECT TRIM(sub_specialism) AS sub_specialism
   FROM specialisms
   WHERE TRIM(specialism) = ?
     AND sub_specialism IS NOT NULL
     AND TRIM(sub_specialism) <> ''
   ORDER BY TRIM(sub_specialism)",
  [$specialism]
);

$out = [];
foreach ($rows as $r) {
  $out[] = $r['sub_specialism'];
}

echo json_encode($out);
