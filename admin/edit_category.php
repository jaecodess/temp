<?php
require_once '../inc/auth.inc.php';
require_admin();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
header('Location: /admin/categories.php' . ($id > 0 ? '?edit=' . $id : ''));
exit;
