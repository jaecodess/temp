<?php
require_once 'inc/auth.inc.php';
require_once 'inc/otp.inc.php';

$email     = trim($_GET['email'] ?? '');
$error     = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
$success   = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$canResend = otp_can_resend($email, 'reset_password');
$pageTitle = 'Reset Password';
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
            <div class="breadcrumb-text"><h1>Reset Password</h1></div>
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
                <p class="mb-4">Enter the 6-digit code sent to <strong><?= htmlspecialchars($email) ?></strong> and choose a new password.</p>

                <form action="/process_reset_password.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <div class="form-group">
                        <label for="otp_code">Verification Code</label>
                        <input type="text" id="otp_code" name="otp_code" class="form-control"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               placeholder="000000" required autocomplete="one-time-code">
                    </div>
                    <div class="form-group mt-3">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="5">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="5">
                    </div>
                    <button type="submit" class="boxed-btn mt-2 w-100">Reset Password</button>
                </form>

                <form action="/process_resend_otp.php" method="post" class="mt-3">
                    <input type="hidden" name="purpose" value="reset_password">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <button type="submit" class="btn btn-outline-secondary w-100"
                        <?= $canResend ? '' : 'disabled' ?>>
                        <?= $canResend ? 'Resend Code' : 'Resend available after 60 seconds' ?>
                    </button>
                </form>
                <p class="mt-3 text-center"><a href="/login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.inc.php'; ?>
</body>
</html>
