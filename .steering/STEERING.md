# STS UI Modernisation — Steering Document

> **Purpose:** Canonical reference for all future UI changes to the STS (Shipper-Driven Traffic Simulator) web application. Every new or refactored page **must** follow these conventions to maintain visual and behavioural consistency.
>
> **Derived from:** `display_station_report.php`, `wheel_report.php`, `db_list_cars.php`, and the deleted `printable_station_report.php` (merged into `display_station_report.php`).

---

## 1. Technology Stack

| Layer | Technology | Version | CDN |
|-------|-----------|---------|-----|
| CSS Framework | Bootstrap | 5.3.0 | `https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css` |
| Icons | Bootstrap Icons | 1.11.0 | `https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css` |
| JS Framework | jQuery | 3.6.0 | `https://code.jquery.com/jquery-3.6.0.min.js` |
| JS Framework | Bootstrap Bundle | 5.3.0 | `https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js` |
| Table Sorting | sorttable.js | local | `sorttable.js` (legacy, retain where already used) |

### Load Order (in `<head>`)
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
```

### Load Order (before `</body>`)
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

> **Note:** Always include the `viewport` meta tag. It prevents iOS zoom on form inputs and enables responsive breakpoints.

---

## 2. Colour Palette

### Primary Colours

| Swatch | Hex | Usage |
|--------|-----|-------|
| 🔵 Primary Blue | `#4a90e2` | Table headers (`thead`), job section headers, navbar accents |
| ⚪ Page Background | `#f8f9fa` | `body` background, table row hover |
| ⬜ Card Background | `#ffffff` | Cards, panels, modals |
| 🔲 Border Grey | `#dee2e6` | Table cell borders, card borders, input borders |
| ◼️ Text Primary | `#333` | Headings, body text |
| ◾ Text Secondary | `#666` | Sub-headings |
| ▫️ Text Muted | `#999` | Captions, small print |

### Status Badge Colours

| Status | Background | Text | Class |
|--------|-----------|------|-------|
| Empty | `#ffeaa7` | `#333` | `.status-empty` |
| Loaded | `#a8e6cf` | `#333` | `.status-loaded` |
| Loading | `#74b9ff` | `#fff` | `.status-loading` |
| Unloading | `#fab1a0` | `#fff` | `.status-unloading` |
| Ordered | `#dfe6e9` | `#333` | `.status-ordered` |
| Unavailable | `#d63031` | `#fff` | `.status-unavailable` |

### Accent Colours

| Swatch | Hex | Usage |
|--------|-----|-------|
| Destination Highlight | `#fff3cd` | Enroute destination cells |
| Pool Highlight | `#fff9c4` | Cars in special pool |
| Summary Row | `#e8f0fe` | Table summary/totals row |
| Edit Hover | `#f0f4ff` | Table row hover on editable tables |
| Focus Ring | `rgba(0, 123, 255, 0.25)` | Input focus box-shadow |
| Focus Border | `#80bdff` | Input focus border |
| Save Flash | `#d4edda` | Momentary green flash on successful save |
| Button Gradient Start | `#667eea` | Display/action buttons |
| Button Gradient End | `#764ba2` | Display/action buttons |

---

## 3. Typography

### Screen

```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

- **Body text:** `14px` (inherited from Bootstrap)
- **Table text:** `0.8rem – 0.9rem` (reports), `13px` (data editor tables)
- **Table headers:** `0.75rem` (reports), `13px` with `font-weight: 600`
- **Navbar brand:** `1.3rem`, `font-weight: 600`
- **Status badges:** `0.85rem`, `font-weight: 600`

### Print

```css
font-family: "Courier New", monospace;
```

- **Body:** `6pt`
- **Table cells:** `6pt`
- **Table headers:** `5pt`, `font-weight: bold`
- **Report title (h2):** `7pt`
- **Report subtitle (h3):** `6pt`
- **Small/caption:** `5pt`

---

## 4. Component Patterns

### 4.1 Navigation Bar

Every page must include a top navbar (hidden in print):

```html
<nav class="navbar navbar-dark bg-primary noprint">
  <div class="container-fluid">
    <span class="navbar-brand"><i class="bi bi-icon-name"></i> Page Title</span>
    <div>
      <a href="index.html" class="btn btn-outline-light btn-sm me-2">
        <i class="bi bi-house"></i> Home
      </a>
      <a href="reports.html" class="btn btn-outline-light btn-sm me-2">
        <i class="bi bi-file-text"></i> Reports
      </a>
      <button class="btn btn-light btn-sm" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
      </button>
    </div>
  </div>
</nav>
```

### 4.2 Cards

Cards are the primary content container. No visible border; shadow only:

```css
.card {
  border: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
```

### 4.3 Report Tables

```css
.report-table {
  font-size: 0.8rem;
  width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}
.report-table thead { background-color: #4a90e2; }
.report-table th {
  color: white !important;
  font-weight: 600;
  padding: 6px 4px;
  border: none;
  background-color: #4a90e2;
  position: sticky;
  top: 0;
  z-index: 50;
}
.report-table td {
  padding: 6px 4px;
  border-bottom: 1px solid #dee2e6;
  vertical-align: middle;
}
.report-table tbody tr:hover { background-color: #f8f9fa; }
```

> **Sticky headers:** Apply `position: sticky` to `<th>` elements directly, **not** `<thead>`. Always set `background-color` on `<th>` (not just `<thead>`) to prevent content bleeding through.

### 4.4 Data Editor Tables

For editable listing pages (e.g. `db_list_cars.php`):

```css
#table_id th {
  background-color: #e9ecef;
  font-weight: 600;
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 10;
}
#table_id th, #table_id td {
  border: 1px solid #dee2e6;
  padding: 6px 8px;
  vertical-align: middle;
}
```

### 4.5 Status Badges

Wrap status text in a `<span>` with the appropriate class:

```html
<span class="status-empty">Empty</span>
<span class="status-loaded">Loaded</span>
```

Base CSS (all badges share):
```css
display: inline-block;
padding: 4px 8px;
border-radius: 3px;
font-weight: 600;
font-size: 0.85rem;
```

Use `strtolower()` to generate the class from the database value:
```php
echo '<span class="status-' . strtolower($row['status']) . '">' . htmlspecialchars($row['status']) . '</span>';
```

### 4.6 Print Header

```html
<div class="print-header">
  <h2>Railroad Name</h2>
  <h3>Report Title</h3>
  <h3>Station/Section Name</h3>
  <small><em>Footnote text</em></small>
</div>
```

The print-header scrolls with the page on screen. It is styled for both screen (centred, decorative border) and print (left-aligned, compact, monospace).

### 4.7 Modal Overlay (Inline Editing)

For touch-friendly editing, use a fixed overlay with a centred panel:

```css
.cell-editor-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cell-editor-panel {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
  padding: 24px 28px;
  min-width: 320px;
  max-width: 90vw;
  animation: editorIn 0.15s ease-out;
}
```

> **Why not `<select>` inline?** Touch devices cannot programmatically open native `<select>` dropdowns. The modal overlay pattern lets the user tap an option list reliably.

### 4.8 Editable Cells

Mark editable cells with a pencil icon that appears on hover:

```css
.editable-cell {
  cursor: pointer;
}
.editable-cell::after {
  content: "\270E";
  position: absolute;
  top: 2px; right: 3px;
  font-size: 10px;
  color: #adb5bd;
  opacity: 0;
  transition: opacity 0.15s;
}
.editable-cell:hover::after { opacity: 1; }
```

---

## 5. Touch & Mobile Guidelines

| Rule | Value | Rationale |
|------|-------|-----------|
| Minimum touch target | `44px` height | Apple HIG / WCAG 2.5.8 |
| Form input min-height | `48px` | Comfortable tap target |
| Input font-size | `≥ 16px` | Prevents iOS auto-zoom |
| Viewport meta | Always include | Enables responsive layout |
| Responsive column hiding | `.col-hide-md` (< 992px), `.col-hide-sm` (< 768px) | Collapse non-critical columns |
| Select elements | Use modal overlay, not inline `<select>` | Touch devices can't open selects programmatically |

---

## 6. Form Controls

```css
.form-control, .form-select {
  border-radius: 0.375rem;
  border: 1.5px solid #dee2e6;
  min-height: 44px;
  font-size: 16px;
}
.form-control:focus, .form-select:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
```

---

## 7. AJAX Patterns

### Report Loading (fetch API)

For pages that generate reports dynamically without a full page reload:

```javascript
form.addEventListener('submit', function(e) {
  e.preventDefault();
  const params = new URLSearchParams(new FormData(form));
  params.append('generate_report', '1');

  fetch('same_page.php?' + params.toString())
    .then(response => response.text())
    .then(html => {
      document.getElementById('report-content').innerHTML = html;
      document.getElementById('report-container').classList.add('show');
    });
});
```

> **Single-file pattern:** The same PHP file handles both the form page (normal load) and the report data (when `?generate_report=1` is set). The report path calls `exit` after output to prevent the form HTML from being appended.

### Data Saving (jQuery AJAX)

For inline cell editing:

```javascript
$.ajax({
  url: "update_endpoint.php",
  type: "POST",
  dataType: "json",
  data: { id: carId, field: fieldName, value: newValue },
  success: function(response) {
    // Flash cell green, update displayed value
  }
});
```

---

## 8. Print CSS

### Page Setup
```css
@page {
  size: landscape;
  margin: 0.3in;
}
```

### Key Rules
```css
@media print {
  /* Hide non-printable elements */
  .navbar, .print-controls, .form-card, .back-btn, .noprint {
    display: none !important;
  }

  /* Reset containers */
  .container { max-width: 100% !important; width: 100% !important; padding: 0 !important; margin: 0 !important; }
  .card { box-shadow: none; border: none; }
  .card-body { padding: 0 !important; }

  /* Monospace for dot-matrix look */
  body { font-family: "Courier New", monospace; font-size: 6pt; }

  /* Table headers repeat on each page */
  .report-table thead { display: table-header-group; }
  .report-table th {
    font-weight: bold !important;
    color: #000 !important;
    background-color: transparent !important;
    border: 1px solid #000;
    font-size: 5pt;
    position: static;  /* remove sticky in print */
  }

  /* Table fits page width */
  .report-table { width: 100%; table-layout: fixed; font-size: 6pt; }
  .report-table td { border: 1px solid #000; padding: 1px 2px; }

  /* Rows don't break across pages */
  .report-table tbody tr { page-break-inside: avoid; }

  /* Status badges: plain text in print */
  .status-empty, .status-loaded, .status-loading,
  .status-unloading, .status-ordered, .status-unavailable {
    background-color: transparent !important;
    color: #000 !important;
    padding: 0 !important;
    font-weight: normal;
    font-size: 6pt;
  }

  /* Preserve bold and underline formatting */
  .destination-highlight { font-weight: bold; }
  /* Do NOT override u { text-decoration: none } */
}
```

### Print Checklist
- [ ] `@page` size set to `landscape` (or `portrait` if appropriate)
- [ ] Navbar, buttons, form controls hidden via `.noprint` or explicit rules
- [ ] Table headers repeat via `display: table-header-group` on `<thead>`
- [ ] `position: sticky` overridden to `static` in print
- [ ] Status badges reset to plain text
- [ ] Bold and underline formatting preserved (do not override `<strong>`, `<u>`)
- [ ] Font family set to `"Courier New", monospace`
- [ ] Table uses `table-layout: fixed; width: 100%` to fit page

---

## 9. File Architecture

### Single-File Report Pattern

Reports that previously used two files (display + printable) are now consolidated into a single file:

```
same_page.php
├── PHP: if ($_GET['generate_report']) → output report HTML → exit
└── PHP: else → output full page with form + empty report container
```

The form submits via `fetch()` to itself with `?generate_report=1`. The response HTML is injected into `#report-content`. The `#report-container` is toggled visible via a `.show` class.

### Page Structure Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STS - Page Title</title>
  <!-- Bootstrap CSS + Icons -->
  <style>/* Page-specific styles */</style>
</head>
<body>
  <nav class="navbar ..."><!-- Nav --></nav>
  <div class="container mt-4">
    <!-- Form / Controls -->
    <!-- Report / Data Output -->
  </div>
  <!-- Bootstrap JS + jQuery -->
</body>
</html>
```

---

## 10. Do's and Don'ts

### Do
- ✅ Use Bootstrap 5 utility classes (`mt-4`, `btn-sm`, `d-flex`, etc.)
- ✅ Use Bootstrap Icons (`bi bi-printer`, `bi bi-house`, etc.)
- ✅ Apply `htmlspecialchars()` to all user-facing database output
- ✅ Use `position: sticky` on `<th>` elements (not `<thead>`)
- ✅ Set `background-color` on sticky `<th>` to match `<thead>` colour
- ✅ Use `!important` on print-specific overrides where Bootstrap conflicts
- ✅ Use the modal overlay pattern for touch-editable fields
- ✅ Keep print font sizes ≤ 7pt to fit landscape pages with many columns
- ✅ Consolidate display + printable into a single-file AJAX pattern
- ✅ Use `set_colors.php` / `set_colors()` for location-based highlighting

### Don't
- ❌ Wrap tables in `.table-responsive` if sticky headers are needed (overflow breaks sticky)
- ❌ Set `overflow-x: auto` on any ancestor of a sticky-header table (creates a new scroll container, breaking `position: sticky`)
- ❌ Use inline `<select>` for touch-editable fields (won't open on tap)
- ❌ Override `<u>` or `<strong>` formatting in print CSS
- ❌ Use `font-size` below `16px` on form inputs (causes iOS zoom)
- ❌ Apply `position: sticky` to `<thead>` (doesn't work reliably)
- ❌ Forget `display: table-header-group` on `<thead>` in print CSS
- ❌ Use hardcoded colours without referencing this palette
- ❌ Create separate "printable" versions of report pages

---

## 11. Z-Index Scale

| Layer | z-index | Usage |
|-------|---------|-------|
| Table sticky headers | `10` – `50` | `<th>` with `position: sticky` |
| Cell editor overlay | `9999` | Modal backdrop for inline editing |

---

## 12. Existing Utility Files

| File | Purpose |
|------|---------|
| `open_db.php` | Database connection (MySQLi) |
| `set_colors.php` | Returns inline style string for location-based cell colouring |
| `show_image.php` | JavaScript function for rolling stock photo popups |
| `credentials.php` | Database credentials |
| `drop_down_list_functions.php` | Shared dropdown/select generation helpers |
| `get_dropdowns_ajax.php` | AJAX endpoint returning car codes, locations, jobs as JSON |

---

*Last updated: 2026-02-27*
*Derived from: `feat/report_ui`, `feat/car_db_ui_refresh` branches*
