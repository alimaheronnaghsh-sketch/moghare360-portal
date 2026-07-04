# MOGHARE360 P11.9-C-2C-FIX-B — Operational UAT Scope Report

**Date:** 2026-07-04  
**Verdict:** **PROCEED** — code-only wiring; no schema/Auth change

---

## 1. Camera / Photo

| Finding | Detail |
|---------|--------|
| Existing camera | `erp-jobcard-camera-capture.php` + `moghare360-camera-media-helper.php` (getUserMedia, base64) |
| Storage | `public_html/storage/jobcard-media/` + optional `erp_jobcard_media` |
| Reception today | Payload `photo_status` text only; JobCard link post-conversion |
| **FIX-B plan** | Intake-level camera via getUserMedia → base64 POST → `storage/reception-intake/{online_request_id}/` (filesystem, no new table) |
| Blocker if skipped | JobCard-gated camera remains; placeholder shown honestly |

## 2. Contract

| Finding | Detail |
|---------|--------|
| Tables | `erp_intake_contracts`, signatures, events (P1.5 migration) |
| Helpers | `m360-intake-contract-helper.php`, generate/send/sign routes |
| Gate | Full P1.5 flow requires **JobCard** |
| **FIX-B plan** | Payload contract run/approval pre-JobCard; link to P1.5 when `converted_jobcard_id` exists |
| No fake approval | Explicit POST `approve_intake_contract` only |

## 3. Diagnostic PDF

| Finding | Detail |
|---------|--------|
| Existing | `erp-jobcard-diagnostic-file.php` + `moghare360-diagnostic-file-helper.php` (JobCard-gated) |
| **FIX-B plan** | PDF-only upload to `storage/reception-intake/{id}/diagnostic/` + payload reference |
| Blocker | Without writable storage → controlled message |

## 4. OTP / SMS

| Finding | Detail |
|---------|--------|
| Real mechanism | `m360_otp_send()` + IPPanel when `m360_otp_sms_configured()` |
| Dev fallback | `m360_otp_can_use_dev_code()` on localhost only |
| Reception | Never writes `otp_verified=1` (C-2C design) |
| **FIX-B plan** | `send_customer_otp` when SMS/dev available; disabled state otherwise |
| No fake OTP | No success without real send |

## 5. Mobile correction

| Column | `erp_customer_online_requests.mobile` exists |
| **FIX-B plan** | `save_mobile_correction` updates column + payload; resets `otp_verified` to 0 |
| History | `m360_online_req_write_history` on save |

## 6. Iranian plate UI

| Finding | Detail |
|---------|--------|
| Existing widget | `customer-request.php` + `.iran-plate-widget` CSS |
| Reception | Was single text field |
| **FIX-B plan** | Reuse plate letters + structured fields → `plate_parts` + normalized plate |

## 7. Team assignment

| Finding | Detail |
|---------|--------|
| `erp_teams` | **Not found** |
| `assigned_team_id` | Schema only on jobcards |
| **FIX-B plan** | Controlled local options in payload `reception_intake.referral` |
| Later | Structured DB team linkage in C-2D+ |

## 8. Section lock / edit

| Storage | `reception_intake.section_status` + data presence |
| UX | Read-only summary + **ویرایش**; `edit_section` GET (no write) |

## 9. Implementation plan

1. Extend helper: new save actions, plate parts, mobile, referral, camera/PDF/contract, section status  
2. Intake UI: sections with lock/edit, plate widget, mobile/OTP, camera, PDF, contract, referral  
3. CSS + JS for plate preview and camera  
4. Tests + operator browser UAT  

## 10. Confirmations

- No DB schema migration  
- No Auth/Login/permission/workflow change  
- No OTP bypass/fake  
- No automatic JobCard  
- No C-2D scope  

**GO**
