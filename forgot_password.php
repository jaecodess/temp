<?php
require_once 'inc/auth.inc.php';
$pageTitle = 'Forgot Password';
$error   = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
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
            <div class="breadcrumb-text"><h1>Forgot Password</h1></div>
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
                <p class="mb-4">Enter your account email address and we will send you a verification code to reset your password.</p>
                <form action="/process_forgot_password.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="boxed-btn mt-2 w-100">Send Reset Code</button>
                </form>
                <p class="mt-3 text-center"><a href="/login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.inc.php'; ?>
</body>
</html>
