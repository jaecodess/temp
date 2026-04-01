<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$cartId   = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($cartId <= 0 || $quantity <= 0) {
    header("Location: /cart.php");
    exit;
}

$conn     = getDbConnection();
$memberId = $_SESSION['user_id'];

// Verify ownership and check available seats
$stmt = $conn->prepare("SELECT cart_items.ticket_category_id, ticket_categories.available_seats AS stock FROM cart_items JOIN ticket_categories ON cart_items.ticket_category_id = ticket_categories.id WHERE cart_items.id = ? AND cart_items.member_id = ?");
$stmt->bind_param("ii", $cartId, $memberId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: /cart.php");
    exit;
}

$row = $result->fetch_assoc();
$stmt->close();

if ($quantity > $row['stock']) {
    $conn->close();
    header("Location: /cart.php?qtyerror=1");
    exit;
}

$stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND member_id = ?");
$stmt->bind_param("iii", $quantity, $cartId, $memberId);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: /cart.php");
exit;
