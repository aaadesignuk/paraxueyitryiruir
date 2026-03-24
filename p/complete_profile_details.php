<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

$title = 'Complete Profile';
$pid = (int)auth_user()['user_id'];

$task_categories = db_fetch_all("SELECT id, name FROM task_categories WHERE is_active=1 ORDER BY sort_order, id");
$task_activities = db_fetch_all("SELECT id, category_id, name FROM task_activities WHERE is_active=1 ORDER BY category_id, sort_order, id");

$existing_act_ids = db_fetch_all(
  "SELECT activity_id FROM paralegal_task_skills WHERE paralegal_id=?",
  [$pid]
);
$selected_activity_ids = array_map(fn($r) => (int)$r['activity_id'], $existing_act_ids);

$existing_cat_exp = db_fetch_all(
  "SELECT category_id, experience_type, experience_value
   FROM paralegal_category_experience
   WHERE paralegal_id=?",
  [$pid]
);
$catExpMap = [];
foreach ($existing_cat_exp as $r) {
  $catExpMap[(int)$r['category_id']] = [
    'experience_type' => $r['experience_type'],
    'experience_value' => $r['experience_value'],
  ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categories = $_POST['categories'] ?? [];
  if (!is_array($categories)) $categories = [];

  $activity_ids = $_POST['activities'] ?? [];
  if (!is_array($activity_ids)) $activity_ids = [];
  $activity_ids = array_values(array_filter(array_map('intval', $activity_ids), fn($v) => $v > 0));

  $errors = [];
  $enabledCategoryIds = [];

  foreach ($task_categories as $c) {
    $cid = (int)$c['id'];

    // ✅ NEW RULE: A category is "enabled" if experience_value is provided (no checkbox)
    $evalRaw = trim((string)($categories[$cid]['experience_value'] ?? ''));
    if ($evalRaw === '') continue;

    if (!is_numeric($evalRaw) || (float)$evalRaw < 0) {
      $errors[] = "Please enter a valid experience value for: " . ($c['name'] ?? 'a category');
      continue;
    }

    $enabledCategoryIds[] = $cid;

    $etype = $categories[$cid]['experience_type'] ?? 'Years';
    $etype = in_array($etype, ['Hours','Years'], true) ? $etype : 'Years';
  }

  if (!$errors) {
    db_query("DELETE FROM paralegal_category_experience WHERE paralegal_id=?", [$pid]);

    foreach ($enabledCategoryIds as $cid) {
      $etype = $categories[$cid]['experience_type'] ?? 'Years';
      $etype = in_array($etype, ['Hours','Years'], true) ? $etype : 'Years';
      $eval = (float)($categories[$cid]['experience_value'] ?? 0);

      db_query(
        "INSERT INTO paralegal_category_experience (paralegal_id, category_id, experience_type, experience_value)
         VALUES (?, ?, ?, ?)",
        [$pid, $cid, $etype, $eval]
      );
    }

    db_query("DELETE FROM paralegal_task_skills WHERE paralegal_id=?", [$pid]);
    foreach ($activity_ids as $aid) {
      db_query("INSERT IGNORE INTO paralegal_task_skills (paralegal_id, activity_id) VALUES (?, ?)", [$pid, $aid]);
    }

    flash('Profile updated successfully.', 'success');
    redirect('/p/complete_profile.php');
  }

  flash(implode(' ', $errors), 'error');

  $selected_activity_ids = $activity_ids;
  $catExpMap = [];
  foreach ($enabledCategoryIds as $cid) {
    $catExpMap[$cid] = [
      'experience_type' => $categories[$cid]['experience_type'] ?? 'Years',
      'experience_value' => $categories[$cid]['experience_value'] ?? '',
    ];
  }
}

render('paralegal/complete_profile', compact('title','task_categories','task_activities','selected_activity_ids','catExpMap'));
