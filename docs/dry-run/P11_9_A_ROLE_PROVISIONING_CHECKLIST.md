# MOGHARE360 P11.9-A — Role Provisioning Checklist

**Method:** `erp-access-management.php` → `erp-access-user-create.php`  
**Do not** create users via raw SQL unless explicitly approved in a later phase.

---

## Rules

- **No passwords in this repository or in committed docs**
- Set passwords manually; store in secure channel outside Git
- Each user: `is_login_enabled = 1`, `lifecycle_state = ACTIVE`
- Staff login requires `erp_company_users` row with correct `role_code`
- After creation: each user logs in once and confirms Staff Home loads
- Run read-only preflight SQL to verify counts

---

## OWNER / SYSTEM_ADMIN

| Item | Check |
|------|-------|
| Existing owner/admin may be used | ☐ |
| Can log in via `owner-login.php` | ☐ |
| Can open `erp-access-management.php` | ☐ |
| Can open Staff Home (if in `erp_company_users`) | ☐ |
| Manager Reference Bridge visible | ☐ |

**Note:** Owner default landing may be Product Home — acceptable for oversight.

---

## RECEPTION

| Field | Value |
|-------|-------|
| Suggested username | `demo.reception` |
| Role | `RECEPTION` |
| Created via | `erp-access-user-create.php` |

| Check | ☐ |
|-------|---|
| User created (`user_id >= 20001`) | ☐ |
| Role RECEPTION assigned in access console | ☐ |
| Login enabled + ACTIVE | ☐ |
| Password set outside repo | ☐ |
| Staff Home shows reception «کار امروز» cards | ☐ |
| Can open `erp-reception-online-requests.php` | ☐ |
| Can open `erp-reception-jobcards.php` | ☐ |

---

## SERVICE_MANAGER

| Field | Value |
|-------|-------|
| Suggested username | `demo.service.manager` |
| Role | `SERVICE_MANAGER` |

| Check | ☐ |
|-------|---|
| User created | ☐ |
| Role assigned | ☐ |
| Login + Staff Home OK | ☐ |
| Coordination bridge visible | ☐ |
| Can open `erp-technical-board.php` | ☐ |

---

## TECHNICIAN

| Field | Value |
|-------|-------|
| Suggested username | `demo.technician` |
| Role | `TECHNICIAN` |

| Check | ☐ |
|-------|---|
| User created | ☐ |
| Role assigned | ☐ |
| Login + Staff Home OK | ☐ |
| part-use card shows runtime_hold (disabled) | ☐ |
| Can open technical + work execution boards | ☐ |

---

## PARTS

| Field | Value |
|-------|-------|
| Suggested username | `demo.parts` |
| Role | `PARTS` |

| Check | ☐ |
|-------|---|
| User created | ☐ |
| Role assigned | ☐ |
| Login + Staff Home OK | ☐ |
| part-use disabled; reserve card clickable | ☐ |
| Can open `erp-part-reserve.php` | ☐ |

---

## FINANCE

| Field | Value |
|-------|-------|
| Suggested username | `demo.finance` |
| Role | `FINANCE` |

| Check | ☐ |
|-------|---|
| User created | ☐ |
| Role assigned | ☐ |
| Login + Staff Home OK | ☐ |
| payment-tracking disabled | ☐ |
| Can open estimate + final invoice boards | ☐ |

---

## QC

| Field | Value |
|-------|-------|
| Suggested username | `demo.qc` |
| Role | `QC` |

| Check | ☐ |
|-------|---|
| User created | ☐ |
| Role assigned | ☐ |
| Login + Staff Home OK | ☐ |
| Can open QC board + delivery control | ☐ |

---

## Post-provisioning verification

| Check | ☐ |
|-------|---|
| Run `P11_9_A_READONLY_PREFLIGHT_CHECK.sql` | ☐ |
| All 6 roles show login-enabled count ≥ 1 | ☐ |
| No duplicate demo usernames unintentionally | ☐ |
| Permission preview reviewed per user (optional) | ☐ |
| Access readiness not BLOCKED | ☐ |

---

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Operator | | | |
| Owner | | | |
