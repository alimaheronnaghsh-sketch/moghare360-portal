# Responsive Layout Rules

## Desktop (>1024px)
- Sidebar visible in grid
- Desktop collapse toggles `is-sidebar-collapsed` (72px width)
- Topbar toggle visible for collapse

## Tablet/Mobile (≤1024px)
- Sidebar off-canvas (translateX)
- `is-mobile-sidebar-open` shows sidebar
- Overlay backdrop closes sidebar
- Escape key closes sidebar
- Body scroll locked when open

## Content
- Padding reduces on mobile
- Card grids auto-fill min 240px
- KPI grid collapses per M31 breakpoints

## Final Responsive Decision
Mobile overlay sidebar; desktop collapsible narrow sidebar.
