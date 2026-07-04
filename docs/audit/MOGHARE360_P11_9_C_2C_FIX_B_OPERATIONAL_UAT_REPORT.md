# MOGHARE360 P11.9-C-2C-FIX-B — Operational UAT Report

**Date:** 2026-07-04  
**Phase:** P11.9-C-2C-FIX-B (Operational Intake UAT Closure)  
**Verdict:** Automated tests PASS — operator browser validation pending

---

## 1. Scope Gate Result

Scope report: `docs/audit/MOGHARE360_P11_9_C_2C_FIX_B_OPERATIONAL_UAT_SCOPE_REPORT.md` — **GO**.

No database schema change, no SQL migration, no Auth/Login change, no `staff-auth.php` / `access-control.php` change, no OTP bypass, no automatic JobCard creation.

---

## 2. Owner UAT Defects Addressed

| # | Defect | FIX-B response |
|---|--------|----------------|
| 1 | No camera for reception photos | getUserMedia camera section + base64 POST to `storage/reception-intake/{id}/` |
| 2 | No contract run/read/approve | Payload contract run + customer-readable text + explicit approval POST |
| 3 | No diagnostic PDF place | PDF-only upload to `storage/reception-intake/{id}/diagnostic/` |
| 4 | Status-only, no operational tools | Mobile/OTP, plate, camera, PDF, contract, referral sections added |
| 5 | No Send OTP | `send_customer_otp` wired to real `m360_otp_send()` when SMS/dev available |
| 6 | No mobile correction | `save_mobile_correction` updates column + payload; OTP reset |
| 7 | Sections too editable | Section lock/edit model with read-only summary + **ویرایش** |
| 8 | Missing Iranian plate UI | Structured plate widget (2+letter+3+Iran) reused from customer-request pattern |
| 9 | Ugly service classification text | Short main label + collapsed **راهنما** |
| 10 | No team referral | Payload referral with تیم ۱/۲/۳/برق/مکانیک options |

---

## 3. Mobile Correction and OTP Handling

- Section **شماره موبایل و تأیید مشتری** on intake page.
- `save_mobile_correction`: validates `09xxxxxxxxx`, updates `erp_customer_online_requests.mobile` and `request_payload_json`, sets `otp_verified = 0`.
- `send_customer_otp`: calls real `m360_otp_send()` only when `m360_otp_sms_configured()` or localhost dev fallback; otherwise button disabled with message **ارسال OTP نیازمند اتصال پیامک فعال است.**
- No fake OTP success; no `otp_verified=1` write from reception intake save path.

---

## 4. Iranian Plate UI

- Widget: `plate_left_2_digits`, `plate_letter`, `plate_middle_3_digits`, `plate_iran_2_digits`.
- Normalized string (e.g. `12ب345-67`) stored in `vehicle_plate` / `plate` + `plate_parts` object.
- Legacy free-form plate still accepted via fallback `plate` POST field; best-effort prefill from stored normalized plate.
- CSS: `.m360-rw-plate-widget` + existing `.iran-plate-*` pattern; JS preview in `m360-reception-intake.js`.

---

## 5. Section Lock/Edit Model

Sections: `mobile_otp`, `vehicle_identity`, `condition_notes`, `service_classification`, `temporary_reception`, `referral`, `documents_cost`, `camera_photo`, `diagnostic_pdf`, `contract`, `reception_confirmation`.

- Completion derived from saved data + `reception_intake.section_status`.
- Completed → read-only summary + **ویرایش** link (`edit_section` GET, no write).
- Editing → form visible; status **در حال ویرایش**.
- POST save returns to read-only summary.

---

## 6. Camera Photo Handling

- Section **عکس پذیرش خودرو** with browser `getUserMedia`, canvas capture, base64 POST.
- Action `save_camera_photo`: validates JPEG/PNG, max 2MB, stores under `storage/reception-intake/{id}/`.
- No generic file-upload bypass for photos.
- Reference stored in `reception_intake.documents.photo_file`.

---

## 7. Diagnostic PDF Handling

- Section **فایل دیاگ اولیه (PDF)** with `accept="application/pdf"`.
- Action `save_diagnostic_pdf`: extension + MIME check, max 5MB, stores in `storage/reception-intake/{id}/diagnostic/`.
- Reference in `reception_intake.documents.diagnostic_pdf`; no internal path exposed in UI.

---

## 8. Contract Run and Customer Approval

- Section **قرارداد پذیرش و تأیید مشتری**.
- `run_intake_contract`: generates customer-readable template text in payload (pre-JobCard).
- `approve_intake_contract`: requires explicit checkbox POST; no auto-approval.
- When JobCard exists, link to structured P1.5 contract generate remains available.
- Full `erp_intake_contracts` linkage deferred until JobCard conversion phase.

---

## 9. Referral / Team Assignment

- Section **ارجاع کارشناسی / تیم مسئول**.
- Options: تیم ۱، تیم ۲، تیم ۳، تیم برق، تیم مکانیک (payload-only; no `erp_teams` table found).
- Stored in `reception_intake.referral` with `referral_team_id`, `referral_team_label`, `referral_type`, `referral_note`.
- No JobCard `assigned_team_id` assignment in this phase.

---

## 10. Service Classification UI Cleanup

- Main visible: **دسته‌بندی داخلی پذیرش** — برای گزارش مدیریتی و مسیر عملیات.
- Long `M360_RW_SERVICE_CLASS_BUSINESS_PURPOSE_FA` moved to collapsed `<details>راهنما</details>`.

---

## 11. Tests Passed

| Test file | Result |
|-----------|--------|
| `test-p11-9-c-2c-fix-b-operational-uat.php` | PASS |
| `test-p11-9-c-2c-fix-b-plate-ui.php` | PASS |
| `test-p11-9-c-2c-fix-b-otp-mobile.php` | PASS |
| `test-p11-9-c-2c-fix-b-section-lock.php` | PASS |
| `test-p11-9-c-2c-fix-b-contract-diagnostic-camera.php` | PASS |
| `test-p11-9-c-2c-fix-b-referral-team.php` | PASS |
| `test-p11-9-c-2c-fix-b-scope-security.php` | PASS |
| `test-p11-9-c-2c-fix-a-save-validation.php` | PASS (regression) |
| `test-p11-9-c-2c-intake-write-*.php` (4 files) | PASS (regression) |
| `test-p11-9-c-2c-scope-security.php` | PASS (updated action list) |
| `test-v1-production-signoff.php` | PASS (regression) |

---

## 12. Browser Validation Status

**PENDING_OPERATOR**

Operator must validate after XAMPP copy:

- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=18`
- `http://localhost:8080/moghare360/erp-reception-intake-file.php?online_request_id=20`

Checklist per spec section M (mobile, plate, vehicle, service, referral, camera, PDF, contract, section lock, no Fatal/TypeError, no fake OTP, no auto JobCard).

---

## 13. What Was Not Changed

- no Auth/Login architecture change
- no `staff-auth.php` change
- no `access-control.php` change
- no permission/role change
- no DB schema change
- no SQL migration
- no workflow architecture change
- no OTP bypass
- no fake OTP
- no automatic JobCard creation
- no private file change
- no secrets committed
- no P12 scope

---

## 14. Remaining Blockers

| Blocker | Notes |
|---------|-------|
| OTP SMS in production | Send OTP disabled until IPPanel/SMS env configured (unless localhost dev) |
| Structured contract tables | Pre-JobCard uses payload contract; full `erp_intake_contracts` flow requires JobCard |
| DB team linkage | Referral teams are payload-only until `erp_teams` or equivalent exists |
| Operator browser UAT | Required before commit eligibility |

---

## 15. Commit Eligibility

**NOT_ELIGIBLE_UNTIL_OPERATOR_BROWSER_PASS**

---

## 16. Recommended Next Phase

After operator browser PASS on IDs 18 and 20:

1. Copy updated files to XAMPP `moghare360/` and confirm storage directory writable.
2. Optional: wire referral to real team table when schema exists (C-2D+).
3. JobCard conversion / structured contract (explicitly out of FIX-B scope).

---

P11.9-C-2C-FIX-B closes operational UAT gaps in the reception intake workflow by adding mobile correction, safe OTP handling boundaries, Iranian plate UI, section lock/edit behavior, camera/photo handling, diagnostic PDF handling, intake contract run/approval handling, referral/team assignment, and cleaner service classification UI while preserving Auth/Login, permissions, roles, database schema, workflow, OTP integrity, private files, secrets, and the no-automatic-JobCard boundary.
