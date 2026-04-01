<?php
require_once 'inc/auth.inc.php';
require_once 'inc/db.inc.php';

$conn = getDbConnection();
$stmt = $conn->prepare("SELECT performances.*, genres.name AS genre_name, MIN(tc.price) AS min_price FROM performances LEFT JOIN genres ON performances.genre_id = genres.id LEFT JOIN ticket_categories tc ON tc.performance_id = performances.id GROUP BY performances.id ORDER BY performances.id");
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
// Fetch genres for filter dropdown
$genreStmt = $conn->query("SELECT id, name FROM genres ORDER BY name");
$genres = $genreStmt->fetch_all(MYSQLI_ASSOC);
$conn->close();
$pageTitle = 'Events';
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
						<h1>Browse Events</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- end breadcrumb section -->

	<!-- products -->
	<div id="main-content" class="product-section mt-150 mb-150 section-warm">
		<div class="container">
			<div class="filter-bar mb-4" id="filter-bar">
			    <select id="genre-filter" class="form-control d-inline-block w-auto mr-2" aria-label="Filter by genre">
			        <option value="">All Genres</option>
			        <?php foreach ($genres as $genre): ?>
			        <option value="<?= htmlspecialchars($genre['name']) ?>">
			            <?= htmlspecialchars($genre['name']) ?>
			        </option>
			        <?php endforeach; ?>
			    </select>

			    <select id="sort-filter" class="form-control d-inline-block w-auto mr-2" aria-label="Sort events">
			        <option value="date">Date</option>
			        <option value="price-asc">Price: Low to High</option>
			        <option value="price-desc">Price: High to Low</option>
			        <option value="name">Name A–Z</option>
			    </select>

			    <button id="clear-filters" type="button" class="btn btn-outline-secondary btn-sm">Clear</button>
			</div>

			<div id="no-results" class="col-12 text-center py-5" style="display:none;">
			    <p class="text-muted">No events match your filters.</p>
			</div>

			<div class="row product-lists" id="events-grid">
				<?php foreach ($items as $item): ?>
					<div class="col-lg-4 col-md-6 text-center strawberry event-card-wrap"
					     data-genre="<?= htmlspecialchars($item['genre_name'] ?? '') ?>"
					     data-price="<?= $item['min_price'] ?? 0 ?>"
					     data-date="<?= $item['event_date'] ?>"
					     data-name="<?= htmlspecialchars($item['name']) ?>">
						<div class="single-product-item">
							<div class="product-image">
								<a href="/item.php?id=<?php echo $item['id']; ?>"><img
									src="/uploads/performances/<?php echo $item['id']; ?>/<?php echo htmlspecialchars($item['img_name']); ?>"
									alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy"></a>
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
	<!-- end products -->

	<?php include "inc/footer.inc.php"; ?>

<script>
$(document).ready(function () {
    var $grid   = $('#events-grid');
    var genreEl = document.getElementById('genre-filter');
    var sortEl  = document.getElementById('sort-filter');
    var clearEl = document.getElementById('clear-filters');
    var noRes   = document.getElementById('no-results');

    $grid.isotope({
        layoutMode:      'fitRows',
        percentPosition: true,
        itemSelector:    '.event-card-wrap',
        getSortData: {
            price: function (el) { return parseFloat($(el).data('price')) || 0; },
            name:  function (el) { return $(el).data('name') || ''; },
            date:  function (el) { return $(el).data('date') || ''; }
        }
    });

    $grid.on('arrangeComplete', function (event, filteredItems) {
        noRes.style.display = filteredItems.length === 0 ? '' : 'none';
    });

    function applyFilters() {
        var genre = genreEl.value;
        var sort  = sortEl.value;

        var filterFn = genre === '' ? '*' : function () {
            return $(this).data('genre') === genre;
        };

        var sortBy = 'date', sortAscending = true;
        if (sort === 'price-asc')  { sortBy = 'price'; sortAscending = true; }
        if (sort === 'price-desc') { sortBy = 'price'; sortAscending = false; }
        if (sort === 'name')       { sortBy = 'name';  sortAscending = true; }

        $grid.isotope({ filter: filterFn, sortBy: sortBy, sortAscending: sortAscending });
    }

    genreEl.addEventListener('change', applyFilters);
    sortEl.addEventListener('change', applyFilters);
    clearEl.addEventListener('click', function () {
        genreEl.value = '';
        sortEl.value  = 'date';
        applyFilters();
    });

    // Honour ?genre= URL parameter on page load
    var params = new URLSearchParams(window.location.search);
    if (params.get('genre')) {
        genreEl.value = params.get('genre');
        applyFilters();
    }
});
</script>
</body>
</html>
