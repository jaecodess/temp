<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /login.php?error=Invalid+request");
    exit;
}

$errorMsg = "";
$success  = true;

// Validate fields present
if (empty($_POST['username'])) {
    $errorMsg .= "Username or email is required.<br>";
    $success = false;
}
if (empty($_POST['password'])) {
    $errorMsg .= "Password is required.<br>";
    $success = false;
}

if (!$success) {
    header("Location: /login.php?error=" . urlencode($errorMsg));
    exit;
}

$input    = sanitize_input($_POST['username']);
$password = $_POST['password']; // Do NOT sanitize password

// Detect email vs username — usernames cannot contain '@'
if (strpos($input, '@') !== false) {
    $email = $input;
} else {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT email FROM members WHERE username = ?");
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header("Location: /login.php?error=" . urlencode("Invalid credentials."));
        exit;
    }

    $email = $result->fetch_assoc()['email'];
    $stmt->close();
    $conn->close();
}

// PHPAuth login — handles rate-limiting and attempt recording automatically
$pdo    = getPHPAuthDbConnection();
$auth   = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
$result = $auth->login($email, $password);

if ($result['error']) {
    header("Location: /login.php?error=" . urlencode($result['message']));
    exit;
}

// Success — regenerate session, store PHPAuth hash, set session vars from members
session_regenerate_id(true);
$_SESSION['phpauth_session_hash'] = $result['hash'];

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, username, name, role, email, email_verified FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$_SESSION['user_id']        = $member['id'];
$_SESSION['username']       = $member['username'];
$_SESSION['name']           = $member['name'];
$_SESSION['role']           = $member['role'];
$_SESSION['email']          = $member['email'];
$_SESSION['email_verified'] = (int) $member['email_verified'];

header("Location: /");
exit;
