<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$conn     = getDbConnection();
$memberId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT cart_items.id, cart_items.quantity, cart_items.ticket_category_id, ticket_categories.name AS ticket_cat_name, ticket_categories.price, performances.id AS performance_id, performances.name AS performance_name, performances.img_name FROM cart_items JOIN ticket_categories ON cart_items.ticket_category_id = ticket_categories.id JOIN performances ON ticket_categories.performance_id = performances.id WHERE cart_items.member_id = ?");
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
$conn->close();

$quantityError = isset($_GET['qtyerror']);
$pageTitle = 'Your Cart';

$paypalConfig = @parse_ini_file('/var/www/private/db-config.ini');
if (!$paypalConfig) {
    $paypalConfig = parse_ini_file(__DIR__ . '/.env');
}
$paypalClientId = $paypalConfig['paypal_client_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <?php if (count($cartItems) > 0): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypalClientId); ?>&currency=SGD&components=buttons"></script>
    <?php endif; ?>
    <style>
        /* ── Cart page layout ── */
        .cart-page {
            padding: 64px 0 100px;
            background-color: var(--bg-body);
            min-height: 60vh;
        }

        /* ── Ticket stub card ── */
        .ticket-stub {
            display: flex;
            background: var(--surface-card);
            border-radius: 16px;
            border: 1px solid var(--surface-border);
            overflow: hidden;
            box-shadow: var(--shadow-card);
            margin-bottom: 18px;
            opacity: 0;
            animation: stubIn 0.42s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transition: box-shadow 0.25s ease, transform 0.25s ease;
        }

        .ticket-stub:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .ticket-stub:nth-child(2) { animation-delay: 0.08s; }
        .ticket-stub:nth-child(3) { animation-delay: 0.14s; }
        .ticket-stub:nth-child(4) { animation-delay: 0.20s; }
        .ticket-stub:nth-child(5) { animation-delay: 0.26s; }
        .ticket-stub:nth-child(6) { animation-delay: 0.32s; }

        @keyframes stubIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* Event image */
        .ticket-image {
            width: 148px;
            min-width: 148px;
            position: relative;
            flex-shrink: 0;
        }

        .ticket-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Perforated right edge on image panel */
        .ticket-image::after {
            content: '';
            position: absolute;
            top: -6px;
            right: -1px;
            bottom: -6px;
            width: 0;
            border-right: 3px dashed var(--surface-border);
            pointer-events: none;
        }

        /* Body */
        .ticket-body {
            flex: 1;
            padding: 18px 20px 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            position: relative;
            min-width: 0;
        }

        /* Remove × button */
        .ticket-remove {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: transparent;
            border: 1.5px solid #ddd;
            color: #bbb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, border-color 0.2s, transform 0.18s;
            z-index: 1;
        }

        .ticket-remove:hover {
            background: #fff0f0;
            border-color: var(--color-red);
            color: var(--color-red);
            transform: scale(1.15) rotate(10deg);
        }

        /* Category badge */
        .ticket-cat-badge {
            display: inline-block;
            background: rgba(14, 159, 173, 0.09);
            color: var(--color-accent);
            border: 1px solid rgba(14, 159, 173, 0.22);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            padding: 3px 11px;
            border-radius: 40px;
            width: fit-content;
            font-family: var(--font-heading);
        }

        /* Event name */
        .ticket-event-name {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.3px;
            line-height: 1.15;
            padding-right: 38px;
            text-decoration: none;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.2s;
        }

        .ticket-event-name:hover { color: var(--color-accent); }

        /* Unit price */
        .ticket-unit-price {
            font-size: 0.78rem;
            color: #aaa;
            font-family: var(--font-heading);
            letter-spacing: 0.02em;
        }

        /* Footer row: stepper + subtotal */
        .ticket-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid var(--surface-border);
        }

        /* Quantity stepper */
        .qty-form { display: flex; align-items: center; gap: 10px; }

        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid var(--surface-border);
            border-radius: 999px;
            overflow: hidden;
            background: #fff;
        }

        .qty-btn {
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            font-size: 17px;
            font-weight: 400;
            color: var(--color-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
            line-height: 1;
        }

        .qty-btn:hover { background: var(--bg-warm-gray); color: var(--color-accent); }

        .qty-input {
            width: 36px;
            border: none;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: var(--color-dark);
            background: transparent;
            outline: none;
            font-family: var(--font-heading);
            -moz-appearance: textfield;
        }

        .qty-input::-webkit-inner-spin-button,
        .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }

        .qty-update-btn {
            background: none;
            border: none;
            font-size: 10.5px;
            font-weight: 700;
            color: #ccc;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            cursor: pointer;
            padding: 0;
            font-family: var(--font-heading);
            transition: color 0.15s;
        }

        .qty-update-btn:hover { color: var(--color-accent); }

        .qty-error {
            font-size: 11px;
            color: var(--color-red);
            font-family: var(--font-heading);
        }

        /* Subtotal */
        .ticket-subtotal {
            font-family: var(--font-display);
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.4px;
        }

        /* ── Cart count badge ── */
        .cart-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: var(--font-heading);
            font-size: 0.8rem;
            color: #888;
            background: var(--bg-warm-gray);
            border: 1px solid var(--surface-border);
            border-radius: 40px;
            padding: 5px 15px;
            margin-bottom: 22px;
            opacity: 0;
            animation: stubIn 0.4s ease 0.02s forwards;
        }

        .cart-count-badge strong { color: var(--color-dark); }

        /* ── Order summary panel ── */
        @media (max-width: 767px) {
            .order-panel { position: static; margin-top: 24px; }
        }

        .order-panel {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
            opacity: 0;
            animation: stubIn 0.42s cubic-bezier(0.22, 1, 0.36, 1) 0.12s forwards;
            position: sticky;
            top: 100px;
        }

        .order-panel-header {
            background: var(--color-dark);
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-panel-header .panel-icon {
            color: var(--color-accent);
            font-size: 13px;
        }

        .order-panel-header h3 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
        }

        .order-panel-body {
            padding: 18px 22px 4px;
        }

        .order-line {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid var(--surface-border);
            font-family: var(--font-heading);
        }

        .order-line:last-of-type { border-bottom: none; }

        .order-line-name {
            color: #666;
            flex: 1;
            font-size: 12.5px;
            line-height: 1.45;
        }

        .order-line-meta {
            font-size: 11px;
            color: #bbb;
        }

        .order-line-price {
            color: var(--color-dark);
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }

        /* Total row */
        .order-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 16px 22px 12px;
            border-top: 2px solid var(--color-dark);
            margin: 0 0 0;
        }

        .order-total-label {
            font-family: var(--font-heading);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #999;
        }

        .order-total-amount {
            font-family: var(--font-display);
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.5px;
        }

        /* PayPal section */
        .paypal-wrap {
            padding: 4px 22px 22px;
        }

        .paypal-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .paypal-divider::before,
        .paypal-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--surface-border);
        }

        .paypal-divider span {
            font-size: 10.5px;
            color: #bbb;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-family: var(--font-heading);
        }

        /* ── Empty cart state ── */
        .cart-empty {
            text-align: center;
            padding: 88px 20px;
            opacity: 0;
            animation: stubIn 0.5s ease 0.05s forwards;
        }

        .cart-empty-icon {
            font-size: 64px;
            color: var(--surface-border);
            margin-bottom: 22px;
            display: block;
        }

        .cart-empty h2 {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--color-dark);
            margin-bottom: 10px;
            letter-spacing: -0.3px;
        }

        .cart-empty p {
            color: #aaa;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            margin-bottom: 28px;
        }

        .btn-browse {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 13.5px;
            letter-spacing: 0.04em;
            padding: 13px 30px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.22s, transform 0.22s;
        }

        .btn-browse:hover {
            background: var(--color-accent);
            color: #fff;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include "inc/header.inc.php"; ?>
    <?php include "inc/search.inc.php"; ?>

    <!-- breadcrumb -->
    <div class="breadcrumb-section breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="breadcrumb-text">
                        <p class="breadcrumb-label">Statik</p>
                        <h1>Your Cart</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-page">
        <div class="container">

            <?php if (empty($cartItems)): ?>

            <div class="cart-empty">
                <i class="fas fa-ticket-alt cart-empty-icon" aria-hidden="true"></i>
                <h2>Nothing in here yet</h2>
                <p>You haven't added any tickets. Go find something good.</p>
                <a href="/shop.php" class="btn-browse">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i> Browse Events
                </a>
            </div>

            <?php else: ?>

            <div class="row g-4">

                <!-- Left: ticket stub cards -->
                <div class="col-lg-8">

                    <div class="cart-count-badge">
                        <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                        <strong><?= count($cartItems) ?></strong>
                        <?= count($cartItems) === 1 ? 'ticket' : 'tickets' ?> in your cart
                    </div>

                    <?php foreach ($cartItems as $cartItem): ?>
                    <div class="ticket-stub">

                        <!-- Event image -->
                        <div class="ticket-image">
                            <img src="/uploads/performances/<?= $cartItem['performance_id'] ?>/<?= htmlspecialchars($cartItem['img_name']) ?>"
                                 alt="<?= htmlspecialchars($cartItem['performance_name']) ?>">
                        </div>

                        <!-- Ticket body -->
                        <div class="ticket-body">

                            <a href="/remove_from_cart.php?id=<?= $cartItem['id'] ?>" class="ticket-remove" title="Remove">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </a>

                            <span class="ticket-cat-badge"><?= htmlspecialchars($cartItem['ticket_cat_name']) ?></span>

                            <a href="/item.php?id=<?= $cartItem['performance_id'] ?>" class="ticket-event-name">
                                <?= htmlspecialchars($cartItem['performance_name']) ?>
                            </a>

                            <div class="ticket-unit-price">SGD <?= number_format($cartItem['price'], 2) ?> per ticket</div>

                            <div class="ticket-footer">
                                <form class="qty-form" action="/update_cart.php" method="post" id="qty-form-<?= $cartItem['id'] ?>">
                                    <input type="hidden" name="cart_id" value="<?= $cartItem['id'] ?>">
                                    <div class="qty-stepper">
                                        <button type="button" class="qty-btn" onclick="stepQty(<?= $cartItem['id'] ?>, -1)">−</button>
                                        <input id="qty-<?= $cartItem['id'] ?>"
                                               class="qty-input"
                                               type="number"
                                               name="quantity"
                                               value="<?= $cartItem['quantity'] ?>"
                                               min="1"
                                               aria-label="Quantity for <?= htmlspecialchars($cartItem['performance_name']) ?>"
                                               required>
                                        <button type="button" class="qty-btn" onclick="stepQty(<?= $cartItem['id'] ?>, 1)">+</button>
                                    </div>
                                    <button type="submit" class="qty-update-btn">Update</button>
                                    <?php if ($quantityError): ?>
                                        <span class="qty-error">Exceeds available seats</span>
                                    <?php endif; ?>
                                </form>

                                <div class="ticket-subtotal">$<?= number_format($cartItem['sub_total'], 2) ?></div>
                            </div>

                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <!-- Right: order summary panel -->
                <div class="col-lg-4">
                    <div class="order-panel">

                        <div class="order-panel-header">
                            <i class="fas fa-receipt panel-icon" aria-hidden="true"></i>
                            <h3>Order Summary</h3>
                        </div>

                        <div class="order-panel-body">
                            <?php foreach ($cartItems as $cartItem): ?>
                            <div class="order-line">
                                <div class="order-line-name">
                                    <?= htmlspecialchars($cartItem['performance_name']) ?>
                                    <br>
                                    <span class="order-line-meta"><?= htmlspecialchars($cartItem['ticket_cat_name']) ?> &times; <?= $cartItem['quantity'] ?></span>
                                </div>
                                <div class="order-line-price">$<?= number_format($cartItem['sub_total'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-total-row">
                            <span class="order-total-label">Total</span>
                            <span class="order-total-amount">$<?= number_format($cartTotal, 2) ?></span>
                        </div>

                        <div class="paypal-wrap">
                            <div class="paypal-divider"><span>Pay securely with</span></div>
                            <div id="paypal-button-container"></div>
                            <form action="/checkout.php" method="post" id="paypalForm">
                                <input type="hidden" name="cartTotal"     id="cartTotal"     value="<?= number_format($cartTotal, 2, '.', '') ?>">
                                <input type="hidden" name="memberId"      id="memberId"      value="<?= $memberId ?>">
                                <input type="hidden" name="orderId"       id="orderId">
                                <input type="hidden" name="transactionId" id="transactionId">
                            </form>
                        </div>

                    </div>
                </div>

            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include "inc/footer.inc.php"; ?>

    <script>
        function stepQty(cartId, delta) {
            var input = document.getElementById('qty-' + cartId);
            var val   = parseInt(input.value, 10) + delta;
            if (val < 1) val = 1;
            input.value = val;
            document.getElementById('qty-form-' + cartId).submit();
        }

        <?php if (count($cartItems) > 0): ?>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: { value: document.getElementById('cartTotal').value }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    document.getElementById('orderId').value       = details.id;
                    document.getElementById('transactionId').value = details.purchase_units[0].payments.captures[0].id;
                    document.getElementById('paypalForm').submit();
                });
            }
        }).render('#paypal-button-container');
        <?php endif; ?>
    </script>
</body>
</html>
