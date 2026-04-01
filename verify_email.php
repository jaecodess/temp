<?php
require_once 'inc/auth.inc.php';
require_once 'inc/otp.inc.php';

require_login();

$email     = $_SESSION['email'] ?? '';
$error     = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
$success   = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$canResend = otp_can_resend($email, 'verify_email');
$pageTitle = 'Verify Email';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include 'inc/head.inc.php'; ?></head>
<body>
<?php include 'inc/header.inc.php'; ?>
<?php include 'inc/search.inc.php'; ?>

<div class="breadcrumb-section breadcrumb-bg">
    <div class="container">
        <div class="row"><div class="col-lg-8 offset-lg-2 text-center">
            <div class="breadcrumb-text"><h1>Verify Your Email</h1></div>
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
                <p class="mb-4">A 6-digit verification code was sent to <strong><?= htmlspecialchars($email) ?></strong>. Enter it below to verify your account.</p>

                <form action="/process_verify_email.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <div class="form-group">
                        <label for="otp_code">Verification Code</label>
                        <input type="text" id="otp_code" name="otp_code" class="form-control"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               placeholder="000000" required autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="boxed-btn mt-2 w-100">Verify Email</button>
                </form>

                <form action="/process_resend_otp.php" method="post" class="mt-3">
                    <input type="hidden" name="purpose" value="verify_email">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-secondary w-100"
                        <?= $canResend ? '' : 'disabled' ?>>
                        <?= $canResend ? 'Resend Code' : 'Resend available after 60 seconds' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.inc.php'; ?>
</body>
</html>
