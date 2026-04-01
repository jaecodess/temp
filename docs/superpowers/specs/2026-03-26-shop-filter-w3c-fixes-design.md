# Design Spec: Shop Filter & W3C Fixes

**Date:** 2026-03-26
**Project:** Statik — Live Events & Tickets
**Scope:** Two improvements — live shop filter/sort and W3C validation fixes.

---

## 1. Shop Page Live Filter/Sort

### Problems
- `shop.php` ignores all URL parameters. `?genre=Concert` from the categories page and nav links silently shows all events — the filter is completely broken.
- The header nav uses `?category=concerts` (lowercase plural) while `categories.php` correctly uses `?genre=Concert` — inconsistent and both broken.
- The nav includes a "Sports" link but "Sports" does not exist as a genre in the DB (`setup.sql` seeds: Concert, Musical, Theatre, Dance, Comedy). Fix: add Sports to `setup.sql` genres and insert into live DB.
- The breadcrumb heading reads "Shop Our Products" — should be "All Events".
- No way to filter or sort events on the page.

---

### Solution

#### Database fix
Add `Sports` to the genres seed in `setup.sql` and run once against the live DB:
```sql
INSERT INTO genres (name) VALUES ('Sports');
```

#### Server-side (`shop.php`)

Read and sanitize the genre from the URL:
```php
$activeGenre = trim($_GET['genre'] ?? '');
```

Fetch all genres from DB:
```php
$genreRows = []; // SELECT id, name FROM genres ORDER BY name
```

Sanitize `$activeGenre` against actual DB values to prevent unrecognised genres from producing a silently empty page:
```php
$validGenres = array_column($genreRows, 'name');
if ($activeGenre !== '' && !in_array($activeGenre, $validGenres, true)) {
    $activeGenre = ''; // unrecognised genre → show all
}
```

Stamp each event card's **outer column `<div>`** with the class `event-card-wrap` (in addition to existing classes) and data attributes:
```php
<div class="col-lg-4 col-md-6 text-center strawberry event-card-wrap"
     data-genre="<?= htmlspecialchars($item['genre_name'] ?? '') ?>"
     data-price="<?= (float) $item['min_price'] ?>"
     data-date="<?= htmlspecialchars($item['event_date']) ?>"
     data-name="<?= htmlspecialchars($item['name']) ?>">
```
- `data-price` uses a `(float)` cast — the value is a `DECIMAL` from MySQL so no HTML-encoding is needed, but the cast makes the numeric intent explicit.
- `data-price` reflects `MIN(tc.price)` regardless of seat availability (known limitation, acceptable for this dataset).

Pass `$activeGenre` to JS safely:
```php
<script>
const activeGenre = <?= json_encode($activeGenre) ?>;
</script>
```
`json_encode` escapes all special characters, preventing XSS.

Fix breadcrumb heading: `"Shop Our Products"` → `"All Events"`.

---

#### Filter bar HTML (above the grid)

```html
<div id="filter-bar" class="...">
    <select id="genre-filter" aria-label="Filter by genre">
        <option value="">All Genres</option>
        <?php foreach ($genreRows as $g): ?>
        <option value="<?= htmlspecialchars($g['name']) ?>"
            <?= $activeGenre === $g['name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <select id="sort-filter" aria-label="Sort events">
        <option value="date-asc">Soonest First</option>
        <option value="price-asc">Price: Low → High</option>
        <option value="price-desc">Price: High → Low</option>
        <option value="name-asc">A–Z</option>
    </select>

    <button id="clear-filters" type="button" style="display:none">
        Clear filters
    </button>
</div>
```

Styled using existing design tokens (`var(--surface-card)`, `var(--color-accent)`, `var(--font-heading)`). Uses Bootstrap 4 `d-flex flex-wrap` row; stacks to column on `col-12` below `md` breakpoint.

---

#### Client-side JS (`filterEvents()`)

Selectors used:
- `#genre-filter` — genre `<select>`
- `#sort-filter` — sort `<select>`
- `#clear-filters` — clear button
- `.event-card-wrap` — each card's outer column div

Logic:
1. Read `genre` and `sort` values from the two selects.
2. Collect all `.event-card-wrap` elements into an array.
3. **Filter:** hide cards whose `data-genre` doesn't match selected genre; skip filter if genre is `""` (All Genres).
4. **Sort:** sort the full array by the selected key, then reinsert all elements into the parent grid in sorted order (hidden cards remain hidden but are repositioned correctly so that showing/hiding a genre always reveals items in the right order).
5. **Empty state:** if every card is hidden after filtering, show inline `#no-results` message with a "Clear filters" link; hide it otherwise.
6. **Clear button:** show the `#clear-filters` button when genre ≠ `""` or sort ≠ `"date-asc"`; hide it otherwise.

Runs on:
- `DOMContentLoaded` — pre-applies `activeGenre` from PHP and triggers initial sort.
- `change` on `#genre-filter` and `#sort-filter`.

Clear button click handler:
```js
document.getElementById('clear-filters').addEventListener('click', function () {
    document.getElementById('genre-filter').value = '';
    document.getElementById('sort-filter').value = 'date-asc';
    history.replaceState(null, '', window.location.pathname); // remove ?genre= from URL
    filterEvents();
});
```
This keeps the URL consistent with the displayed filter state so reload and browser Back behave correctly.

---

#### Fix header nav (`inc/header.inc.php`)

| Old | New |
|---|---|
| `?category=concerts` | `?genre=Concert` |
| `?category=sports` | `?genre=Sports` |
| `?category=comedy` | `?genre=Comedy` |
| `?category=musical` | `?genre=Musical` |

---

### Files changed
- `setup.sql` — add Sports genre to seed INSERT
- `shop.php` — filter bar HTML, `event-card-wrap` class + data attributes on cards, JS, read `$_GET['genre']`, sanitize against DB values, fix heading
- `inc/header.inc.php` — fix 4 nav category link query strings

---

## 2. W3C Validation Audit + Fixes

### Approach
1. Run each key page through the W3C Markup Validation Service (`validator.w3.org`).
2. Collect all **errors** (must fix) and **warnings** (fix where practical).
3. Fix in source, re-validate until clean.

### Pages to validate
- `index.php` — known stray `</div>` to find and fix
- `shop.php`
- `item.php`
- `cart.php`
- `account.php`
- `register.php`
- `login.php`
- `orders.php` — recently modified, inline `<style>` block worth checking

### Common expected issues
| Issue | Fix |
|---|---|
| Duplicate `id` attributes in PHP loops | Use `id="item-<?= $id ?>"` pattern |
| Missing `alt` on images | Add descriptive alt text |
| `type="text/javascript"` on `<script>` | Remove `type` attribute (obsolete in HTML5) |
| Invalid element nesting | Correct nesting |
| Stray or unclosed tags | Fix markup |
| Presentational attributes (`border="0"`) | Remove, use CSS |

### Out of scope for this audit
- CSS validation (e.g. duplicate `font-family` declarations in inline `<style>` blocks) — HTML validator only.
- Moving inline `<style>` blocks to external files — cosmetic, not a validator error.

---

## Implementation Order

1. `setup.sql` + live DB — add Sports genre (2 min)
2. `inc/header.inc.php` — fix 4 nav category links (5 min)
3. `shop.php` — filter bar, `event-card-wrap` class, data attributes, JS (1–2 hrs)
4. W3C audit and fixes across key pages (1–2 hrs)

---

## Out of Scope
- Forgot password flow (deferred to future session)
- AJAX/server-side filtering (client-side sufficient for this dataset size)
- Adding Theatre/Dance to the header nav (not currently linked; no change needed)
