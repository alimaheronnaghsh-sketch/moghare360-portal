# MOGHARE360 — Customer Acceptance Record Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

**Customer acceptance must be recorded** before contract APPLIED and before scope/cost amendments take effect. No anonymous or undocumented acceptance.

---

## Customer Acceptance Record Types

### In-Person Acceptance

| Aspect | Rule |
|--------|------|
| Meaning | Customer signs or verbally accepts in workshop |
| Evidence | Staff witness + optional scanned signature page (future — **no signature implementation in Phase 19**) |
| Recording | Reception staff records acceptance in ERP |

### Phone-Confirmed Acceptance

| Aspect | Rule |
|--------|------|
| Meaning | Customer confirms scope/ceiling by phone |
| Evidence | Call log reference, caller ID note, staff attestation |
| Recording | Staff enters acceptance with phone confirmation type |

### Message-Confirmed Acceptance

| Aspect | Rule |
|--------|------|
| Meaning | SMS/messaging app confirmation |
| Evidence | Message screenshot stored **locally** — no domain storage |
| Recording | Staff attaches evidence ref + records acceptance |

### Manager-Entered Acceptance After Evidence

| Aspect | Rule |
|--------|------|
| Meaning | Manager records acceptance when evidence reviewed offline |
| Evidence | Owner-approved evidence bundle on local server |
| Recording | Manager role required; dual audit |

---

## Evidence Requirement

| Type | Minimum evidence |
|------|------------------|
| In-person | Staff ID + timestamp |
| Phone | Call time + number called + summary note |
| Message | Local evidence file ref or message ID note |
| Manager-entered | Evidence checklist completed |

**No free upload** — evidence capture follows Phase 18 camera/local rules where applicable.

---

## Actor / User Recording Requirement

| Field | Rule |
|-------|------|
| **Actor/user recording** | `recorded_by` — staff user ID from session |
| Role | Must have `contract.acceptance.record` permission (future) |
| Impersonation | FORBIDDEN |

---

## Timestamp Requirement

| Field | Rule |
|-------|------|
| **Timestamp** | `accepted_at` — server authoritative time |
| Timezone | Workshop local — consistent with audit log |

---

## Link to JobCard

| Rule | Requirement |
|------|-------------|
| **Link to JobCard** | Every acceptance `jobcard_id` mandatory |
| Contract ref | Linked contract version |
| Amendment | New acceptance row per scope change |

---

## Link to Workflow

| Transition | Acceptance required |
|------------|---------------------|
| Contract DRAFT → APPLIED | Initial acceptance |
| Out-of-contract → APPLIED | Amendment acceptance |
| Ceiling increase | Acceptance or typed approval per level |

---

## Correction Policy

| Action | Rule |
|--------|------|
| Wrong type recorded | Correction workflow — new record supersedes; old retained |
| **No silent delete** | Audit `acceptance_corrected` |
| Owner/admin | Approval for correction |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `customer_acceptance_recorded` | type, jobcard_id, actor, timestamp |
| `customer_acceptance_corrected` | old_id, new_id, approver |
| `customer_acceptance_missing_blocked` | workflow block |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — **no signature capture** in Phase 19.

---

**END OF CUSTOMER ACCEPTANCE RECORD RULE**
