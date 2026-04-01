<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

require_login();

$memberId = $_SESSION['user_id'];

// Allow admin to view another member's orders
if (is_admin() && isset($_GET['member_id'])) {
    $memberId = intval($_GET['member_id']);
}

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT order_items.id, order_items.quantity, order_items.price, order_items.order_id, order_items.transaction_id, order_items.order_date, order_items.ticket_category_id, ticket_categories.name AS ticket_cat_name, performances.id AS performance_id, performances.name AS performance_name, performances.img_name, performances.description FROM order_items JOIN ticket_categories ON order_items.ticket_category_id = ticket_categories.id JOIN performances ON ticket_categories.performance_id = performances.id WHERE order_items.member_id = ? ORDER BY order_items.order_date DESC");
$stmt->bind_param("i", $memberId);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}
$stmt->close();
$conn->close();
$pageTitle = 'My Orders';

// Group flat rows by order_id
$orders = [];
foreach ($orderItems as $item) {
    $key = $item['order_id'] ?? $item['id'];
    if (!isset($orders[$key])) {
        $orders[$key] = [
            'order_id'       => $key,
            'transaction_id' => $item['transaction_id'] ?? '-',
            'order_date'     => $item['order_date'],
            'items'          => [],
            'total'          => 0,
        ];
    }
    $item['sub_total'] = $item['price'] * $item['quantity'];
    $orders[$key]['total'] += $item['sub_total'];
    $orders[$key]['items'][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <style>
        .orders-page {
            padding: 64px 0 100px;
            background-color: var(--bg-body);
            min-height: 60vh;
        }

        @keyframes orderIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Count badge ── */
        .orders-count-badge {
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
            margin-bottom: 26px;
            opacity: 0;
            animation: orderIn 0.4s ease 0.02s forwards;
        }
        .orders-count-badge strong { color: var(--color-dark); }

        /* ── Order card ── */
        .order-card {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
            margin-bottom: 20px;
            opacity: 0;
            animation: orderIn 0.42s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transition: box-shadow 0.25s ease, transform 0.25s ease;
        }
        .order-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .order-card:nth-child(1) { animation-delay: 0.06s; }
        .order-card:nth-child(2) { animation-delay: 0.12s; }
        .order-card:nth-child(3) { animation-delay: 0.18s; }
        .order-card:nth-child(4) { animation-delay: 0.24s; }
        .order-card:nth-child(5) { animation-delay: 0.30s; }
        .order-card:nth-child(6) { animation-delay: 0.36s; }

        /* Header band */
        .order-card-header {
            background: var(--color-dark);
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .order-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* Date stamp box */
        .order-date-stamp {
            background: rgba(14, 159, 173, 0.12);
            border: 1px solid rgba(14, 159, 173, 0.25);
            border-radius: 10px;
            padding: 5px 12px;
            text-align: center;
            min-width: 50px;
        }
        .order-date-day {
            font-family: var(--font-display);
            font-size: 1.45rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            display: block;
        }
        .order-date-month {
            font-family: var(--font-heading);
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--color-accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: block;
            margin-top: 2px;
        }

        /* Order ref text */
        .order-ref-label {
            font-family: var(--font-heading);
            font-size: 0.68rem;
            color: rgba(255,255,255,0.4);
            letter-spacing: 0.07em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .order-ref-value {
            font-family: var(--font-heading);
            font-size: 0.82rem;
            font-weight: 700;
            color: rgba(255,255,255,0.85);
            letter-spacing: 0.02em;
            font-family: 'Courier New', monospace;
        }

        /* Total chip */
        .order-total-chip {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--color-accent);
            letter-spacing: -0.3px;
            white-space: nowrap;
        }

        /* Download button */
        .btn-download-pdf {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: var(--font-heading);
            font-size: 0.75rem;
            font-weight: 700;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 5px 14px;
            transition: background 0.18s, color 0.18s, border-color 0.18s;
            white-space: nowrap;
        }
        .btn-download-pdf:hover {
            background: var(--color-accent);
            color: #fff;
            border-color: var(--color-accent);
        }
        .btn-download-pdf:focus-visible {
            outline: 2px solid var(--color-accent);
            outline-offset: 3px;
        }

        /* Item rows */
        .order-item-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 15px 22px;
            border-bottom: 1px solid var(--surface-border);
        }
        .order-item-row:last-child { border-bottom: none; }

        .order-item-img {
            width: 62px;
            height: 62px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .order-item-info {
            flex: 1;
            min-width: 0;
        }

        .order-item-name {
            font-family: var(--font-display);
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.2px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-item-cat {
            display: inline-block;
            background: rgba(14, 159, 173, 0.09);
            color: var(--color-accent);
            border: 1px solid rgba(14, 159, 173, 0.22);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 40px;
            margin-top: 4px;
            font-family: var(--font-heading);
        }

        .order-item-pricing {
            text-align: right;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .order-item-subtotal {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--color-dark);
            letter-spacing: -0.3px;
        }
        .order-item-unit {
            font-size: 0.72rem;
            color: #bbb;
            font-family: var(--font-heading);
        }

        /* ── Empty state ── */
        .orders-empty {
            text-align: center;
            padding: 84px 20px;
            opacity: 0;
            animation: orderIn 0.5s ease 0.05s forwards;
        }
        .orders-empty-icon {
            font-size: 60px;
            color: var(--surface-border);
            margin-bottom: 20px;
            display: block;
        }
        .orders-empty h2 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-dark);
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        .orders-empty p {
            color: #aaa;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            margin-bottom: 26px;
        }
        .btn-orders-cta {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 13.5px;
            padding: 13px 30px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.22s, transform 0.22s;
        }
        .btn-orders-cta:hover {
            background: var(--color-accent);
            color: #fff;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <?php include "inc/header.inc.php"; ?>
    <?php include "inc/search.inc.php"; ?>

    <div class="breadcrumb-section breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2 text-center">
                    <div class="breadcrumb-text">
                        <p class="breadcrumb-label">Statik</p>
                        <h1>My Orders</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="orders-page">
        <div class="container">

            <?php if (empty($orders)): ?>

            <div class="orders-empty">
                <i class="fas fa-receipt orders-empty-icon" aria-hidden="true"></i>
                <h2>No orders yet</h2>
                <p>You haven't purchased any tickets. Go catch a show.</p>
                <a href="/shop.php" class="btn-orders-cta">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i> Browse Events
                </a>
            </div>

            <?php else: ?>

            <div class="orders-count-badge">
                <i class="fas fa-receipt" aria-hidden="true"></i>
                <strong><?= count($orders) ?></strong>
                <?= count($orders) === 1 ? 'order' : 'orders' ?>
            </div>

            <?php foreach ($orders as $order):
                $ts      = !empty($order['order_date']) ? strtotime($order['order_date']) : time();
                $day     = date('d', $ts);
                $month   = date('M Y', $ts);
                $shortId = strlen($order['order_id']) > 22
                           ? substr($order['order_id'], 0, 22) . '…'
                           : $order['order_id'];
            ?>
            <div class="order-card">

                <div class="order-card-header">
                    <div class="order-header-left">
                        <div class="order-date-stamp">
                            <span class="order-date-day"><?= $day ?></span>
                            <span class="order-date-month"><?= $month ?></span>
                        </div>
                        <div>
                            <div class="order-ref-label">Order</div>
                            <div class="order-ref-value"><?= htmlspecialchars($shortId) ?></div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <div class="order-total-chip">SGD <?= number_format($order['total'], 2) ?></div>
                        <?php if (!empty($order['order_id'])): ?>
                        <a href="/download_ticket.php?order_id=<?= urlencode($order['order_id']) ?>"
                           class="btn-download-pdf"
                           aria-label="Download PDF receipt for order <?= htmlspecialchars($shortId) ?>">
                            <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item-row">
                    <img class="order-item-img"
                         src="/uploads/performances/<?= $item['performance_id'] ?>/<?= htmlspecialchars($item['img_name']) ?>"
                         alt="<?= htmlspecialchars($item['performance_name']) ?>"
                         loading="lazy">
                    <div class="order-item-info">
                        <div class="order-item-name"><?= htmlspecialchars($item['performance_name']) ?></div>
                        <span class="order-item-cat"><?= htmlspecialchars($item['ticket_cat_name']) ?></span>
                    </div>
                    <div class="order-item-pricing">
                        <div class="order-item-subtotal">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                        <div class="order-item-unit">SGD <?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
            <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>

    <?php include "inc/footer.inc.php"; ?>
</body>
</html>
