<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

$query = isset($_GET['query']) ? sanitize_input($_GET['query']) : "";
$pageTitle = $query !== '' ? 'Search: ' . $query : 'Search';
$items = [];

if (!empty($query)) {
	$conn = getDbConnection();
	$searchTerm = "%" . $query . "%";
	$stmt = $conn->prepare("SELECT performances.*, genres.name AS genre_name, MIN(tc.price) AS min_price FROM performances LEFT JOIN genres ON performances.genre_id = genres.id LEFT JOIN ticket_categories tc ON tc.performance_id = performances.id WHERE performances.name LIKE ? OR performances.description LIKE ? GROUP BY performances.id");
	$stmt->bind_param("ss", $searchTerm, $searchTerm);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		$items[] = $row;
	}
	$stmt->close();
	$conn->close();

	// If exactly one result, redirect to that item
	if (count($items) === 1) {
		header("Location: /item.php?id=" . $items[0]['id']);
		exit;
	}
}

// No results - show no results page
if (empty($items)):
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
							<h1>No Results Found !</h1>
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
								<h1>Oops!</h1>
								<p>No events matched your search.</p>
								<a href="/shop.php" class="boxed-btn">Browse Events</a>
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
<?php else: ?>
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
							<h1>Search Results</h1>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- end breadcrumb section -->

		<!-- events -->
		<div class="product-section mt-150 mb-150 section-warm">
			<div class="container">
				<div class="row product-lists">
					<?php foreach ($items as $item): ?>
						<div class="col-lg-4 col-md-6 text-center strawberry">
							<div class="single-product-item">
								<div class="product-image">
									<a href="/item.php?id=<?php echo $item['id']; ?>"><img
											src="/uploads/performances/<?php echo $item['id']; ?>/<?php echo htmlspecialchars($item['img_name']); ?>"
											alt="<?php echo htmlspecialchars($item['name']); ?>"></a>
								</div>
								<h3><?php echo htmlspecialchars($item['name']); ?></h3>
								<p><?php echo htmlspecialchars($item['description']); ?></p>
								<p class="product-price">
									<span>From $<?php echo number_format($item['min_price'], 2); ?></span>
								</p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<!-- end events -->

		<?php include "inc/footer.inc.php"; ?>
	</body>

	</html>
<?php endif; ?>