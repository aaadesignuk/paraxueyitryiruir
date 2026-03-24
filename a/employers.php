<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$title = 'Employers';

$rows = db_fetch_all("
  SELECT u.user_id, u.full_name, u.email, u.created_at, u.is_active, u.status,
         ep.firm_name, ep.area_of_law
  FROM users u
  LEFT JOIN employer_profiles ep ON ep.user_id = u.user_id
  WHERE u.role=?
  ORDER BY u.created_at DESC
", [ROLE_EMPLOYER]);

render('admin/employers', compact('title','rows'));
