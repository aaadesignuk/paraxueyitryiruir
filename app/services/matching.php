<?php

function match_paralegals_for_job($job_id, $limit = 25){
  $job = db_fetch_one("SELECT * FROM jobs WHERE job_id=? LIMIT 1", [(int)$job_id]);
  if(!$job) return [];

  $job_id = (int)$job_id;
  $sp  = (string)($job['specialism'] ?? '');
  $sub = (string)($job['sub_specialism'] ?? '');
  // Rate matching (simple): employer chooses a rate type + an offered rate.
  // Backwards compatible with older schemas that only have max_rate.
  $rate_type  = strtolower(trim((string)($job['rate_type'] ?? 'standard')));
  if (!in_array($rate_type, ['standard','urgent','overnight','specialist'], true)) {
    $rate_type = 'standard';
  }
  $offer = $job['rate_amount'] ?? null;
  if ($offer === null || $offer === '') {
    $offer = $job['max_rate'] ?? null;
  }
  $offer = ($offer === null || $offer === '') ? null : (float)$offer;
  $on_site = (int)($job['on_site'] ?? 0);

  // Employer location used when on-site is required (simple MVP)
  $employer_location = (string)db_fetch_value(
    "SELECT location FROM employer_profiles WHERE user_id = ?",
    [(int)$job['employer_id']]
  );
  $employer_location = trim($employer_location);

  // Count required task activities for this job (0 => skip task scoring)
  $required_count = (int)db_fetch_value("SELECT COUNT(*) FROM job_task_requirements WHERE job_id = ?", [$job_id]);

  // Schema compatibility: premium rate columns may not exist yet in some environments.
  $has_urgent = function_exists('db_has_column') ? db_has_column('paralegal_profiles', 'preferred_rate_urgent') : false;
  $has_overnight = function_exists('db_has_column') ? db_has_column('paralegal_profiles', 'preferred_rate_overnight') : false;
  $has_specialist = function_exists('db_has_column') ? db_has_column('paralegal_profiles', 'preferred_rate_specialist') : false;

  $col_urgent = $has_urgent ? 'pp.preferred_rate_urgent' : 'NULL';
  $col_overnight = $has_overnight ? 'pp.preferred_rate_overnight' : 'NULL';
  $col_specialist = $has_specialist ? 'pp.preferred_rate_specialist' : 'NULL';

  $sql = "
    SELECT
      u.user_id,
      u.full_name,
      pp.preferred_rate,
      {$col_urgent} AS preferred_rate_urgent,
      {$col_overnight} AS preferred_rate_overnight,
      {$col_specialist} AS preferred_rate_specialist,
      pp.is_available,
      pp.location_preference,

      -- Rate used for this match (falls back to base preferred_rate)
      (
        CASE
          WHEN ? = 'urgent' THEN COALESCE(NULLIF({$col_urgent},0), pp.preferred_rate)
          WHEN ? = 'overnight' THEN COALESCE(NULLIF({$col_overnight},0), pp.preferred_rate)
          WHEN ? = 'specialist' THEN COALESCE(NULLIF({$col_specialist},0), pp.preferred_rate)
          ELSE pp.preferred_rate
        END
      ) AS matched_rate,

      -- Task overlap scoring
      (
        SELECT COUNT(*)
        FROM job_task_requirements jtr
        JOIN paralegal_task_skills pts
          ON pts.activity_id = jtr.activity_id
         AND pts.paralegal_id = u.user_id
        WHERE jtr.job_id = ?
      ) AS matched_tasks,

      ? AS required_tasks

    FROM users u
    JOIN paralegal_profiles pp ON pp.user_id = u.user_id
    WHERE u.role = ?
      AND u.is_active = 1
      AND pp.is_available = 1

      -- Specialism match (MVP: allow base specialism match OR combined 'Specialism — Sub')
      AND (
        ? = ''
        OR pp.specialism IS NULL
        OR pp.specialism = ?
        OR pp.specialism = CONCAT(?, ' — ', ?)
      )

      -- Rate filter (offer is in £/hr). We match if paralegal's relevant rate is <= offer.
      -- If offer is NULL, skip rate filtering.
      AND (
        ? IS NULL
        OR (
          CASE
            WHEN ? = 'urgent' THEN COALESCE(NULLIF({$col_urgent},0), pp.preferred_rate)
            WHEN ? = 'overnight' THEN COALESCE(NULLIF({$col_overnight},0), pp.preferred_rate)
            WHEN ? = 'specialist' THEN COALESCE(NULLIF({$col_specialist},0), pp.preferred_rate)
            ELSE pp.preferred_rate
          END
        ) IS NULL
        OR (
          CASE
            WHEN ? = 'urgent' THEN COALESCE(NULLIF({$col_urgent},0), pp.preferred_rate)
            WHEN ? = 'overnight' THEN COALESCE(NULLIF({$col_overnight},0), pp.preferred_rate)
            WHEN ? = 'specialist' THEN COALESCE(NULLIF({$col_specialist},0), pp.preferred_rate)
            ELSE pp.preferred_rate
          END
        ) <= ?
      )

      -- On-site location filter (MVP string contains)
      AND (
        ? = 0
        OR ? = ''
        OR pp.location_preference IS NULL
        OR pp.location_preference LIKE CONCAT('%', ?, '%')
        OR pp.location_preference LIKE '%Hybrid%'
        OR pp.location_preference LIKE '%On-site%'
      )

      -- Exclude anyone already invited for this job
      AND NOT EXISTS (
        SELECT 1
        FROM job_invitations ji
        WHERE ji.job_id = ?
          AND ji.paralegal_id = u.user_id
      )

      -- Exclude anyone already assigned to this job
      AND NOT EXISTS (
        SELECT 1
        FROM job_assignments ja
        WHERE ja.job_id = ?
          AND ja.paralegal_id = u.user_id
      )

    ORDER BY
      -- if tasks are required, rank by task overlap ratio then matched tasks
      (CASE WHEN ? > 0 THEN (matched_tasks / NULLIF(?,0)) ELSE 0 END) DESC,
      matched_tasks DESC,
      (pp.specialism = ?) DESC,
      (matched_rate IS NULL) ASC,
      matched_rate ASC
    LIMIT " . (int)$limit;

  return db_fetch_all($sql, [
    // matched_rate CASE (3)
    $rate_type, $rate_type, $rate_type,
    // matched_tasks (job_id)
    $job_id,
    // required_tasks
    $required_count,
    // role
    ROLE_PARALEGAL,
    // specialism filter
    $sp,
    $sp,
    $sp, $sub,
    // rate filter
    $offer,
    $rate_type, $rate_type, $rate_type,
    $rate_type, $rate_type, $rate_type,
    $offer,
    // on-site
    $on_site,
    $employer_location,
    $employer_location,
    // exclude invited/assigned
    $job_id,
    $job_id,
    // order
    $required_count, $required_count,
    $sp
  ]);
}
