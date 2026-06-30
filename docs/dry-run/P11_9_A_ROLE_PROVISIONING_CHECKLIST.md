# MOGHARE360 P11.9-A — Role Provisioning Checklist

**P11.9-B dry-run path (authoritative):** `erp-access-management.php` → `erp-access-user-create.php`  
**Reference:** P11.9-B-1 reconciliation · P11.9-B-FIX-A doc correction

---

## Provisioning path (read first)

### Use for P11.9-B demo staff

1. Owner/admin logs in via `owner-login.php`
2. Open **`erp-access-management.php`**
3. Click **«+ ایجاد پرسنل»** → **`erp-access-user-create.php`**
4. Enter username, display name, department, position, **UI `role_code`**, temporary password (≥8 chars), `lifecycle_state=ACTIVE`, login enabled
5. Each user logs in once via `staff-login.php` and confirms **`erp-staff-home.php`**

### Do **not** use for demo user creation

| Path | Why |
|------|-----|
| **`erp-v1-unit-access-console.php`** | Read-only route/access console — **does not create users**; production import reference only |
| **`erp-access-request-admin.php`** | Read-only access **request** admin — **not** demo user provisioning |
| **Raw SQL** | Forbidden unless a future phase explicitly approves |
| **`private/production-users.json` + PowerShell import** | Production bootstrap/fallback — **not** P11.9-B dry-run path unless a future production-import phase explicitly approves |

### Unit Access Console vs Access Management

| Page | Purpose |
|------|---------|
| `erp-v1-unit-access-console.php` | Read-only unit/route documentation; points to JSON import for **production** provisioning |
| `erp-access-management.php` | P11.4 **primary UI** — creates staff with role + temporary password |

---

## Role code notes (P11.9-B UI)

| Topic | Rule |
|-------|------|
| **PARTS vs INVENTORY** | In Access Management UI select **`PARTS`**. Unit Access Console and production JSON may show **`INVENTORY`** for the same parts/inventory function — **do not substitute INVENTORY** in P11.9-B UI provisioning unless a later phase explicitly approves. |
| **SERVICE_MANAGER** | Valid **UI `role_code`** in Access Management (`operations_manager` core mapping). **Not present** in `private/templates/production-users.template.json` — create via UI for dry run. |

---

## Preflight STOP rules (provisioning)

**STOP** and report before continuing P11.9-B-A if:

- Access Management cannot create the required demo users
- **`PARTS`** is unavailable in the Access Management UI role dropdown
- **`SERVICE_MANAGER`** is unavailable in the Access Management UI role dropdown
- Operator switches to JSON import, Unit Access Console, or raw SQL without a new approved phase

**Do not** create users via raw SQL unless explicitly approved in a later phase.

---

## Rules

- **No passwords in this repository or in committed docs**
- **No screenshots containing passwords**
- Set temporary passwords manually; store in secure channel outside Git
- Each user: `is_login_enabled = 1`, `lifecycle_state = ACTIVE`
- Staff login requires `erp_company_users` row with correct UI `role_code` (`PARTS`, not `INVENTORY`, for parts staff)
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
| UI role_code | `RECEPTION` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

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
| UI role_code | `SERVICE_MANAGER` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

**Note:** `SERVICE_MANAGER` is not in the production JSON user template — UI creation only for dry run.

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
| UI role_code | `TECHNICIAN` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

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
| UI role_code | `PARTS` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

**Note:** Unit Access Console / production JSON may show `INVENTORY` for the same function — use **`PARTS`** in UI for P11.9-B.

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
| UI role_code | `FINANCE` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

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
| UI role_code | `QC` |
| Created via | `erp-access-management.php` → `erp-access-user-create.php` |

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
