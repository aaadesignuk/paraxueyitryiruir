<?php
function billing_recalc_for_assignment($assignment_id){
  // Schema compatible: jobs.rate_amount/rate_type may not exist yet.
  $jcols = ['j.max_rate'];
  if (function_exists('db_has_column') && db_has_column('jobs','rate_amount')) $jcols[] = 'j.rate_amount';
  if (function_exists('db_has_column') && db_has_column('jobs','rate_type')) $jcols[] = 'j.rate_type';
  $a=db_fetch_one(
    "SELECT ja.*, " . implode(',', $jcols) . " FROM job_assignments ja JOIN jobs j ON j.job_id=ja.job_id WHERE ja.assignment_id=? LIMIT 1",
    [(int)$assignment_id]
  );
  if(!$a) return;
  $rate=(float)($a['agreed_rate']??0);
  if($rate<=0) $rate=(float)($a['rate_amount']??0);
  if($rate<=0) $rate=(float)($a['max_rate']??0);
  $hours=(float)db_fetch_value("SELECT COALESCE(SUM(hours_worked),0) FROM timesheets WHERE assignment_id=? AND status='Approved'",[(int)$assignment_id]);
  $gross=round($hours*$rate,2);

  // Commission is configurable per rate type (falls back to default setting then config constant).
  $rt = strtolower(trim((string)($a['rate_type'] ?? 'standard')));
  if (!in_array($rt, ['standard','urgent','overnight','specialist'], true)) $rt = 'standard';

  $default_pct = (float)setting_get('commission_rate_default', (string)PLATFORM_COMMISSION_PCT);
  $pct = $default_pct;
  if ($rt !== 'standard') {
    $k = 'commission_rate_' . $rt;
    $v = setting_get($k, null);
    if ($v !== null && $v !== '') $pct = (float)$v;
  }

  $comm=round($gross*($pct/100),2);
  $net=round($gross-$comm,2);
  $ex=db_fetch_one("SELECT billing_id FROM billing_records WHERE assignment_id=? LIMIT 1",[(int)$assignment_id]);
  if($ex){db_query("UPDATE billing_records SET total_hours=?, gross_amount=?, commission_amount=?, net_amount=? WHERE assignment_id=?",[$hours,$gross,$comm,$net,(int)$assignment_id]);}
  else{db_query("INSERT INTO billing_records (assignment_id,total_hours,gross_amount,commission_amount,net_amount,status,created_at) VALUES (?,?,?,?,?,'Pending',NOW())",[(int)$assignment_id,$hours,$gross,$comm,$net]);}
}
