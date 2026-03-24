<?php
// Legacy endpoint: acceptance is handled via /p/invite_action.php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_PARALEGAL]);
flash('Please accept invitations from your dashboard.', 'info');
redirect('/p/dashboard.php');
