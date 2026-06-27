# Layout Rules

## App Shell Placeholder (Locked)
```
m360-app-shell
  m360-app-sidebar (grid area sidebar)
  m360-app-toolbar (grid area toolbar)
  m360-app-main (grid area main)
```

## Dimensions
- Sidebar width: 260px (hidden on mobile)
- Toolbar height: 56px, sticky
- Main padding: space-6 (space-4 on mobile)

## Page Structure
1. Demo banner (prototype only)
2. App shell
3. Content container
4. Page header
5. Page toolbar
6. Module sections

## Page Toolbar
- `m360-page-toolbar` — title area + `m360-toolbar-actions`
- Actions reverse in RTL

## Responsive Breakpoints
- 1024px: hide sidebar, single column two-column layout
- 640px: single column grids, stacked toolbar

## Mobile-Friendly
- Overflow-x auto on main
- Table wrap for horizontal scroll
- Full-width buttons in stacked toolbar

## Final Layout Decision
CSS grid app shell; responsive collapse; no JS required for M31 demo.
