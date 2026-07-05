# MOGHARE360 P11.9-C-2C-FIX-E — Stepper UX Scope Gate Report

**Phase:** P11.9-C-2C-FIX-E (Reception Intake Single-Page Stepper UX)  
**Date:** 2026-07-04  
**Status:** SCOPE_APPROVED — UX-only; no DB/Auth/schema change required

---

## 1. Current Section List and Action List (Pre-FIX-E)

| Section ID | Persian label | POST action_type |
|---|---|---|
| mobile_otp | موبایل و OTP | save_mobile_correction, send_customer_otp, verify_customer_otp |
| vehicle_identity | خودرو و پلاک | save_vehicle_identity |
| condition_notes | وضعیت خودرو | save_condition_notes |
| service_classification | دسته‌بندی خدمات | save_service_classification |
| temporary_reception | پذیرش موقت | save_temporary_reception |
| referral | ارجاع تیم | save_referral_team |
| camera_photo | عکس‌های پذیرش | save_camera_photo |
| diagnostic_pdf | PDF دیاگ | save_diagnostic_pdf |
| contract | قرارداد | run_intake_contract, approve_intake_contract |
| documents_cost | مستندات و توافق | save_documents_and_cost |
| reception_confirmation | تأیید نهایی | save_reception_confirmation |
| gate_checklist | چک‌لیست Gate | (read-only) |

---

## 2. Why the Page Jumps to Top

1. **Full-page POST redirects** without `active_step` or hash — browser reloads at document origin.
2. **All sections rendered open** — operator loses scroll context after redirect.
3. **Legacy `edit_section` lock** hid OTP verify form behind «ویرایش» when mobile-only was marked complete.
4. **No sticky step navigation** — flash messages appear in header, pulling viewport to top.

---

## 3. Redirects That Did Not Preserve Active Step (Pre-FIX-E)

- Saves used section anchors (`#section-vehicle-identity`) inconsistently.
- `send_customer_otp` did not set `otp_sent=1` visibility cue on return.
- Photo saves did not stay on photos step until 6/6.
- Documents saves did not advance to `final` when documents step complete.

**FIX-E resolution:** `m360_rw_intake_save_redirect_url()` now always emits `active_step` + step hash (`#step-*`).

---

## 4. Sections Too Long / Always Open (Pre-FIX-E)

- All 11+ section blocks visible simultaneously (~600+ lines of form HTML).
- Raw online payload dump always expanded in vehicle area.
- Service taxonomy tree always visible.
- Management business-purpose text always in main flow.

---

## 5. Actions Hard to Find (Pre-FIX-E)

- Send OTP / Verify OTP buried mid-page after customer/vehicle recovery blocks.
- Save buttons scattered across sections without stage context.
- Gate checklist at page bottom only.
- Photo camera controls far below OTP/vehicle blocks.

---

## 6. How OTP Input Got Hidden After Send OTP

- `m360_rw_intake_section_is_complete('mobile_otp')` returned true when mobile existed, even if `otp_verified=false`.
- `m360_rw_intake_section_ui_state()` set `show_form=false` for complete non-editing sections.
- Operator had to click «ویرایش» (`edit_section=mobile_otp`) to reveal verify form.

**FIX-E resolution:** mobile_otp completion requires OTP verified; verify form rendered outside edit lock on active OTP step.

---

## 7. Proposed Step Order

| Step | Key | Label |
|---|---|---|
| 1 | otp | موبایل/OTP |
| 2 | vehicle | خودرو |
| 3 | condition | وضعیت |
| 4 | service | خدمات |
| 5 | referral | ارجاع |
| 6 | photos | عکس |
| 7 | documents | مستندات |
| 8 | final | نهایی |

---

## 8. Exact Files to Modify

| File | Change |
|---|---|
| `public_html/erp-reception-intake-file.php` | Single-page stepper UI, collapsed technical details |
| `public_html/erp-reception-intake-save.php` | (already passes conn for redirect) |
| `public_html/includes/m360-reception-workbench-helper.php` | Stepper helpers, redirect map, OTP completion fix |
| `public_html/assets/js/m360-reception-intake.js` | Scroll-to-active-step enhancement |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Stepper layout, hide inactive panels |
| `tools/test-p11-9-c-2c-fix-e-*.php` | Five new regression tests |
| `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E_STEPPER_UX_*.md` | Scope + final reports |

**Forbidden (not modified):** staff-auth.php, access-control.php, OTP architecture, IPPanel auth, DB schema, SQL migrations, private config.

---

## 9. Scope Gate Confirmation

| Constraint | Required? | Status |
|---|---|---|
| DB schema change | NO | ✅ No migration |
| Auth/Login change | NO | ✅ Untouched |
| staff-auth.php | NO | ✅ Untouched |
| access-control.php | NO | ✅ Untouched |
| OTP architecture change | NO | ✅ Wiring preserved |
| OTP fake/bypass | NO | ✅ Not introduced |
| Automatic JobCard | NO | ✅ Not introduced |
| C-2D scope | NO | ✅ Not started |

**SCOPE_GATE: PASS — proceed with UX-only stepper implementation.**
