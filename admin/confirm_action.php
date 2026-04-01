<?php
require_once '../inc/auth.inc.php';
require_once '../inc/otp.inc.php';

require_admin();

if (!isset($_SESSION['pending_action'])) {
    header('Location: /admin/analytics.php');
    exit;
}

$action     = $_SESSION['pending_action'];
$adminEmail = $_SESSION['email'] ?? '';
$canResend  = otp_can_resend($adminEmail, 'admin_confirm');
$error      = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
$success    = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

$cancelUrls = [
    'delete_member'   => '/admin/manage.php?tab=members',
    'delete_item'     => '/admin/manage.php?tab=events',
    'delete_category' => '/admin/manage.php?tab=genres',
];
$cancelUrl = $cancelUrls[$action['type']] ?? '/admin/analytics.php';

$pageTitle = 'Confirm Action';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include '../inc/head.inc.php'; ?></head>
<body>
<?php include '../inc/header.inc.php'; ?>
<?php include '../inc/search.inc.php'; ?>

<div class="breadcrumb-section breadcrumb-bg">
    <div class="container">
        <div class="row"><div class="col-lg-8 offset-lg-2 text-center">
            <div class="breadcrumb-text"><h1>Confirm Delete</h1></div>
        </div></div>
    </div>
</div>

<div id="main-content" class="mt-150 mb-150 section-warm">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <div class="alert alert-warning">
                    You are about to permanently delete <strong><?= $action['label'] ?></strong>. This action cannot be undone.
                </div>
                <p class="mb-4">A verification code was sent to <strong><?= htmlspecialchars($adminEmail) ?></strong>. Enter it to confirm.</p>

                <form action="/admin/process_confirm.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <div class="form-group">
                        <label for="otp_code">Verification Code</label>
                        <input type="text" id="otp_code" name="otp_code" class="form-control"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               placeholder="000000" required autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="btn btn-danger w-100 mt-2">Confirm Delete</button>
                </form>

                <form action="/process_resend_otp.php" method="post" class="mt-3">
                    <input type="hidden" name="purpose" value="admin_confirm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-secondary w-100"
                        <?= $canResend ? '' : 'disabled' ?>>
                        <?= $canResend ? 'Resend Code' : 'Resend available after 60 seconds' ?>
                    </button>
                </form>

                <p class="mt-3 text-center"><a href="<?= $cancelUrl ?>">Cancel</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.inc.php'; ?>
</body>
</html>
