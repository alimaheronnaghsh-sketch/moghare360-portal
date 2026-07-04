# MOGHARE360 P11.9-C-2C-FIX-C — Scroll Anchor / Section Lock / Six Photos Scope Report

**Date:** 2026-07-04  
**Verdict:** **PROCEED** — UX and payload-only changes; no schema/Auth change

---

## 1. Edit buttons today (pre-FIX-C)

| Section key | Edit via `m360_rw_intake_render_section_header` |
|-------------|--------------------------------------------------|
| mobile_otp | Yes |
| vehicle_identity | Yes |
| referral | Yes |
| camera_photo | Yes (single photo) |
| diagnostic_pdf | Yes |
| contract | Yes |
| condition_notes, service_classification, temporary_reception, documents_cost, reception_confirmation | **No** — forms always open |

Edit URLs lacked `#section-*` hash → browser jumped to top.

---

## 2. Redirect after save (pre-FIX-C)

All save actions used `m360_rw_intake_save_redirect_url($id, $msg, $ok)` **without** fragment or `return_section` → redirect to page top.

---

## 3. Save actions lacking anchor handling

All 13 intake POST actions lacked `return_section` and redirect hash.

---

## 4. Sections open when completed (pre-FIX-C)

Open after completion: condition_notes, service_classification, temporary_reception, documents_cost, reception_confirmation.

---

## 5. Camera/photo storage (pre-FIX-C)

```json
reception_intake.documents.photo_file = "reception-intake/{id}/photo_*.jpg"
reception_intake.documents.photo_status = "ثبت شد"
```

Single image only; gate passed on `photo_status` or legacy ERP media count.

---

## 6. Blueprint six-photo rule

`docs/media/MOGHARE360_INPUT_PHOTO_6_RULE.md` defines **6 required INPUT photos** (Front, Rear, Left, Right, Dashboard, Interior) — JobCard stage, not yet implemented in reception intake.

No reception-intake-specific slot names in code.

---

## 7–8. Slot model for FIX-C

**Default controlled six-slot model** (Persian labels per owner UAT):

| Key | Label |
|-----|-------|
| front | نمای جلو خودرو |
| rear | نمای عقب خودرو |
| right | سمت راست خودرو |
| left | سمت چپ خودرو |
| interior | داخل کابین |
| dashboard | کیلومتر / داشبورد |

Stored under `reception_intake.documents.reception_photos[slot]` + `photo_count` + `photo_min_required = 6`.

Legacy `photo_file` preserved; does **not** satisfy gate alone.

---

## 9. Implementation plan

1. Section anchors `section-*` on all intake blocks  
2. `return_section` hidden on all forms; redirect URL with `#anchor`  
3. Edit links with hash  
4. Section lock on all 11 editable sections  
5. Six-slot camera UI + `photo_slot` validation on save  
6. Gate: photo OK only when 6/6 slots complete  
7. Tests + operator browser UAT  

---

## 10. Constraints confirmed

- No DB schema / SQL migration  
- No Auth/Login / staff-auth / access-control change  
- No OTP bypass / fake OTP  
- No JobCard auto-creation  
- No P12 scope  

**STOP gate:** Not triggered.
