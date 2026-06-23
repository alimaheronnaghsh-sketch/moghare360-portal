# PHASE 18 — Media & Diagnostic Capture System — Boundary

**Status:** Planning-only boundary

---

## What This Phase Does

- Locks media capture system architecture and JobCard binding
- Documents camera-only rules, 6 input / 8 output photo rules
- Documents diagnostic PDF stages (initial, secondary, final)
- Documents audit/immutability and local storage boundary
- Records Phase 18 media decision

---

## What This Phase Does NOT Do

| Action | Forbidden |
|--------|-----------|
| Create PHP runtime files | Yes |
| Implement camera capture in runtime | Yes |
| Implement diagnostic upload in runtime | Yes |
| Create upload UI / file input fields | Yes |
| Use free upload | Yes |
| Modify existing form pages | Yes |
| Modify `public_html` | Yes |
| Create SQL / modify schema | Yes |
| Modify auth/login/config/permission | Yes |
| Deploy | Yes |

---

## Runtime Boundary

- All media modules: **PLANNED_NOT_IMPLEMENTED**
- **No upload UI** in Phase 18

---

**END OF BOUNDARY**
