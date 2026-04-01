<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'vendor/autoload.php';
require_once 'inc/phpauth_db.inc.php';
require_once 'inc/otp.inc.php';

// CSRF check
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: /register.php?error=Invalid+request");
    exit;
}

$name = $username = $email = $password = "";
$errorMsg = "";
$success  = true;

// Validate name
if (empty($_POST['name'])) {
    $errorMsg .= "Name is required.<br>";
    $success = false;
} else {
    $name = sanitize_input($_POST['name']);
    if (strlen($name) < 5 || strlen($name) > 50) {
        $errorMsg .= "Name must be 5-50 characters.<br>";
        $success = false;
    }
}

// Validate username
if (empty($_POST['username'])) {
    $errorMsg .= "Username is required.<br>";
    $success = false;
} else {
    $username = sanitize_input($_POST['username']);
    if (strlen($username) < 5 || strlen($username) > 20) {
        $errorMsg .= "Username must be 5-20 characters.<br>";
        $success = false;
    }
    if (strpos($username, '@') !== false) {
        $errorMsg .= "Username cannot contain @.<br>";
        $success = false;
    }
}

// Validate email
if (empty($_POST['email'])) {
    $errorMsg .= "Email is required.<br>";
    $success = false;
} else {
    $email = sanitize_input($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Invalid email format.<br>";
        $success = false;
    }
}

// Validate password
if (empty($_POST['password'])) {
    $errorMsg .= "Password is required.<br>";
    $success = false;
} else {
    $password = $_POST['password']; // Do NOT sanitize password
    if (strlen($password) < 5) {
        $errorMsg .= "Password must be at least 5 characters.<br>";
        $success = false;
    }
}

if (!$success) {
    header("Location: /register.php?error=" . urlencode($errorMsg));
    exit;
}

$conn = getDbConnection();

// Check username uniqueness (PHPAuth only checks email; we own username)
$stmt = $conn->prepare("SELECT id FROM members WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: /register.php?error=" . urlencode("Username already taken."));
    exit;
}
$stmt->close();

// Insert into members FIRST — catches duplicate email before PHPAuth is called.
// '!' is the sentinel password (an invalid bcrypt hash). PHPAuth owns the real password.
$stmt = $conn->prepare(
    "INSERT INTO members (name, username, email, password, role) VALUES (?, ?, ?, '!', 'user')"
);
$stmt->bind_param("sss", $name, $username, $email);

if (!$stmt->execute()) {
    $errno  = $conn->errno;
    $errmsg = $stmt->error;
    $stmt->close();
    $conn->close();

    if ($errno === 1062) {
        header("Location: /register.php?error=" . urlencode("Email already registered."));
    } else {
        error_log("Registration DB error: " . $errmsg);
        header("Location: /register.php?error=" . urlencode("Registration failed. Please try again."));
    }
    exit;
}
$stmt->close();

// PHPAuth register — members row is the rollback target if this fails.
// Passing ['username' => $username] sets the username column on phpauth_users directly.
$pdo    = getPHPAuthDbConnection();
$auth   = new PHPAuth\Auth($pdo, new PHPAuth\Config($pdo));
$result = $auth->register($email, $password, $password, ['username' => $username]);

if ($result['error']) {
    // Best-effort: remove any partial phpauth_users row PHPAuth may have written
    try {
        $pdo->prepare("DELETE FROM phpauth_users WHERE email = ?")->execute([$email]);
    } catch (PDOException $e) {
        error_log("PHPAuth cleanup failed for {$email}: " . $e->getMessage());
    }

    // Required rollback: remove the members row inserted above
    $del = $conn->prepare("DELETE FROM members WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    if ($del->affected_rows !== 1) {
        error_log("Registration rollback failed — orphaned members row for email: {$email}");
        $del->close();
        $conn->close();
        die("A server error occurred. Please contact support.");
    }
    $del->close();
    $conn->close();

    header("Location: /register.php?error=" . urlencode($result['message']));
    exit;
}

// Back-fill members.phpauth_uid with the INT id returned by PHPAuth
if (!isset($result['uid'])) {
    error_log("PHPAuth register() succeeded but returned no uid for email: {$email}");
    $conn->close();
    die("A server error occurred. Please contact support.");
}
$uid  = $result['uid'];
$stmt = $conn->prepare("UPDATE members SET phpauth_uid = ? WHERE email = ?");
$stmt->bind_param("is", $uid, $email);
$stmt->execute();
$stmt->close();
$conn->close();

$code = otp_generate($email, 'verify_email');
otp_send($email, 'verify_email', $code);

header("Location: /login.php?success=Registration+successful.+Please+log+in+and+verify+your+email.");
exit;
