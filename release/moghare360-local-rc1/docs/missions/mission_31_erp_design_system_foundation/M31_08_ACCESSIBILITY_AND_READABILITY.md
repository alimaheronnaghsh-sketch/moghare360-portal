# Accessibility and Readability

## Readability (Locked)
- Base font size: 1rem (16px)
- Line height: 1.5 body, 1.25 headings
- Contrast: navy on light gray app background
- Semantic colors meet operational distinction (not WCAG audit in M31)

## RTL Readability
- Persian labels right-aligned
- Technical IDs in LTR embed to avoid bidi confusion
- Tabular numerals for amounts and IDs

## Focus States
- Form inputs: 3px focus ring using primary-500 at 15% opacity
- Buttons: hover state color shift

## Diagnostic Safety
- `m360-diagnostic-block` — warning border, no sensitive data in demo
- Demo banner states: no PHP, no DB, no auth change

## Accessibility Notes (Future M32+)
- Add aria-labels when integrating PHP pages
- Sidebar nav aria-label in demo
- Form labels associated with inputs in demo

## Mission 31 Boundary
No formal WCAG audit; foundation rules documented for future integration.

## Final Readability Decision
Persian RTL + LTR technical islands + focus rings + safe diagnostic styling.
