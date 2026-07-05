# MOGHARE360 P11.9-C-2C-FIX-E5 — Photo Completion Scope Report

## 1. Current Photo UI Field Names

- `photo_slot` — canonical slot key (`front`, `rear`, `right`, `left`, `cabin`, `dashboard`)
- `camera_image_base64` — base64 camera capture per slot
- `action_type=save_camera_photo`
- Wizard step key: `photos` (`#step-photos`)

## 2. Current save_camera_photo Behavior (pre-fix)

Saved one slot into `reception_intake.documents.reception_photos[slot]` with `file`, `label`, `saved_at`. Recalculated `photo_count` / `photo_status` from legacy structure only. Did not write canonical `reception_intake.photos`.

## 3. Payload Keys Written for Photos (pre-fix)

- `reception_intake.documents.reception_photos.{slot}.file`
- `reception_intake.documents.photo_count`
- `reception_intake.documents.photo_status`
- Top-level `photo_status`

Legacy single key: `reception_intake.documents.photo_file` (not sufficient for 6/6).

## 4. Wizard Completion Logic for Photos (pre-fix)

`m360_rw_intake_wizard_step_is_complete('photos')` used `m360_rw_intake_reception_photo_status()['complete']` counting files in `documents.reception_photos` only.

## 5. Gate Logic for Photos

`m360_rw_build_gate()` uses `m360_rw_intake_reception_photo_status()` → `complete` flag for gate photo field.

## 6. Furthest Incomplete Step Logic

`m360_rw_intake_wizard_furthest_operational_step()` walks operational keys; returns first step where `m360_rw_intake_wizard_step_is_complete()` is false.

## 7. Root Cause — Why User Returned to Photo Step

**Primary bug:** `save_documents_and_cost` replaced the entire `reception_intake.documents` object:

```php
$payload['reception_intake']['documents'] = [ 'photo_status' => ..., ... ];
```

This **wiped** `reception_photos`, `diagnostic_pdf`, and all photo slot data when user saved documents/cost after completing photos. Furthest-step logic then recomputed photos as incomplete → redirect loop back to `photos`.

Secondary: no canonical `reception_intake.photos` object; wizard/gate relied on nested documents structure vulnerable to overwrite.

## 8. Photo State Classification

| Issue | Verdict |
|-------|---------|
| Not saved | No — saves worked until documents step |
| Overwritten | **Yes** — by `save_documents_and_cost` full replace |
| Saved but not counted | Partial — legacy count OK until wipe |
| Counted but not read | No |
| Stale legacy key | `photo_file` alone never passed 6/6 |
| Invalidated by later step | **Yes** — documents save |

## 9. Files Modified

- `public_html/includes/m360-reception-workbench-helper.php` — canonical photos, save fix, wizard/gate/lock reads, UI render
- `public_html/erp-reception-intake-file.php` — (unchanged; uses helper render)
- `tools/test-p11-9-c-2c-fix-e5-*.php` — six new regression tests
- `tools/test-p11-9-c-2c-fix-c-photo-six.php` — slot label alignment
- `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E5_PHOTO_COMPLETION_*.md`

## 10. OTP / Auth / DB Confirmation

- No OTP file changes (`m360-otp-helper.php`, `m360-otp-config-loader.php` untouched)
- No Auth/Login/staff-auth/access-control changes
- **No DB schema change required** — photos persist in existing `request_payload_json`
- No SQL migration

**Scope gate: PROCEED — no schema migration needed.**
