<?php
require_once '../inc/auth.inc.php';
require_once '../inc/db.inc.php';
require_once '../inc/otp.inc.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/analytics.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: /admin/analytics.php');
    exit;
}

$type = $_POST['type']   ?? '';
$id   = intval($_POST['delete'] ?? 0);

if (!$id || !in_array($type, ['delete_member', 'delete_item', 'delete_category'], true)) {
    header('Location: /admin/analytics.php');
    exit;
}

// Look up human-readable label
$conn  = getDbConnection();
$label = '';

if ($type === 'delete_member') {
    $stmt = $conn->prepare("SELECT username FROM members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $label = $row ? 'member "' . htmlspecialchars($row['username']) . '"' : "member #{$id}";
} elseif ($type === 'delete_item') {
    $stmt = $conn->prepare("SELECT name FROM performances WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $label = $row ? 'performance "' . htmlspecialchars($row['name']) . '"' : "performance #{$id}";
} elseif ($type === 'delete_category') {
    $stmt = $conn->prepare("SELECT name FROM genres WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $label = $row ? 'genre "' . htmlspecialchars($row['name']) . '"' : "genre #{$id}";
}

$conn->close();

$_SESSION['pending_action'] = [
    'type'  => $type,
    'id'    => $id,
    'label' => $label,
];

$adminEmail = $_SESSION['email'] ?? '';
$code = otp_generate($adminEmail, 'admin_confirm');
otp_send($adminEmail, 'admin_confirm', $code);

header('Location: /admin/confirm_action.php');
exit;
