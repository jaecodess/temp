<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/otp.inc.php';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /forgot_password.php?error=Invalid+request');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /forgot_password.php?error=Please+enter+a+valid+email+address');
    exit;
}

// Look up email — send OTP only if found, but always redirect identically
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$found = $stmt->num_rows > 0;
$stmt->close();
$conn->close();

if ($found) {
    $code = otp_generate($email, 'reset_password');
    otp_send($email, 'reset_password', $code);
}

// Always redirect to the same place regardless of whether the email exists
header('Location: /reset_password.php?email=' . urlencode($email));
exit;
