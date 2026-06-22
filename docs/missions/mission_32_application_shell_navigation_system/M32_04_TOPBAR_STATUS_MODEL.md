# Topbar Status Model

## Topbar Components
- Sidebar toggle button
- Page title + breadcrumb
- Status pills: Soft Run Prototype, Local Only
- User chip placeholder: user_id 10001

## Sticky Behavior
- position: sticky; top: 0
- z-index: toolbar tier

## Status Pills
| Class | Meaning |
|-------|---------|
| is-soft-run | Green tint — prototype environment |
| is-prototype | Amber tint — local only |

## User Placeholder
- Avatar initial "U"
- Text: user_id 10001 placeholder
- No real auth session read in M32

## Final Topbar Decision
Informational status + placeholder user; no auth integration.
