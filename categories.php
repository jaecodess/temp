<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';
require_once 'inc/breadcrumb.inc.php';

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, name FROM genres ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
	$categories[] = $row;
}
$stmt->close();
$conn->close();
$pageTitle = 'Categories';
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<?php include "inc/head.inc.php"; ?>
	<link rel="stylesheet" href="/css/categories.css">
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
						<h1>Categories</h1>
						<?php
						renderBreadcrumb([
							['label' => 'Home', 'href' => '/'],
							['label' => 'Categories'],
						]);
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- categories -->
	<div class="product-section mt-150 mb-150">
		<div class="container">
			<?php if (empty($categories)): ?>
				<div class="row">
					<div class="col-lg-8 offset-lg-2 text-center">
						<p>No categories found.</p>
						<a href="/shop.php" class="boxed-btn">Browse Events</a>
					</div>
				</div>
			<?php else: ?>
				<div class="row">
					<?php
					$icons = [
						'Concerts' => 'fas fa-music',
						'Sports'   => 'fas fa-football-ball',
						'Comedy'   => 'fas fa-laugh',
						'Theatre'  => 'fas fa-theater-masks',
					];
					foreach ($categories as $cat):
						$icon = $icons[$cat['name']] ?? 'fas fa-ticket-alt';
					?>
						<div class="col-lg-3 col-md-6 col-sm-6 col-6 mb-4">
							<a class="category-card"
								href="/shop.php?genre=<?php echo rawurlencode($cat['name']); ?>">
								<div class="category-card__icon">
									<i class="<?php echo $icon; ?>"></i>
								</div>
								<div class="category-card__name">
									<?php echo htmlspecialchars($cat['name']); ?>
								</div>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<!-- end categories -->

	<?php include "inc/footer.inc.php"; ?>
</body>

</html>