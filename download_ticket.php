<?php
/**
 * download_ticket.php — generate a PDF booking confirmation via Dompdf
 * (https://github.com/dompdf/dompdf)
 *
 * GET param: order_id (the PayPal order ID string)
 * Security: order must belong to the currently logged-in member.
 */
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_login();

$orderId  = trim($_GET['order_id'] ?? '');
$memberId = (int) $_SESSION['user_id'];

if ($orderId === '') {
    http_response_code(400);
    exit('Missing order_id.');
}

// ── Fetch all line items for this order, scoped to the logged-in member ──────
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT
        oi.order_id,
        oi.transaction_id,
        oi.order_date,
        oi.quantity,
        oi.price,
        (oi.quantity * oi.price) AS subtotal,
        tc.name  AS cat_name,
        p.name   AS perf_name,
        p.venue,
        p.event_date,
        p.event_time
    FROM order_items oi
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN performances p       ON tc.performance_id = p.id
    WHERE oi.order_id = ? AND oi.member_id = ?
    ORDER BY oi.id ASC
");
$stmt->bind_param("si", $orderId, $memberId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
$conn->close();

if (empty($items)) {
    http_response_code(404);
    exit('Order not found or access denied.');
}

// ── Build order summary ───────────────────────────────────────────────────────
$first       = $items[0];
$orderDate   = date('d M Y \a\t g:ia', strtotime($first['order_date']));
$transId     = htmlspecialchars($first['transaction_id'] ?? 'N/A');
$orderIdDisp = htmlspecialchars($orderId);
$total       = array_sum(array_column($items, 'subtotal'));
$memberName  = htmlspecialchars($_SESSION['name']     ?? '');
$memberUser  = htmlspecialchars($_SESSION['username'] ?? '');

// ── Build item rows HTML ──────────────────────────────────────────────────────
$rows = '';
foreach ($items as $item) {
    $eventDate = date('d M Y', strtotime($item['event_date']));
    $eventTime = date('g:ia', strtotime($item['event_time']));
    $rows .= '
    <tr>
        <td class="item-name">
            <strong>' . htmlspecialchars($item['perf_name']) . '</strong><br>
            <small>' . htmlspecialchars($item['venue']) . ' &mdash; ' . $eventDate . ' ' . $eventTime . '</small>
        </td>
        <td class="center">' . htmlspecialchars($item['cat_name']) . '</td>
        <td class="center">' . (int) $item['quantity'] . '</td>
        <td class="right">SGD ' . number_format($item['price'], 2) . '</td>
        <td class="right bold">SGD ' . number_format($item['subtotal'], 2) . '</td>
    </tr>';
}

// ── HTML for the PDF ─────────────────────────────────────────────────────────
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 11px;
    color: #051922;
    background: #FDFCF9;
    padding: 0;
  }

  /* Header band */
  .pdf-header {
    background: #051922;
    color: #fff;
    padding: 28px 36px 22px;
  }
  .pdf-brand {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #0E9FAD;
  }
  .pdf-tagline {
    font-size: 9px;
    color: rgba(255,255,255,0.45);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 2px;
  }

  /* Confirmation strip */
  .confirm-strip {
    background: #0E9FAD;
    padding: 10px 36px;
    color: #fff;
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }

  /* Body */
  .pdf-body { padding: 28px 36px; }

  /* Meta block */
  .meta-grid {
    width: 100%;
    margin-bottom: 22px;
    border-collapse: collapse;
  }
  .meta-grid td {
    padding: 6px 0;
    vertical-align: top;
    width: 50%;
  }
  .meta-label {
    font-size: 8px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.9px;
    color: #7a8f99;
    display: block;
    margin-bottom: 2px;
  }
  .meta-value {
    font-size: 11px;
    font-weight: 700;
    color: #051922;
  }
  .meta-value.accent { color: #0E9FAD; }

  /* Divider */
  .divider {
    border: none;
    border-top: 1px solid #E8E0D5;
    margin: 18px 0;
  }

  /* Items table */
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
  }
  .items-table thead th {
    background: #051922;
    color: #fff;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    padding: 9px 10px;
  }
  .items-table thead th.center { text-align: center; }
  .items-table thead th.right  { text-align: right; }
  .items-table tbody tr { border-bottom: 1px solid #F0EBE3; }
  .items-table tbody td {
    padding: 10px 10px;
    font-size: 10.5px;
    vertical-align: middle;
  }
  .items-table tbody td.item-name small {
    color: #7a8f99;
    font-size: 9px;
  }
  .items-table tbody td.center { text-align: center; }
  .items-table tbody td.right  { text-align: right; }
  .items-table tbody td.bold   { font-weight: 700; }

  /* Total row */
  .total-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
  }
  .total-row td {
    padding: 10px 10px;
    font-size: 13px;
    font-weight: 700;
  }
  .total-row .total-label { color: #051922; }
  .total-row .total-amount {
    text-align: right;
    color: #0E9FAD;
    font-size: 16px;
  }

  /* Footer */
  .pdf-footer {
    margin-top: 32px;
    padding: 18px 36px;
    background: #F2EDE6;
    font-size: 8.5px;
    color: #7a8f99;
    text-align: center;
    line-height: 1.6;
  }
</style>
</head>
<body>

  <div class="pdf-header">
    <div class="pdf-brand">Statik</div>
    <div class="pdf-tagline">Live Events &amp; Tickets &mdash; Singapore</div>
  </div>

  <div class="confirm-strip">&#10003; Booking Confirmed</div>

  <div class="pdf-body">

    <table class="meta-grid">
      <tr>
        <td>
          <span class="meta-label">Booked by</span>
          <span class="meta-value">' . $memberName . ' <span style="color:#7a8f99;font-weight:400;">(@' . $memberUser . ')</span></span>
        </td>
        <td>
          <span class="meta-label">Order Date</span>
          <span class="meta-value">' . $orderDate . '</span>
        </td>
      </tr>
      <tr>
        <td>
          <span class="meta-label">Order ID</span>
          <span class="meta-value accent" style="font-size:9.5px;">' . $orderIdDisp . '</span>
        </td>
        <td>
          <span class="meta-label">Transaction ID</span>
          <span class="meta-value accent" style="font-size:9.5px;">' . $transId . '</span>
        </td>
      </tr>
    </table>

    <hr class="divider">

    <table class="items-table">
      <thead>
        <tr>
          <th style="width:48%;">Event</th>
          <th class="center" style="width:16%;">Category</th>
          <th class="center" style="width:10%;">Qty</th>
          <th class="right"  style="width:13%;">Unit Price</th>
          <th class="right"  style="width:13%;">Subtotal</th>
        </tr>
      </thead>
      <tbody>' . $rows . '</tbody>
    </table>

    <table class="total-row">
      <tr>
        <td class="total-label">Total Paid</td>
        <td class="total-amount">SGD ' . number_format($total, 2) . '</td>
      </tr>
    </table>

    <hr class="divider">
    <p style="font-size:9px;color:#aaa;margin-top:8px;">
      Please present this confirmation (printed or on-screen) along with a valid photo ID at the venue entrance.
      All sales are final — no refunds or exchanges.
    </p>

  </div>

  <div class="pdf-footer">
    Statik &mdash; Live Events &amp; Tickets &mdash; Singapore<br>
    This is an automatically generated booking confirmation. For enquiries, visit statik.com/contact.
  </div>

</body>
</html>';

// ── Render PDF with Dompdf ────────────────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);   // no remote assets needed
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Statik_Ticket_' . preg_replace('/[^A-Za-z0-9_-]/', '', substr($orderId, 0, 24)) . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
