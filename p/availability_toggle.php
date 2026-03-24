<?php require_once __DIR__.'/../app/bootstrap.php'; require_role([ROLE_PARALEGAL]);
$pid=(int)auth_user()['user_id'];
$profile=db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1",[$pid]);
if(!$profile){ db_query("INSERT INTO paralegal_profiles (user_id,is_available) VALUES (?,1)",[$pid]); $profile=db_fetch_one("SELECT * FROM paralegal_profiles WHERE user_id=? LIMIT 1",[$pid]); }
$new=((int)$profile['is_available']===1)?0:1;
db_query("UPDATE paralegal_profiles SET is_available=? WHERE user_id=?",[$new,$pid]);
flash($new?'Availability ON':'Availability OFF','info');
redirect('/p/dashboard.php');
