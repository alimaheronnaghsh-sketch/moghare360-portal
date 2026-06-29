# MOGHARE360 P11.9-A — OTP / Customer Leg Deferral Protocol

**Default for staff-centric dry run:** Defer live customer OTP legs with documented operator log.

---

## 1. Policy summary

| Rule | Detail |
|------|--------|
| Staff workflow P2–P7 | **No live SMS required** |
| Customer OTP legs | **WARNING** — may defer |
| Production fake OTP | **Forbidden** |
| OTP config/secrets in repo | **Forbidden** |
| P11.9-0 host state | `private/m360-otp-config.php` typically absent; SMS not configured |

---

## 2. Customer legs that may involve OTP

| Phase | Page / flow | OTP typical? |
|-------|-------------|--------------|
| P1.5 | `customer-intake-contract.php`, sign | Yes if live |
| P4 | `customer-estimate-approval.php`, sign | Yes if live |
| P7 | `customer-delivery-review.php`, sign | Yes if live |
| API | `api/customer/*-otp.php` | Yes if live |

---

## 3. Deferral allowed when

- Operator + owner sign deferral decision before dry run
- Each deferred leg logged with required fields (below)
- Staff phases continue without blocking on customer SMS
- No fake OTP codes entered in production config

---

## 4. Required log fields (each deferred leg)

| Field | Example |
|-------|---------|
| Phase | P1.5 |
| Step # (P11.9-1) | 041 |
| Reason | SMS not configured — deferred per P11.9-A |
| Affected page | customer-intake-contract-sign |
| Operator initials | OP |
| Decision | DEFER — staff gate verified manually |
| Owner initials | OW |
| Timestamp | 2026-06-26 10:30 |

Use execution log + this protocol cross-reference.

---

## 5. If SMS configured later

- Re-run **customer legs only** in separate session
- Do not mix deferred and live OTP in same log row without note
- Re-test contract, estimate, delivery sign flows independently

---

## 6. What staff do when OTP deferred

| Leg | Staff action |
|-----|--------------|
| Contract gate | RECEPTION verifies contract state in UI; operator records manual witness if gate UI requires customer sign |
| Estimate approval | FINANCE progresses staff-side estimate; customer approval marked DEFERRED in log |
| Delivery sign | QC/FINANCE complete staff close steps; customer delivery DEFERRED in log |

**Do not** bypass workflow handlers with manual SQL.

---

## 7. Sign-off (before dry run)

| Statement | ☐ |
|-----------|---|
| We defer customer OTP legs for this staff-centric dry run | ☐ |
| We will log each deferral with required fields | ☐ |
| We will not enable fake production OTP | ☐ |
| We will not commit OTP secrets | ☐ |

| Role | Name | Date |
|------|------|------|
| Operator | | |
| Owner | | |
