# MOGHARE360 P11.9-A — M360-DEMO JobCard Preparation Plan

**Decision (P11.9-1):** Use a **fresh** traceable JobCard — **do not** standardize on JobCard ID 1.

---

## 1. Target record profile

| Field | Recommended value |
|-------|-------------------|
| JobCard number prefix | `M360-DEMO-` |
| Sample number | `M360-DEMO-001` |
| Customer name | M360 Demo Customer |
| Customer code | `M360-DEMO-CUST-001` |
| Vehicle | Toyota Camry |
| Vehicle code | `M360-DEMO-VEH-001` |
| Plate | M360-DEMO-001 |
| Complaint | Dry Run controlled service flow test |
| Starting `jobcard_status` | `RECEIVED` (P2 reception entry) |
| Priority | `NORMAL` |
| Lifecycle | `ACTIVE` |

---

## 2. Why not JobCard ID 1

- ID 1 may be stale, wrong tenant, or mid-workflow from prior tests
- Some UI samples use `jobcard_id=1` as placeholder only
- Soft Run finder uses **`M360-DEMO` prefix** or scenario code — not ID 1
- Traceability requires unique demo marker visible in boards and Soft Run center

---

## 3. Creation methods (operator chooses one)

### Method A — Reception UI (preferred)

1. RECEPTION logs in → Staff Home → JobCards board
2. Create JobCard via existing reception flow (customer + vehicle + complaint)
3. Ensure jobcard_number starts with `M360-DEMO-`
4. Record `jobcard_id` in execution log header

### Method B — Guarded SQL template (optional)

1. Review `database/dry-run/P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql`
2. Set `@OPERATOR_USER_ID` to valid reception user (e.g. demo.reception)
3. Set `@CONFIRM_CREATE_M360_DEMO = N'CREATE_M360_DEMO'` after review
4. Execute manually in SSMS
5. Record printed IDs in log

**P11.9-A does not execute this SQL.**

---

## 4. Required fields checklist

### Customer (`erp_customers`)

- ☐ `customer_code` unique (`M360-DEMO-CUST-001`)
- ☐ `full_name` = M360 Demo Customer
- ☐ `primary_mobile` populated (demo number — not real customer PII if avoidable)
- ☐ `lifecycle_state` = ACTIVE
- ☐ `created_by_user_id` valid

### Vehicle (`erp_vehicles`)

- ☐ `vehicle_code` unique (`M360-DEMO-VEH-001`)
- ☐ `brand` = Toyota, `model` = Camry
- ☐ `plate_number` = M360-DEMO-001
- ☐ `lifecycle_state` = ACTIVE

### Relation (`erp_customer_vehicle_relations`)

- ☐ Links customer + vehicle (OWNER relation)

### JobCard (`erp_jobcards`)

- ☐ `jobcard_number` unique with `M360-DEMO-` prefix
- ☐ `customer_id`, `vehicle_id` linked
- ☐ `reception_user_id` = demo reception user
- ☐ `jobcard_status` = RECEIVED (not CLOSED, not mid-QC)
- ☐ `customer_complaint` = Dry Run controlled service flow test
- ☐ `created_by_user_id` valid

---

## 5. Verification steps

1. Run read-only preflight — section «M360-DEMO JobCards»
2. RECEPTION opens JobCards board — row visible
3. Open detail — responsibility strip shows JobCard ID + status
4. Soft Run control center finds demo JobCard (if P9 tables present)
5. Write canonical **jobcard_id** + **jobcard_number** on all run documents

---

## 6. Avoid duplicates

Before create:

- Preflight: check existing `M360-DEMO%` jobcard_number count
- If duplicate exists: **reuse** agreed record OR use next suffix (`M360-DEMO-002`)
- Do not create second demo JobCard without operator decision

---

## 7. Identify later

Search keys:

- `jobcard_number LIKE 'M360-DEMO%'`
- Customer code `M360-DEMO-CUST-001`
- Plate `M360-DEMO-001`
- Execution log header fields

---

## 8. After dry run — cleanup (optional)

If operator chooses cleanup (separate approved window):

- Document whether demo rows remain for regression
- Do not DELETE production-like records without owner approval
- Prefer marking closed/archived through normal P7 close flow during run
- Any cleanup SQL is **out of P11.9-A scope**

---

## Sign-off

| Item | Value |
|------|-------|
| Agreed jobcard_number | |
| Agreed jobcard_id | |
| Creation method (UI/SQL) | |
| Operator | |
| Owner | |
| Date | |
