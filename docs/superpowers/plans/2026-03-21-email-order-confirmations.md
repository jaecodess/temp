# Email Order Confirmations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Send an HTML order confirmation email via Gmail SMTP (PHPMailer) immediately after a successful PayPal checkout, without blocking the checkout flow on email failure.

**Architecture:** A new `inc/mailer.inc.php` helper exposes `send_order_confirmation()`, which loads SMTP credentials from `.env` / `/var/www/private/db-config.ini`, configures PHPMailer, and sends a branded HTML email. `checkout.php` calls it after the DB work is committed and before `$conn->close()`. Email failure is logged and non-fatal.

**Tech Stack:** PHPMailer (`vendor/phpmailer/phpmailer/` — already installed), Gmail SMTP, plain PHP, MySQLi.

**Spec:** `docs/superpowers/specs/2026-03-21-email-order-confirmations-design.md`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `inc/mailer.inc.php` | CREATE | SMTP setup, HTML/plain-text builders, `send_order_confirmation()` |
| `checkout.php` | MODIFY | Extend cart SELECT; fetch member email; call mailer before `$conn->close()` |
| `.env` | MODIFY | Add `smtp_username` and `smtp_password` keys |
| `.env.example` | MODIFY | Add placeholder SMTP keys |

---

## Task 1: Add SMTP credentials to `.env` and `.env.example`

**Files:**
- Modify: `.env`
- Modify: `.env.example`

- [ ] **Step 1: Add SMTP keys to `.env`**

Open `.env` and append these two lines (replace the password placeholder with your actual Gmail App Password):

```ini
smtp_username = "inf1005.statik@gmail.com"
smtp_password = "your_gmail_app_password_here"
```

`.env` should now have 8 keys total: `servername`, `username`, `password`, `dbname`, `port`, `paypal_client_id`, `smtp_username`, `smtp_password`.

- [ ] **Step 2: Add placeholder SMTP keys to `.env.example`**

Open `.env.example` and append:

```ini
smtp_username = "your_gmail_address@gmail.com"
smtp_password = "your_16_char_app_password"
```

- [ ] **Step 3: Verify `.env` parses correctly**

Create a temporary file `test_env.php` at the project root:

```php
<?php
$config = parse_ini_file(__DIR__ . '/.env');
var_dump($config['smtp_username'] ?? 'MISSING');
var_dump($config['smtp_password'] ?? 'MISSING');
```

Visit `http://localhost/test_env.php` in a browser. Expected output:
```
string(26) "inf1005.statik@gmail.com"
string(19) "your_app_password"
```

Both values must not be `"MISSING"`.

- [ ] **Step 4: Delete `test_env.php`**

```bash
rm test_env.php
```

- [ ] **Step 5: Commit**

```bash
git add .env.example
git commit -m "feat: add SMTP credential keys for email confirmations"
```

Note: `.env` is gitignored and must NOT be committed.

**Cloud server:** Also add the same two keys to `/var/www/private/db-config.ini` on the cloud server (`35.212.206.211`) so email works in production:
```ini
smtp_username = "inf1005.statik@gmail.com"
smtp_password = "your_gmail_app_password_here"
```

---

## Task 2: Create `inc/mailer.inc.php`

**Files:**
- Create: `inc/mailer.inc.php`

### Background

PHPMailer is already installed at `vendor/phpmailer/phpmailer/`. The autoloader is at `vendor/autoload.php`. Since `inc/mailer.inc.php` lives inside the `inc/` subdirectory, the autoload path must be `__DIR__ . '/../vendor/autoload.php'` (one level up).

`$orderId`, `$transactionId`, and `$memberName` passed to `send_order_confirmation()` are already HTML-encoded (produced by `sanitize_input()` / stored in session). Output them directly — do not call `htmlspecialchars()` on them again. DB values (`performance_name`, `venue`, `ticket_cat_name`) are raw and must be passed through `htmlspecialchars()`.

- [ ] **Step 1: Create a smoke-test script to confirm PHPMailer loads**

Create `test_mailer_load.php` at the project root:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer();
echo "PHPMailer loaded OK: " . PHPMailer::VERSION . PHP_EOL;
```

Visit `http://localhost/test_mailer_load.php`. Expected: `PHPMailer loaded OK: 6.x.x` (any version). If you see a fatal error, check that `vendor/autoload.php` exists at the project root.

Delete `test_mailer_load.php` after confirming.

- [ ] **Step 2: Create `inc/mailer.inc.php`**

Create the file with the following complete content:

```php
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
        $mail->setFrom($smtpUser, 'TicketSG');
        $mail->addAddress($memberEmail, $memberName);
        $mail->isHTML(true);
        $mail->Subject = "Booking Confirmed \xe2\x80\x93 Order #{$orderId} | TicketSG";
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
<title>Booking Confirmed | TicketSG</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;max-width:600px;">

      <!-- Header -->
      <tr>
        <td style="background:#e63946;padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:28px;font-weight:800;letter-spacing:1px;">TicketSG</h1>
          <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:15px;">Your tickets are confirmed</p>
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
                <td style="padding:12px;text-align:right;font-weight:bold;color:#e63946;font-size:16px;">{$totalFormatted}</td>
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
            Questions? Contact us at <a href="mailto:support@ticketsg.com" style="color:#e63946;">support@ticketsg.com</a>
          </p>
        </td>
      </tr>

      <!-- Copyright footer -->
      <tr>
        <td style="background:#f8f9fa;padding:16px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#aaa;">&copy; 2026 TicketSG. All rights reserved.</p>
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
 * $memberName, $orderId, $transactionId are already HTML-encoded — output directly.
 */
function build_confirmation_plain(
    string $memberName,
    array  $orderItems,
    string $orderId,
    string $transactionId,
    float  $total
): string {
    $lines = [];
    $lines[] = "TicketSG - Booking Confirmed";
    $lines[] = str_repeat('-', 40);
    $lines[] = "";
    $lines[] = "Hi {$memberName}, your booking is confirmed!";
    $lines[] = "";
    $lines[] = "Order ID:      {$orderId}";
    $lines[] = "Transaction ID: {$transactionId}";
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
    $lines[] = "Questions? Contact us at support@ticketsg.com";
    $lines[] = "";
    $lines[] = "(c) 2026 TicketSG. All rights reserved.";

    return implode("\n", $lines);
}
```

- [ ] **Step 3: Smoke-test the mailer with a test script**

Create `test_mailer.php` at the project root to call `send_order_confirmation()` directly without needing to go through checkout:

```php
<?php
session_start();
require_once __DIR__ . '/inc/mailer.inc.php';

$testItems = [
    [
        'performance_name' => 'Test Concert',
        'event_date'       => '2026-06-15',
        'venue'            => 'Singapore Indoor Stadium',
        'ticket_cat_name'  => 'Cat 1',
        'quantity'         => 2,
        'price'            => 150.00,
        'sub_total'        => 300.00,
    ],
];

$result = send_order_confirmation(
    'Test User',
    'your_own_email@example.com',   // <-- replace with your own email to receive the test
    $testItems,
    'TEST-ORDER-001',
    'TEST-TXN-001',
    300.00
);

echo $result ? "Email sent successfully!" : "Email failed — check error_log.";
```

Replace `your_own_email@example.com` with an address you can check.

Visit `http://localhost/test_mailer.php`. Expected output: `Email sent successfully!`

Check your inbox — the email should arrive within ~30 seconds. Verify:
- Subject: `Booking Confirmed – Order #TEST-ORDER-001 | TicketSG`
- Red header with "TicketSG"
- Items table with "Test Concert", "15 Jun 2026", venue, category, qty, price
- Total: SGD 300.00
- Amber "Important Information" box
- Footer

If output is `Email failed`, visit the PHP error log (typically `C:\Dev\php\logs\php_error.log` or check `php.ini` for `error_log` path) for the PHPMailer error message.

- [ ] **Step 4: Delete `test_mailer.php`**

```bash
rm test_mailer.php
```

- [ ] **Step 5: Commit**

```bash
git add inc/mailer.inc.php
git commit -m "feat: add mailer helper with send_order_confirmation"
```

---

## Task 3: Wire mailer into `checkout.php`

**Files:**
- Modify: `checkout.php` (lines 16 and 55–56)

### Background

`checkout.php` has two places to edit:
1. **Line 16** — the cart SELECT query; add `performances.event_date, performances.venue` to the SELECT list
2. **Lines 55–56** — after the cart-clear `$stmt->close()` and before `$conn->close()`; insert the email fetch + send block

`$conn->close()` on line 56 must remain the last DB operation before HTML output. The entire email block goes between lines 55 and 56.

- [ ] **Step 1: Extend the cart SELECT query**

Find the existing `$stmt = $conn->prepare(...)` on line 16 of `checkout.php`. It currently ends with:
```
...performances.img_name, performances.description FROM cart_items...
```

Replace the SELECT list to also include `performances.event_date, performances.venue`. The full updated query string:

```php
$stmt = $conn->prepare("SELECT cart_items.id, cart_items.quantity, cart_items.ticket_category_id, ticket_categories.name AS ticket_cat_name, ticket_categories.price, ticket_categories.available_seats AS stock, performances.id AS performance_id, performances.name AS performance_name, performances.img_name, performances.description, performances.event_date, performances.venue FROM cart_items JOIN ticket_categories ON cart_items.ticket_category_id = ticket_categories.id JOIN performances ON ticket_categories.performance_id = performances.id WHERE cart_items.member_id = ?");
```

- [ ] **Step 2: Add email send block before `$conn->close()`**

Find line 55: `$stmt->close();` (the close of the DELETE statement) and line 56: `$conn->close();`.

Insert the following block between them:

```php
// Send order confirmation email
require_once 'inc/mailer.inc.php';
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
```

After the edit, the structure around line 55 should be:
```
$stmt->close();         // closes the DELETE cart stmt
// Send order confirmation email
require_once 'inc/mailer.inc.php';
... (fetch email, send) ...
$conn->close();         // closes DB connection — still the last DB call before HTML
?>
<!DOCTYPE html>
```

- [ ] **Step 3: Manual end-to-end test**

1. Log in as a test user (not admin)
2. Add at least one ticket to the cart
3. Complete checkout via PayPal sandbox
4. Expected on the page: the receipt page renders as before (Order Confirmed, items, total)
5. Expected in inbox: HTML confirmation email arrives with correct order details, performance name, date, venue, category, total

- [ ] **Step 4: Test email failure is non-fatal**

Temporarily break the SMTP password in `.env` (change one character). Complete another checkout. Expected: receipt page still renders correctly; no crash; PHP error log contains `Mailer error: ...` or `smtp_username or smtp_password missing`. Restore the correct password after.

- [ ] **Step 5: Commit**

```bash
git add checkout.php
git commit -m "feat: send order confirmation email after checkout"
```
