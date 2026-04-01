<?php
require_once '../inc/auth.inc.php';
require_once '../inc/db.inc.php';
require_once '../inc/otp.inc.php';

require_admin();

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /admin/confirm_action.php?error=Invalid+request');
    exit;
}

$code       = trim($_POST['otp_code'] ?? '');
$adminEmail = $_SESSION['email'] ?? '';

if (!preg_match('/^\d{6}$/', $code)) {
    header('Location: /admin/confirm_action.php?error=Please+enter+a+valid+6-digit+code');
    exit;
}

if (!otp_verify($adminEmail, $code, 'admin_confirm')) {
    header('Location: /admin/confirm_action.php?error=Invalid+or+expired+code.+Please+try+again.');
    exit;
}

if (!isset($_SESSION['pending_action'])) {
    error_log('process_confirm.php: OTP verified but no pending_action in session for admin ' . $adminEmail);
    header('Location: /admin/analytics.php');
    exit;
}

$action = $_SESSION['pending_action'];
unset($_SESSION['pending_action']);

$conn = getDbConnection();

if ($action['type'] === 'delete_member') {
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param("i", $action['id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: /admin/manage.php?tab=members&success=' . urlencode("Member deleted successfully."));
    exit;
}

if ($action['type'] === 'delete_item') {
    $stmt = $conn->prepare("DELETE FROM performances WHERE id = ?");
    $stmt->bind_param("i", $action['id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: /admin/manage.php?tab=events&success=' . urlencode("Performance deleted successfully."));
    exit;
}

if ($action['type'] === 'delete_category') {
    // FK check: block if any performances reference this genre
    $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM performances WHERE genre_id = ?");
    $chk->bind_param("i", $action['id']);
    $chk->execute();
    $cnt = $chk->get_result()->fetch_assoc()['cnt'];
    $chk->close();
    if ($cnt > 0) {
        $conn->close();
        header('Location: /admin/manage.php?tab=genres&error=' . urlencode(
            "Cannot delete this genre — {$cnt} performance(s) are still assigned to it. Reassign or delete those events first."
        ));
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM genres WHERE id = ?");
    $stmt->bind_param("i", $action['id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header('Location: /admin/manage.php?tab=genres&success=' . urlencode("Genre deleted successfully."));
    exit;
}

$conn->close();
header('Location: /admin/analytics.php');
exit;
