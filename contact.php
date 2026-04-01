<?php
require_once 'inc/auth.inc.php';
require_once 'inc/mailer.inc.php';

$submitSuccess = false;
$submitError   = '';
$pageTitle = 'Contact Us';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderName  = sanitize_input($_POST['name']         ?? '');
    $senderEmail = sanitize_input($_POST['email']        ?? '');
    $requestType = sanitize_input($_POST['request_type'] ?? '');
    $orderId     = sanitize_input($_POST['order_id']     ?? '');
    $subject     = sanitize_input($_POST['subject']      ?? '');
    $message     = sanitize_input($_POST['message']      ?? '');

    if (empty($senderName) || empty($senderEmail) || empty($requestType) || empty($subject) || empty($message)) {
        $submitError = 'Please fill in all required fields.';
    } elseif (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        $submitError = 'Please enter a valid email address.';
    } else {
        $attachmentPath = null;
        $attachmentName = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
            $detectedMime = mime_content_type($_FILES['attachment']['tmp_name']);
            $fileSize     = $_FILES['attachment']['size'];

            if (!in_array($detectedMime, $allowedMimes, true)) {
                $submitError = 'Only PNG, JPG, and PDF attachments are allowed.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $submitError = 'Attachment must be under 5MB.';
            } else {
                $attachmentPath = $_FILES['attachment']['tmp_name'];
                $attachmentName = basename($_FILES['attachment']['name']);
            }
        }

        if (empty($submitError)) {
            $sent = send_support_request(
                $senderName,
                $senderEmail,
                $requestType,
                $orderId,
                $subject,
                $message,
                $attachmentPath,
                $attachmentName
            );

            if ($sent) {
                $submitSuccess = true;
            } else {
                $submitError = 'Sorry, we could not send your message right now. Please try again or email us directly at support@statik.sg.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <style>
        .request-page {
            padding: 64px 0 100px;
            background-color: var(--bg-body);
            min-height: 60vh;
        }
        .request-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 40px;
            align-items: start;
        }
        @media (max-width: 991px) {
            .request-layout { grid-template-columns: 1fr; }
            .request-sidebar { order: -1; }
        }
        .request-card {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }
        .request-card-header {
            background: var(--color-dark);
            padding: 22px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .request-card-header i { color: var(--color-accent); font-size: 15px; }
        .request-card-header h2 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .request-card-body { padding: 32px 28px; }
        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            font-family: var(--font-heading);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 7px;
        }
        .form-group label .required { color: var(--color-accent); margin-left: 3px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: var(--bg-body);
            border: 1.5px solid var(--surface-border);
            border-radius: 10px;
            padding: 12px 16px;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            color: var(--color-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(14, 159, 173, 0.12);
        }
        .form-group textarea { resize: vertical; min-height: 130px; }
        .select-wrap { position: relative; }
        .select-wrap::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            font-size: 10px;
            color: var(--text-subtle);
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
        .file-upload-wrap {
            border: 1.5px dashed var(--surface-border);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: var(--bg-body);
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            display: block;
        }
        .file-upload-wrap:hover { border-color: var(--color-accent); background: rgba(14, 159, 173, 0.04); }
        .file-upload-wrap input[type="file"] { display: none; }
        .file-upload-wrap i { font-size: 22px; color: var(--text-subtle); display: block; margin-bottom: 6px; }
        .file-upload-wrap p { font-family: var(--font-heading); font-size: 0.8rem; color: var(--text-subtle); margin: 0; }
        .file-upload-wrap p span { color: var(--color-accent); font-weight: 700; }
        #file-name-display { font-family: var(--font-heading); font-size: 0.78rem; color: var(--color-accent); margin-top: 8px; min-height: 18px; }
        .btn-submit-request {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.04em;
            padding: 14px 32px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: background 0.22s, transform 0.22s;
            width: 100%;
            justify-content: center;
            margin-top: 8px;
        }
        .btn-submit-request:hover { background: var(--color-accent); transform: translateY(-2px); }
        .btn-submit-request:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .alert-error {
            background: rgba(210,43,43,0.06);
            border: 1px solid rgba(210,43,43,0.22);
            border-radius: 10px;
            padding: 14px 18px;
            font-family: var(--font-heading);
            font-size: 0.85rem;
            color: var(--color-red);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .request-success { text-align: center; padding: 56px 28px; }
        .success-circle {
            width: 64px;
            height: 64px;
            border: 2px solid var(--color-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            animation: circleIn 0.4s ease forwards;
        }
        .success-circle i { color: var(--color-accent); font-size: 24px; }
        @keyframes circleIn {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .request-success h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: var(--color-dark); margin-bottom: 8px; }
        .request-success p { font-family: var(--font-heading); font-size: 0.88rem; color: #aaa; margin-bottom: 22px; }
        .btn-back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-dark);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 13px;
            padding: 12px 26px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-back-home:hover { background: var(--color-accent); color: #fff; }
        .sidebar-card { background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-card); margin-bottom: 16px; }
        .sidebar-card-header { background: var(--color-dark); padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
        .sidebar-card-header i { color: var(--color-accent); font-size: 13px; }
        .sidebar-card-header h3 { font-family: var(--font-display); font-size: 0.85rem; font-weight: 800; color: #fff; letter-spacing: 1px; text-transform: uppercase; margin: 0; }
        .sidebar-card-body { padding: 18px 20px; }
        .contact-info-row { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--surface-border); }
        .contact-info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .contact-info-row i { color: var(--color-accent); font-size: 14px; margin-top: 2px; width: 16px; text-align: center; flex-shrink: 0; }
        .contact-info-row .info-label { font-family: var(--font-heading); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-subtle); margin-bottom: 2px; }
        .contact-info-row .info-value { font-family: var(--font-heading); font-size: 0.85rem; color: var(--color-dark); line-height: 1.5; }
        .hours-table { width: 100%; font-family: var(--font-heading); font-size: 0.82rem; }
        .hours-table tr td:first-child { color: var(--text-muted); padding: 4px 0; }
        .hours-table tr td:last-child { color: var(--color-dark); font-weight: 700; text-align: right; padding: 4px 0; }
        .quick-link { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px solid var(--surface-border); text-decoration: none; transition: color 0.2s; }
        .quick-link:last-child { border-bottom: none; padding-bottom: 0; }
        .quick-link span { font-family: var(--font-heading); font-size: 0.85rem; color: var(--color-dark); display: flex; align-items: center; gap: 9px; }
        .quick-link span i { color: var(--color-accent); font-size: 13px; width: 16px; text-align: center; }
        .quick-link .link-arrow { color: var(--text-subtle); font-size: 11px; transition: transform 0.2s, color 0.2s; }
        .quick-link:hover span { color: var(--color-accent); }
        .quick-link:hover .link-arrow { transform: translateX(3px); color: var(--color-accent); }
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
                        <p class="breadcrumb-label">Statik Support</p>
                        <h1>Submit a Request</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="request-page">
        <div class="container">
            <div class="request-layout">

                <!-- Left: form / success -->
                <div>
                    <div class="request-card">
                        <div class="request-card-header">
                            <i class="fas fa-headset"></i>
                            <h2>Contact Support</h2>
                        </div>

                        <?php if ($submitSuccess): ?>

                        <div class="request-success">
                            <div class="success-circle"><i class="fas fa-check"></i></div>
                            <h3>Request Submitted!</h3>
                            <p>We've received your message and will get back to you at your email address within 1–2 business days.</p>
                            <a href="/" class="btn-back-home">
                                <i class="fas fa-arrow-left"></i> Back to Home
                            </a>
                        </div>

                        <?php else: ?>

                        <div class="request-card-body">

                            <?php if (!empty($submitError)): ?>
                            <div class="alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($submitError) ?>
                            </div>
                            <?php endif; ?>

                            <form method="post" action="/contact.php" enctype="multipart/form-data" id="request-form">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Your name <span class="required">*</span></label>
                                        <input type="text" name="name" placeholder="e.g. Billy Tan" required
                                            value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['name'] ?? '')) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Email address <span class="required">*</span></label>
                                        <input type="email" name="email" placeholder="you@example.com" required
                                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Request type <span class="required">*</span></label>
                                    <div class="select-wrap">
                                        <select name="request_type" required>
                                            <option value="" disabled <?= empty($_POST['request_type']) ? 'selected' : '' ?>>Choose a topic…</option>
                                            <?php
                                            $types = [
                                                'order'     => 'Order & Tickets',
                                                'payment'   => 'Payment & Billing',
                                                'refund'    => 'Refund & Cancellation',
                                                'account'   => 'Account & Login',
                                                'event'     => 'Event Information',
                                                'technical' => 'Technical Issue',
                                                'other'     => 'Other',
                                            ];
                                            foreach ($types as $val => $label):
                                                $sel = (($_POST['request_type'] ?? '') === $val) ? 'selected' : '';
                                            ?>
                                            <option value="<?= $val ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Order ID <span style="color:var(--text-subtle);font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                    <input type="text" name="order_id" placeholder="e.g. 5O190127TN364715T"
                                        value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Subject <span class="required">*</span></label>
                                    <input type="text" name="subject" placeholder="Brief description of your issue" required
                                        value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label>Description <span class="required">*</span></label>
                                    <textarea name="message" placeholder="Please describe your issue in as much detail as possible…" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Attachment <span style="color:var(--text-subtle);font-weight:400;text-transform:none;letter-spacing:0;">(optional — PNG, JPG, PDF up to 5MB)</span></label>
                                    <label class="file-upload-wrap" for="file-input">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Drag &amp; drop or <span>browse file</span></p>
                                        <input type="file" id="file-input" name="attachment" accept=".png,.jpg,.jpeg,.pdf" onchange="showFileName(this)">
                                    </label>
                                    <div id="file-name-display"></div>
                                </div>

                                <button type="submit" class="btn-submit-request" id="submit-btn">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>

                            </form>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: sidebar -->
                <div class="request-sidebar">

                    <div class="sidebar-card">
                        <div class="sidebar-card-header">
                            <i class="fas fa-address-book"></i>
                            <h3>Contact Info</h3>
                        </div>
                        <div class="sidebar-card-body">
                            <div class="contact-info-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="info-label">Address</div>
                                    <div class="info-value">1 Punggol Coast Road<br>Singapore 828608</div>
                                </div>
                            </div>
                            <div class="contact-info-row">
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <div class="info-label">Phone</div>
                                    <div class="info-value">+65 6510 3000</div>
                                </div>
                            </div>
                            <div class="contact-info-row">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <div class="info-label">Email</div>
                                    <div class="info-value">inf1005.statik@gmail.com</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <div class="sidebar-card-header">
                            <i class="far fa-clock"></i>
                            <h3>Support Hours</h3>
                        </div>
                        <div class="sidebar-card-body">
                            <table class="hours-table">
                                <tr><td>Mon – Fri</td><td>8:00 AM – 9:00 PM</td></tr>
                                <tr><td>Sat – Sun</td><td>10:00 AM – 8:00 PM</td></tr>
                                <tr><td>Public Holidays</td><td>Closed</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <div class="sidebar-card-header">
                            <i class="fas fa-bolt"></i>
                            <h3>Quick Links</h3>
                        </div>
                        <div class="sidebar-card-body">
                            <a href="/help.php" class="quick-link">
                                <span><i class="fas fa-question-circle"></i> Browse FAQ</span>
                                <i class="fas fa-chevron-right link-arrow"></i>
                            </a>
                            <a href="/orders.php" class="quick-link">
                                <span><i class="fas fa-receipt"></i> View My Orders</span>
                                <i class="fas fa-chevron-right link-arrow"></i>
                            </a>
                            <a href="/account.php" class="quick-link">
                                <span><i class="fas fa-user-circle"></i> Account Details</span>
                                <i class="fas fa-chevron-right link-arrow"></i>
                            </a>
                            <a href="/shop.php" class="quick-link">
                                <span><i class="fas fa-ticket-alt"></i> Browse Events</span>
                                <i class="fas fa-chevron-right link-arrow"></i>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php include "inc/footer.inc.php"; ?>

    <script>
        function showFileName(input) {
            var display = document.getElementById('file-name-display');
            display.textContent = input.files.length > 0 ? input.files[0].name : '';
        }

        var form = document.getElementById('request-form');
        if (form) {
            form.addEventListener('submit', function () {
                var btn = document.getElementById('submit-btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
            });
        }
    </script>
</body>
</html>
