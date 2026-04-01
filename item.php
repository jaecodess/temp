<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

if (!isset($_GET['id'])) {
    header("Location: /shop.php");
    exit;
}

$id   = intval($_GET['id']);
$conn = getDbConnection();

$stmt = $conn->prepare("SELECT performances.*, genres.name AS genre_name FROM performances LEFT JOIN genres ON performances.genre_id = genres.id WHERE performances.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: /shop.php");
    exit;
}

$item = $result->fetch_assoc();
$pageTitle = htmlspecialchars($item['name']);
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM ticket_categories WHERE performance_id = ? ORDER BY name");
$stmt->bind_param("i", $id);
$stmt->execute();
$tcResult        = $stmt->get_result();
$ticketCategories = [];
while ($row = $tcResult->fetch_assoc()) {
    $ticketCategories[] = $row;
}
$stmt->close();
$conn->close();

$errorMsg = isset($_GET['error']) ? $_GET['error'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include "inc/head.inc.php"; ?>
</head>

<body>
	<?php include "inc/header.inc.php"; ?>
	<?php include "inc/search.inc.php"; ?>

	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p class="breadcrumb-label">Performance Details</p>
						<h1><?php echo htmlspecialchars($item['name']); ?></h1>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="single-product mt-150 mb-150">
		<div class="container">
			<div class="row">
				<div class="col-md-5">
					<div class="single-product-img">
						<img src="/uploads/performances/<?php echo $item['id']; ?>/<?php echo htmlspecialchars($item['img_name']); ?>"
							alt="<?php echo htmlspecialchars($item['name']); ?>">
					</div>
				</div>
				<div class="col-md-7">
					<div class="single-product-content">
						<h3><?php echo htmlspecialchars($item['name']); ?></h3>
						<p><?php echo htmlspecialchars($item['description']); ?></p>
						<p><strong>Venue:</strong> <?php echo htmlspecialchars($item['venue']); ?></p>
						<p><strong>Date:</strong> <?php echo date('D, d M Y', strtotime($item['event_date'])); ?></p>
						<p><strong>Time:</strong> <?php echo date('g:i A', strtotime($item['event_time'])); ?></p>
						<p><strong>Genre:</strong> <?php echo htmlspecialchars($item['genre_name'] ?? ''); ?></p>

						<div class="single-product-form mt-3">
							<?php if (!empty($errorMsg)): ?>
								<p class="text-danger"><?php echo htmlspecialchars($errorMsg); ?></p>
							<?php endif; ?>

							<?php
							$tcByName = [];
							foreach ($ticketCategories as $tc) { $tcByName[$tc['name']] = $tc; }
							$c1 = $tcByName['Cat 1'] ?? null;
							$c2 = $tcByName['Cat 2'] ?? null;
							$c3 = $tcByName['Cat 3'] ?? null;
							$so1 = $c1 && $c1['available_seats'] <= 0;
							$so2 = $c2 && $c2['available_seats'] <= 0;
							$so3 = $c3 && $c3['available_seats'] <= 0;
							?>

							<!-- Seat map -->
							<div class="smap-wrap">
								<p class="smap-title"><i class="fas fa-map-marker-alt"></i> Choose Your Section</p>
								<svg class="smap-svg" viewBox="0 0 560 300" xmlns="http://www.w3.org/2000/svg">

									<!-- Stage -->
									<rect x="190" y="10" width="180" height="36" rx="4" fill="#051922"/>
									<text x="280" y="33" text-anchor="middle" fill="#0E9FAD" font-size="13" font-family="Poppins,sans-serif" font-weight="700" letter-spacing="3">STAGE</text>

									<!-- ROW A — Cat 1 (6 sections) -->
									<!-- A1 -->
									<polygon class="smap-zone smap-cat1<?php echo $so1?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c1?$c1['id']:''; ?>"
										data-max="<?php echo $c1?$c1['available_seats']:0; ?>"
										data-cat="zone--cat1"
										data-label="Cat 1 — Premium"
										data-price="<?php echo $c1?number_format($c1['price'],2):''; ?>"
										<?php echo $so1?'aria-disabled="true"':''; ?>
										points="155,58 215,58 210,98 148,98"/>
									<text x="182" y="83" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">A1</text>

									<!-- A2 -->
									<polygon class="smap-zone smap-cat1<?php echo $so1?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c1?$c1['id']:''; ?>"
										data-max="<?php echo $c1?$c1['available_seats']:0; ?>"
										data-cat="zone--cat1"
										data-label="Cat 1 — Premium"
										data-price="<?php echo $c1?number_format($c1['price'],2):''; ?>"
										<?php echo $so1?'aria-disabled="true"':''; ?>
										points="220,58 280,58 278,98 216,98"/>
									<text x="248" y="83" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">A2</text>

									<!-- A3 -->
									<polygon class="smap-zone smap-cat1<?php echo $so1?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c1?$c1['id']:''; ?>"
										data-max="<?php echo $c1?$c1['available_seats']:0; ?>"
										data-cat="zone--cat1"
										data-label="Cat 1 — Premium"
										data-price="<?php echo $c1?number_format($c1['price'],2):''; ?>"
										<?php echo $so1?'aria-disabled="true"':''; ?>
										points="284,58 344,58 346,98 282,98"/>
									<text x="314" y="83" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">A3</text>

									<!-- A4 -->
									<polygon class="smap-zone smap-cat1<?php echo $so1?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c1?$c1['id']:''; ?>"
										data-max="<?php echo $c1?$c1['available_seats']:0; ?>"
										data-cat="zone--cat1"
										data-label="Cat 1 — Premium"
										data-price="<?php echo $c1?number_format($c1['price'],2):''; ?>"
										<?php echo $so1?'aria-disabled="true"':''; ?>
										points="350,58 410,58 414,98 350,98"/>
									<text x="382" y="83" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">A4</text>

									<!-- ROW B — Cat 2 (6 sections) -->
									<!-- B1 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="100,110 162,110 156,152 92,152"/>
									<text x="128" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B1</text>

									<!-- B2 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="166,110 228,110 226,152 162,152"/>
									<text x="196" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B2</text>

									<!-- B3 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="232,110 294,110 294,152 232,152"/>
									<text x="263" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B3</text>

									<!-- B4 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="298,110 360,110 360,152 298,152"/>
									<text x="329" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B4</text>

									<!-- B5 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="364,110 426,110 432,152 368,152"/>
									<text x="397" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B5</text>

									<!-- B6 -->
									<polygon class="smap-zone smap-cat2<?php echo $so2?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c2?$c2['id']:''; ?>"
										data-max="<?php echo $c2?$c2['available_seats']:0; ?>"
										data-cat="zone--cat2"
										data-label="Cat 2 — Standard"
										data-price="<?php echo $c2?number_format($c2['price'],2):''; ?>"
										<?php echo $so2?'aria-disabled="true"':''; ?>
										points="436,110 498,110 506,152 444,152"/>
									<text x="471" y="136" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">B6</text>

									<!-- ROW C — Cat 3 (7 sections) -->
									<!-- C1 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="40,164 102,164 96,208 28,208"/>
									<text x="67" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C1</text>

									<!-- C2 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="106,164 168,164 166,208 102,208"/>
									<text x="136" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C2</text>

									<!-- C3 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="172,164 234,164 234,208 172,208"/>
									<text x="203" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C3</text>

									<!-- C4 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="238,164 300,164 300,208 238,208"/>
									<text x="269" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C4</text>

									<!-- C5 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="304,164 366,164 366,208 304,208"/>
									<text x="335" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C5</text>

									<!-- C6 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="370,164 432,164 438,208 374,208"/>
									<text x="404" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C6</text>

									<!-- C7 -->
									<polygon class="smap-zone smap-cat3<?php echo $so3?' smap-soldout':''; ?>"
										data-tc-id="<?php echo $c3?$c3['id']:''; ?>"
										data-max="<?php echo $c3?$c3['available_seats']:0; ?>"
										data-cat="zone--cat3"
										data-label="Cat 3 — Economy"
										data-price="<?php echo $c3?number_format($c3['price'],2):''; ?>"
										<?php echo $so3?'aria-disabled="true"':''; ?>
										points="442,164 504,164 512,208 450,208"/>
									<text x="477" y="191" text-anchor="middle" fill="#051922" font-size="10" font-family="Poppins,sans-serif" font-weight="700" pointer-events="none">C7</text>

								</svg>

								<!-- Tooltip -->
								<div class="smap-tooltip" id="smap-tooltip"></div>

								<!-- Legend -->
								<div class="smap-legend">
									<?php foreach ([['Cat 1','smap-cat1'],['Cat 2','smap-cat2'],['Cat 3','smap-cat3']] as $l): ?>
									<span class="smap-legend-item">
										<span class="smap-legend-swatch <?php echo $l[1]; ?>"></span>
										<?php
										$key = $l[0]; $t = $tcByName[$key] ?? null;
										echo htmlspecialchars($key) . ($t ? ' — $' . number_format($t['price'],2) : '');
										?>
									</span>
									<?php endforeach; ?>
									<span class="smap-legend-item"><span class="smap-legend-swatch smap-soldout-swatch"></span>Sold Out</span>
								</div>
							</div>

							<form class="needs-validation" novalidate
								action="/add_to_cart.php" method="post">
								<input type="hidden" name="seat_number" id="seat_number" value="">
								<div class="mb-2">
									<span id="seat-badge" style="display:none;background:#0E9FAD;color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;padding:5px 14px;border-radius:999px;"></span>
								</div>
								<div class="mb-3">
									<label for="ticket_category" class="form-label">Selected Category</label>
									<select id="ticket_category" name="ticket_category_id" class="form-select" required>
										<option value="" disabled selected>Click a section above to select</option>
										<?php foreach ($ticketCategories as $tc): ?>
											<option value="<?php echo $tc['id']; ?>"
												<?php if ($tc['available_seats'] <= 0) echo 'disabled'; ?>>
												<?php echo htmlspecialchars($tc['name']); ?> — $<?php echo number_format($tc['price'], 2); ?>
												<?php if ($tc['available_seats'] <= 0) echo ' (Sold Out)'; ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="mb-3">
									<label for="quantity" class="form-label">Quantity</label>
									<input id="quantity" type="number" name="quantity" class="form-control" value="1" min="1" required>
								</div>
								<button type="submit" class="cart-btn">
									<i class="fas fa-shopping-cart" aria-hidden="true"></i> Add to Cart
								</button>
							</form>

							<script>
							(function () {
								var zones     = document.querySelectorAll('.smap-zone:not(.smap-soldout)');
								var select    = document.getElementById('ticket_category');
								var qtyInput  = document.getElementById('quantity');
								var seatInput = document.getElementById('seat_number');
								var seatBadge = document.getElementById('seat-badge');
								var tooltip   = document.getElementById('smap-tooltip');
								var rows = ['A','B','C','D','E','F','G','H'];

								function randomSeat(catCls) {
									var rowPool = catCls === 'zone--cat1' ? rows.slice(0,3)
												: catCls === 'zone--cat2' ? rows.slice(3,6)
												: rows.slice(6);
									var row = rowPool[Math.floor(Math.random() * rowPool.length)];
									var num = Math.floor(Math.random() * 30) + 1;
									return row + num;
								}

								zones.forEach(function (zone) {
									zone.addEventListener('mouseenter', function (e) {
										tooltip.innerHTML = '<strong>' + zone.dataset.label + '</strong><br>$' + zone.dataset.price + ' &bull; ' + zone.dataset.max + ' seats left';
										tooltip.style.display = 'block';
									});
									zone.addEventListener('mousemove', function (e) {
										var rect = zone.closest('.smap-wrap').getBoundingClientRect();
										tooltip.style.left = (e.clientX - rect.left + 12) + 'px';
										tooltip.style.top  = (e.clientY - rect.top  - 10) + 'px';
									});
									zone.addEventListener('mouseleave', function () {
										tooltip.style.display = 'none';
									});
									zone.addEventListener('click', function () {
										zones.forEach(function (z) { z.classList.remove('smap-selected'); });
										zone.classList.add('smap-selected');
										select.value    = zone.dataset.tcId;
										qtyInput.max    = zone.dataset.max;
										var seat        = randomSeat(zone.dataset.cat);
										seatInput.value = seat;
										seatBadge.textContent = 'Assigned seat: ' + seat;
										seatBadge.style.display = 'inline-block';
									});
								});
							})();
							</script>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php include "inc/footer.inc.php"; ?>
</body>
</html>
