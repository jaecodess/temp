<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$memberId = $_SESSION['user_id'];

// Get PayPal order/transaction IDs from the form
$orderId       = isset($_POST['orderId'])       ? sanitize_input($_POST['orderId'])       : "ORD-" . strtoupper(uniqid());
$transactionId = isset($_POST['transactionId']) ? sanitize_input($_POST['transactionId']) : "TXN-" . strtoupper(uniqid());

$conn = getDbConnection();

// Fetch cart items with ticket category and performance details
$stmt = $conn->prepare("SELECT cart_items.id, cart_items.quantity, cart_items.ticket_category_id, ticket_categories.name AS ticket_cat_name, ticket_categories.price, ticket_categories.available_seats AS stock, performances.id AS performance_id, performances.name AS performance_name, performances.img_name, performances.description, performances.event_date, performances.venue FROM cart_items JOIN ticket_categories ON cart_items.ticket_category_id = ticket_categories.id JOIN performances ON ticket_categories.performance_id = performances.id WHERE cart_items.member_id = ?");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$cartTotal = 0;
while ($row = $result->fetch_assoc()) {
    $row['sub_total'] = $row['quantity'] * $row['price'];
    $cartTotal += $row['sub_total'];
    $cartItems[] = $row;
}
$stmt->close();

if (empty($cartItems)) {
    $conn->close();
    header("Location: /cart.php");
    exit;
}

// Process each cart item: insert order record and deduct available seats
foreach ($cartItems as $cartItem) {
    $stmt = $conn->prepare("INSERT INTO order_items (member_id, ticket_category_id, quantity, price, order_id, transaction_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidss", $memberId, $cartItem['ticket_category_id'], $cartItem['quantity'], $cartItem['price'], $orderId, $transactionId);
    $stmt->execute();
    $stmt->close();

    // Deduct available seats
    $newStock = $cartItem['stock'] - $cartItem['quantity'];
    $stmt = $conn->prepare("UPDATE ticket_categories SET available_seats = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStock, $cartItem['ticket_category_id']);
    $stmt->execute();
    $stmt->close();
}

// Clear cart
$stmt = $conn->prepare("DELETE FROM cart_items WHERE member_id = ?");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$stmt->close();

// Send order confirmation email
require_once 'inc/mailer.inc.php';
$memberEmail = null;
$stmtEmail = $conn->prepare("SELECT email FROM members WHERE id = ?");
$stmtEmail->bind_param("i", $memberId);
$stmtEmail->execute();
$stmtEmail->bind_result($memberEmail);
$stmtEmail->fetch();
$stmtEmail->close();

if (!empty($memberEmail)) {
    $sent = send_order_confirmation(
        $_SESSION['name'],
        $memberEmail,
        $cartItems,
        $orderId,
        $transactionId,
        $cartTotal
    );
    if (!$sent) {
        error_log("Order confirmation email failed for order #{$orderId}");
    }
} else {
    error_log("Order confirmation email skipped: no email for member #{$memberId}");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <style>
        /* ── Page: cream backdrop ── */
        .receipt-page {
            background: var(--bg-body);
            min-height: 100vh;
            padding: 56px 0 80px;
            display: flex;
            align-items: flex-start;
        }

        /* ── Receipt card ── */
        .receipt-card {
            max-width: 560px;
            margin: 0 auto;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.5);
            opacity: 0;
            animation: receiptIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.1s forwards;
        }

        @keyframes receiptIn {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* ── Dark top section: checkmark + title + IDs ── */
        .receipt-top {
            background: #071e2a;
            padding: 36px 36px 28px;
            text-align: center;
        }

        /* Animated check circle */
        .check-circle {
            width: 64px;
            height: 64px;
            border: 2.5px solid var(--color-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            opacity: 0;
            animation: circleIn 0.38s ease 0.4s forwards;
        }
        .check-circle i {
            font-size: 26px;
            color: var(--color-accent);
            opacity: 0;
            animation: checkIn 0.28s ease 0.7s forwards;
        }
        @keyframes circleIn {
            from { opacity: 0; transform: scale(0.3); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes checkIn {
            from { opacity: 0; transform: scale(0) rotate(-25deg); }
            to   { opacity: 1; transform: scale(1) rotate(0deg); }
        }

        .receipt-confirmed {
            font-family: var(--font-display);
            font-size: 1.9rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 6px;
            line-height: 1;
        }
        .receipt-tagline {
            font-family: var(--font-heading);
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.4);
            margin: 0 0 24px;
        }

        /* IDs strip */
        .receipt-ids {
            background: rgba(14, 159, 173, 0.07);
            border: 1px solid rgba(14, 159, 173, 0.15);
            border-radius: 10px;
            padding: 12px 18px;
            display: flex;
            flex-direction: column;
            gap: 7px;
            text-align: left;
        }
        .receipt-id-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .receipt-id-label {
            font-family: var(--font-heading);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.35);
            white-space: nowrap;
        }
        .receipt-id-value {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 0.04em;
            text-align: right;
            word-break: break-all;
        }

        /* ── Perforated tear divider ── */
        .receipt-perforation {
            background: var(--surface-card);
            height: 20px;
            position: relative;
            overflow: visible;
        }
        .receipt-perforation::before,
        .receipt-perforation::after {
            content: '';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--color-dark);
            z-index: 2;
        }
        .receipt-perforation::before { left: -12px; background: var(--bg-body); }
        .receipt-perforation::after  { right: -12px; background: var(--bg-body); }
        .receipt-perforation-line {
            position: absolute;
            top: 50%;
            left: 16px;
            right: 16px;
            height: 0;
            border-top: 2px dashed var(--surface-border);
            transform: translateY(-50%);
        }

        /* ── Cream body: items ── */
        .receipt-body {
            background: var(--surface-card);
            padding: 6px 0 0;
        }
        .receipt-items-label {
            font-family: var(--font-heading);
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.13em;
            color: #ccc;
            padding: 12px 28px 8px;
        }
        .receipt-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 28px;
            border-bottom: 1px solid var(--surface-border);
        }
        .receipt-item:last-child { border-bottom: none; }

        .receipt-item-img {
            width: 54px;
            height: 54px;
            border-radius: 9px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .receipt-item-info { flex: 1; min-width: 0; }
        .receipt-item-name {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--color-dark);
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.2px;
        }
        .receipt-item-cat {
            display: inline-block;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: var(--color-accent);
            font-family: var(--font-heading);
            margin-top: 3px;
        }
        .receipt-item-pricing {
            text-align: right;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .receipt-item-subtotal {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.2px;
        }
        .receipt-item-unit {
            font-size: 0.7rem;
            color: #bbb;
            font-family: var(--font-heading);
        }

        /* ── Total band ── */
        .receipt-total {
            background: var(--color-dark);
            padding: 18px 28px;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
        }
        .receipt-total-label {
            font-family: var(--font-heading);
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(255, 255, 255, 0.4);
        }
        .receipt-total-amount {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .receipt-total-currency {
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.45);
            margin-right: 5px;
            font-family: var(--font-heading);
        }

        /* ── Action buttons ── */
        .receipt-actions {
            background: var(--surface-card);
            padding: 20px 28px 26px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-receipt-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-receipt-primary:hover {
            background: var(--color-accent);
            color: #fff;
            transform: translateY(-2px);
        }
        .btn-receipt-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            color: #aaa;
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 13px;
            padding: 12px 20px;
            border-radius: 999px;
            text-decoration: none;
            border: 1.5px solid var(--surface-border);
            transition: color 0.2s, border-color 0.2s, transform 0.2s;
        }
        .btn-receipt-ghost:hover {
            color: var(--color-dark);
            border-color: var(--color-dark);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include "inc/header.inc.php"; ?>
    <?php include "inc/search.inc.php"; ?>

    <div class="receipt-page">
        <div class="container">
            <div class="receipt-card">

                <!-- Dark top: checkmark + title + IDs -->
                <div class="receipt-top">
                    <div class="check-circle">
                        <i class="fas fa-check"></i>
                    </div>
                    <p class="receipt-confirmed">Order Confirmed</p>
                    <p class="receipt-tagline">Your tickets are booked. See you there.</p>

                    <div class="receipt-ids">
                        <div class="receipt-id-row">
                            <span class="receipt-id-label">Order Ref</span>
                            <span class="receipt-id-value"><?= htmlspecialchars($orderId) ?></span>
                        </div>
                        <div class="receipt-id-row">
                            <span class="receipt-id-label">Transaction</span>
                            <span class="receipt-id-value"><?= htmlspecialchars($transactionId) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Perforated tear divider -->
                <div class="receipt-perforation">
                    <div class="receipt-perforation-line"></div>
                </div>

                <!-- Cream items body -->
                <div class="receipt-body">
                    <div class="receipt-items-label">Items Purchased</div>
                    <?php foreach ($cartItems as $cartItem): ?>
                    <div class="receipt-item">
                        <img class="receipt-item-img"
                             src="/uploads/performances/<?= $cartItem['performance_id'] ?>/<?= htmlspecialchars($cartItem['img_name']) ?>"
                             alt="<?= htmlspecialchars($cartItem['performance_name']) ?>">
                        <div class="receipt-item-info">
                            <div class="receipt-item-name"><?= htmlspecialchars($cartItem['performance_name']) ?></div>
                            <span class="receipt-item-cat"><?= htmlspecialchars($cartItem['ticket_cat_name']) ?></span>
                        </div>
                        <div class="receipt-item-pricing">
                            <div class="receipt-item-subtotal">$<?= number_format($cartItem['sub_total'], 2) ?></div>
                            <div class="receipt-item-unit">SGD <?= number_format($cartItem['price'], 2) ?> × <?= $cartItem['quantity'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total band -->
                <div class="receipt-total">
                    <span class="receipt-total-label">Total Paid</span>
                    <span class="receipt-total-amount">
                        <span class="receipt-total-currency">SGD</span>$<?= number_format($cartTotal, 2) ?>
                    </span>
                </div>

                <!-- Actions -->
                <div class="receipt-actions">
                    <a href="/orders.php" class="btn-receipt-primary">
                        <i class="fas fa-receipt"></i> View My Orders
                    </a>
                    <a href="/shop.php" class="btn-receipt-ghost">
                        <i class="fas fa-ticket-alt"></i> More Events
                    </a>
                </div>

            </div>
        </div>
    </div>

    <?php include "inc/footer.inc.php"; ?>
</body>
</html>
