<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

// to get landscape images
function getLandscapeImageName(string $imgName): string
{
	$info = pathinfo($imgName);
	$filename = $info['filename'] ?? $imgName;
	$ext = $info['extension'] ?? '';
	return $ext !== '' ? ($filename . '_l.' . $ext) : ($filename . '_l');
}

$allEvents = [];
try {
	$conn = getDbConnection();
	$stmt = $conn->prepare(
		"SELECT performances.*, MIN(tc.price) AS min_price
		 FROM performances
		 LEFT JOIN ticket_categories tc ON tc.performance_id = performances.id
		 GROUP BY performances.id
		 ORDER BY performances.event_date ASC, performances.event_time ASC, performances.id ASC"
	);
	$stmt->execute();
	$result = $stmt->get_result();
	while ($row = $result->fetch_assoc()) {
		$allEvents[] = $row;
	}
	$stmt->close();
	$conn->close();
} catch (Throwable $e) {
	$allEvents = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php include "inc/head.inc.php"; ?>
</head>

<body>
	<?php include "inc/header.inc.php"; ?>
	<?php include "inc/search.inc.php"; ?>

	<!-- hero area (all events) -->
	<div id="main-content" class="hero-bg">
		<?php if (empty($allEvents)): ?>
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center pt-80 pb-80">
					<p>No events available right now.</p>
					<a href="/shop.php" class="boxed-btn mt-3">Browse Events</a>
				</div>
			</div>
		<?php else: ?>
			<div class="all-events-banner owl-carousel">
				<?php foreach ($allEvents as $event): ?>
					<?php
					$posterImg = (string)($event['img_name'] ?? '');
					$landscapeCandidate = getLandscapeImageName($posterImg);
					$landscapePath = __DIR__ . '/uploads/performances/' . $event['id'] . '/' . $landscapeCandidate;
					$bannerImg = ($posterImg !== '' && file_exists($landscapePath)) ? $landscapeCandidate : $posterImg;
					?>
					<a class="all-events-slide" href="/item.php?id=<?php echo $event['id']; ?>">
						<div class="all-events-slide__bg"
							style="background-image: url('/uploads/performances/<?php echo $event['id']; ?>/<?php echo htmlspecialchars($bannerImg); ?>');">
						</div>
						<span class="all-events-slide__overlay">
							<span class="all-events-slide__title"><?php echo htmlspecialchars($event['name']); ?></span>
							<span class="all-events-slide__meta">
								<?php echo date('D, d M Y', strtotime($event['event_date'])); ?>
								<?php if (!empty($event['event_time'])): ?>
									— <?php echo date('g:i A', strtotime($event['event_time'])); ?>
								<?php endif; ?>
							</span>
							<span class="all-events-slide__getTixBtn">Get Tickets →</span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
	<!-- end hero area -->

	<!-- features list section -->
	<div class="list-section pt-80 pb-80">
		<div class="container">

			<div class="row">
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-ticket-alt" aria-hidden="true"></i>
						</div>
						<div class="content">
							<h3>Instant e-Tickets</h3>
							<p>Your tickets, ready right after checkout</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
					<div class="list-box d-flex align-items-center">
						<div class="list-icon">
							<i class="fas fa-lock" aria-hidden="true"></i>
						</div>
						<div class="content">
							<h3>Secure checkout</h3>
							<p>Simple and secure ticket purchases</p>
						</div>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="list-box d-flex justify-content-start align-items-center">
						<div class="list-icon">
							<i class="fas fa-headset" aria-hidden="true"></i>
						</div>
						<div class="content">
							<h3>Live support</h3>
							<p>We're here if anything goes wrong</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	<!-- end features list section -->

	<!-- upcoming events section -->
	<div class="feature-section pt-80 pb-80">
		<div class="container">

			<div class="row">
				<div class="col-lg-12">
					<div class="section-title text-center mb-50">
						<h2>Upcoming Events</h2>
					</div>
				</div>
			</div>

			<?php
			$allEvents = [];
			try {
				$conn = getDbConnection();
				$stmt = $conn->prepare(
					"SELECT performances.*, MIN(tc.price) AS min_price
                 FROM performances
                 LEFT JOIN ticket_categories tc ON tc.performance_id = performances.id
                 WHERE performances.event_date >= CURDATE()
                 GROUP BY performances.id
                 ORDER BY performances.event_date ASC, performances.event_time ASC"
				);
				$stmt->execute();
				$result = $stmt->get_result();
				while ($row = $result->fetch_assoc()) {
					$allEvents[] = $row;
				}
				$stmt->close();
				$conn->close();
			} catch (Throwable $e) {
				$allEvents = [];
			}
			?>

			<?php if (empty($allEvents)): ?>
				<div class="row">
					<div class="col-12 text-center">
						<p>No upcoming events at the moment. Check back soon!</p>
					</div>
				</div>
			<?php else: ?>
				<div class="row">
					<?php foreach ($allEvents as $event): ?>
						<?php
						$posterImg = (string)($event['img_name'] ?? '');
						$imgSrc = ($posterImg !== '')
							? '/uploads/performances/' . $event['id'] . '/' . htmlspecialchars($posterImg)
							: '/img/placeholder.jpg';
						?>
						<div class="col-lg-4 col-md-6 mb-4">
							<div class="single-event-card">
								<div class="event-card-img">
									<a href="/item.php?id=<?php echo $event['id']; ?>">
										<img src="<?php echo $imgSrc; ?>"
											alt="<?php echo htmlspecialchars($event['name']); ?>">
									</a>
								</div>
								<div class="event-card-body">
									<div class="event-card-date">
										<i class="far fa-calendar-alt" aria-hidden="true"></i>
										<?php echo date('D, d M Y', strtotime($event['event_date'])); ?>
										<?php if (!empty($event['event_time'])): ?>
											&nbsp;—&nbsp;
											<i class="far fa-clock" aria-hidden="true"></i>
											<?php echo date('g:i A', strtotime($event['event_time'])); ?>
										<?php endif; ?>
									</div>
									<h3 class="event-card-title">
										<a href="/item.php?id=<?php echo $event['id']; ?>">
											<?php echo htmlspecialchars($event['name']); ?>
										</a>
									</h3>
									<?php if (!empty($event['venue'])): ?>
										<p class="event-card-venue">
											<i class="fas fa-map-marker-alt" aria-hidden="true"></i>
											<?php echo htmlspecialchars($event['venue']); ?>
										</p>
									<?php endif; ?>
									<a href="/item.php?id=<?php echo $event['id']; ?>"
										class="boxed-btn mt-3">Get Tickets</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
	<!-- end upcoming events section -->

	<!-- advertisement section -->
	<div class="abt-section mb-150">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-6 col-md-12">
					<div class="embed-responsive embed-responsive-16by9">
					<iframe class="embed-responsive-item"
						src="https://www.youtube.com/embed/8YiR9v3sOpk"
						title="YouTube video player" frameborder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						allowfullscreen></iframe>
				</div>
				</div>
				<div class="col-lg-6 col-md-12">
					<div class="abt-text">
						<p class="top-sub">Made for quick booking</p>
						<h2>
							We are <span class="orange-text">Statik</span>
						</h2>
						<p>Statik is your place to discover performances and book tickets
							with clear categories, pricing, and availability.</p>
						<p>Browse events, pick your seats, and checkout in minutes.</p>
						<a href="/about.php" class="boxed-btn mt-4">Learn more</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end advertisement section -->

	<?php include "inc/footer.inc.php"; ?>
</body>

</html>