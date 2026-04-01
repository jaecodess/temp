<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an HTML order confirmation email.
 *
 * @param string $memberName    Customer name (already HTML-encoded via sanitize_input/session)
 * @param string $memberEmail   Customer email address (raw from DB)
 * @param array  $orderItems    Cart items — each row must contain:
 *                              ticket_cat_name, performance_name, event_date,
 *                              venue, quantity, price, sub_total
 * @param string $orderId       PayPal/internal order ID (already HTML-encoded)
 * @param string $transactionId PayPal transaction ID (already HTML-encoded)
 * @param float  $total         Order total
 * @return bool  true on success, false on any failure (non-fatal)
 */
function send_order_confirmation(
    string $memberName,
    string $memberEmail,
    array  $orderItems,
    string $orderId,
    string $transactionId,
    float  $total
): bool {
    // Load SMTP credentials
    $config = @parse_ini_file('/var/www/private/db-config.ini');
    if ($config === false) {
        $config = parse_ini_file(__DIR__ . '/../.env');
        if ($config === false) {
            error_log('Mailer: could not load SMTP credentials from ini or .env');
            return false;
        }
    }

    if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
        error_log('Mailer: smtp_username or smtp_password missing from config');
        return false;
    }

    $smtpUser = $config['smtp_username'];
    $smtpPass = $config['smtp_password'];

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom($smtpUser, 'Statik');
        $mail->addAddress($memberEmail, html_entity_decode($memberName, ENT_QUOTES, 'UTF-8'));
        $mail->isHTML(true);
        $mail->Subject = "Booking Confirmed \xe2\x80\x93 Order #{$orderId} | Statik";
        $mail->Body    = build_confirmation_html($memberName, $orderItems, $orderId, $transactionId, $total);
        $mail->AltBody = build_confirmation_plain($memberName, $orderItems, $orderId, $transactionId, $total);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Build the HTML body of the confirmation email.
 * $memberName, $orderId, $transactionId are already HTML-encoded — output directly.
 * DB-sourced strings (performance_name, venue, ticket_cat_name) must use htmlspecialchars().
 */
function build_confirmation_html(
    string $memberName,
    array  $orderItems,
    string $orderId,
    string $transactionId,
    float  $total
): string {
    $rows = '';
    foreach ($orderItems as $item) {
        $name    = htmlspecialchars($item['performance_name'], ENT_QUOTES, 'UTF-8');
        $date    = date('d M Y', strtotime($item['event_date']));
        $venue   = htmlspecialchars($item['venue'], ENT_QUOTES, 'UTF-8');
        $cat     = htmlspecialchars($item['ticket_cat_name'], ENT_QUOTES, 'UTF-8');
        $qty     = (int)$item['quantity'];
        $price   = 'SGD ' . number_format((float)$item['price'], 2);
        $sub     = 'SGD ' . number_format((float)$item['sub_total'], 2);
        $rows   .= "
            <tr>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;'>{$name}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;'>{$date}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;'>{$venue}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;'>{$cat}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:center;'>{$qty}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:right;'>{$price}</td>
                <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:right;font-weight:bold;'>{$sub}</td>
            </tr>";
    }

    $totalFormatted = 'SGD ' . number_format($total, 2);

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Booking Confirmed | Statik</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:600px;">

      <!-- Header -->
      <tr>
        <td style="background:#051922;padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:28px;font-weight:800;letter-spacing:1px;">Statik</h1>
          <p style="margin:8px 0 0;color:rgba(255,255,255,0.55);font-size:13px;letter-spacing:1px;text-transform:uppercase;">Booking Confirmed</p>
        </td>
      </tr>

      <!-- Greeting -->
      <tr>
        <td style="padding:32px 40px 16px;">
          <h2 style="margin:0 0 8px;font-size:22px;color:#1a1a2e;">Hi {$memberName}, your booking is confirmed!</h2>
          <p style="margin:0;color:#666;font-size:15px;">Thank you for your purchase. Here is a summary of your order.</p>
        </td>
      </tr>

      <!-- Order reference -->
      <tr>
        <td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa;border-radius:6px;padding:16px 20px;">
            <tr>
              <td style="font-size:13px;color:#888;padding:4px 0;">Order ID</td>
              <td style="font-size:13px;color:#1a1a2e;font-weight:bold;text-align:right;padding:4px 0;">{$orderId}</td>
            </tr>
            <tr>
              <td style="font-size:13px;color:#888;padding:4px 0;">Transaction ID</td>
              <td style="font-size:13px;color:#1a1a2e;font-weight:bold;text-align:right;padding:4px 0;">{$transactionId}</td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Items table -->
      <tr>
        <td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eee;border-radius:6px;font-size:13px;">
            <thead>
              <tr style="background:#f8f9fa;">
                <th style="padding:10px 12px;text-align:left;color:#888;font-weight:600;border-bottom:1px solid #eee;">Performance</th>
                <th style="padding:10px 12px;text-align:left;color:#888;font-weight:600;border-bottom:1px solid #eee;">Date</th>
                <th style="padding:10px 12px;text-align:left;color:#888;font-weight:600;border-bottom:1px solid #eee;">Venue</th>
                <th style="padding:10px 12px;text-align:left;color:#888;font-weight:600;border-bottom:1px solid #eee;">Category</th>
                <th style="padding:10px 12px;text-align:center;color:#888;font-weight:600;border-bottom:1px solid #eee;">Qty</th>
                <th style="padding:10px 12px;text-align:right;color:#888;font-weight:600;border-bottom:1px solid #eee;">Unit Price</th>
                <th style="padding:10px 12px;text-align:right;color:#888;font-weight:600;border-bottom:1px solid #eee;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              {$rows}
            </tbody>
            <tfoot>
              <tr>
                <td colspan="6" style="padding:12px;text-align:right;font-weight:bold;color:#1a1a2e;font-size:14px;">Total</td>
                <td style="padding:12px;text-align:right;font-weight:bold;color:#0E9FAD;font-size:16px;">{$totalFormatted}</td>
              </tr>
            </tfoot>
          </table>
        </td>
      </tr>

      <!-- Important Information -->
      <tr>
        <td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:16px 20px;">
            <tr>
              <td>
                <p style="margin:0 0 8px;font-size:14px;font-weight:bold;color:#856404;">&#9888; Important Information</p>
                <ul style="margin:0;padding-left:18px;color:#856404;font-size:13px;line-height:1.7;">
                  <li>Arrive at least 30 minutes before the event</li>
                  <li>Bring a valid photo ID and this booking confirmation</li>
                  <li>No re-entry permitted after exiting the venue</li>
                </ul>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Refund policy -->
      <tr>
        <td style="padding:0 40px 24px;">
          <p style="margin:0;font-size:13px;color:#999;text-align:center;">
            All sales are final. No cancellations or exchanges.
          </p>
        </td>
      </tr>

      <!-- Support footer -->
      <tr>
        <td style="padding:0 40px 32px;text-align:center;">
          <p style="margin:0;font-size:13px;color:#666;">
            Questions? Contact us at <a href="mailto:inf1005.statik@gmail.com" style="color:#0E9FAD;">inf1005.statik@gmail.com</a>
          </p>
        </td>
      </tr>

      <!-- Copyright footer -->
      <tr>
        <td style="background:#f8f9fa;padding:16px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#aaa;">&copy; 2026 Statik. All rights reserved.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

/**
 * Build the plain-text fallback body.
 * $memberName, $orderId, $transactionId are HTML-encoded on entry; decoded before plain-text output.
 */
function build_confirmation_plain(
    string $memberName,
    array  $orderItems,
    string $orderId,
    string $transactionId,
    float  $total
): string {
    $plainName          = html_entity_decode($memberName,    ENT_QUOTES, 'UTF-8');
    $plainOrderId       = html_entity_decode($orderId,       ENT_QUOTES, 'UTF-8');
    $plainTransactionId = html_entity_decode($transactionId, ENT_QUOTES, 'UTF-8');

    $lines = [];
    $lines[] = "Statik - Booking Confirmed";
    $lines[] = str_repeat('-', 40);
    $lines[] = "";
    $lines[] = "Hi {$plainName}, your booking is confirmed!";
    $lines[] = "";
    $lines[] = "Order ID:      {$plainOrderId}";
    $lines[] = "Transaction ID: {$plainTransactionId}";
    $lines[] = "";
    $lines[] = "Items:";

    foreach ($orderItems as $item) {
        $date  = date('d M Y', strtotime($item['event_date']));
        $price = number_format((float)$item['price'], 2);
        $sub   = number_format((float)$item['sub_total'], 2);
        $lines[] = sprintf(
            "  - %s | %s | %s | %s | Qty: %d | SGD %s each | Subtotal: SGD %s",
            $item['performance_name'],
            $date,
            $item['venue'],
            $item['ticket_cat_name'],
            (int)$item['quantity'],
            $price,
            $sub
        );
    }

    $lines[] = "";
    $lines[] = "Total: SGD " . number_format($total, 2);
    $lines[] = "";
    $lines[] = "Important Information:";
    $lines[] = "  - Arrive at least 30 minutes before the event";
    $lines[] = "  - Bring a valid photo ID and this booking confirmation";
    $lines[] = "  - No re-entry permitted after exiting the venue";
    $lines[] = "";
    $lines[] = "Refund Policy: All sales are final. No cancellations or exchanges.";
    $lines[] = "";
    $lines[] = "Questions? Contact us at inf1005.statik@gmail.com";
    $lines[] = "";
    $lines[] = "(c) 2026 Statik. All rights reserved.";

    return implode("\n", $lines);
}

/**
 * Send a support request email to the Statik support inbox.
 * Also sends an auto-reply confirmation to the customer.
 *
 * @param string      $senderName     Customer's name
 * @param string      $senderEmail    Customer's email address
 * @param string      $requestType    Category slug (e.g. 'order', 'refund')
 * @param string      $orderId        Optional order ID (may be empty)
 * @param string      $subject        Subject line entered by customer
 * @param string      $message        Body message entered by customer
 * @param string|null $attachmentPath Temp file path of uploaded attachment (or null)
 * @param string|null $attachmentName Original filename of attachment (or null)
 * @return bool true on success, false on any failure
 */
function send_support_request(
    string  $senderName,
    string  $senderEmail,
    string  $requestType,
    string  $orderId,
    string  $subject,
    string  $message,
    ?string $attachmentPath,
    ?string $attachmentName
): bool {
    $config = @parse_ini_file('/var/www/private/db-config.ini');
    if ($config === false) {
        $config = parse_ini_file(__DIR__ . '/../.env');
        if ($config === false) {
            error_log('Mailer: could not load SMTP credentials for support request');
            return false;
        }
    }

    if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
        error_log('Mailer: smtp_username or smtp_password missing');
        return false;
    }

    $smtpUser = $config['smtp_username'];
    $smtpPass = $config['smtp_password'];

    $typeLabels = [
        'order'     => 'Order & Tickets',
        'payment'   => 'Payment & Billing',
        'refund'    => 'Refund & Cancellation',
        'account'   => 'Account & Login',
        'event'     => 'Event Information',
        'technical' => 'Technical Issue',
        'other'     => 'Other',
    ];
    $typeLabel = $typeLabels[$requestType] ?? ucfirst($requestType);

    try {
        // ── 1. Email to support inbox ──────────────────────────────────
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtpUser, 'Statik Support Form');
        $mail->addAddress($smtpUser, 'Statik Support');   // support inbox = same Gmail
        $mail->addReplyTo($senderEmail, $senderName);     // reply goes to the customer

        if ($attachmentPath && $attachmentName) {
            $mail->addAttachment($attachmentPath, $attachmentName);
        }

        $mail->isHTML(true);
        $mail->Subject = "[Support] [{$typeLabel}] {$subject}";
        $mail->Body    = build_support_request_html($senderName, $senderEmail, $typeLabel, $orderId, $subject, $message);
        $mail->AltBody = build_support_request_plain($senderName, $senderEmail, $typeLabel, $orderId, $subject, $message);
        $mail->send();

        // ── 2. Auto-reply to customer ──────────────────────────────────
        $mail2 = new PHPMailer(true);
        $mail2->isSMTP();
        $mail2->Host       = 'smtp.gmail.com';
        $mail2->SMTPAuth   = true;
        $mail2->Username   = $smtpUser;
        $mail2->Password   = $smtpPass;
        $mail2->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail2->Port       = 587;

        $mail2->setFrom($smtpUser, 'Statik Support');
        $mail2->addAddress($senderEmail, $senderName);

        $mail2->isHTML(true);
        $mail2->Subject = "We received your request – {$subject} | Statik";
        $mail2->Body    = build_support_autoreply_html($senderName, $typeLabel, $subject);
        $mail2->AltBody = build_support_autoreply_plain($senderName, $typeLabel, $subject);
        $mail2->send();

        return true;
    } catch (Exception $e) {
        error_log('Support mailer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * HTML body for the support-inbox email.
 */
function build_support_request_html(
    string $senderName,
    string $senderEmail,
    string $typeLabel,
    string $orderId,
    string $subject,
    string $message
): string {
    $name    = htmlspecialchars($senderName,  ENT_QUOTES, 'UTF-8');
    $email   = htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8');
    $type    = htmlspecialchars($typeLabel,   ENT_QUOTES, 'UTF-8');
    $oid     = $orderId ? htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') : '<span style="color:#bbb;">—</span>';
    $subj    = htmlspecialchars($subject,  ENT_QUOTES, 'UTF-8');
    $msg     = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Support Request</title></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:600px;">

      <tr>
        <td style="background:#051922;padding:28px 36px;">
          <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;">Statik Support</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,0.5);font-size:13px;">New support request received</p>
        </td>
      </tr>

      <tr>
        <td style="padding:28px 36px 8px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa;border-radius:6px;padding:16px 20px;font-size:13px;">
            <tr>
              <td style="color:#888;padding:5px 0;width:130px;">From</td>
              <td style="color:#1a1a2e;font-weight:bold;padding:5px 0;">{$name} &lt;{$email}&gt;</td>
            </tr>
            <tr>
              <td style="color:#888;padding:5px 0;">Category</td>
              <td style="padding:5px 0;"><span style="background:#e1f5ee;color:#0f6e56;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">{$type}</span></td>
            </tr>
            <tr>
              <td style="color:#888;padding:5px 0;">Order ID</td>
              <td style="color:#1a1a2e;padding:5px 0;">{$oid}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:5px 0;">Subject</td>
              <td style="color:#1a1a2e;font-weight:bold;padding:5px 0;">{$subj}</td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td style="padding:16px 36px 32px;">
          <p style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px;">Message</p>
          <div style="font-size:14px;color:#333;line-height:1.75;border-left:3px solid #0e9fad;padding-left:16px;">
            {$msg}
          </div>
        </td>
      </tr>

      <tr>
        <td style="background:#f8f9fa;padding:14px 36px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#aaa;">Reply directly to this email to respond to the customer.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

/**
 * Plain-text body for the support-inbox email.
 */
function build_support_request_plain(
    string $senderName,
    string $senderEmail,
    string $typeLabel,
    string $orderId,
    string $subject,
    string $message
): string {
    $oid = $orderId ?: '—';
    return implode("\n", [
        "Statik Support – New Request",
        str_repeat('-', 40),
        "",
        "From:     {$senderName} <{$senderEmail}>",
        "Category: {$typeLabel}",
        "Order ID: {$oid}",
        "Subject:  {$subject}",
        "",
        "Message:",
        $message,
        "",
        "Reply to this email to respond to the customer.",
    ]);
}

/**
 * HTML auto-reply to the customer confirming receipt.
 */
function build_support_autoreply_html(
    string $senderName,
    string $typeLabel,
    string $subject
): string {
    $name  = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
    $type  = htmlspecialchars($typeLabel,  ENT_QUOTES, 'UTF-8');
    $subj  = htmlspecialchars($subject,    ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Request Received</title></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:600px;">

      <tr>
        <td style="background:#051922;padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:26px;font-weight:800;">Statik</h1>
          <p style="margin:8px 0 0;color:rgba(255,255,255,0.5);font-size:14px;">Support Centre</p>
        </td>
      </tr>

      <tr>
        <td style="padding:32px 40px 16px;">
          <h2 style="margin:0 0 10px;font-size:20px;color:#1a1a2e;">We got your message, {$name}!</h2>
          <p style="margin:0;color:#666;font-size:14px;line-height:1.7;">
            Thanks for reaching out. Your support request has been received and a member of our team will get back to you within <strong>1–2 business days</strong>.
          </p>
        </td>
      </tr>

      <tr>
        <td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa;border-radius:6px;padding:14px 18px;font-size:13px;">
            <tr>
              <td style="color:#888;padding:4px 0;">Category</td>
              <td style="text-align:right;padding:4px 0;"><span style="background:#e1f5ee;color:#0f6e56;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">{$type}</span></td>
            </tr>
            <tr>
              <td style="color:#888;padding:4px 0;">Subject</td>
              <td style="color:#1a1a2e;font-weight:bold;text-align:right;padding:4px 0;">{$subj}</td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td style="padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:14px 18px;">
            <tr>
              <td>
                <p style="margin:0 0 6px;font-size:13px;font-weight:bold;color:#856404;">While you wait, you may find your answer in our FAQ:</p>
                <p style="margin:0;font-size:13px;color:#856404;">
                  Visit <a href="https://statik.sg/help.php" style="color:#0E9FAD;">statik.sg/help.php</a> for answers to common questions about orders, payments, and refunds.
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td style="padding:0 40px 28px;text-align:center;">
          <p style="margin:0;font-size:13px;color:#999;">
            If you did not submit this request, please ignore this email.<br>
            Questions? Email us at <a href="mailto:inf1005.statik@gmail.com" style="color:#0E9FAD;">inf1005.statik@gmail.com
</a>
          </p>
        </td>
      </tr>

      <tr>
        <td style="background:#f8f9fa;padding:14px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#aaa;">&copy; 2026 Statik @Singapore Institute of Technology. All rights reserved.</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

/**
 * Plain-text auto-reply to customer.
 */
function build_support_autoreply_plain(
    string $senderName,
    string $typeLabel,
    string $subject
): string {
    return implode("\n", [
        "Statik Support Centre",
        str_repeat('-', 40),
        "",
        "Hi {$senderName},",
        "",
        "We've received your support request and will get back to you within 1-2 business days.",
        "",
        "Category: {$typeLabel}",
        "Subject:  {$subject}",
        "",
        "While you wait, check our FAQ at statik.sg/help.php for answers to common questions.",
        "",
        "If you did not submit this request, please ignore this email.",
        "",
        "support@statik.sg",
        "(c) 2026 Statik. All rights reserved.",
    ]);
}