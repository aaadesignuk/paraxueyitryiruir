<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Paralegals';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$total = (int)db_fetch_one(
  "SELECT COUNT(*) AS c FROM users WHERE role=?",
  [ROLE_PARALEGAL]
)['c'];

$pg = pagination_meta($total, $page, $per_page);

$rows = db_fetch_all("
  SELECT u.user_id, u.full_name, u.email, u.created_at, u.is_active, u.status,
         pp.specialism, pp.experience_type, pp.experience_value, pp.preferred_rate
  FROM users u
  LEFT JOIN paralegal_profiles pp ON pp.user_id = u.user_id
  WHERE u.role=?
  ORDER BY u.created_at DESC
  LIMIT {$pg['per_page']} OFFSET {$pg['offset']}
", [ROLE_PARALEGAL]);


render('admin/paralegals', compact('title','rows','pg'));
