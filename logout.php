<?php
require_once 'inc/auth.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';

// Invalidate the PHPAuth server-side session token.
// $hash was stored at login time. Pre-migration sessions won't have it — that's fine.
$hash = $_SESSION['phpauth_session_hash'] ?? null;

if ($hash !== null) {
    try {
        $pdo  = getPHPAuthDbConnection();
        $auth = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
        $auth->logout($hash);
    } catch (Exception $e) {
        error_log("PHPAuth logout error: " . $e->getMessage());
    }
}

session_unset();
session_destroy();

header("Location: /login.php?logout=1");
exit;
