# MOGHARE360 — HR Documents / Attendance / Payroll Preview Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## HR Document Readiness

| Document type | Requirement |
|---------------|-------------|
| Employment contract | Registered — local archive |
| ID / certifications | Per employee profile rule |
| Policy acknowledgements | Checklist |
| **HR document readiness** | All active staff complete before go-live (owner policy) |

Storage: local laptop server only — not moghareh360.ir.

---

## Attendance Preview

| Element | Rule |
|---------|------|
| **Attendance preview** | Daily in/out planning records |
| Source | Manual entry or future device — not payroll post |
| Overtime flag | Planning field |
| Link to employee | FK |
| **No payroll runtime activation** | LOCKED |

---

## Payroll Preview Only

| Capability | Phase 23 |
|------------|----------|
| **Payroll preview only** | Calculate gross preview from attendance + contract type |
| Deductions | Planning placeholders — not legal filing |
| Payslip | NOT generated officially |
| Bank payroll file | NOT generated |
| **Payroll remains preview/planning unless explicitly approved** | LOCKED |

---

## Overtime / Holiday / Night Work Planning

| Type | Rule |
|------|------|
| **Overtime/holiday/night work planning** | Rate multipliers — owner config (planning) |
| Contract type dependency | Full-time vs part-time rules |
| **Legal/labor rule readiness** | Owner responsible for compliance — ERP documents rates only |

---

## Contract Type Dependency

| Type | Payroll preview basis |
|------|----------------------|
| Full-time | Monthly base + overtime preview |
| Part-time | Hourly × attendance preview |
| Contract | Per contract terms field |
| Apprentice | Owner policy |

Per Phase 17 HR contract type dropdown.

---

## No Payroll Runtime Activation

| Action | Status |
|--------|--------|
| Salary transfer execution | NOT in Phase 23 |
| Tax withholding post | NOT active |
| **No official accounting activation** | E-10 for GL payroll post |

---

## HR Access Boundary

| Data | Access |
|------|--------|
| Attendance | HR + manager |
| Payroll preview | Owner + HR admin |
| Other staff | FORBIDDEN |
| Finance official | NOT linked until future gate |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `attendance_preview_recorded` | employee_id, date |
| `payroll_preview_calculated` | period, total preview |
| `payroll_runtime_blocked` | E-10 |
| `erp_hr_history` | Append |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF HR DOCUMENTS / ATTENDANCE / PAYROLL PREVIEW RULE**
