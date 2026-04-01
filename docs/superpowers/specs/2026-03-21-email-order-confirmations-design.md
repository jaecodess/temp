# Email Order Confirmations Design

**Date:** 2026-03-21
**Project:** TicketSG (INF1005_Statik)
**Status:** Approved

---

## Goal

Send an HTML order confirmation email to the customer immediately after a successful PayPal checkout, using PHPMailer (already installed via Composer) and Gmail SMTP.

---

## Constraints

| Constraint | Decision |
|---|---|
| No new Composer packages | PHPMailer already installed at `vendor/phpmailer/phpmailer/` as a PHPAuth dependency |
| SMTP credentials must not be hardcoded | Store `smtp_username` and `smtp_password` in `.env` (and `.env.example`); load in `inc/mailer.inc.php` |
| Email failure must not block checkout | Wrap send call in try/catch; log failures via `error_log()` but continue the checkout success flow |
| No separate template engine | HTML template is inline in `inc/mailer.inc.php` — no additional files or dependencies |
| `$conn` is closed on line 56 of `checkout.php` | Email send (including member email fetch) happens **before** `$conn->close()`, in the PHP block above `?>` |

---

## Architecture

```
checkout.php  (PHP block, before $conn->close())
    │
    ├── extend cart SELECT to also fetch performances.event_date, performances.venue
    ├── fetch member email: SELECT email FROM members WHERE id = ?  (via $conn)
    └── send_order_confirmation($memberName, $memberEmail, $orderItems, $orderId, $transactionId, $total)
              │
              inc/mailer.inc.php
              │   ├── loads smtp_username / smtp_password from ini/env
              │   ├── configures PHPMailer (Gmail SMTP, STARTTLS, port 587)
              │   ├── builds HTML + plain-text email inline
              │   └── sends; on exception → error_log(), return false
              │
              Gmail SMTP (smtp.gmail.com:587)
                    │
                    └── Customer inbox
```

---

## SMTP Configuration

- **Host:** `smtp.gmail.com`
- **Port:** `587`
- **Encryption:** `STARTTLS` (`PHPMailer::ENCRYPTION_STARTTLS`)
- **Auth:** Gmail address + 16-character App Password
- **From address:** `smtp_username` (the Gmail address)
- **From name:** `TicketSG`
- **Credentials source:** `.env` keys `smtp_username` and `smtp_password`; same keys in `/var/www/private/db-config.ini` on the cloud server

---

## New / Modified Files

| File | Action |
|---|---|
| `inc/mailer.inc.php` | CREATE — PHPMailer setup + `send_order_confirmation()` |
| `checkout.php` | EDIT — extend cart SELECT; fetch member email; call `send_order_confirmation()` before `$conn->close()` |
| `.env` | EDIT — add `smtp_username` and `smtp_password` keys |
| `.env.example` | EDIT — add placeholder keys |

---

## `checkout.php` Changes

### 1. Extend the cart SELECT query

Add `performances.event_date, performances.venue` to the existing SELECT on line 16:

```sql
SELECT cart_items.id, cart_items.quantity, cart_items.ticket_category_id,
       ticket_categories.name AS ticket_cat_name, ticket_categories.price,
       ticket_categories.available_seats AS stock,
       performances.id AS performance_id, performances.name AS performance_name,
       performances.img_name, performances.description,
       performances.event_date, performances.venue
FROM cart_items
JOIN ticket_categories ON cart_items.ticket_category_id = ticket_categories.id
JOIN performances ON ticket_categories.performance_id = performances.id
WHERE cart_items.member_id = ?
```

After this change, each `$cartItems` row will contain the keys:
`id`, `quantity`, `ticket_category_id`, `ticket_cat_name`, `price`, `stock`,
`performance_id`, `performance_name`, `img_name`, `description`,
`event_date`, `venue`, `sub_total`

### 2. Fetch member email and send confirmation

Insert the following block **after line 55 (`$stmt->close()` — the final statement of the cart-clear block) and before line 56 (`$conn->close()`)**:

`$cartTotal` is already computed by the cart-fetch loop earlier in the file and does not need to be recalculated here.

`$_SESSION['name']` and the `$orderId` / `$transactionId` values produced by `sanitize_input()` are already HTML-encoded strings. Pass them directly to `send_order_confirmation()` — do not call `htmlspecialchars()` on them again inside the build functions.

Note: `checkout.php`'s HTML receipt page applies `htmlspecialchars($orderId)` a second time when rendering the page (defensive output escaping). That second call is only for the HTML page output — it is separate from the email path. In the email build functions, output `$orderId` and `$transactionId` directly.

```php
// Fetch member email for confirmation email
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

`$conn->close()` remains immediately after this block, unchanged.

---

## `inc/mailer.inc.php`

### Autoload

At the top of `inc/mailer.inc.php`, before any `use` statements:

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

The path is relative to the `inc/` directory (where `mailer.inc.php` lives), so `__DIR__ . '/../vendor/autoload.php'` resolves to the project root's `vendor/autoload.php` regardless of which file included `mailer.inc.php`.

### Credential loading

1. Try `parse_ini_file('/var/www/private/db-config.ini')` — cloud server.
2. If that returns `false`, try `parse_ini_file(__DIR__ . '/../.env')` — local dev.
3. If both return `false`, call `error_log("Mailer: could not load SMTP credentials")` and `return false` — **do not die()** (email is non-critical).
4. Check that `$config['smtp_username']` and `$config['smtp_password']` are set; if either is missing, `error_log()` and `return false`.

This diverges deliberately from `inc/db.inc.php` — the DB connection dies on failure because the site cannot function without it; the mailer must not die because email failure is non-fatal.

### Function signature

```php
function send_order_confirmation(
    string $memberName,
    string $memberEmail,
    array  $orderItems,    // each row: ticket_cat_name, performance_name, event_date,
                           //           venue, quantity, price, sub_total
    string $orderId,       // PayPal order ID (e.g. "5O190127TN364715T") or internal ref
    string $transactionId,
    float  $total
): bool
```

Returns `true` on success, `false` on any failure.

### PHPMailer setup

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
$mail->Subject = "Booking Confirmed – Order #{$orderId} | TicketSG";
$mail->Body    = build_confirmation_html($memberName, $orderItems, $orderId, $transactionId, $total);
$mail->AltBody = build_confirmation_plain($memberName, $orderItems, $orderId, $transactionId, $total);
$mail->send();
return true;
```

Wrap the entire block in `try { ... } catch (Exception $e) { error_log("Mailer error: " . $e->getMessage()); return false; }`.

### Helper functions (private, defined in `mailer.inc.php`)

#### `build_confirmation_html(...): string`

Same parameters as `send_order_confirmation()`. Returns a complete HTML string. Structure:

1. `<html><head>` with inline `<style>` reset + font-family: Arial, sans-serif
2. **Header banner** — background `#e63946`, white text "TicketSG", subtext "Your tickets are confirmed"
3. **Greeting** — "Hi [Name], your booking is confirmed!"
4. **Order reference box** — grey background, two rows: "Order ID: [orderId]" and "Transaction ID: [transactionId]"
5. **Items table** — columns: Performance | Date | Venue | Category | Qty | Unit Price | Subtotal; one `<tr>` per item using `$orderItems` keys: `performance_name`, `event_date`, `venue`, `ticket_cat_name`, `quantity`, `price`, `sub_total`; format prices as `"SGD " . number_format($price, 2)`; format date as `date('d M Y', strtotime($event_date))`
6. **Total row** — right-aligned, bold: "Total: SGD X.XX"
7. **Important Information box** — amber (`#fff3cd`) background, bold heading "Important Information", three bullet points:
   - Arrive at least 30 minutes before the event
   - Bring a valid photo ID and this booking confirmation
   - No re-entry permitted after exiting the venue
8. **Refund Policy** — muted text: "All sales are final. No cancellations or exchanges."
9. **Support footer** — "Questions? Contact us at support@ticketsg.com"
10. **Footer** — "© 2026 TicketSG. All rights reserved." in muted grey

**Encoding rules:**
- `$memberName`, `$orderId`, `$transactionId` — already HTML-encoded (from session / `sanitize_input()`); output them **directly** with no additional `htmlspecialchars()` call.
- Values from `$orderItems` rows (`performance_name`, `venue`, `ticket_cat_name`) come directly from the DB and have not been through `htmlspecialchars()`; these **must** be wrapped in `htmlspecialchars()` before output.

`build_confirmation_html` and `build_confirmation_plain` are **file-scope functions** (not closures or nested functions inside `send_order_confirmation`), so PHP resolves them regardless of declaration order in the file.

#### `build_confirmation_plain(...): string`

Same parameters. Returns a plain-text string with the following structure:

```
TicketSG - Booking Confirmed

Hi [Name], your booking is confirmed!

Order ID: [orderId]
Transaction ID: [transactionId]

Items:
- [performance_name] | [date] | [venue] | [ticket_cat_name] | Qty: [qty] | SGD [price] each | Subtotal: SGD [sub_total]
(one line per item)

Total: SGD [total]

Important Information:
- Arrive at least 30 minutes before the event
- Bring a valid photo ID and this booking confirmation
- No re-entry permitted after exiting the venue

Refund Policy: All sales are final. No cancellations or exchanges.

Questions? Contact us at support@ticketsg.com

(c) 2026 TicketSG
```

---

## `.env` / `.env.example` Changes

Add to `.env`:
```ini
smtp_username = "inf1005.statik@gmail.com"
smtp_password = "your_gmail_app_password"
```

Add to `.env.example`:
```ini
smtp_username = "your_gmail_address@gmail.com"
smtp_password = "your_16_char_app_password"
```

Add to `/var/www/private/db-config.ini` on cloud server:
```ini
smtp_username = "inf1005.statik@gmail.com"
smtp_password = "your_gmail_app_password"
```

---

## Error Handling

| Scenario | Behaviour |
|---|---|
| SMTP credentials missing from `.env` / ini | `error_log()`, return `false`, checkout proceeds |
| PHPMailer throws `Exception` | Caught, `error_log($e->getMessage())`, return `false`, checkout proceeds |
| Member email fetch returns null/empty | Guarded in `checkout.php` before calling `send_order_confirmation()`; logs and skips silently |

Email failure is always non-fatal — the customer's order is already committed to the database.

---

## Verification Checklist

1. `.env` has `smtp_username` and `smtp_password` populated with Gmail App Password credentials
2. Successful checkout → email arrives in customer inbox within ~30 seconds
3. Email renders correctly in Gmail — HTML table, brand header, amber info box
4. Email shows correct order ID, transaction ID, event date, venue, category, quantity, price, and total
5. Plain-text `AltBody` is present and readable without HTML
6. Invalid/missing SMTP credentials → checkout still completes; `error_log()` entry written; no crash
7. Cloud server: `smtp_username` and `smtp_password` added to `/var/www/private/db-config.ini`; email sends from server
