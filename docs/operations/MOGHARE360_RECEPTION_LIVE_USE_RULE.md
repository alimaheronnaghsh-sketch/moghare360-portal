# MOGHARE360 — Reception Live Use Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Reception Intake Responsibilities

| Task | Owner |
|------|-------|
| Greet customer; verify identity | Reception |
| Open or locate customer record | Reception |
| Register or bind vehicle | Reception |
| Initiate service contract | Reception / service advisor |
| Capture 6 input photos | Reception (camera direct) |
| Create JobCard | Reception |
| Record customer acceptance | Reception / advisor |

---

## Customer Validation Dependency

| Requirement | Phase 17 rule |
|-------------|---------------|
| Persian name | `MOGHARE360_PERSIAN_NAME_VALIDATION_RULE.md` |
| National ID | `MOGHARE360_NATIONAL_ID_VALIDATION_RULE.md` |
| Mobile | `MOGHARE360_MOBILE_VALIDATION_RULE.md` |
| Channel / class dropdowns | `MOGHARE360_DROPDOWN_CASCADING_SELECT_RULES.md` |

Reception **must not save** invalid customer data — Validation Engine blocks (E-02).

---

## Vehicle Validation Dependency

| Requirement | Phase 17 rule |
|-------------|---------------|
| Segmented plate | `MOGHARE360_IRANIAN_PLATE_VALIDATION_RULE.md` |
| VIN | `MOGHARE360_VIN_VALIDATION_RULE.md` |
| Brand / model cascade | Dropdown rules |
| Engine/chassis | If required by policy |

---

## Contract Dependency

| Requirement | Phase 19 rule |
|-------------|---------------|
| Contract draft from template | `MOGHARE360_CONTRACT_TEMPLATE_CONTROL_RULE.md` |
| Authorization level selected | `MOGHARE360_CUSTOMER_AUTHORIZATION_LEVEL_RULE.md` |
| Cost ceiling set (if applicable) | `MOGHARE360_COST_CEILING_RULE.md` |
| Storage terms if vehicle stays | `MOGHARE360_SLEEP_STORAGE_TERMS_RULE.md` |
| Customer acceptance before paid work | `MOGHARE360_CUSTOMER_ACCEPTANCE_RECORD_RULE.md` |

**Contract authorization controls operation** — reception cannot skip contract for chargeable intake.

---

## Input Photo Dependency

| Requirement | Phase 18 rule |
|-------------|---------------|
| Exactly 6 input photos | `MOGHARE360_INPUT_PHOTO_6_RULE.md` |
| Camera direct only | `MOGHARE360_CAMERA_ONLY_CAPTURE_RULE.md` |
| Bound to JobCard | `MOGHARE360_JOBCARD_MEDIA_BINDING_RULE.md` |

Gate before technical workflow starts.

---

## JobCard Creation Dependency

| Requirement | Rule |
|-------------|------|
| Valid customer + vehicle refs | FK validation |
| Contract ref (path policy) | Phase 19 binding |
| JobCard type / service category | Dropdown — Critical Forms v2 |
| Starting state | **DRAFT** per workflow contract |

Per `MOGHARE360_JOBCARD_LIVE_ENTRY_RULE.md`.

---

## No Bypass Rule

| Bypass | Status |
|--------|--------|
| Skip validation | FORBIDDEN |
| Skip contract | FORBIDDEN (except inspection-only level) |
| Skip input photos | FORBIDDEN without manager exception |
| Paper JobCard without later ERP entry | FORBIDDEN as permanent state |

---

## Manual Fallback If System Unavailable

| Step | Action |
|------|--------|
| 1 | Notify manager/owner |
| 2 | Use paper fallback form — `MOGHARE360_MANUAL_FALLBACK_PROTOCOL.md` |
| 3 | Minimum fields: customer name, mobile, plate, intake time, complaint |
| 4 | Photos on device — transfer to ERP when system restored |
| 5 | Log `manual_fallback_usage` in daily error log |
| 6 | Enter into ERP within owner-defined SLA (e.g. same day) |

---

## Audit Requirement

All reception writes → `erp_customer_core_history`, JobCard history, contract audit events per Phase 19.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF RECEPTION LIVE USE RULE**
