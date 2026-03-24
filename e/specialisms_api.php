<?php require_once __DIR__.'/../app/bootstrap.php'; require_role([ROLE_EMPLOYER]);
header('Content-Type: application/json; charset=utf-8');
$sp=trim($_GET['specialism']??''); if($sp===''){echo json_encode(['sub_specialisms'=>[]]);exit;}
$subs=array_map(fn($r)=>$r['sub_specialism'], db_fetch_all("SELECT sub_specialism FROM specialisms WHERE specialism=? ORDER BY sub_specialism",[$sp]));
echo json_encode(['sub_specialisms'=>array_values(array_filter($subs))]);
