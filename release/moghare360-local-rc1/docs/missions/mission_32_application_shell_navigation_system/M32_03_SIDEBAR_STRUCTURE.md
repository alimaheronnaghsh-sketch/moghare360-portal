# Sidebar Structure

## Sections (RTL)
| Section key | Label |
|-------------|-------|
| core | هسته عملیات |
| operations | عملیات تعمیرگاه |
| finance | مالی |
| delivery | کیفیت و تحویل |

## Sidebar Parts
- Brand block (logo M3 + title)
- Navigation sections with module links
- Footer (role label placeholder)
- Collapse on desktop (icon-only)
- Slide-in on mobile

## Active State
- Class `is-active` on current module link
- Server-set on render; JS reinforces on click

## Icons
Text abbreviations (DB, JC, SO, etc.) — no external icon font in M32

## Final Sidebar Decision
Grouped RTL sidebar with section headers and role-filtered module list.
