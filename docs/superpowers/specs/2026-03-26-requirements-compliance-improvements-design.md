# Requirements Compliance Improvements — Design Spec

**Date:** 2026-03-26
**Status:** Approved
**Scope:** W3C validation fixes, WCAG accessibility quick wins, login JS validation, shop filter/sort feature

---

## Overview

This spec covers four improvements to bring the Statik site into full compliance with the INF1005 project requirements (W3C validation, WCAG standards, custom JavaScript, form validation) and to implement the shop filter feature previously specced but not yet built.

Changes are ordered by priority: compliance fixes first, new feature last.

---

## 1. W3C Validation Fixes

**Pages in scope:** `index.php`, `shop.php`, `item.php`, `cart.php`, `account.php`, `register.php`, `login.php`, `orders.php`

**Fixes to apply on each page:**

- Remove `type="text/javascript"` from any `<script>` tags — obsolete in HTML5
- Fix duplicate `id` attributes inside `foreach` loops — replace bare `id="thing"` with `id="thing-<?= $id ?>"` using the row's DB primary key
- Fix any stray or unclosed tags found during the audit
- Remove deprecated presentational attributes (`border=`, `align=`, `bgcolor=`) if present

**Verification:** Each page can be copy-pasted into validator.w3.org to confirm zero errors after fixes are applied.

---

## 2. WCAG Quick Wins

### 2a. Decorative icon aria-hidden

All FontAwesome `<i>` icons that appear alongside visible text are purely decorative and should be hidden from screen readers.

- Add `aria-hidden="true"` to every `<i class="fas ...">` / `<i class="far ...">` element that sits next to readable text
- Applies across all pages and shared includes (`inc/header.inc.php`, `inc/footer.inc.php`, `inc/search.inc.php`)
- Exception: icon-only interactive elements (see 2b) need a label instead

### 2b. Shopping cart accessible label

In `inc/header.inc.php`, the cart link has no text — only a FontAwesome icon:

```html
<!-- Current -->
<a class="shopping-cart" href="/cart.php"><i class="fas fa-shopping-cart"></i></a>

<!-- Fixed -->
<a class="shopping-cart" href="/cart.php" aria-label="Shopping cart">
    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
</a>
```

### 2c. Muted text colour contrast

`#aaaaaa` text on the cream background (`#FAF8F4`) produces a contrast ratio of ~2.9:1, failing WCAG AA (minimum 4.5:1 for normal text).

- Update `--text-subtle` CSS variable in `css/theme.css` from `#aaaaaa` to `#767676`
- `#767676` is the lightest grey that passes WCAG AA on white/near-white backgrounds
- This single variable change fixes all muted text sitewide without touching individual pages

---

## 3. Login Form JavaScript Validation

`login.php` currently only uses the HTML5 `required` attribute. Add Bootstrap's validation pattern, consistent with `register.php`.

**Changes to `login.php`:**

1. Add `class="needs-validation"` and `novalidate` to the `<form>` tag
2. Add `invalid-feedback` divs beneath each input:
   - Username/email field: "Please enter your username or email."
   - Password field: "Please enter your password."
3. Add a `<script>` block at the bottom of the page:

```javascript
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
```

No changes to `process_login.php` — server-side validation remains unchanged.

---

## 4. Shop Filter & Sort

### 4a. Event card data attributes

In `shop.php`, the existing card column div (`<div class="col-lg-4 col-md-6 text-center strawberry">`) gets the `event-card-wrap` class appended and four `data-*` attributes added — the existing class list is preserved:

```html
<div class="col-lg-4 col-md-6 text-center strawberry event-card-wrap"
     data-genre="<?= htmlspecialchars($item['genre_name'] ?? '') ?>"
     data-price="<?= $item['min_price'] ?? 0 ?>"
     data-date="<?= $item['event_date'] ?>"
     data-name="<?= htmlspecialchars($item['name']) ?>">
```

`min_price` and `genre_name` are added to the existing `shop.php` DB query via a `LEFT JOIN genres` and `MIN(tc.price)` join on `ticket_categories`. The existing query is not replaced — these two columns are added to it. The `?? 0` and `?? ''` guards handle performances with no ticket categories or no genre (both use `LEFT JOIN`).

### 4b. Filter bar HTML

Inserted above the event grid, inside the existing container:

```html
<div class="filter-bar" id="filter-bar">
    <select id="genre-filter" aria-label="Filter by genre">
        <option value="">All Genres</option>
        <?php foreach ($genres as $genre): ?>
        <option value="<?= htmlspecialchars($genre['name']) ?>">
            <?= htmlspecialchars($genre['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <select id="sort-filter" aria-label="Sort events">
        <option value="date">Date</option>
        <option value="price-asc">Price: Low to High</option>
        <option value="price-desc">Price: High to Low</option>
        <option value="name">Name A–Z</option>
    </select>

    <button id="clear-filters" type="button">Clear</button>
</div>

<div id="no-results" style="display:none;">
    No events match your filters.
</div>
```

Genres are fetched via a second, additive query added to the top of `shop.php` — the existing events query is not changed:
```php
$genreStmt = $conn->query("SELECT id, name FROM genres ORDER BY name");
$genres = $genreStmt->fetch_all(MYSQLI_ASSOC);
```

### 4c. Client-side filter/sort JS

The existing events grid div (`<div class="row product-lists">`) gets `id="events-grid"` added so the JS can reorder cards via `appendChild`. Added in a `<script>` block at the bottom of `shop.php`:

```javascript
(function () {
    const cards   = Array.from(document.querySelectorAll('.event-card-wrap'));
    const grid    = document.getElementById('events-grid');
    const genreEl = document.getElementById('genre-filter');
    const sortEl  = document.getElementById('sort-filter');
    const clearEl = document.getElementById('clear-filters');
    const noRes   = document.getElementById('no-results');

    function applyFilters() {
        const genre = genreEl.value;
        const sort  = sortEl.value;

        // Filter
        let visible = cards.filter(function (card) {
            return genre === '' || card.dataset.genre === genre;
        });

        // Sort
        visible.sort(function (a, b) {
            if (sort === 'price-asc')  return a.dataset.price - b.dataset.price;
            if (sort === 'price-desc') return b.dataset.price - a.dataset.price;
            if (sort === 'name')       return a.dataset.name.localeCompare(b.dataset.name);
            return a.dataset.date.localeCompare(b.dataset.date); // default: date
        });

        // Hide all, then show/reorder visible
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
    const params = new URLSearchParams(window.location.search);
    if (params.get('genre')) {
        genreEl.value = params.get('genre');
        applyFilters();
    }
})();
```

### 4d. Header nav link fix

In `inc/header.inc.php`, all genre nav links are updated from `?category=` to `?genre=`. The genre value must exactly match the `genres.name` values in the DB (title case, singular) so the JS pre-selection works:

```html
<!-- Before (lowercase plural, wrong parameter name) -->
<a href="/shop.php?category=concerts">Concerts</a>

<!-- After (title case singular, matching DB genre names) -->
<a href="/shop.php?genre=Concert">Concert</a>
```

Full mapping: `Concert`, `Musical`, `Theatre`, `Dance`, `Comedy`.

---

## Files Changed

| File | Change |
|---|---|
| `index.php` | W3C fixes, aria-hidden on icons |
| `shop.php` | W3C fixes, aria-hidden, data attributes, filter bar HTML + JS, genres query |
| `item.php` | W3C fixes, aria-hidden on icons |
| `cart.php` | W3C fixes, aria-hidden on icons |
| `account.php` | W3C fixes, aria-hidden on icons |
| `register.php` | W3C fixes, aria-hidden on icons |
| `login.php` | W3C fixes, aria-hidden on icons, JS validation |
| `orders.php` | W3C fixes, aria-hidden on icons |
| `inc/header.inc.php` | aria-label on cart link, aria-hidden on icons, fix `?category=` → `?genre=` |
| `inc/footer.inc.php` | aria-hidden on icons |
| `inc/search.inc.php` | aria-hidden on icons |
| `css/theme.css` | `--text-subtle: #767676` |
