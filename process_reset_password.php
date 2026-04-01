<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/otp.inc.php';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /forgot_password.php?error=Invalid+request');
    exit;
}

$email           = trim($_POST['email'] ?? '');
$code            = trim($_POST['otp_code'] ?? '');
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate email exists in members
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$conn->close();

if (!$found) {
    header('Location: /forgot_password.php?error=Invalid+request');
    exit;
}

if (!preg_match('/^\d{6}$/', $code)) {
    header('Location: /reset_password.php?email=' . urlencode($email) . '&error=Please+enter+a+valid+6-digit+code');
    exit;
}

if (strlen($newPassword) < 5) {
    header('Location: /reset_password.php?email=' . urlencode($email) . '&error=Password+must+be+at+least+5+characters');
    exit;
}

if ($newPassword !== $confirmPassword) {
    header('Location: /reset_password.php?email=' . urlencode($email) . '&error=Passwords+do+not+match');
    exit;
}

if (!otp_verify($email, $code, 'reset_password')) {
    header('Location: /reset_password.php?email=' . urlencode($email) . '&error=Invalid+or+expired+code.+Please+try+again.');
    exit;
}

update_member_password($email, $newPassword);
header('Location: /login.php?success=Password+reset+successfully.+You+can+now+log+in.');
exit;
