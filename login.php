<?php
require_once 'inc/auth.inc.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: /");
    exit;
}

$error   = isset($_GET['error'])   ? $_GET['error']   : "";
$success = isset($_GET['success']) ? $_GET['success'] : "";
$logout  = isset($_GET['logout']);
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include "inc/head.inc.php"; ?>
</head>

<body>
	<?php include "inc/header.inc.php"; ?>
	<?php include "inc/search.inc.php"; ?>

	<!-- breadcrumb-section -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p class="breadcrumb-label">Statik</p>
						<h1>Login</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- login form -->
	<div class="cart-section mt-150 mb-150 section-warm">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-5 col-md-8">
					<div class="form-card">
						<h3 class="mb-4">Please Login</h3>
						<?php if (!empty($error)): ?>
							<div class="alert alert-danger">
								<?php echo htmlspecialchars($error); ?>
							</div>
						<?php endif; ?>
						<?php if (!empty($success)): ?>
							<div class="alert alert-success">
								<?php echo htmlspecialchars($success); ?>
							</div>
						<?php endif; ?>
						<?php if ($logout): ?>
							<div class="alert alert-success">
								You have been logged out.
							</div>
						<?php endif; ?>
						<form action="/process_login.php" method="post" class="needs-validation" novalidate>
							<div class="mb-3">
								<label for="username" class="form-label">Username or Email</label>
								<input type="text" class="form-control" id="username" name="username" required />
								<div class="invalid-feedback">Please enter your username or email.</div>
							</div>
							<div class="mb-3">
								<label for="password" class="form-label">Password</label>
								<input type="password" class="form-control" id="password" name="password" required />
								<div class="invalid-feedback">Please enter your password.</div>
							</div>
							<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
							<div class="mt-3">
								<button type="submit" class="btn btn-primary">Log in</button>
							</div>
							<div class="mt-3">
								<a href="/forgot_password.php">Forgot your password?</a>
							</div>
							<div class="mt-3">
								<span>No Account? <a href="/register.php">Register</a> for an account now!</span>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end login form -->

	<?php include "inc/footer.inc.php"; ?>
	<script>
	(function () {
	    'use strict';
	    var form = document.querySelector('.needs-validation');
	    form.addEventListener('submit', function (e) {
	        if (!form.checkValidity()) {
	            e.preventDefault();
	            e.stopPropagation();
	        }
	        form.classList.add('was-validated');
	    }, false);
	})();
	</script>
</body>
</html>
