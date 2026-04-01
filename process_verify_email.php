<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/otp.inc.php';

require_login();

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /verify_email.php?error=Invalid+request');
    exit;
}

$code  = trim($_POST['otp_code'] ?? '');
$email = $_SESSION['email'] ?? '';

if (!preg_match('/^\d{6}$/', $code)) {
    header('Location: /verify_email.php?error=Please+enter+a+valid+6-digit+code');
    exit;
}

if (otp_verify($email, $code, 'verify_email')) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE members SET email_verified = 1 WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $_SESSION['email_verified'] = 1;
    header('Location: /?success=Email+verified+successfully');
    exit;
}

header('Location: /verify_email.php?error=Invalid+or+expired+code.+Please+try+again.');
exit;
