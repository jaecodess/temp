<?php
require_once 'inc/auth.inc.php';
$pageTitle = 'Help & FAQ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <style>
        /* ── FAQ Page ── */
        .help-page {
            padding: 64px 0 100px;
            background-color: var(--bg-body);
            min-height: 60vh;
        }

        .help-intro {
            text-align: center;
            margin-bottom: 48px;
        }
        .help-intro h2 {
            font-family: var(--font-display);
            font-size: 1.9rem;
            font-weight: 800;
            color: var(--color-dark);
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        .help-intro p {
            color: var(--text-subtle);
            font-family: var(--font-heading);
            font-size: 0.9rem;
        }

        /* Category heading */
        .faq-category {
            margin-bottom: 36px;
        }
        .faq-category-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-heading);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--color-accent);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-accent);
        }

        /* FAQ item card */
        .faq-item {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 12px;
            margin-bottom: 8px;
            overflow: hidden;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .faq-item:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-1px);
        }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            cursor: pointer;
            text-align: left;
            gap: 14px;
        }
        .faq-question span {
            font-family: var(--font-heading);
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--color-dark);
            line-height: 1.4;
            flex: 1;
        }
        .faq-chevron {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            border: 1.5px solid var(--surface-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: var(--text-subtle);
            transition: transform 0.28s ease, background 0.2s, border-color 0.2s;
        }
        .faq-item.open .faq-chevron {
            transform: rotate(180deg);
            background: var(--color-accent);
            border-color: var(--color-accent);
            color: #fff;
        }

        /* Answer panel — CSS Grid row expansion (no layout-thrashing max-height) */
        .faq-answer {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.35s ease;
        }
        .faq-item.open .faq-answer {
            grid-template-rows: 1fr;
        }
        .faq-answer p {
            overflow: hidden;
            font-family: var(--font-heading);
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.75;
            border-top: 1px solid var(--surface-border);
            padding: 14px 22px 18px;
            margin: 0;
        }
        .faq-answer a {
            color: var(--color-accent);
            text-decoration: none;
        }
        .faq-answer a:hover {
            text-decoration: underline;
        }

        /* Contact CTA strip */
        .contact-strip {
            background: var(--color-dark);
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            margin-top: 56px;
        }
        .contact-strip h3 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
            margin-bottom: 8px;
        }
        .contact-strip p {
            font-family: var(--font-heading);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.45);
            margin-bottom: 22px;
        }
        .contact-strip a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-accent);
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 13.5px;
            padding: 13px 30px;
            border-radius: 999px;
            text-decoration: none;
            transition: background 0.22s, transform 0.22s;
        }
        .contact-strip a:hover {
            background: var(--color-accent-hover);
            transform: translateY(-2px);
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
                        <p class="breadcrumb-label">Statik</p>
                        <h1>Help & FAQ</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="help-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="help-intro">
                        <h2>How can we help you?</h2>
                        <p>Find answers to common questions about booking, payments, and your account.</p>
                    </div>

                    <!-- Tickets & Booking -->
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <i class="fas fa-ticket-alt"></i> Tickets &amp; Booking
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-1">
                                <span>How do I purchase tickets?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-1" role="region">
                                <p>Browse events on our <a href="/shop.php">Shop page</a>, select a ticket category and quantity, then add to your cart. Complete checkout securely via PayPal — you'll receive a booking confirmation email immediately.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-2">
                                <span>What ticket categories are available?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-2" role="region">
                                <p>Most events offer Cat 1, Cat 2, and Cat 3 seating. Cat 1 seats are best positioned and highest priced; Cat 3 seats are most affordable. Pricing and availability for each category are shown on the event detail page.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-3">
                                <span>Do I need an account to buy tickets?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-3" role="region">
                                <p>Yes, an account is required to purchase tickets. This lets us securely store your order history and send your confirmation email. <a href="/register.php">Registration</a> is free and only takes a minute.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-4">
                                <span>How many tickets can I buy per order?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-4" role="region">
                                <p>You can purchase up to the number of available seats remaining for a given ticket category. If your requested quantity exceeds available stock, you'll see an error before checkout.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <i class="fas fa-credit-card"></i> Payments
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-5">
                                <span>What payment methods are accepted?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-5" role="region">
                                <p>We accept payments via PayPal, which also supports major credit and debit cards including Visa, Mastercard, and American Express. All transactions are processed securely in SGD.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-6">
                                <span>Is my payment information secure?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-6" role="region">
                                <p>Yes. All payment processing is handled directly by PayPal — Statik never stores your card details. PayPal uses industry-standard TLS encryption and fraud detection.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-7">
                                <span>Will I receive a receipt after payment?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-7" role="region">
                                <p>Yes. A booking confirmation email is sent to your registered email address immediately after a successful checkout. It includes your Order ID, Transaction ID, event details, and total amount paid.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Refunds -->
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <i class="fas fa-sync-alt"></i> Refunds &amp; Cancellations
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-8">
                                <span>Can I cancel my order and get a refund?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-8" role="region">
                                <p>All sales are final. We do not offer cancellations or exchanges once an order is confirmed. Please review your selection carefully before completing checkout.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-9">
                                <span>What if an event is cancelled or postponed?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-9" role="region">
                                <p>If an event is cancelled or significantly changed by the organiser, we will contact affected ticket holders at their registered email address with next steps. Please keep your account email up to date.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Account -->
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <i class="fas fa-user-circle"></i> Account &amp; Orders
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-10">
                                <span>How do I view my past orders?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-10" role="region">
                                <p>Log in and go to <a href="/orders.php">My Orders</a> from the navigation menu. All your purchases are listed there, grouped by order with dates and totals.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-11">
                                <span>I forgot my password. How do I reset it?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-11" role="region">
                                <p>Password self-reset is not yet available. Please <a href="/contact.php">contact us</a> with your registered email address and we'll assist you promptly.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-12">
                                <span>Can I update my account details?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-12" role="region">
                                <p>You can view your details on the <a href="/account.php">Account page</a>. To update your name, email, or username, <a href="/contact.php">contact us</a> and we'll make the change for you.</p>
                            </div>
                        </div>
                    </div>

                    <!-- At the event -->
                    <div class="faq-category">
                        <div class="faq-category-title">
                            <i class="fas fa-map-marker-alt"></i> At the Event
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-13">
                                <span>What do I need to bring to the event?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-13" role="region">
                                <p>Bring a valid photo ID and your booking confirmation email (printed or on your phone). Arrive at least 30 minutes before the event starts. No re-entry is permitted once you exit the venue.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" type="button" onclick="toggleFaq(this)" aria-expanded="false" aria-controls="faq-14">
                                <span>Are there age restrictions for events?</span>
                                <div class="faq-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></div>
                            </button>
                            <div class="faq-answer" id="faq-14" role="region">
                                <p>Age restrictions vary by event. Comedy events are typically adults-only (18+). Check the event description on the listing page for specific requirements before purchasing.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact CTA -->
                    <div class="contact-strip">
                        <h3>Still need help?</h3>
                        <p>Our team is available Mon – Fri, 8am – 9pm &nbsp;|&nbsp; Sat – Sun, 10am – 8pm</p>
                        <a href="/contact.php">
                            <i class="fas fa-envelope"></i> Contact us
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include "inc/footer.inc.php"; ?>

    <script>
        function toggleFaq(btn) {
            var item = btn.parentElement;
            var isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item.open').forEach(function(el) {
                el.classList.remove('open');
                el.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            });
            if (!isOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        }
    </script>
</body>
</html>
