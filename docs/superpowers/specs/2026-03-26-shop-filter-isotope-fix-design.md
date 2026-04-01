# Shop Filter Isotope Fix & Nav "All" Link — Design Spec

**Date:** 2026-03-26
**Status:** Approved
**Scope:** Fix shop.php filter/sort JS conflict with Isotope; add "All" to Categories nav sub-menu

---

## Problem

`main.js` initialises Isotope on `.product-lists`, applying `position: absolute` to every card. The existing shop filter JS uses `style.display = 'none'` and `grid.appendChild()` to hide/reorder cards — bypassing Isotope's layout engine. After any filter operation, Isotope's internal state is out of sync: cards pile up on the left edge, and subsequent filters leave only one card visible.

---

## Solution Overview

Replace the vanilla JS filter/sort with Isotope's native filter and sort API, configured for Bootstrap-compatible layout. Add an "All" link to the header nav sub-menu.

---

## 1. shop.php Script Replacement

Replace the existing vanilla IIFE entirely with a jQuery `$(document).ready` block.

### Isotope re-initialisation

Call `$('#events-grid').isotope({ ... })` inside `$(document).ready`. This runs after `main.js`'s own `$(document).ready` initialises Isotope with defaults, then upgrades the instance with the options below.

Options:
```javascript
$('#events-grid').isotope({
    layoutMode:      'fitRows',
    percentPosition: true,
    itemSelector:    '.event-card-wrap',
    getSortData: {
        price: function (el) { return parseFloat($(el).data('price')) || 0; },
        name:  function (el) { return $(el).data('name') || ''; },
        date:  function (el) { return $(el).data('date') || ''; }
    }
});
```

- `layoutMode: 'fitRows'` — items flow left-to-right in rows, respecting each card's rendered CSS width (Bootstrap `col-lg-4` / `col-md-6`). Isotope re-layouts on window resize so breakpoints work automatically.
- `percentPosition: true` — positions are calculated as percentages so the layout adapts to the container width at every breakpoint.
- `itemSelector: '.event-card-wrap'` — Isotope only manages the event cards, not the `#no-results` div.
- `getSortData` — three sort keys parsed from `data-*` attributes: numeric float for price, string for name, string for date.

### Filter & sort function

```javascript
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
```

- `filter: '*'` shows all items (Isotope's "show all" selector).
- Custom filter function uses strict equality `$(this).data('genre') === genre` — same logic as before, now executed by Isotope.

### No-results handling

Use Isotope's `arrangeComplete` event instead of manual counting:

```javascript
$grid.on('arrangeComplete', function (event, filteredItems) {
    noRes.style.display = filteredItems.length === 0 ? '' : 'none';
});
```

### URL param pre-selection

Unchanged logic — read `?genre=` from `URLSearchParams`, set `genreEl.value`, call `applyFilters()`.

### Event listeners

Unchanged — `change` on genre/sort selects, `click` on clear button.

---

## 2. Header Nav "All" Link

In `inc/header.inc.php`, add "All" as the first item in the Categories sub-menu:

```html
<ul class="sub-menu">
    <li><a href="/shop.php">All</a></li>
    <li><a href="/shop.php?genre=Concerts">Concerts</a></li>
    <li><a href="/shop.php?genre=Comedy">Comedy</a></li>
    <li><a href="/shop.php?genre=Musical">Musical</a></li>
    <li><a href="/shop.php?genre=Theatre">Theatre</a></li>
</ul>
```

Navigating to `/shop.php` with no `?genre=` param means the JS pre-selection block is skipped and all events are shown.

---

## Files Changed

| File | Change |
|---|---|
| `shop.php` | Replace vanilla JS IIFE with jQuery/Isotope `$(document).ready` block |
| `inc/header.inc.php` | Add `<li><a href="/shop.php">All</a></li>` as first sub-menu item |
