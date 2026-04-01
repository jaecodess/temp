<?php
require_once 'inc/auth.inc.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: /");
    exit;
}

$error   = isset($_GET['error'])   ? $_GET['error']   : "";
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
$pageTitle = 'Create Account';
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
						<h1>Register</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- register form -->
	<div class="cart-section mt-150 mb-150 section-warm">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-lg-6 col-md-8">
					<div class="form-card">
						<h3 class="mb-4">Register Account</h3>
						<?php if ($deleted): ?>
							<div class="alert alert-success">Your account has been deleted. You can create a new account below.</div>
						<?php endif; ?>
						<?php if (!empty($error)): ?>
							<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
						<?php endif; ?>
						<form class="needs-validation" novalidate action="/process_register.php" method="post">
							<div class="mb-3">
								<label for="member_name" class="form-label">Full name</label>
								<input id="member_name" type="text" class="form-control" name="name"
									required minlength="5" maxlength="50" placeholder="e.g. Alex Tan" />
								<div class="invalid-feedback">Please enter your full name (at least 5 characters)</div>
							</div>

							<div class="mb-3">
								<label for="member_username" class="form-label">Username</label>
								<input id="member_username" type="text" class="form-control" name="username"
									required minlength="5" maxlength="20" placeholder="e.g. alextan99" />
								<div class="form-text">5–20 characters. Cannot contain @.</div>
								<div class="invalid-feedback">Please choose a username (5–20 characters, no @)</div>
							</div>

							<div class="mb-3">
								<label for="member_password" class="form-label">Password</label>
								<input id="member_password" type="password" class="form-control" name="password"
									required minlength="5" maxlength="200" />
								<div class="form-text">At least 5 characters.</div>
								<div class="invalid-feedback">Please enter a password (at least 5 characters)</div>
							</div>

							<div class="mb-3">
								<label for="member_email" class="form-label">Email address</label>
								<input id="member_email" type="email" class="form-control" name="email"
									required minlength="5" maxlength="50" placeholder="e.g. alex@example.com" />
								<div class="form-text">We'll send your booking confirmations here.</div>
								<div class="invalid-feedback">Please enter a valid email address</div>
							</div>

							<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
							<div class="mt-3">
								<button type="submit" class="btn btn-primary">Register Account</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end register form -->

	<!-- Form Validation -->
	<script>
		'use strict'
		var form = document.querySelector('.needs-validation')
		form.addEventListener('submit', function(event) {
			if (!form.checkValidity()) {
				event.preventDefault()
				event.stopPropagation()
			}
			form.classList.add('was-validated')
		})
	</script>
	<!-- End Form Validation -->

	<?php include "inc/footer.inc.php"; ?>
</body>
</html>
