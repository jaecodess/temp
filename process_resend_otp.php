<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/otp.inc.php';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /');
    exit;
}

$purpose = $_POST['purpose'] ?? '';
$allowed = ['verify_email', 'reset_password', 'admin_confirm'];
if (!in_array($purpose, $allowed, true)) {
    header('Location: /');
    exit;
}

// Resolve email based on purpose
if ($purpose === 'verify_email') {
    require_login();
    $email = $_SESSION['email'] ?? '';
} elseif ($purpose === 'admin_confirm') {
    require_admin();
    $email = $_SESSION['email'] ?? '';
} else {
    // reset_password — unauthenticated; email comes from POST
    $email = trim($_POST['email'] ?? '');
    // Verify email exists in members before proceeding (silent if not found)
    $conn  = getDbConnection();
    $stmt  = $conn->prepare("SELECT id FROM members WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    if (!$exists) {
        // Identical redirect to prevent enumeration
        header('Location: /reset_password.php?email=' . urlencode($email));
        exit;
    }
}

if (!otp_can_resend($email, $purpose)) {
    $redirects = [
        'verify_email'   => '/verify_email.php?error=Please+wait+60+seconds+before+requesting+a+new+code',
        'reset_password' => '/reset_password.php?email=' . urlencode($email) . '&error=Please+wait+60+seconds+before+requesting+a+new+code',
        'admin_confirm'  => '/admin/confirm_action.php?error=Please+wait+60+seconds+before+requesting+a+new+code',
    ];
    header('Location: ' . $redirects[$purpose]);
    exit;
}

$code = otp_generate($email, $purpose);
otp_send($email, $purpose, $code);

$redirects = [
    'verify_email'   => '/verify_email.php?success=A+new+code+has+been+sent',
    'reset_password' => '/reset_password.php?email=' . urlencode($email) . '&success=A+new+code+has+been+sent',
    'admin_confirm'  => '/admin/confirm_action.php?success=A+new+code+has+been+sent',
];
header('Location: ' . $redirects[$purpose]);
exit;
