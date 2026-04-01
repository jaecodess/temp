<?php
require_once '../inc/auth.inc.php';
require_admin();
header('Location: /admin/members.php');
exit;
