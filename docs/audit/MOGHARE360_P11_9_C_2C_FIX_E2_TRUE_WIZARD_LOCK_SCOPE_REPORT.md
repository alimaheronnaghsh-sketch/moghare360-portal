# MOGHARE360 P11.9-C-2C-FIX-E2 — True Wizard + Post-Signature Lock Scope Report

**Phase:** P11.9-C-2C-FIX-E2  
**Date:** 2026-07-05  
**Status:** Scope gate PASSED — implementation permitted without schema change

---

## 1. Why FIX-E / FIX-E1 Remain Accordion/Tomar-Like

FIX-E introduced an 8-step stepper with `active_step` routing. FIX-E1 improved OTP flash handling and hid inactive step **forms** behind `if ($activeStep === 'step')` guards, but still:

- Rendered **all step panels** in the DOM via `m360_rw_intake_step_panel_open()` / `close()`.
- Rendered **compact summary cards** for every inactive step below the active step (`m360_rw_intake_render_step_compact_summary()`).
- Exposed **clickable `<a>` navigation** in `m360_rw_intake_render_stepper_nav()` for every step regardless of completion order.

This produces vertical scroll through collapsed/compact cards — owner UAT correctly classifies this as tomar/accordion UX, not a wizard page.

---

## 2. Controls Allowing Free Forward/Back Navigation (Pre-E2)

| Control | Location | Issue |
|---------|----------|-------|
| Stepper nav links | `m360_rw_intake_render_stepper_nav()` | Every step clickable via GET `active_step` |
| `m360_rw_intake_resolve_active_step()` (E1) | Helper | Honored any valid `active_step` from query |
| Section edit links | `edit_section` / section headers | Direct jump to section |
| Footer nav | `m360_rw_intake_render_stepper_footer_nav()` | Previous/next links |

---

## 3. Sections Editable After Completion (Pre-E2)

Section UI state (`m360_rw_intake_section_ui_state`) allowed re-edit via `edit_section` query on any completed section. No post-signature lock existed; `save_reception_confirmation` could run without binding customer electronic approval to an immutable snapshot.

---

## 4. Legally/Customer-Sensitive Data After Electronic Signature

After signature + final receptionist confirmation, these become sensitive and must not be directly editable:

- Customer mobile / OTP verification state
- Vehicle identity (plate, brand, model, mileage, fuel)
- Condition notes (belongings, damage, initial condition)
- Service classification and fault path decision
- Referral assignment
- Six reception photos
- Diagnostic PDF, contract approval, cost agreement
- Customer approval + receptionist confirmation metadata

---

## 5. Payload Storage Capability (No Schema Change)

Existing `request_payload_json.reception_intake` can store:

| Field | Supported |
|-------|-----------|
| `intake_lock.status` | Yes |
| `intake_lock.locked_at` | Yes |
| `intake_lock.locked_by` | Yes |
| `customer_signature.status` | Yes |
| `customer_signature.signed_at` | Yes |
| `receptionist_final_confirmation` | Yes (nested object + legacy top-level flag) |

All are JSON payload keys under existing `reception_intake` namespace.

---

## 6. History / Audit for Post-Lock Amendment Attempts

`m360_online_req_write_history()` with prefix `RECEPTION_INTAKE_SAVE_` already exists. E2 adds:

- `RECEPTION_INTAKE_SAVE_LOCK_BLOCKED` on guarded save rejection
- `RECEPTION_INTAKE_SAVE_SIGN_AND_LOCK` on successful lock

No new table required.

---

## 7. Schema Change Required?

**No.**

---

## 8. STOP Condition

Not triggered. Lock and wizard navigation can be implemented safely in payload + PHP guards only.

---

## 9. No-Schema Implementation Plan

1. Replace 8-step accordion stepper with **9 wizard steps** (`signature`, `locked_summary`).
2. Render **only one operational wizard page** per request (switch on `active_step`).
3. Replace clickable stepper nav with **non-clickable progress indicator** (`m360_rw_intake_render_wizard_progress`).
4. Restrict GET navigation: furthest incomplete step only; controlled amend via `wizard_edit=1` before signature.
5. Add `sign_and_lock_intake` POST action with customer + receptionist checkboxes.
6. Persist `intake_lock`, `locked_snapshot`, `customer_signature` in payload.
7. Add `m360_rw_intake_assert_not_locked()` guard on all mutating save actions.
8. Amendment UI: disabled placeholder button only.
9. CSS: compact wizard card, no stacked inactive panels.

---

## 10. Confirmations — Out of Scope / Unchanged

| Area | Change Required |
|------|-----------------|
| Auth / Login | No |
| `staff-auth.php` | No |
| `access-control.php` | No |
| Roles / permissions | No |
| DB schema / SQL migrations | No |
| OTP architecture / IPPanel | No |
| Automatic JobCard | No |
| C-2D | No |

---

**Scope gate result:** PROCEED with payload-only true wizard + post-signature lock.
