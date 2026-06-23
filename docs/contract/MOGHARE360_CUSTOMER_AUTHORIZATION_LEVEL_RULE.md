# MOGHARE360 — Customer Authorization Level Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

**Customer authorization level** defines how much operational work technicians may perform without additional customer contact or manager escalation.

---

## Authorization Levels

### Level 1 — Inspection Only

| Aspect | Rule |
|--------|------|
| **Meaning** | Diagnose and inspect only — no repair or part replacement |
| **Allowed operation** | Visual inspection, diagnostic read, test drive, Initial Diagnostic PDF |
| **Forbidden operation** | Parts install, paid labor, disassembly beyond inspection access |
| **Workflow dependency** | JobCard may reach SUBMITTED; operations limited to inspection ops |
| **Audit requirement** | `authorization_level_inspection_only` on contract apply |

---

### Level 2 — Repair Up to Ceiling

| Aspect | Rule |
|--------|------|
| **Meaning** | Authorized repair within customer-approved **cost ceiling** |
| **Allowed operation** | Services listed in contract authorized services within ceiling |
| **Forbidden operation** | Work above ceiling; services not in contract list |
| **Workflow dependency** | Operations Engine checks running total vs ceiling before each chargeable step |
| **Audit requirement** | `authorization_level_repair_to_ceiling`; `ceiling_check_pass/fail` |

---

### Level 3 — Repair After Approval

| Aspect | Rule |
|--------|------|
| **Meaning** | No repair until explicit customer approval recorded (even below ceiling) |
| **Allowed operation** | Inspection; repair only after acceptance record for proposed scope |
| **Forbidden operation** | Any chargeable labor before acceptance event |
| **Workflow dependency** | Block APPROVED→APPLIED operations without acceptance |
| **Audit requirement** | `authorization_level_repair_after_approval` |

---

### Level 4 — Emergency Approval Required

| Aspect | Rule |
|--------|------|
| **Meaning** | Safety-critical or breakdown — limited emergency work with mandatory post approval |
| **Allowed operation** | Pre-defined emergency ops (e.g. secure vehicle, tow within bay) per owner policy |
| **Forbidden operation** | Full rebuild without manager + customer follow-up approval |
| **Workflow dependency** | Emergency flag on operation; out-of-contract approval within SLA |
| **Audit requirement** | `authorization_level_emergency`; `emergency_work_logged` |

---

### Level 5 — No Work Without Written Approval

| Aspect | Rule |
|--------|------|
| **Meaning** | Zero chargeable or invasive work until written contract acceptance + PDF archived |
| **Allowed operation** | Reception intake, photos, contract draft only |
| **Forbidden operation** | Any workshop labor, parts reservation, diagnostic disassembly |
| **Workflow dependency** | JobCard cannot leave DRAFT for operations until contract APPLIED |
| **Audit requirement** | `authorization_level_written_required`; block events logged |

---

## Selection Control

| Rule | Requirement |
|------|-------------|
| UI control | Dropdown on contract — not free text |
| Default | Owner policy per customer class (e.g. fleet → Level 2) |
| Change mid-JobCard | Out-of-contract approval + new acceptance record |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CUSTOMER AUTHORIZATION LEVEL RULE**
