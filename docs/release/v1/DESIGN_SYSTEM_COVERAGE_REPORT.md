# MOGHARE360 V1 — Design System Coverage Report (Phase 4)

**Status:** COMPLETE  
**Branch:** `feature/workshop-service-sales`  
**Canonical token source:** `public_html/assets/moghare360-ui/moghare360-design-tokens.css`  
**Loader:** `public_html/includes/m360-design-system-helper.php`  
**Ops entry CSS:** `public_html/assets/css/m360-design-system.css`  
**Ops shell (token-bound):** `public_html/assets/css/m360-operational-shell.css`

## Canonical selection

| Candidate | Role | Decision |
|-----------|------|----------|
| `moghare360-design-tokens.css` | Owner-global tokens | **CANONICAL** |
| `moghare360-components.css` | Buttons, forms, tables, badges | KEEP — consumes tokens |
| `m360-design-system.css` | Control heights, density, drawer, responsive | KEEP — Phase 4 entry |
| `m360-operational-shell.css` | Ops nav/strip | KEEP — rewritten to tokens |
| `moghare360-v1-luxury-ui.css` | Public/staff dark skin | **SKIN ONLY** — aliases `--m360-public-*` / font from tokens |
| Competing page-local `font-family` | Page overrides | Forbidden except PDF/print |

## Owner-global token coverage

| Token group | Present |
|-------------|---------|
| Persian font family | `--m360-font-family-persian` / `--m360-font-family-sans` |
| Base font size | `--m360-font-size-base` |
| Headings | `--m360-font-size-h1` … `h4` |
| Colors | primary / accent / semantic / neutral / public skin |
| Border radius | `--m360-radius-*` |
| Spacing | `--m360-space-*` |
| Control heights | `--m360-control-height*` |
| Table density | `--m360-table-cell-pad-*` / compact |
| Drawer width | `--m360-drawer-width*` |
| Status badges | `--m360-badge-*` |
| Shadows | `--m360-shadow-*` |

## Migrated load paths

| Surface | How tokens load |
|---------|-----------------|
| `mirror_render_head()` | tokens → mirror.css → luxury skin |
| Access management head | tokens → mirror → luxury |
| Reception helper head | tokens → luxury |
| Operational boards/details using shell | `m360_operational_shell_render_stylesheets()` → tokens + components + design-system + ops-shell |
| Inventory controlled helpers | `m360_ds_render_stylesheets('ops')` + luxury |
| Existing Mission helpers already linking tokens | unchanged (already canonical) |

## Operational pages wired to design-system stack

- `erp-reception-jobcards.php`
- `erp-reception-jobcard-detail.php`
- `erp-intake-contracts.php`
- `erp-technical-board.php`
- `erp-technical-jobcard-detail.php`
- `erp-work-execution-board.php`
- `erp-work-execution-detail.php`
- `erp-estimate-board.php`
- `erp-estimate-detail.php`
- `erp-qc-board.php`
- `erp-qc-detail.php`
- `erp-final-invoice-detail.php`
- `erp-settlement-detail.php`
- `erp-jobcard-timeline.php`
- Workshop service sales pages via `mirror_render_head()` (entry/pricing/summary)

## Responsive checks (CSS breakpoints)

| Width | Rule location |
|-------|----------------|
| 1600 | `m360-design-system.css` |
| 1366 | sidebar token |
| 1024 | drawer / content max |
| 768 | control / actions stack |
| 390 | compact padding |

## Preserved

- Business logic and PHP handlers unchanged
- Existing JS / DOM class selectors for ops shell preserved
- No competing second token file created
- Focused operational layouts preferred (`m360-ops-focus`); no new long-scroll tomari pages

## Follow-ups (non-blocking)

- Remaining staff pages that only link luxury without tokens can adopt `m360_ds_render_stylesheets('mirror')` incrementally
- Page-local inline colors on some boards remain visual only; gradually replace with badge/button classes
