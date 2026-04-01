<?php
require_once '../inc/auth.inc.php';
require_once '../inc/db.inc.php';

require_admin();

$conn = getDbConnection();

// --- Summary stats ---
$totalRevenue = 0;
$r = $conn->query("SELECT COALESCE(SUM(price * quantity), 0) AS rev FROM order_items");
if ($r) { $totalRevenue = $r->fetch_assoc()['rev']; }

$totalOrders = 0;
$r = $conn->query("SELECT COUNT(DISTINCT order_id) AS cnt FROM order_items");
if ($r) { $totalOrders = $r->fetch_assoc()['cnt']; }

$totalMembers = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM members WHERE role = 'user'");
if ($r) { $totalMembers = $r->fetch_assoc()['cnt']; }

$totalPerformances = 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM performances");
if ($r) { $totalPerformances = $r->fetch_assoc()['cnt']; }

// --- Revenue by Genre ---
$genreStats = [];
$r = $conn->query("
    SELECT g.name AS genre,
           COUNT(DISTINCT oi.order_id) AS order_count,
           SUM(oi.quantity) AS tickets_sold,
           SUM(oi.price * oi.quantity) AS revenue
    FROM order_items oi
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN performances p ON tc.performance_id = p.id
    JOIN genres g ON p.genre_id = g.id
    GROUP BY g.id, g.name
    ORDER BY revenue DESC
");
if ($r) {
    while ($row = $r->fetch_assoc()) $genreStats[] = $row;
}

// --- Top Selling Performances ---
$topPerformances = [];
$r = $conn->query("
    SELECT p.name AS performance,
           g.name AS genre,
           SUM(oi.quantity) AS tickets_sold,
           SUM(oi.price * oi.quantity) AS revenue
    FROM order_items oi
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN performances p ON tc.performance_id = p.id
    JOIN genres g ON p.genre_id = g.id
    GROUP BY p.id, p.name, g.name
    ORDER BY tickets_sold DESC
    LIMIT 10
");
if ($r) {
    while ($row = $r->fetch_assoc()) $topPerformances[] = $row;
}

// --- Recent Transactions ---
$recentOrders = [];
$r = $conn->query("
    SELECT oi.order_id,
           m.username,
           m.name AS member_name,
           p.name AS performance,
           tc.name AS category,
           oi.quantity,
           oi.price,
           (oi.quantity * oi.price) AS subtotal,
           oi.order_date
    FROM order_items oi
    JOIN members m ON oi.member_id = m.id
    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
    JOIN performances p ON tc.performance_id = p.id
    ORDER BY oi.order_date DESC
    LIMIT 20
");
if ($r) {
    while ($row = $r->fetch_assoc()) $recentOrders[] = $row;
}

// --- Seat Availability ---
$seatStats = [];
$r = $conn->query("
    SELECT p.name AS performance,
           SUM(tc.total_seats) AS total_seats,
           SUM(tc.available_seats) AS available_seats,
           SUM(tc.total_seats - tc.available_seats) AS sold_seats
    FROM ticket_categories tc
    JOIN performances p ON tc.performance_id = p.id
    GROUP BY p.id, p.name
    ORDER BY sold_seats DESC
");
if ($r) {
    while ($row = $r->fetch_assoc()) $seatStats[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include "../inc/head.inc.php"; ?>
	<style>
		/* ── Analytics Dashboard Styles ── */
		.analytics-hero {
			background: linear-gradient(135deg, #051922 0%, #0d2e42 60%, #051922 100%);
			padding: 150px 0 40px;
			position: relative;
			overflow: hidden;
		}
		.analytics-hero::before {
			content: '';
			position: absolute;
			inset: 0;
			background-image:
				radial-gradient(circle at 15% 50%, rgba(14,159,173,0.10) 0%, transparent 50%),
				radial-gradient(circle at 85% 20%, rgba(14,159,173,0.06) 0%, transparent 40%);
			pointer-events: none;
		}
		.analytics-hero h1 {
			color: #fff;
			font-family: 'Poppins', sans-serif;
			font-size: 2.4rem;
			font-weight: 800;
			letter-spacing: -0.5px;
			margin: 0;
		}
		.analytics-hero h1 span {
			color: #0E9FAD;
		}
		.analytics-hero p {
			color: rgba(255,255,255,0.55);
			margin: 8px 0 0;
			font-size: 0.95rem;
		}

		/* Stat cards */
		.stat-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 20px;
			margin: -30px 0 40px;
		}
		@media (max-width: 991px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
		@media (max-width: 575px)  { .stat-grid { grid-template-columns: 1fr; } }

		.stat-card {
			background: #fff;
			border-radius: 12px;
			padding: 28px 24px;
			box-shadow: 0 4px 24px rgba(5, 25, 34, 0.10);
			border-left: 4px solid #0E9FAD;
			display: flex;
			flex-direction: column;
			gap: 6px;
			transition: transform 0.18s, box-shadow 0.18s;
		}
		.stat-card:hover {
			transform: translateY(-3px);
			box-shadow: 0 8px 32px rgba(5, 25, 34, 0.14);
		}
		.stat-card .stat-label {
			font-size: 0.78rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1.2px;
			color: #7a8f99;
		}
		.stat-card .stat-value {
			font-family: 'Poppins', sans-serif;
			font-size: 2rem;
			font-weight: 800;
			color: #051922;
			line-height: 1.1;
		}
		.stat-card .stat-icon {
			font-size: 1.6rem;
			color: #0E9FAD;
			margin-bottom: 4px;
		}

		/* Section headers */
		.analytics-section {
			margin-bottom: 48px;
		}
		.section-title {
			font-family: 'Poppins', sans-serif;
			font-size: 1.15rem;
			font-weight: 700;
			color: #051922;
			margin-bottom: 16px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.section-title::after {
			content: '';
			flex: 1;
			height: 2px;
			background: linear-gradient(to right, #0E9FAD, transparent);
			border-radius: 2px;
		}

		/* Tables */
		.analytics-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 0.9rem;
			background: #fff;
			border-radius: 10px;
			overflow: hidden;
			box-shadow: 0 2px 16px rgba(5, 25, 34, 0.07);
		}
		.analytics-table thead tr {
			background: #051922;
			color: #0E9FAD;
		}
		.analytics-table thead th {
			padding: 14px 16px;
			font-family: 'Poppins', sans-serif;
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.8px;
			border: none;
		}
		.analytics-table tbody tr {
			border-bottom: 1px solid #f0f3f5;
			transition: background 0.12s;
		}
		.analytics-table tbody tr:last-child { border-bottom: none; }
		.analytics-table tbody tr:hover { background: #fdf9ee; }
		.analytics-table tbody td {
			padding: 12px 16px;
			color: #2c3e50;
			vertical-align: middle;
		}

		/* Progress bar for seat fill */
		.seat-bar {
			height: 8px;
			background: #eef2f5;
			border-radius: 4px;
			overflow: hidden;
			min-width: 80px;
		}
		.seat-bar-fill {
			height: 100%;
			background: linear-gradient(90deg, #0E9FAD, #0B8090);
			border-radius: 4px;
			transition: width 0.6s ease;
		}
		.seat-bar-fill.sold-out { background: linear-gradient(90deg, #D22B2B, #a01f1f); }

		/* Badge */
		.badge-genre {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 20px;
			font-size: 0.72rem;
			font-weight: 700;
			background: rgba(14,159,173,0.12);
			color: #0B5E69;
			border: 1px solid rgba(14,159,173,0.25);
			letter-spacing: 0.4px;
		}

		.order-id {
			font-family: monospace;
			font-size: 0.78rem;
			color: #7a8f99;
			max-width: 140px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.no-data {
			text-align: center;
			color: #aab4bc;
			padding: 32px 16px;
			font-style: italic;
		}

		/* Two-column layout for lower tables */
		.two-col-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 28px;
		}
		@media (max-width: 991px) { .two-col-grid { grid-template-columns: 1fr; } }

		/* ── Chart section ── */
		.chart-card {
			background: #fff;
			border: 1px solid #eee;
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 2px 16px rgba(5,25,34,0.07);
		}
		.chart-card-title {
			font-family: 'Poppins', sans-serif;
			font-size: 0.82rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.8px;
			color: #7a8f99;
			margin-bottom: 18px;
		}
		.chart-wrap { position: relative; }
	</style>
</head>
<body>
	<?php include "../inc/header.inc.php"; ?>
	<?php include "../inc/search.inc.php"; ?>

	<!-- Analytics Hero -->
	<div class="analytics-hero">
		<div class="container">
			<h1>Admin <span>Analytics</span></h1>
			<p>Business performance overview &mdash; <?php echo date('F j, Y'); ?></p>
		</div>
	</div>

	<div class="mt-0 mb-150 section-mid" style="padding-top: 50px; padding-bottom: 60px;">
		<div class="container">

			<!-- Stat Cards -->
			<div class="stat-grid">
				<div class="stat-card">
					<div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
					<div class="stat-label">Total Revenue</div>
					<div class="stat-value">$<?php echo number_format($totalRevenue, 2); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-icon"><i class="fas fa-receipt"></i></div>
					<div class="stat-label">Total Orders</div>
					<div class="stat-value"><?php echo number_format($totalOrders); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-icon"><i class="fas fa-users"></i></div>
					<div class="stat-label">Registered Members</div>
					<div class="stat-value"><?php echo number_format($totalMembers); ?></div>
				</div>
				<div class="stat-card">
					<div class="stat-icon"><i class="fas fa-music"></i></div>
					<div class="stat-label">Performances</div>
					<div class="stat-value"><?php echo number_format($totalPerformances); ?></div>
				</div>
			</div>

			<!-- ── Charts Row ── -->
			<div class="two-col-grid analytics-section">
				<div class="chart-card">
					<div class="chart-card-title"><i class="fas fa-chart-pie" style="color:#0E9FAD;margin-right:6px;"></i>Revenue by Genre</div>
					<div class="chart-wrap" style="height:260px;">
						<canvas id="chartGenre" aria-label="Revenue by genre chart" role="img"></canvas>
					</div>
				</div>
				<div class="chart-card">
					<div class="chart-card-title"><i class="fas fa-chart-bar" style="color:#0E9FAD;margin-right:6px;"></i>Top Performances — Tickets Sold</div>
					<div class="chart-wrap" style="height:260px;">
						<canvas id="chartPerf" aria-label="Top performances by tickets sold chart" role="img"></canvas>
					</div>
				</div>
			</div>

			<!-- Top Selling Performances + Revenue by Genre side-by-side -->
			<div class="two-col-grid analytics-section">
				<!-- Top Performances -->
				<div>
					<div class="section-title"><i class="fas fa-trophy" style="color:#0E9FAD;"></i> Top Selling Performances</div>
					<table class="analytics-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Performance</th>
								<th>Genre</th>
								<th>Sold</th>
								<th>Revenue</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($topPerformances)): ?>
							<tr><td colspan="5" class="no-data">No sales data yet.</td></tr>
							<?php else: foreach ($topPerformances as $i => $p): ?>
							<tr>
								<td style="font-weight:700; color:#0E9FAD;"><?php echo $i + 1; ?></td>
								<td><?php echo htmlspecialchars($p['performance']); ?></td>
								<td><span class="badge-genre"><?php echo htmlspecialchars($p['genre']); ?></span></td>
								<td style="font-weight:600;"><?php echo number_format($p['tickets_sold']); ?></td>
								<td style="font-weight:700; color:#051922;">$<?php echo number_format($p['revenue'], 2); ?></td>
							</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Revenue by Genre -->
				<div>
					<div class="section-title"><i class="fas fa-chart-pie" style="color:#0E9FAD;"></i> Revenue by Genre</div>
					<table class="analytics-table">
						<thead>
							<tr>
								<th>Genre</th>
								<th>Orders</th>
								<th>Tickets</th>
								<th>Revenue</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($genreStats)): ?>
							<tr><td colspan="4" class="no-data">No sales data yet.</td></tr>
							<?php else: foreach ($genreStats as $g): ?>
							<tr>
								<td><span class="badge-genre"><?php echo htmlspecialchars($g['genre']); ?></span></td>
								<td><?php echo number_format($g['order_count']); ?></td>
								<td><?php echo number_format($g['tickets_sold']); ?></td>
								<td style="font-weight:700; color:#051922;">$<?php echo number_format($g['revenue'], 2); ?></td>
							</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Seat Availability -->
			<div class="analytics-section">
				<div class="section-title"><i class="fas fa-chair" style="color:#0E9FAD;"></i> Seat Availability</div>
				<table class="analytics-table">
					<thead>
						<tr>
							<th>Performance</th>
							<th>Total Seats</th>
							<th>Sold</th>
							<th>Available</th>
							<th>Fill Rate</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($seatStats)): ?>
						<tr><td colspan="5" class="no-data">No performances found.</td></tr>
						<?php else: foreach ($seatStats as $s):
							$fillPct = $s['total_seats'] > 0 ? round(($s['sold_seats'] / $s['total_seats']) * 100) : 0;
							$soldOut = $s['available_seats'] == 0;
						?>
						<tr>
							<td style="font-weight:600;"><?php echo htmlspecialchars($s['performance']); ?></td>
							<td><?php echo number_format($s['total_seats']); ?></td>
							<td style="font-weight:600; color:<?php echo $soldOut ? '#D22B2B' : '#051922'; ?>;">
								<?php echo number_format($s['sold_seats']); ?>
							</td>
							<td><?php echo number_format($s['available_seats']); ?></td>
							<td>
								<div style="display:flex; align-items:center; gap:10px;">
									<div class="seat-bar" style="flex:1;">
										<div class="seat-bar-fill <?php echo $soldOut ? 'sold-out' : ''; ?>"
											style="width:<?php echo $fillPct; ?>%"></div>
									</div>
									<span style="font-size:0.78rem; font-weight:700; width:36px; text-align:right; color:<?php echo $soldOut ? '#D22B2B' : '#7a8f99'; ?>;">
										<?php echo $fillPct; ?>%
									</span>
								</div>
							</td>
						</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Recent Transactions -->
			<div class="analytics-section">
				<div class="section-title"><i class="fas fa-clock" style="color:#0E9FAD;"></i> Recent Transactions</div>
				<div style="overflow-x:auto;">
					<table class="analytics-table">
						<thead>
							<tr>
								<th>Order ID</th>
								<th>Member</th>
								<th>Performance</th>
								<th>Category</th>
								<th>Qty</th>
								<th>Unit Price</th>
								<th>Subtotal</th>
								<th>Date</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($recentOrders)): ?>
							<tr><td colspan="8" class="no-data">No transactions yet.</td></tr>
							<?php else: foreach ($recentOrders as $o): ?>
							<tr>
								<td><span class="order-id" title="<?php echo htmlspecialchars($o['order_id']); ?>"><?php echo htmlspecialchars($o['order_id']); ?></span></td>
								<td>
									<div style="font-weight:600;"><?php echo htmlspecialchars($o['member_name']); ?></div>
									<div style="font-size:0.78rem; color:#7a8f99;">@<?php echo htmlspecialchars($o['username']); ?></div>
								</td>
								<td><?php echo htmlspecialchars($o['performance']); ?></td>
								<td><span class="badge-genre"><?php echo htmlspecialchars($o['category']); ?></span></td>
								<td style="text-align:center; font-weight:600;"><?php echo $o['quantity']; ?></td>
								<td>$<?php echo number_format($o['price'], 2); ?></td>
								<td style="font-weight:700; color:#051922;">$<?php echo number_format($o['subtotal'], 2); ?></td>
								<td style="font-size:0.82rem; color:#7a8f99; white-space:nowrap;">
									<?php echo htmlspecialchars(date('d M Y H:i', strtotime($o['order_date']))); ?>
								</td>
							</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>

	<?php include "../inc/footer.inc.php"; ?>

	<!-- Chart.js (https://github.com/chartjs/Chart.js) -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
	<script>
	(function () {
		// ── Data from PHP ────────────────────────────────────────────────────
		var genreLabels  = <?= json_encode(array_column($genreStats,       'genre'))       ?>;
		var genreRevenue = <?= json_encode(array_column($genreStats,       'revenue'))     ?>;
		var perfLabels   = <?= json_encode(array_column(array_slice($topPerformances, 0, 6), 'performance')) ?>;
		var perfSold     = <?= json_encode(array_column(array_slice($topPerformances, 0, 6), 'tickets_sold')) ?>;

		// ── Palette: earthy, warm — matches Statik cream aesthetic ──────────
		var palette = ['#0E9FAD','#B07D4F','#6B8C6A','#C17068','#4A7A8D','#8C6B9A'];

		// ── Revenue by Genre — donut ─────────────────────────────────────────
		var ctxGenre = document.getElementById('chartGenre');
		if (ctxGenre) {
			new Chart(ctxGenre, {
				type: 'doughnut',
				data: {
					labels: genreLabels.length ? genreLabels : ['No data'],
					datasets: [{
						data:            genreRevenue.length ? genreRevenue : [1],
						backgroundColor: palette.slice(0, Math.max(genreLabels.length, 1)),
						borderWidth:     0,
						hoverOffset:     6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutout: '60%',
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								font:        { family: 'Poppins', size: 11 },
								color:       '#051922',
								padding:     14,
								boxWidth:    12,
								borderRadius: 3
							}
						},
						tooltip: {
							callbacks: {
								label: function(ctx) {
									return ' SGD ' + parseFloat(ctx.parsed).toLocaleString('en-SG', {minimumFractionDigits:2, maximumFractionDigits:2});
								}
							}
						}
					}
				}
			});
		}

		// ── Top Performances — horizontal bar ───────────────────────────────
		var ctxPerf = document.getElementById('chartPerf');
		if (ctxPerf) {
			new Chart(ctxPerf, {
				type: 'bar',
				data: {
					labels: perfLabels.length ? perfLabels : ['No data'],
					datasets: [{
						label:           'Tickets sold',
						data:            perfSold.length ? perfSold : [0],
						backgroundColor: 'rgba(14,159,173,0.18)',
						borderColor:     '#0E9FAD',
						borderWidth:     2,
						borderRadius:    6
					}]
				},
				options: {
					indexAxis: 'y',
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function(ctx) { return '  ' + ctx.parsed.x + ' tickets'; }
							}
						}
					},
					scales: {
						x: {
							ticks:  { font: { family: 'Poppins', size: 11 }, color: '#7a8f99' },
							grid:   { color: 'rgba(0,0,0,0.05)' },
							border: { display: false }
						},
						y: {
							ticks: {
								font:    { family: 'Poppins', size: 11 },
								color:   '#051922',
								maxRotation: 0,
								callback: function(val, idx) {
									var lbl = this.getLabelForValue(val);
									return lbl.length > 22 ? lbl.slice(0, 22) + '…' : lbl;
								}
							},
							grid: { display: false }
						}
					}
				}
			});
		}
	})();
	</script>
</body>
</html>
