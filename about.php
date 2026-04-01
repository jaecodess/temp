<?php
require_once 'inc/auth.inc.php';
$pageTitle = 'About';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include "inc/head.inc.php"; ?>
	<style>
		/* ── About Page ── */
		.about-page { background-color: var(--bg-body); }

		/* Mission strip */
		.mission-strip {
			padding: 72px 0 64px;
			border-bottom: 1px solid var(--surface-border);
		}
		.mission-eyebrow {
			font-family: var(--font-heading);
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 2.5px;
			text-transform: uppercase;
			color: var(--color-accent);
			margin-bottom: 18px;
		}
		.mission-headline {
			font-family: var(--font-display);
			font-size: clamp(2rem, 5vw, 3.2rem);
			font-weight: 800;
			color: var(--color-dark);
			line-height: 1.15;
			letter-spacing: -0.5px;
			margin-bottom: 24px;
			max-width: 600px;
		}
		.mission-headline em {
			font-style: normal;
			color: var(--color-accent);
		}
		.mission-body {
			font-family: var(--font-heading);
			font-size: 1rem;
			color: var(--text-muted);
			line-height: 1.75;
			max-width: 480px;
		}
		.mission-cta-row {
			margin-top: 32px;
			display: flex;
			align-items: center;
			gap: 20px;
			flex-wrap: wrap;
		}
		.mission-cta-row a.boxed-btn {
			font-size: 0.85rem;
		}
		.mission-stat-pair {
			display: flex;
			flex-direction: column;
		}
		.mission-stat-num {
			font-family: var(--font-display);
			font-size: 1.6rem;
			font-weight: 800;
			color: var(--color-dark);
			line-height: 1;
		}
		.mission-stat-label {
			font-family: var(--font-heading);
			font-size: 0.72rem;
			color: var(--text-subtle);
			font-weight: 600;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			margin-top: 2px;
		}
		.mission-divider {
			width: 1px;
			height: 36px;
			background: var(--surface-border);
		}
		.mission-right {
			display: flex;
			align-items: stretch;
			justify-content: flex-end;
		}
		.mission-badge-block {
			background: var(--color-dark);
			border-radius: 20px;
			padding: 40px 36px;
			display: flex;
			flex-direction: column;
			gap: 28px;
			max-width: 320px;
			width: 100%;
		}
		.mission-badge-item {
			display: flex;
			align-items: flex-start;
			gap: 16px;
		}
		.mission-badge-icon {
			width: 40px;
			height: 40px;
			border-radius: 10px;
			background: rgba(14,159,173,0.15);
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--color-accent);
			font-size: 16px;
			flex-shrink: 0;
		}
		.mission-badge-text h4 {
			font-family: var(--font-heading);
			font-size: 0.88rem;
			font-weight: 700;
			color: #fff;
			margin: 0 0 4px;
		}
		.mission-badge-text p {
			font-family: var(--font-heading);
			font-size: 0.78rem;
			color: rgba(255,255,255,0.45);
			margin: 0;
			line-height: 1.55;
		}

		/* Features grid */
		.features-section {
			padding: 72px 0;
		}
		.features-label {
			font-family: var(--font-heading);
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: var(--color-accent);
			margin-bottom: 10px;
		}
		.features-title {
			font-family: var(--font-display);
			font-size: clamp(1.6rem, 3.5vw, 2.2rem);
			font-weight: 800;
			color: var(--color-dark);
			margin-bottom: 48px;
			letter-spacing: -0.3px;
		}
		.feature-card {
			background: var(--surface-card);
			border: 1px solid var(--surface-border);
			border-radius: 16px;
			padding: 32px 28px;
			height: 100%;
			transition: box-shadow 0.25s ease, transform 0.25s ease;
			position: relative;
			overflow: hidden;
		}
		.feature-card::before {
			content: '';
			position: absolute;
			top: 0; left: 0; right: 0;
			height: 3px;
			background: linear-gradient(90deg, var(--color-accent), transparent);
			opacity: 0;
			transition: opacity 0.25s ease;
		}
		.feature-card:hover {
			box-shadow: var(--shadow-hover);
			transform: translateY(-4px);
		}
		.feature-card:hover::before { opacity: 1; }
		.feature-card-icon {
			width: 52px;
			height: 52px;
			border-radius: 14px;
			background: rgba(14,159,173,0.10);
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--color-accent);
			font-size: 20px;
			margin-bottom: 20px;
		}
		.feature-card h3 {
			font-family: var(--font-heading);
			font-size: 1rem;
			font-weight: 700;
			color: var(--color-dark);
			margin-bottom: 8px;
		}
		.feature-card p {
			font-family: var(--font-heading);
			font-size: 0.85rem;
			color: var(--text-muted);
			line-height: 1.7;
			margin: 0;
		}

		/* Team section */
		.team-section {
			padding: 72px 0;
			background: var(--bg-warm-gray);
		}
		.team-eyebrow {
			font-family: var(--font-heading);
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: var(--color-accent);
			margin-bottom: 10px;
		}
		.team-title {
			font-family: var(--font-display);
			font-size: clamp(1.6rem, 3.5vw, 2.2rem);
			font-weight: 800;
			color: var(--color-dark);
			margin-bottom: 48px;
			letter-spacing: -0.3px;
		}

		/* Testimonials */
		.testimonials-section {
			padding: 72px 0 80px;
		}
		.testimonials-label {
			font-family: var(--font-heading);
			font-size: 0.72rem;
			font-weight: 700;
			letter-spacing: 2px;
			text-transform: uppercase;
			color: var(--color-accent);
			margin-bottom: 10px;
		}
		.testimonials-title {
			font-family: var(--font-display);
			font-size: clamp(1.6rem, 3.5vw, 2.2rem);
			font-weight: 800;
			color: var(--color-dark);
			margin-bottom: 48px;
			letter-spacing: -0.3px;
		}
		.testimonial-card {
			background: var(--surface-card);
			border: 1px solid var(--surface-border);
			border-radius: 16px;
			padding: 32px 28px;
			height: 100%;
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.testimonial-quote-icon {
			color: var(--color-accent);
			font-size: 20px;
			opacity: 0.5;
		}
		.testimonial-body {
			font-family: var(--font-heading);
			font-size: 0.9rem;
			color: var(--color-dark);
			line-height: 1.75;
			flex: 1;
			font-style: italic;
		}
		.testimonial-author {
			display: flex;
			align-items: center;
			gap: 12px;
			padding-top: 16px;
			border-top: 1px solid var(--surface-border);
		}
		.testimonial-avatar {
			width: 40px;
			height: 40px;
			border-radius: 50%;
			background: linear-gradient(135deg, var(--color-accent), var(--color-dark));
			display: flex;
			align-items: center;
			justify-content: center;
			font-family: var(--font-display);
			font-size: 1rem;
			font-weight: 800;
			color: #fff;
			flex-shrink: 0;
		}
		.testimonial-name {
			font-family: var(--font-heading);
			font-size: 0.88rem;
			font-weight: 700;
			color: var(--color-dark);
			margin: 0 0 2px;
		}
		.testimonial-role {
			font-family: var(--font-heading);
			font-size: 0.75rem;
			color: var(--text-subtle);
			margin: 0;
		}

		/* CTA strip */
		.about-cta {
			background: var(--color-dark);
			padding: 64px 0;
			text-align: center;
		}
		.about-cta h2 {
			font-family: var(--font-display);
			font-size: clamp(1.8rem, 4vw, 2.6rem);
			font-weight: 800;
			color: #fff;
			letter-spacing: -0.3px;
			margin-bottom: 12px;
		}
		.about-cta p {
			font-family: var(--font-heading);
			font-size: 0.9rem;
			color: rgba(255,255,255,0.45);
			margin-bottom: 32px;
		}
		.about-cta .boxed-btn {
			background: var(--color-accent);
			border-color: var(--color-accent);
		}
		.about-cta .boxed-btn:hover {
			background: var(--color-accent-hover);
			border-color: var(--color-accent-hover);
		}

		@media (max-width: 991px) {
			.mission-right { justify-content: flex-start; margin-top: 40px; }
			.mission-badge-block { max-width: 100%; }
		}
		@media (max-width: 576px) {
			.mission-cta-row { flex-direction: column; align-items: flex-start; }
		}
	</style>
</head>

<body>
	<?php include "inc/header.inc.php"; ?>
	<?php include "inc/search.inc.php"; ?>

	<!-- breadcrumb -->
	<div class="breadcrumb-section breadcrumb-bg">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 offset-lg-2 text-center">
					<div class="breadcrumb-text">
						<p class="breadcrumb-label">Your Stage. Your Sound.</p>
						<h1>About Us</h1>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="about-page">

		<!-- Mission -->
		<section class="mission-strip" id="main-content">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-7">
						<p class="mission-eyebrow">Our mission</p>
						<h2 class="mission-headline">Live events, <em>without</em> the friction</h2>
						<p class="mission-body">Statik was built so that buying a ticket is never the hard part. Browse what's on, pick your seats, pay securely, and get back to looking forward to the show.</p>
						<div class="mission-cta-row">
							<a href="/shop.php" class="boxed-btn">Browse events</a>
							<div class="mission-stat-pair">
								<span class="mission-stat-num">100+</span>
								<span class="mission-stat-label">Events listed</span>
							</div>
							<div class="mission-divider"></div>
							<div class="mission-stat-pair">
								<span class="mission-stat-num">5,000+</span>
								<span class="mission-stat-label">Tickets sold</span>
							</div>
							<div class="mission-divider"></div>
							<div class="mission-stat-pair">
								<span class="mission-stat-num">4.9★</span>
								<span class="mission-stat-label">Average rating</span>
							</div>
						</div>
					</div>
					<div class="col-lg-5">
						<div class="mission-right">
							<div class="mission-badge-block">
								<div class="mission-badge-item">
									<div class="mission-badge-icon"><i class="fas fa-ticket-alt"></i></div>
									<div class="mission-badge-text">
										<h4>Instant delivery</h4>
										<p>Tickets hit your inbox the moment checkout completes.</p>
									</div>
								</div>
								<div class="mission-badge-item">
									<div class="mission-badge-icon"><i class="fas fa-shield-alt"></i></div>
									<div class="mission-badge-text">
										<h4>Payments via PayPal</h4>
										<p>We never store your card details. Every transaction is encrypted end-to-end.</p>
									</div>
								</div>
								<div class="mission-badge-item">
									<div class="mission-badge-icon"><i class="fas fa-headset"></i></div>
									<div class="mission-badge-text">
										<h4>Real support</h4>
										<p>Mon–Fri 8am–9pm · Sat–Sun 10am–8pm. Actual humans, fast replies.</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Why Statik features -->
		<section class="features-section">
			<div class="container">
				<p class="features-label">Why Statik</p>
				<h2 class="features-title">Built around the event-goer</h2>
				<div class="row">
					<div class="col-lg-3 col-md-6 mb-4">
						<div class="feature-card">
							<div class="feature-card-icon"><i class="fas fa-bolt"></i></div>
							<h3>Instant e-Tickets</h3>
							<p>No waiting, no printing. Your booking confirmation — with full order details — lands in your inbox right after checkout.</p>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 mb-4">
						<div class="feature-card">
							<div class="feature-card-icon"><i class="fas fa-tags"></i></div>
							<h3>Transparent pricing</h3>
							<p>Cat 1, Cat 2, Cat 3 — every price shown upfront. No surprise fees added at the last step. What you see is what you pay.</p>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 mb-4">
						<div class="feature-card">
							<div class="feature-card-icon"><i class="fas fa-chair"></i></div>
							<h3>Live availability</h3>
							<p>Seat counts update in real time. If a category sells out, you'll see it before you add to cart — not after.</p>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 mb-4">
						<div class="feature-card">
							<div class="feature-card-icon"><i class="fas fa-lock"></i></div>
							<h3>Secure checkout</h3>
							<p>Powered by PayPal. All card processing happens off our servers — your payment details are never stored by Statik.</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Team -->
		<section class="team-section">
			<div class="container">
				<p class="team-eyebrow text-center">The people behind it</p>
				<h2 class="team-title text-center">Our Team</h2>
				<div class="row">
					<div class="col-lg-4 col-md-6">
						<div class="single-team-item">
							<div class="team-bg team-bg-1"></div>
							<h4>Me<span>Owner</span></h4>
							<ul class="social-link-team">
								<li><a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-twitter"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-instagram"></i></a></li>
							</ul>
						</div>
					</div>
					<div class="col-lg-4 col-md-6">
						<div class="single-team-item">
							<div class="team-bg team-bg-2"></div>
							<h4>Me<span>Front-end</span></h4>
							<ul class="social-link-team">
								<li><a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-twitter"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-instagram"></i></a></li>
							</ul>
						</div>
					</div>
					<div class="col-lg-4 col-md-6 offset-md-3 offset-lg-0">
						<div class="single-team-item">
							<div class="team-bg team-bg-3"></div>
							<h4>Me<span>Back-end</span></h4>
							<ul class="social-link-team">
								<li><a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-twitter"></i></a></li>
								<li><a href="#" target="_blank"><i class="fab fa-instagram"></i></a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Testimonials -->
		<section class="testimonials-section">
			<div class="container">
				<p class="testimonials-label text-center">What people say</p>
				<h2 class="testimonials-title text-center">Heard from the crowd</h2>
				<div class="row">
					<div class="col-lg-4 col-md-6 mb-4">
						<div class="testimonial-card">
							<i class="fas fa-quote-left testimonial-quote-icon"></i>
							<p class="testimonial-body">"Bought tickets to a sold-out show I thought I'd missed. Checkout took less than two minutes — the confirmation email was waiting before I even put my phone down."</p>
							<div class="testimonial-author">
								<div class="testimonial-avatar">P</div>
								<div>
									<p class="testimonial-name">Priya Tan</p>
									<p class="testimonial-role">Concert-goer</p>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-4 col-md-6 mb-4">
						<div class="testimonial-card">
							<i class="fas fa-quote-left testimonial-quote-icon"></i>
							<p class="testimonial-body">"The seat categories and pricing are laid out clearly. No hidden fees, no surprises at the door. This is exactly what buying tickets should feel like."</p>
							<div class="testimonial-author">
								<div class="testimonial-avatar">M</div>
								<div>
									<p class="testimonial-name">Marcus Lim</p>
									<p class="testimonial-role">Regular event-goer</p>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-4 col-md-6 mb-4">
						<div class="testimonial-card">
							<i class="fas fa-quote-left testimonial-quote-icon"></i>
							<p class="testimonial-body">"Had a question about my booking and the support team resolved it the same day. Quick, friendly, and actually helpful. Won't use any other platform for events now."</p>
							<div class="testimonial-author">
								<div class="testimonial-avatar">A</div>
								<div>
									<p class="testimonial-name">Aisha Rahman</p>
									<p class="testimonial-role">Satisfied customer</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- CTA -->
		<div class="about-cta">
			<div class="container">
				<h2>Ready to catch a show?</h2>
				<p>Hundreds of events, all in one place. Find yours today.</p>
				<a href="/shop.php" class="boxed-btn">Browse all events</a>
			</div>
		</div>

	</div><!-- /about-page -->

	<?php include "inc/footer.inc.php"; ?>
</body>
</html>
