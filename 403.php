<?php require_once 'inc/auth.inc.php'; ?>
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
						<h1>403 - Unauthorised</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- error section -->
	<div class="full-height-section error-section">
		<div class="full-height-tablecell">
			<div class="container">
				<div class="row">
					<div class="col-lg-8 offset-lg-2 text-center">
						<div class="error-text">
							<i class="far fa-sad-cry"></i>
							<h1>Oops!</h1>
							<p>YOU ARE NOT AUTHORISED TO BE HERE!</p>
							<a href="/" class="boxed-btn">Back to Home</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end error section -->

	<?php include "inc/footer.inc.php"; ?>
</body>
</html>
