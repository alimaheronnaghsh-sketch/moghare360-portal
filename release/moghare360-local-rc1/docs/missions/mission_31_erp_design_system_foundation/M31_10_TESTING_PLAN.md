# Testing Plan

## Manual Test Steps
1. Open `public_html/assets/moghare360-ui/moghare360-demo.html` in browser (local XAMPP or file preview)
2. Verify RTL layout — Persian text right-aligned
3. Verify KPI cards, badges, entity cards render
4. Verify table, form, alerts, timeline, empty state, diagnostic block
5. Resize window — mobile layout collapses sidebar and grids
6. Confirm no PHP errors (static HTML only)
7. Confirm no network calls to database

## Pass Criteria
- All demo sections visible
- Colors match industrial navy/gold palette
- Numerals readable in KPI and table
- No forbidden files modified

## Signoff
Update M31_90 to PASSED and M31_99 after user confirms.
