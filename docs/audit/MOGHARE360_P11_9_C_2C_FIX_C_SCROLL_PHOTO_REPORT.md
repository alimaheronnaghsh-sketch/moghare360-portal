# MOGHARE360 P11.9-C-2C-FIX-C — Scroll Anchor / Six Photos Report

**Date:** 2026-07-04  
**Phase:** P11.9-C-2C-FIX-C  
**Verdict:** Automated tests PASS — operator browser validation pending

---

## 1. Scope Gate Result

Scope report: `docs/audit/MOGHARE360_P11_9_C_2C_FIX_C_SCROLL_PHOTO_SCOPE_REPORT.md` — **GO**.

Blueprint reference: `docs/media/MOGHARE360_INPUT_PHOTO_6_RULE.md` (6 INPUT photos). No reception-intake slot code existed; **default six-slot model** applied with Persian labels.

No DB schema, Auth, OTP bypass, or JobCard auto-creation changes.

---

## 2. Scroll Anchor Fix

Stable section IDs on intake page:

- `section-mobile-otp`, `section-vehicle-identity`, `section-condition-notes`, `section-service-classification`, `section-temporary-reception`, `section-referral-team`, `section-camera-photo`, `section-diagnostic-pdf`, `section-contract`, `section-documents-cost`, `section-reception-confirmation`, `section-gate-checklist`

Edit links include `#section-*` hash via `m360_rw_intake_edit_url()`.

JS scrolls to hash on load (`m360-reception-intake.js`).

---

## 3. Save Redirect Fix

- All forms include hidden `return_section` via `m360_rw_intake_return_section_hidden()`.
- `m360_rw_intake_save_redirect_url()` appends `#section-*` on success; on error adds `edit_section` + hash.
- Default anchor per action via `m360_rw_intake_action_default_anchor()`.

---

## 4. Section Lock/Edit Enforcement

Section lock applied to all 11 editable sections including condition_notes, service_classification, temporary_reception, documents_cost, reception_confirmation.

Completed → read-only summary + **ویرایش**; form only when incomplete or `edit_section` matches.

---

## 5. Minimum 6 Photo Requirement

Six slots: front, rear, right, left, interior, dashboard (Persian labels per UAT).

Payload:

```json
reception_intake.documents.reception_photos[slot] = { label, file, saved_at }
reception_intake.documents.photo_count
reception_intake.documents.photo_min_required = 6
```

Legacy `photo_file` preserved; does not satisfy gate alone.

---

## 6. Camera Slot Model

- Per-slot capture forms with `photo_slot` + base64 from getUserMedia.
- `save_camera_photo` validates slot against allowed keys.
- No generic photo file upload on intake page.
- Photo status dropdown removed from documents form (cannot bypass 6-photo gate).

---

## 7. Gate Photo Logic

- Gate photo check uses `m360_rw_intake_reception_photo_status()` — complete only when 6/6 slots filled.
- Checklist label: `عکس پذیرش: N/6`.
- Missing messages: `عکس پذیرش کامل نیست؛ حداقل ۶ عکس الزامی است.` or slot list when partial.
- Legacy single photo: `عکس قدیمی موجود است، اما چک‌لیست ۶ عکس هنوز کامل نیست.`

---

## 8. Tests Passed

| Test | Result |
|------|--------|
| test-p11-9-c-2c-fix-c-scroll-anchor.php | PASS |
| test-p11-9-c-2c-fix-c-section-lock.php | PASS |
| test-p11-9-c-2c-fix-c-photo-six.php | PASS |
| test-p11-9-c-2c-fix-c-scope-security.php | PASS |
| FIX-A / FIX-B / C-2C regressions + signoff | PASS |

---

## 9. Browser Validation Status

**PENDING_OPERATOR**

Validate on XAMPP after copy:

- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18`
- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20`

Check: Edit stays in section, save returns to section, six photo slots, gate 6/6, no jump to top.

---

## 10. What Was Not Changed

- no Auth/Login architecture change
- no permission/role change
- no DB schema change
- no SQL migration
- no workflow architecture change
- no OTP bypass / fake OTP
- no automatic JobCard creation
- no private file change
- no secrets committed
- no P12 scope

---

## 11. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_OPERATOR_BROWSER_PASS**

---

P11.9-C-2C-FIX-C fixes intake section scroll/anchor behavior, enforces completed-section summary/edit mode, and implements the minimum six-photo reception requirement with gate recalculation while preserving Auth/Login, permissions, database schema, workflow, OTP integrity, private files, secrets, and the no-automatic-JobCard boundary.
