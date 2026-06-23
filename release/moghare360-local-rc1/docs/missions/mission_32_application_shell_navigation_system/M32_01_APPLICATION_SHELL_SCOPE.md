# Application Shell Scope

## In Scope
- Shell CSS (sidebar, topbar, content, responsive)
- Shell JS (toggle, overlay, active highlight — UI only)
- PHP render helper (read-only menu visibility placeholder)
- Demo dashboard page with role querystring

## Out of Scope
- Login production change
- Auth architecture change
- Permission model change
- Core DB schema change
- SQL / database write
- Operational PHP workflow modification
- Legacy portal change
- Production deploy

## Role Modes (Demo Only)
owner, service, reception, finance, qc

## Final Scope Decision
Mission 32 = application shell + navigation UI layer only.
