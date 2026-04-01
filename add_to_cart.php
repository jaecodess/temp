<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$memberId = $_SESSION['user_id'];
$tcId     = isset($_POST['ticket_category_id']) ? intval($_POST['ticket_category_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($tcId <= 0 || $quantity <= 0) {
    header("Location: /shop.php");
    exit;
}

$conn = getDbConnection();

// Check ticket category exists and has enough seats
$stmt = $conn->prepare("SELECT available_seats, performance_id FROM ticket_categories WHERE id = ?");
$stmt->bind_param("i", $tcId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: /shop.php");
    exit;
}

$tc = $result->fetch_assoc();
$stmt->close();

if ($quantity > $tc['available_seats']) {
    $conn->close();
    header("Location: /item.php?id=" . $tc['performance_id'] . "&error=" . urlencode("Quantity exceeds available seats."));
    exit;
}

// Check if this ticket category is already in the cart
$stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE member_id = ? AND ticket_category_id = ?");
$stmt->bind_param("ii", $memberId, $tcId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing cart entry
    $existing = $result->fetch_assoc();
    $newQty   = $existing['quantity'] + $quantity;

    if ($newQty > $tc['available_seats']) {
        $stmt->close();
        $conn->close();
        header("Location: /item.php?id=" . $tc['performance_id'] . "&error=" . urlencode("Total quantity exceeds available seats."));
        exit;
    }

    $stmt->close();
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $newQty, $existing['id']);
    $stmt->execute();
} else {
    // Insert new cart entry
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO cart_items (member_id, ticket_category_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $memberId, $tcId, $quantity);
    $stmt->execute();
}

$stmt->close();
$conn->close();

header("Location: /cart.php");
exit;
