<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$current = $_SERVER['REQUEST_URI'];
?>
<a href="#main-content" class="skip-to-main">Skip to main content</a>
<?php if (is_logged_in() && isset($_SESSION['email_verified']) && $_SESSION['email_verified'] == 0): ?>
<div class="alert alert-warning alert-dismissible fade show mb-0 rounded-0 text-center" role="alert" style="z-index:9999;position:relative;">
    <strong>Your email address is not verified.</strong>
    <a href="/verify_email.php" class="alert-link ml-2">Verify now</a>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>
<!-- header -->
<div class="top-header-area" id="sticker">
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-sm-12 text-center">
				<div class="main-menu-wrap">
					<!-- logo -->
					<div class="site-logo">
						<a href="/"><img src="/images/statik_logo.png" alt="Statik Logo"></a>
					</div>
					<!-- logo -->

					<!-- menu start -->
					<nav class="main-menu">
						<ul>
							<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
							<li class="admin-nav-item <?= str_starts_with($current, '/admin/') ? 'current-list-item' : '' ?>">
								<a href="/admin/analytics.php"><i class="fas fa-shield-alt" aria-hidden="true"></i> Admin</a>
								<ul class="sub-menu">
									<li><a href="/admin/analytics.php"><i class="fas fa-chart-bar" aria-hidden="true"></i> Analytics</a></li>
									<li><a href="/admin/manage.php"><i class="fas fa-cog" aria-hidden="true"></i> Manage</a></li>
								</ul>
							</li>
							<?php endif; ?>

							<li class="<?= str_starts_with($current, '/shop.php') ? 'current-list-item' : '' ?>">
								<a href="/shop.php">Events</a>
							</li>

							<li class="<?= str_starts_with($current, '/categories') ? 'current-list-item' : '' ?>">
								<a href="/categories.php">Categories</a>
								<ul class="sub-menu">
									<li><a href="/shop.php">All</a></li>
									<li><a href="/shop.php?genre=Concerts">Concerts</a></li>
									<li><a href="/shop.php?genre=Comedy">Comedy</a></li>
									<li><a href="/shop.php?genre=Musical">Musical</a></li>
									<li><a href="/shop.php?genre=Theatre">Theatre</a></li>
									<li><a href="/shop.php?genre=Dance">Dance</a></li>
								</ul>
							</li>

							<li class="<?= str_starts_with($current, '/about.php') ? 'current-list-item' : '' ?>">
								<a href="/about.php">About</a>
							</li>

							<li class="<?= str_starts_with($current, '/help.php') ? 'current-list-item' : '' ?>">
								<a href="/help.php">Help</a>
							</li>

							<li>
								<div class="header-icons">
									<!-- user identity -->
									<?php if (!isset($_SESSION['user_id'])): ?>
										<a href="/login.php" class="nav-user-btn">
											<i class="fas fa-user-circle" aria-hidden="true"></i>
											<span>Login</span>
										</a>
									<?php else: ?>
										<div class="nav-user">
											<div class="nav-user-pill">
												<i class="fas fa-user-circle" aria-hidden="true"></i>
												<span class="nav-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
												<i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
											</div>
											<div class="nav-user-dropdown">
												<a href="/account.php"><i class="fas fa-user-edit" aria-hidden="true"></i> My Account</a>
												<a href="/orders.php"><i class="fas fa-receipt" aria-hidden="true"></i> My Orders</a>
												<a href="/logout.php"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout</a>
											</div>
										</div>
										<a class="shopping-cart" href="/cart.php" aria-label="Shopping cart"><i class="fas fa-shopping-cart" aria-hidden="true"></i></a>
									<?php endif; ?>
									<!-- search -->
									<form class="header-search" action="/search.php" method="get" role="search" aria-label="Search events">
										<input id="header-search-input" type="text" name="query" placeholder="Search events..." aria-label="Search events" list="header-search-suggestions" autocomplete="off">
										<datalist id="header-search-suggestions"></datalist>
										<button type="submit" aria-label="Search">
											<i class="fas fa-search" aria-hidden="true"></i>
										</button>
									</form>
								</div>
							</li>
						</ul>
					</nav>

					<div class="mobile-menu"></div>
					<!-- menu end -->
				</div>
			</div>
		</div>
	</div>
</div>
<!-- end header -->
