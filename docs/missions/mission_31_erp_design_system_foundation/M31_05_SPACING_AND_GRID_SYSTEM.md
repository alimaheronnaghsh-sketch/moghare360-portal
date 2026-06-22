# Spacing and Grid System

## Spacing Scale (4px base)
| Token | Value |
|-------|-------|
| space-1 | 0.25rem (4px) |
| space-2 | 0.5rem (8px) |
| space-3 | 0.75rem (12px) |
| space-4 | 1rem (16px) |
| space-5 | 1.25rem (20px) |
| space-6 | 1.5rem (24px) |
| space-8 | 2rem (32px) |
| space-10 | 2.5rem (40px) |
| space-12 | 3rem (48px) |
| space-16 | 4rem (64px) |

## Grid Classes
- `m360-grid` — base grid with gap-4
- `m360-grid-2` — 2 columns
- `m360-grid-3` — 3 columns
- `m360-grid-4` — 4 columns (2 on tablet, 1 on mobile)

## Two-Column Layout
- `m360-two-column` — main + 340px sidebar column
- Collapses to single column below 1024px

## Content Width
- `--m360-content-max-width: 1280px`
- `m360-content-container` centers content

## Module Spacing
- `m360-module-section` — margin-bottom space-8
- Section title with accent underline (accent-500)

## Final Grid Decision
4px-based spacing scale; responsive 1–4 column grids; 1280px max content width.
