# Requirements Compliance Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the Statik site into full compliance with INF1005 project requirements — W3C validation, WCAG accessibility, login JS form validation, and shop genre/sort filter.

**Architecture:** Four areas applied in order: (1) CSS contrast fix, (2) WCAG aria attributes sitewide, (3) login JS validation, (4) shop filter feature. No new files are created. The shop filter is purely client-side JS reading `data-*` attributes injected server-side; the existing `shop.php` DB query already returns `genre_name` and `min_price` so no query changes are needed.

**Tech Stack:** Plain PHP 8, MySQLi, Bootstrap 4, vanilla JavaScript (ES5-compatible), FontAwesome 5, CSS custom properties.

---

## Files Modified

| File | Changes |
|---|---|
| `css/theme.css` | `--text-subtle` #aaa → #767676 |
| `inc/header.inc.php` | aria-hidden on 12 icons, aria-label on cart link, `?category=` → `?genre=` nav links |
| `inc/search.inc.php` | aria-hidden on 2 icons |
| `inc/footer.inc.php` | No changes — audited, contains no FontAwesome icons |
| `index.php` | aria-hidden on 6 icons, W3C fixes |
| `item.php` | aria-hidden on 1 icon, W3C fixes |
| `cart.php` | aria-hidden on 5 icons, W3C fixes |
| `orders.php` | aria-hidden on 3 icons, W3C fixes |
| `account.php` | W3C fixes only — all icons already covered by parent `aria-hidden` |
| `register.php` | W3C fixes |
| `login.php` | `needs-validation` + `novalidate`, `invalid-feedback` divs, JS block, W3C fixes |
| `shop.php` | aria-hidden, `id="events-grid"`, `event-card-wrap` + `data-*` attrs, genres query, filter bar HTML + JS, W3C fixes |

---

## Task 1: CSS — Fix muted text contrast

**Files:**
- Modify: `css/theme.css` line 24

- [ ] **Step 1: Change `--text-subtle` from `#aaaaaa` to `#767676`**

In `css/theme.css`, find line 24:
```css
--text-subtle:        #aaaaaa;
```
Change to:
```css
--text-subtle:        #767676;
```

- [ ] **Step 2: Verify visually**

Open any page that uses muted/secondary text (e.g. `orders.php`, `shop.php`). Muted text should be slightly darker but still clearly secondary. No layout changes expected.

- [ ] **Step 3: Commit**

```bash
git add css/theme.css
git commit -m "fix: darken --text-subtle to #767676 for WCAG AA contrast"
```

---

## Task 2: WCAG — Shared includes (header + search)

**Files:**
- Modify: `inc/header.inc.php`
- Modify: `inc/search.inc.php`

- [ ] **Step 1: Add `aria-hidden="true"` to all decorative icons in `inc/header.inc.php`**

Apply `aria-hidden="true"` to every FontAwesome icon. Also fix the icon-only cart link. All 12 icons with their exact lines:

Line 50 — login button icon (has "Login" text):
```html
<i class="fas fa-user-circle" aria-hidden="true"></i>
```
Line 56 — logged-in user pill icon (has username text):
```html
<i class="fas fa-user-circle" aria-hidden="true"></i>
```
Line 58 — dropdown chevron:
```html
<i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
```
Line 61 — My Account link icon:
```html
<i class="fas fa-user-edit" aria-hidden="true"></i>
```
Line 62 — My Orders link icon:
```html
<i class="fas fa-receipt" aria-hidden="true"></i>
```
Line 63 — Logout link icon:
```html
<i class="fas fa-sign-out-alt" aria-hidden="true"></i>
```
Line 66 — Cart link (icon-only — add `aria-label` to the `<a>` AND `aria-hidden` to icon):
```html
<!-- Before -->
<a class="shopping-cart" href="/cart.php"><i class="fas fa-shopping-cart"></i></a>
<!-- After -->
<a class="shopping-cart" href="/cart.php" aria-label="Shopping cart"><i class="fas fa-shopping-cart" aria-hidden="true"></i></a>
```
Line 73 — Search submit button icon (parent `<button>` already has `aria-label="Search"`):
```html
<i class="fas fa-search" aria-hidden="true"></i>
```
Line 82 — Analytics admin link icon:
```html
<i class="fas fa-chart-bar" aria-hidden="true"></i>
```
Line 83 — Events admin link icon:
```html
<i class="fas fa-calendar-alt" aria-hidden="true"></i>
```
Line 84 — Members admin link icon:
```html
<i class="fas fa-users" aria-hidden="true"></i>
```
Line 85 — Categories admin link icon:
```html
<i class="fas fa-tags" aria-hidden="true"></i>
```

- [ ] **Step 2: Fix nav genre links in `inc/header.inc.php`**

The sub-menu is at lines 29–35. The current links use `?category=` with incorrect values. Replace the entire `<ul class="sub-menu">` block:

```html
<!-- Before (lines 29–35) -->
<ul class="sub-menu">
    <li><a href="/shop.php?category=concerts">Concerts</a></li>
    <li><a href="/shop.php?category=sports">Sports</a></li>
    <li><a href="/shop.php?category=comedy">Comedy</a></li>
    <li><a href="/shop.php?category=musical">Musical</a></li>
</ul>

<!-- After — values match genres.name in DB exactly (Sports removed: not in DB) -->
<ul class="sub-menu">
    <li><a href="/shop.php?genre=Concert">Concert</a></li>
    <li><a href="/shop.php?genre=Comedy">Comedy</a></li>
    <li><a href="/shop.php?genre=Musical">Musical</a></li>
    <li><a href="/shop.php?genre=Theatre">Theatre</a></li>
</ul>
```

- [ ] **Step 3: Add `aria-hidden="true"` to icons in `inc/search.inc.php`**

Line 6 — close icon:
```html
<!-- Before -->
<i class="fas fa-window-close"></i>
<!-- After -->
<i class="fas fa-window-close" aria-hidden="true"></i>
```

Line 12 — search icon:
```html
<!-- Before -->
<i class="fas fa-search"></i>
<!-- After -->
<i class="fas fa-search" aria-hidden="true"></i>
```

- [ ] **Step 4: Verify**

Load any page. Check that:
- Cart icon in header is accessible (inspect — `<a>` has `aria-label="Shopping cart"`)
- Nav genre links use `?genre=Concert` etc.
- No visual regressions

- [ ] **Step 5: Commit**

```bash
git add inc/header.inc.php inc/search.inc.php
git commit -m "fix: aria-hidden on decorative icons, aria-label on cart, fix genre nav links"
```

---

## Task 3: WCAG — index.php and item.php icons

**Files:**
- Modify: `index.php`
- Modify: `item.php`

- [ ] **Step 1: Add `aria-hidden="true"` to decorative icons in `index.php`**

Line 96:
```html
<i class="fas fa-ticket-alt" aria-hidden="true"></i>
```
Line 107:
```html
<i class="fas fa-lock" aria-hidden="true"></i>
```
Line 118:
```html
<i class="fas fa-headset" aria-hidden="true"></i>
```
Line 193:
```html
<i class="far fa-calendar-alt" aria-hidden="true"></i>
```
Line 197:
```html
<i class="far fa-clock" aria-hidden="true"></i>
```
Line 208:
```html
<i class="fas fa-map-marker-alt" aria-hidden="true"></i>
```

- [ ] **Step 2: Add `aria-hidden="true"` to decorative icon in `item.php`**

Line 132 — icon inside "Add to Cart" button (has text beside it):
```html
<!-- Before -->
<i class="fas fa-shopping-cart"></i> Add to Cart
<!-- After -->
<i class="fas fa-shopping-cart" aria-hidden="true"></i> Add to Cart
```

- [ ] **Step 3: Commit**

```bash
git add index.php item.php
git commit -m "fix: aria-hidden on decorative icons in index and item pages"
```

---

## Task 4: WCAG — cart.php, orders.php, account.php icons

**Files:**
- Modify: `cart.php`
- Modify: `orders.php`
- Modify: `account.php`

- [ ] **Step 1: Add `aria-hidden="true"` to decorative icons in `cart.php`**

Line 504 — empty cart icon:
```html
<i class="fas fa-ticket-alt cart-empty-icon" aria-hidden="true"></i>
```
Line 508 — Browse button arrow:
```html
<i class="fas fa-arrow-right" aria-hidden="true"></i>
```
Line 520 — cart count badge icon:
```html
<i class="fas fa-ticket-alt" aria-hidden="true"></i>
```
Line 538 — remove ticket icon (the `<a>` already has `title="Remove"`, so the icon is decorative):
```html
<i class="fas fa-times" aria-hidden="true"></i>
```
Line 584 — order panel receipt icon:
```html
<i class="fas fa-receipt panel-icon" aria-hidden="true"></i>
```

- [ ] **Step 2: Add `aria-hidden="true"` to decorative icons in `orders.php`**

Line 342 — empty state icon:
```html
<i class="fas fa-receipt orders-empty-icon" aria-hidden="true"></i>
```
Line 346 — browse button arrow:
```html
<i class="fas fa-arrow-right" aria-hidden="true"></i>
```
Line 353 — orders heading icon:
```html
<i class="fas fa-receipt" aria-hidden="true"></i>
```
(Line 385 already has `aria-hidden="true"` — skip.)

- [ ] **Step 3: Verify `account.php` — no icon changes needed**

All icons in `account.php` are already covered: the user profile icon at line 429 sits inside `<div class="account-avatar" aria-hidden="true">` (line 428) — the parent `aria-hidden` hides all children from screen readers. No changes needed for `account.php` icons.

- [ ] **Step 4: Commit**

```bash
git add cart.php orders.php
git commit -m "fix: aria-hidden on remaining decorative icons in cart and orders"
```

---

## Task 5: Login JS Validation

**Files:**
- Modify: `login.php`

- [ ] **Step 1: Add `class="needs-validation"` and `novalidate` to the form tag**

Find the form tag (line 56):
```html
<!-- Before -->
<form action="/process_login.php" method="post">
<!-- After -->
<form action="/process_login.php" method="post" class="needs-validation" novalidate>
```

- [ ] **Step 2: Add `invalid-feedback` divs under each input**

Under the username/email input, add:
```html
<div class="invalid-feedback">Please enter your username or email.</div>
```

Under the password input, add:
```html
<div class="invalid-feedback">Please enter your password.</div>
```

- [ ] **Step 3: Add the validation script at the bottom of `login.php` (before `</body>`)**

```html
<script>
(function () {
    'use strict';
    var form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>
```

- [ ] **Step 4: Verify**

Open `login.php` and click Submit with empty fields. Red validation messages should appear under each empty field without the page reloading.

- [ ] **Step 5: Commit**

```bash
git add login.php
git commit -m "feat: add Bootstrap JS validation to login form"
```

---

## Task 6: W3C Validation Sweep

**Files:**
- Modify: `index.php`, `shop.php`, `item.php`, `cart.php`, `account.php`, `register.php`, `login.php`, `orders.php` (fix any errors found)

W3C validation must be done on rendered HTML output, not PHP source. Use the validator at https://validator.w3.org/#validate_by_input.

- [ ] **Step 1: Validate `index.php`**

Visit the live page, use browser "View Page Source", copy the full HTML, paste into validator.w3.org. Fix any errors reported. Common fixes:
- Remove `type="text/javascript"` from any `<script>` tags
- Fix any duplicate `id` attributes
- Fix any unclosed or incorrectly nested tags

- [ ] **Step 2: Validate `register.php` and `login.php`**

Repeat the process. For `login.php`, the `novalidate` attribute added in Task 5 is valid HTML5.

- [ ] **Step 3: Validate `shop.php`, `item.php`, `cart.php`**

Repeat. Note: `cart.php` requires being logged in with items in cart to see the full HTML — test both empty and populated states if possible.

- [ ] **Step 4: Validate `account.php` and `orders.php`**

Repeat. Both require being logged in.

- [ ] **Step 5: Commit any fixes**

```bash
git add index.php shop.php item.php cart.php account.php register.php login.php orders.php
git commit -m "fix: W3C validation errors across all pages"
```

---

## Task 7: Shop Filter — Grid ID and Card Data Attributes

**Files:**
- Modify: `shop.php`

Note: The existing `shop.php` DB query already returns `genre_name` and `min_price` from its `LEFT JOIN genres` and `LEFT JOIN ticket_categories` — no query changes needed.

- [ ] **Step 1: Add `id="events-grid"` to the events grid wrapper**

Find the `<div class="row product-lists">` and add the id:
```html
<!-- Before -->
<div class="row product-lists">
<!-- After -->
<div class="row product-lists" id="events-grid">
```

- [ ] **Step 2: Add `event-card-wrap` class and `data-*` attributes to the card column div**

Inside the foreach loop, find the opening card column div and extend it. The existing class list must be preserved:
```html
<!-- Before -->
<div class="col-lg-4 col-md-6 text-center strawberry">
<!-- After -->
<div class="col-lg-4 col-md-6 text-center strawberry event-card-wrap"
     data-genre="<?= htmlspecialchars($item['genre_name'] ?? '') ?>"
     data-price="<?= $item['min_price'] ?? 0 ?>"
     data-date="<?= $item['event_date'] ?>"
     data-name="<?= htmlspecialchars($item['name']) ?>">
```

- [ ] **Step 3: Verify data attributes are rendered**

Load `shop.php` in the browser, right-click an event card, inspect element. The outer column div should have `data-genre`, `data-price`, `data-date`, `data-name` attributes with correct values.

- [ ] **Step 4: Commit**

```bash
git add shop.php
git commit -m "feat: add data-* attributes to shop event cards for client-side filtering"
```

---

## Task 8: Shop Filter — Genres Query and Filter Bar HTML

**Files:**
- Modify: `shop.php`

- [ ] **Step 1: Add the genres query before `$conn->close()` in `shop.php`**

The existing `shop.php` closes the DB connection on line 14 with `$conn->close()`. The genres query must be inserted **before** this line — after `$stmt->close()` (line 13) but before `$conn->close()` (line 14):

```php
// After line 13 ($stmt->close()), before line 14 ($conn->close()):
// Fetch genres for filter dropdown
$genreStmt = $conn->query("SELECT id, name FROM genres ORDER BY name");
$genres = $genreStmt->fetch_all(MYSQLI_ASSOC);
// then $conn->close(); follows on the next line
```

The existing events query is not changed.

- [ ] **Step 2: Add the filter bar HTML above the events grid**

Insert the filter bar immediately before `<div class="row product-lists" id="events-grid">`:

```html
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
```

- [ ] **Step 3: Verify**

Load `shop.php`. Two dropdowns (genre, sort) and a Clear button should appear above the event grid. The genre dropdown should be populated with genres from the DB. Selecting a genre should do nothing yet (JS not added yet).

- [ ] **Step 4: Commit**

```bash
git add shop.php
git commit -m "feat: add genre filter bar HTML to shop page"
```

---

## Task 9: Shop Filter — Client-side JS

**Files:**
- Modify: `shop.php`

- [ ] **Step 1: Add the filter/sort script at the bottom of `shop.php` (before `</body>`)**

```html
<script>
(function () {
    var cards   = Array.from(document.querySelectorAll('.event-card-wrap'));
    var grid    = document.getElementById('events-grid');
    var genreEl = document.getElementById('genre-filter');
    var sortEl  = document.getElementById('sort-filter');
    var clearEl = document.getElementById('clear-filters');
    var noRes   = document.getElementById('no-results');

    function applyFilters() {
        var genre = genreEl.value;
        var sort  = sortEl.value;

        // Filter
        var visible = cards.filter(function (card) {
            return genre === '' || card.dataset.genre === genre;
        });

        // Sort
        visible.sort(function (a, b) {
            if (sort === 'price-asc')  return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            if (sort === 'price-desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            if (sort === 'name')       return a.dataset.name.localeCompare(b.dataset.name);
            return a.dataset.date.localeCompare(b.dataset.date); // default: date
        });

        // Hide all, then show and reorder visible
        cards.forEach(function (c) { c.style.display = 'none'; });
        visible.forEach(function (c) {
            c.style.display = '';
            grid.appendChild(c);
        });

        noRes.style.display = visible.length === 0 ? '' : 'none';
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
}());
</script>
```

- [ ] **Step 2: Verify filtering works**

Load `shop.php`:
- Select a genre — only events of that genre should be visible
- Select a different genre — grid updates
- Select "All Genres" — all events show again
- Select "Price: Low to High" — cards reorder by lowest `data-price`
- Click Clear — both dropdowns reset to default, all cards visible
- Visit `/shop.php?genre=Concert` — Concert genre should be pre-selected and filtered on load

- [ ] **Step 3: Verify "No results" message**

If there is a genre with no events, select it — the "No events match your filters." message should appear and the grid should be empty.

- [ ] **Step 4: Commit**

```bash
git add shop.php
git commit -m "feat: client-side genre filter and sort on shop page"
```

---

## Task 10: Final verification commit

- [ ] **Step 1: Smoke-test the full site**

Visit each of the 8 validated pages, confirm no regressions:
- `index.php` — loads, icons present, no JS errors in console
- `shop.php` — filter bar visible, filter/sort working, nav genre links use `?genre=`
- `item.php` — Add to Cart button works
- `cart.php` — cart renders, PayPal button loads
- `login.php` — empty submit shows red validation messages
- `register.php` — validation still works
- `account.php` — edit profile and delete zone work
- `orders.php` — order list renders

- [ ] **Step 2: Check browser console on all pages**

Open DevTools > Console on each page. Zero JavaScript errors expected.

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "chore: final smoke-test pass — requirements compliance improvements complete"
```
