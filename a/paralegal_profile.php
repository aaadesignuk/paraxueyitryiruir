<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_ADMIN]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/a/paralegals.php');

$paralegal = db_fetch_one(
  "SELECT u.user_id, u.full_name, u.email, u.created_at, u.is_active, u.status,
          u.approved_at, u.approved_by,
          pp.specialism, pp.experience_type, pp.experience_value, pp.preferred_rate,
          pp.location_preference, pp.skills AS skills_free_text, pp.is_available
   FROM users u
   LEFT JOIN paralegal_profiles pp ON pp.user_id = u.user_id
   WHERE u.user_id=? AND u.role=?",
  [$id, ROLE_PARALEGAL]
);
if (!$paralegal) {
  flash('Paralegal not found.', 'error');
  redirect('/a/paralegals.php');
}

// Task categories experience (optional: some environments may not have these tables yet)
$category_experience = [];
if (db_has_table('paralegal_category_experience') && db_has_table('task_categories')) {
  $category_experience = db_fetch_all(
    "SELECT t.category_name, pce.experience_type, pce.experience_value, pce.updated_at
     FROM paralegal_category_experience pce
     JOIN task_categories t ON t.category_id = pce.category_id
     WHERE pce.paralegal_id=?
     ORDER BY t.category_name ASC",
    [$id]
  );
}

// Skills (tag-based, optional)
$skills = [];
if (db_has_table('paralegal_skills') && db_has_table('skills')) {
  $skills = db_fetch_all(
    "SELECT s.skill_name
     FROM paralegal_skills ps
     JOIN skills s ON s.skill_id = ps.skill_id
     WHERE ps.user_id=?
     ORDER BY s.skill_name ASC",
    [$id]
  );
}

$title = 'Paralegal Profile';
render('admin/paralegal_profile', compact('title','paralegal','category_experience','skills'));
