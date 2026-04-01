<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$cartId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cartId <= 0) {
    header("Location: /cart.php");
    exit;
}

$conn = getDbConnection();
$memberId = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND member_id = ?");
$stmt->bind_param("ii", $cartId, $memberId);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: /cart.php");
exit;
