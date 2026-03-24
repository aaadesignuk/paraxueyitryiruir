<?php
// /p/assignments.php
// Legacy link support: older UI/bookmarks may point here.
// The current assignments list is /p/jobs.php.

require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);

redirect('/p/jobs.php');
