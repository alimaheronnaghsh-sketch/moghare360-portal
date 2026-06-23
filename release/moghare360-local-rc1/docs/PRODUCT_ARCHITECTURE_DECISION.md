# MOGHARE360 Product Architecture Decision
# تصمیم معماری محصول MOGHARE360

**Document type:** Architecture Decision Record (ADR)  
**Status:** Approved direction for product design  
**Scope:** Product-level architecture (not implementation detail)

---

## 1) Final architecture decision / تصمیم نهایی معماری

**Single Tenant Execution + Multi-Tenant Ready Architecture**

**اجرای تک‌مستأجر + معماری آماده برای چندمستأجری**

| Term | Meaning |
|------|---------|
| **Single Tenant Execution** | اولین پیاده‌سازی فقط برای یک کسب‌وکار (مغاره) اجرا می‌شود؛ پیچیدگی multi-tenant در V0 پیاده نمی‌شود. |
| **Multi-Tenant Ready** | طراحی، نام‌گذاری و لایه‌بندی طوری است که در آینده بتوان چند تعمیرگاه / کسب‌وکار دیگر را بدون بازنویسی هسته اضافه کرد. |

---

## 2) Meaning / معنا

- **Moghareh (مغاره)** is the **first pilot customer / first tenant**.  
  مغاره اولین مشتری پایلوت و اولین tenant محصول است.

- **MOGHARE360** is a **sellable ERP product**, not a one-off internal tool for a single shop.  
  MOGHARE360 یک **محصول ERP قابل فروش** است، نه نرم‌افزار اختصاصی فقط برای یک مرکز.

- The system must be designed so it can later support **other auto repair shops** (تعمیرگاه‌های خودروی دیگر).

- Future expansion to **service + sales businesses** (e.g. restaurants, other service centers) must remain **possible** without trapping the core in auto-only logic.  
  گسترش آینده به کسب‌وکارهای خدماتی و فروش (مثلاً رستوران) باید از نظر معماری مسدود نشود.

**Principle:** Build the product for Moghareh first; design the platform for many tenants later.

---

## 3) Architecture layers / لایه‌های معماری

```
┌─────────────────────────────────────────────────────────────┐
│  Layer 4: Moghareh Tenant Configuration                     │
│  (برندینگ، قرارداد، SMS، قوانین مالی/خدماتی مغاره)          │
├─────────────────────────────────────────────────────────────┤
│  Layer 3: Auto Repair Package                               │
│  (خودرو، JobCard، دیاگ، QC، ترخیص)                          │
├─────────────────────────────────────────────────────────────┤
│  Layer 2: Service Business Core                             │
│  (درخواست، Work Order، تأیید، فاکتور، پرداخت، تحویل)        │
├─────────────────────────────────────────────────────────────┤
│  Layer 1: Core Platform                                     │
│  (کاربر، نقش، دسترسی، audit، تنظیمات، API)                  │
└─────────────────────────────────────────────────────────────┘
```

### Layer 1: Core Platform / هسته پلتفرم

Platform-level capabilities shared by any tenant and any vertical:

| Area | Examples |
|------|----------|
| Identity & access | users, roles, permissions, departments, positions |
| Access lifecycle | access requests, approvals, suspensions, restrictions |
| Governance | audit logs, access change history |
| Platform services | settings, reports, files, notifications |
| Integration | API readiness |

**V0 status:** Partially implemented in `moghare360_ERP` (`core_*` tables for staff access lifecycle).

### Layer 2: Service Business Core / هسته کسب‌وکار خدماتی

Generic service-business workflow (vertical-agnostic):

- service request
- work order
- approval
- estimate
- invoice
- payment
- inventory usage
- delivery / completion

Applicable to auto repair, restaurants, and similar service models with adaptation at Layer 3/4.

### Layer 3: Auto Repair Package / بسته تعمیرگاه خودرو

Auto-repair-specific domain on top of Layer 2:

- vehicle
- jobcard
- diagnosis
- fault codes
- parts request
- repair operation
- QC
- road test
- vehicle delivery

Aligns with `INTAKE_TO_DELIVERY_WORKFLOW` and `ERP_MASTER_ARCHITECTURE.md` process rules.

### Layer 4: Moghareh Tenant Configuration / پیکربندی tenant مغاره

Moghareh-specific customization (not in Core naming):

- Moghareh branding
- Moghareh contract templates
- Moghareh organization settings
- Moghareh SMS text
- Moghareh workflow settings
- Moghareh financial / service rules

This layer must **not** leak into `core_*` table names or platform logic.

---

## 4) Ownership model / مدل مالکیت

Two distinct concepts must remain separate in documentation and future database design:

| Concept | English | Persian | Scope |
|---------|---------|---------|--------|
| **Platform Owner** | Owner of the software/product | مالک پلتفرم / محصول | MOGHARE360 product governance, platform bootstrap, emergency policy |
| **Tenant Owner** | Owner/manager of one business using the software | مالک/مدیر یک کسب‌وکار (tenant) | Business operations, staff access policy within that tenant |

**First implementation note:**  
The same person may **temporarily** act as both Platform Owner and Moghareh Tenant Owner (e.g. Amir Ali during pilot).  
**این دو نقش نباید در مدل داده یا مستندات ادغام شوند** — only the assignment may overlap in practice.

| Role in V0 bootstrap (proposed) | Maps to |
|----------------------------------|---------|
| `user_id = 1`, role `owner` | **Platform Owner** (System Owner) |
| Moghareh business manager (future) | **Tenant Owner** (tenant-scoped responsibility) |

---

## 5) Version 0 decision / تصمیم نسخه ۰

| Decision | Status |
|----------|--------|
| Current V0 SQL foundation remains **valid** | Yes — 16 `core_*` tables in `moghare360_ERP` |
| V0 `core_*` tables are **platform-level foundation** | Yes |
| **Customer access** remains **out of V0** | Yes — case-based portal access is a future module |
| **No multi-tenant complexity** implemented yet | Yes — single DB, single tenant execution |
| Future tenant support **must not be blocked** by naming or design | Yes — `core_` prefix, no `moghareh_*` in core |

Reference: `docs/V0_SQL_EXECUTION_STATUS.md`, `docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md`

---

## 6) Naming rules / قوانین نام‌گذاری

### Good (Core Platform) / درست (هسته)

- `core_users`
- `core_roles`
- `core_permissions`
- `core_departments`
- `core_audit_logs`
- `core_access_requests`
- `core_access_approval_rules`

### Avoid in Core / در Core استفاده نکنید

- `moghareh_owner`
- `moghareh_mechanic`
- `moghareh_contract_rule`
- Hardcoded Moghareh-only workflow inside Core tables or seeds
- Tenant-specific branding/SMS in `core_*` seeds

**Rule:** If it is Moghareh-only today, it belongs in **Layer 4** (tenant configuration), not in `core_*`.

---

## 7) Future database direction / جهت آینده پایگاه داده

Multi-tenant tables are **not** required for V0. When needed, introduce **tenant / business configuration** explicitly:

| Possible future table | Purpose |
|----------------------|---------|
| `tenant_businesses` | Registered tenants (shops, businesses) |
| `tenant_settings` | Key-value or structured tenant settings |
| `tenant_branding` | Logo, colors, display name |
| `tenant_contract_templates` | Contract text per tenant |
| `tenant_workflow_settings` | Workflow toggles and thresholds |
| `tenant_module_settings` | Enabled modules per tenant |

**Data isolation strategy (future, not V0):**  
`tenant_id` on tenant-scoped tables; Core platform tables remain tenant-agnostic or hold tenant context only where required (e.g. staff users scoped to one tenant per deployment phase).

---

## 8) Bootstrap impact / اثر Bootstrap

Per `docs/V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md`:

| Item | Decision |
|------|----------|
| `user_id = 1` | Treated as **Platform Owner** (System Owner), not “Moghareh-only owner” |
| `user_id = 2` | **System Admin** (technical operator) |
| Moghareh Tenant Owner | Represented later as **tenant-level responsibility** (role + tenant binding), not merged with Platform Owner |
| Same person, two hats | Allowed temporarily in pilot; **concepts stay separate** in docs and future schema |

Bootstrap does **not** create customer users, operational staff, or tenant configuration rows in V0.

---

## 9) Final rule / قانون نهایی

> **Build for Moghareh first, but do not trap the product inside Moghareh-only logic.**  
> **ابتدا برای مغاره بساز، اما محصول را در منطق اختصاصی مغاره حبس نکن.**

| Do now | Defer |
|--------|--------|
| Complete Moghareh pilot on single tenant | `tenant_*` tables |
| Use `core_*` for platform access lifecycle | Multi-tenant routing / billing |
| Layer 3 auto workflows for Moghareh | Restaurant vertical package |
| Layer 4 config as files/settings where needed | Full tenant admin UI |

**Next technical step (after strategy approval):** controlled bootstrap SQL for Platform Owner / System Admin only — not tenant rollout, not staff migration, not customer portal.

---

## Related documents

- `docs/ERP_MASTER_ARCHITECTURE.md` — process and ERP principles
- `docs/V0_ACCESS_SQLSERVER_DESIGN_PROPOSAL.md` — V0 core schema
- `docs/V0_SQL_EXECUTION_STATUS.md` — V0 execution status
- `docs/V0_BOOTSTRAP_ADMIN_USER_STRATEGY.md` — bootstrap users
- `docs/V0_ACCESS_LIFECYCLE_POLICY_FA.md` — access lifecycle policy

---

*End of product architecture decision document.*
