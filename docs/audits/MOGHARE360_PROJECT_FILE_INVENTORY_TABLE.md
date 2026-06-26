# MOGHARE360 — جدول موجودی فایل پروژه (File Inventory)

**تاریخ:** ۲۰۲۶-۰۶-۲۶  
**Repo:** `moghare360-portal`  
**Legend Status:** ACTIVE | REFERENCE_ONLY | LEGACY_DO_NOT_USE | TEMPLATE_ONLY | PACKAGE_OUTPUT | TEST_ONLY | NEEDS_REVIEW | RUNTIME_ONLY  
**Legend Risk:** LOW | MEDIUM | HIGH | CRITICAL

> این جدول فایل‌های کلیدی و الگوهای دسته‌ای را پوشش می‌دهد. کپی‌های duplicate داخل `release/*` با وضعیت PACKAGE_OUTPUT علامت‌گذاری شده‌اند؛ منبع حقیقت: `public_html/` در root repo.

---

## Core PHP — ERP Master Entry & Console

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Core PHP | `public_html/index.php` | Local Master ERP landing | PHP ERP Master | ACTIVE | MEDIUM | بنر SQL Server — نه برای cPanel |
| Core PHP | `public_html/cpanel-public-index.php` | Public cPanel landing source | Public Site | ACTIVE | LOW | در package به index.php تبدیل می‌شود |
| Core PHP | `public_html/erp-v1-master-console.php` | Master console UI | Admin | ACTIVE | LOW | V1 entry hub |
| Core PHP | `public_html/erp-v1-unit-access-console.php` | Unit access control UI | Admin | ACTIVE | LOW | |
| Core PHP | `public_html/erp-v1-production-signoff.php` | Production signoff dashboard | Admin | ACTIVE | MEDIUM | Owner formal signoff |
| Core PHP | `public_html/erp-v1-fix-register.php` | Post-run fix register | Admin | ACTIVE | MEDIUM | Only authorized change path post-signoff |
| Core PHP | `public_html/erp-moghare-ready.php` | Readiness gate page | Admin | ACTIVE | LOW | |
| Core PHP | `public_html/erp-soft-run-home.php` | Soft run entry | Soft Run | ACTIVE | LOW | |

## Core PHP — ERP Modules (representative)

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Core PHP | `public_html/erp-customer-*.php` (۱۳ files) | Customer module pages | Customer Core | ACTIVE | LOW | Includes create/list/detail |
| Core PHP | `public_html/submit-customer-v2.php` | Customer write route | Customer Core | ACTIVE | MEDIUM | Write-capable |
| Core PHP | `public_html/erp-jobcard-*.php` (۳۱ files) | JobCard module | JobCard | ACTIVE | LOW | Largest module set |
| Core PHP | `public_html/submit-jobcard-v2.php` | JobCard write route | JobCard | ACTIVE | MEDIUM | Write-capable |
| Core PHP | `public_html/erp-inventory-*.php` | Inventory UI | Inventory | ACTIVE | LOW | |
| Core PHP | `public_html/erp-purchase-*.php` | Purchase UI | Purchase | ACTIVE | LOW | |
| Core PHP | `public_html/erp-payment-*.php` | Payment UI | Finance | ACTIVE | LOW | Gateway locked V2 |
| Core PHP | `public_html/erp-crm-*.php` | CRM UI | CRM | ACTIVE | LOW | |
| Core PHP | `public_html/erp-hr-*.php` | HR UI | HR | ACTIVE | LOW | |
| Core PHP | `public_html/erp-qc-*.php` | QC UI | QC/Delivery | ACTIVE | LOW | |
| Core PHP | `public_html/erp-soft-run-*.php` (۲۲ files) | Soft run dashboards | Soft Run | ACTIVE | LOW | Waves 6–8 |
| Core PHP | `public_html/erp-executive-*.php` | Executive readiness/go-no-go | Soft Run | ACTIVE | LOW | Wave 9 |
| Core PHP | `public_html/erp-access-*.php` | Access management ERP | Access | ACTIVE | MEDIUM | |
| Core PHP | `public_html/moghare360-release-download.php` | Release download page | Release | ACTIVE | LOW | |

## API

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| API | `public_html/api/customer/request.php` | Public customer intake API | API | ACTIVE | MEDIUM | Writes to SQL Server via Master |
| API | `public_html/api/access/request.php` | Access request API | API | ACTIVE | MEDIUM | |
| API | `public_html/api/auth/staff-login.php` | Staff auth API | Auth | ACTIVE | HIGH | Login boundary |
| API | `public_html/api/auth/owner-login.php` | Owner auth API | Auth | ACTIVE | HIGH | Login boundary |
| API | `public_html/api/dashboard/company-owner.php` | Owner dashboard data | API | ACTIVE | MEDIUM | |
| API | `public_html/api/mirror/health.php` | Mirror health check | API | ACTIVE | LOW | cPanel diagnostic |
| API | `public_html/api/sync/pending.php` | Sync queue read | API | NEEDS_REVIEW | MEDIUM | Prototype sync |
| API | `public_html/api/sync/ack.php` | Sync acknowledge | API | NEEDS_REVIEW | MEDIUM | Write-capable |
| API | `public_html/api/sync/config-sync.php` | Config sync | API | NEEDS_REVIEW | HIGH | |
| API | `public_html/api/sync/health.php` | Sync health | API | NEEDS_REVIEW | LOW | |
| API | `public_html/api/sync/debug-pending.php` | Debug endpoint | API | NEEDS_REVIEW | HIGH | Remove/disable production |

## Includes

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Includes | `public_html/includes/mirror-layout.php` | Public mirror layout/head/foot | Public Site | ACTIVE | LOW | Brand MOGHAREH360 LTR class |
| Includes | `public_html/includes/mirror-api-client.php` | HTTP client to Master API | Public Site | ACTIVE | MEDIUM | Depends MASTER_SERVER_BASE_URL |
| Includes | `public_html/includes/moghare360-v1-master-console-helper.php` | Master console render | Admin | ACTIVE | LOW | |
| Includes | `public_html/includes/moghare360-v1-api-bootstrap.php` | API bootstrap | API | ACTIVE | MEDIUM | |
| Includes | `public_html/includes/moghare360-saas-config-loader.php` | SaaS config loader | Auth | ACTIVE | HIGH | |
| Includes | `public_html/includes/moghare360-saas-tenant-context.php` | Tenant context | Auth | ACTIVE | MEDIUM | |
| Includes | `public_html/includes/moghare360-permission-guard.php` | Permission guard | Access | ACTIVE | HIGH | |
| Includes | `public_html/includes/erp-config-loader.php` | ERP config loader (code) | Core PHP | ACTIVE | MEDIUM | Not secrets — loads private file |
| Includes | `public_html/includes/moghare360-*-helper.php` (~۵۰+ files) | Module helpers | Various | ACTIVE | LOW | Pattern: one per wave/module |

## Auth

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Auth | `public_html/staff-auth.php` | Staff authentication core | Auth | ACTIVE | CRITICAL | LOCKED — do not modify |
| Auth | `public_html/access-control.php` | Access control core | Auth | ACTIVE | CRITICAL | LOCKED — do not modify |
| Auth | `public_html/staff-login.php` | Staff login UI (public) | Public Site | ACTIVE | HIGH | LOCKED boundary |
| Auth | `public_html/owner-login.php` | Owner login UI (public) | Public Site | ACTIVE | HIGH | |
| Auth | `public_html/user-access-request.php` | User access request form | Public Site | ACTIVE | MEDIUM | |

## Public Site

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Public Site | `public_html/customer-request.php` | Customer online request form | Public Site | ACTIVE | MEDIUM | Core public flow |
| Public Site | `public_html/company-owner-dashboard.php` | Owner dashboard (mirror) | Public Site | ACTIVE | MEDIUM | |
| Public Site | `public_html/mirror-health.php` | Public health page | Public Site | ACTIVE | LOW | Diagnostic |
| Public Site | `public_html/mirror-config.example.php` | Mirror config template | Public Site | TEMPLATE_ONLY | LOW | Copy to mirror-config.php on host |
| Public Site | `public_html/service-worker.js` | PWA cache shell | Public Site | ACTIVE | MEDIUM | Cache stale CSS risk |
| Public Site | `public_html/manifest.webmanifest` | PWA manifest | Public Site | ACTIVE | LOW | |
| Public Site | `public_html/assets/brand/moghareh-motors-logo.jpg` | Brand logo asset | Public Site | ACTIVE | LOW | Constrained by CSS max 48px |

## Assets CSS

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Assets CSS | `public_html/assets/css/mirror.css` | Public mirror styles + import | Public Site | ACTIVE | MEDIUM | Logo/brand rules here |
| Assets CSS | `public_html/assets/css/moghare360-v1-luxury-ui.css` | Luxury industrial UI tokens | Public Site | ACTIVE | MEDIUM | Global shell variables |
| Assets CSS | `public_html/assets/moghare360-ui/*.css` (~۱۵ files) | ERP module styles | PHP ERP | ACTIVE | LOW | Jobcard, finance, HR, etc. |

## Assets JS

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Assets JS | `public_html/assets/js/customer-form.js` | Customer form logic/calendar | Public Site | ACTIVE | LOW | Plate digits, booking window |
| Assets JS | `public_html/assets/js/iran-provinces-cities.js` | Iran geo data | Public Site | ACTIVE | LOW | |
| Assets JS | `public_html/assets/js/vehicle-brand-classes.js` | Vehicle brand data | Public Site | ACTIVE | LOW | |

## SQL Server

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| SQL Server | `public_html/sql/sqlserver/MOGHARE360_V1_CANONICAL_DATABASE.sql` | Single DB orchestrator | SQL | ACTIVE | HIGH | Canonical apply path |
| SQL Server | `public_html/sql/sqlserver/MOGHARE360_V1_DATABASE_VERIFY.sql` | Read-only verify | SQL | ACTIVE | LOW | Safe to run |
| SQL Server | `public_html/sql/sqlserver/v1_saas_activation_foundation.sql` | SaaS activation | SQL | ACTIVE | MEDIUM | |
| SQL Server | `public_html/sql/sqlserver/v1_post_run_fix_register.sql` | Fix register schema | SQL | ACTIVE | MEDIUM | |
| SQL Server | `public_html/sql/sqlserver/v1_canonical_extensions.sql` | Column extensions | SQL | ACTIVE | MEDIUM | Additive |
| SQL Server | `public_html/sql/sqlserver/phase_*.sql` (۱۰ files) | Phase schemas | SQL | ACTIVE | MEDIUM | Included in orchestrator |
| SQL Server | `public_html/sql/sqlserver/mission_*.sql` (۸ files) | Mission foundations | SQL | ACTIVE | MEDIUM | |
| SQL Server | `public_html/sql/wave_*.sql` (~۱۰ files) | Wave log tables | SQL | ACTIVE | LOW | |

## Legacy SQL/MySQL

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Legacy SQL/MySQL | `public_html/sql/MOTHER_RUN_ALL_IN_ORDER.sql` | MySQL mother script | SQL | LEGACY_DO_NOT_USE | HIGH | Codex-era |
| Legacy SQL/MySQL | `public_html/sql/patch_*.sql` | MySQL patches | SQL | LEGACY_DO_NOT_USE | HIGH | |
| Legacy SQL/MySQL | `public_html/sql/erp_jobcard_workflow_v1.sql` | MySQL workflow proto | SQL | LEGACY_DO_NOT_USE | MEDIUM | |
| Legacy SQL/MySQL | `public_html/sql/*.sql` (root, ۲۲ files) | MySQL staging tables | SQL | LEGACY_DO_NOT_USE | HIGH | Do NOT run for V1 |

## Tools

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Tools | `tools/test-*.php` (۸۸ files) | Automated verification | Tests | TEST_ONLY | LOW–MEDIUM | 1d–1f write-capable |
| Tools | `tools/package-moghare360-cpanel-public-final.ps1` | Build cPanel ZIP | Release Scripts | ACTIVE | LOW | Flat root staging |
| Tools | `tools/package-moghare360-cpanel-mirror-clean.ps1` | Build clean mirror ZIP | Release Scripts | ACTIVE | LOW | |
| Tools | `tools/package-moghare360-mirror-site.ps1` | Build nested mirror ZIP | Release Scripts | ACTIVE | MEDIUM | Nested public_html |
| Tools | `tools/local/SYNC_PUBLIC_SITE_TO_LOCAL_XAMPP.ps1` | Sync public to XAMPP | Local Runtime Sync | ACTIVE | MEDIUM | Does not sync service-worker |
| Tools | `tools/local/CREATE_LOCAL_MIRROR_CONFIG.ps1` | Create local mirror-config | Local Runtime Sync | ACTIVE | MEDIUM | Runtime only |
| Tools | `tools/production/INSTALL_MOGHARE360_V1.ps1` | Production installer | Release Scripts | ACTIVE | HIGH | Server install |
| Tools | `tools/production/AUTO_DEPLOY_MOGHARE360_V1.ps1` | Auto deploy | Release Scripts | ACTIVE | CRITICAL | Can overwrite config |
| Tools | `tools/_legacy_codex_review/` | Codex extract reference | Tools | REFERENCE_ONLY | LOW | Gitignored |

## Release Packages (ZIP)

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Release Packages | `release/moghare360-cpanel-public-final.zip` | **Recommended cPanel** | cPanel/Mirror | PACKAGE_OUTPUT | LOW | ۲۲ entries, no secrets |
| Release Packages | `release/moghare360-cpanel-mirror-clean.zip` | Clean mirror variant | cPanel/Mirror | PACKAGE_OUTPUT | MEDIUM | |
| Release Packages | `release/moghare360-mirror-site-package.zip` | Nested mirror package | cPanel/Mirror | PACKAGE_OUTPUT | MEDIUM | public_html/ prefix |
| Release Packages | `release/moghare360-v1-production-installer.zip` | Full ERP installer | Release | PACKAGE_OUTPUT | MEDIUM | ~۵۰۰ entries |
| Release Packages | `release/moghare360-v1-auto-deploy.zip` | Auto deploy bundle | Release | PACKAGE_OUTPUT | HIGH | |
| Release Packages | `release/moghare360-v1-production-final-delivery.zip` | Production delivery bundle | Release | PACKAGE_OUTPUT | MEDIUM | Nested sub-zips |
| Release Packages | `release/moghare360-desktop-run-package.zip` | Desktop run | Release | PACKAGE_OUTPUT | LOW | Internal |
| Release Packages | `release/moghare360-demo-package.zip` | Demo | Release | PACKAGE_OUTPUT | LOW | |
| Release Packages | `release/moghare360-local-rc1.zip` | Local RC1 | Release | PACKAGE_OUTPUT | LOW | |
| Release Packages | `release/_cpanel_public_final_stage/` | Staging dir (unzipped) | Release | PACKAGE_OUTPUT | LOW | Source for cpanel-final zip |
| Release Packages | `release/_mirror_clean_stage/` | Mirror clean staging | Release | PACKAGE_OUTPUT | LOW | |
| Release Packages | `release/moghare360-*/` (۱۰ dirs) | Unpacked package trees | Release | PACKAGE_OUTPUT | LOW | Duplicates of source |

## Docs

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Docs | `docs/master/MOGHARE360_MASTER_EXECUTION_PROMPT_FINAL_LOCKED.md` | Locked governance | Docs | ACTIVE | LOW | Authoritative decisions |
| Docs | `docs/master/MOGHARE360_MASTER_01..10_*.md` | Architecture plans | Docs | ACTIVE | LOW | |
| Docs | `docs/missions/phase_*/*` (~۴۷۷ unique missions) | Phase mission docs | Docs | ACTIVE | LOW | Signoffs per phase |
| Docs | `docs/release/MOGHARE360_V1_CANONICAL_DATABASE_LOCK.md` | DB lock doc | Docs | ACTIVE | LOW | |
| Docs | `docs/release/MOGHARE360_V1_PRODUCTION_RUN_SIGNOFF.md` | Production signoff | Docs | ACTIVE | LOW | 24/24 smoke |
| Docs | `docs/release/MOGHARE360_MIRROR_SITE_DEPLOYMENT_GUIDE.md` | Mirror deploy guide | Docs | ACTIVE | LOW | |
| Docs | `docs/audits/MOGHARE360_*.md` (this audit) | Audit reports | Docs | ACTIVE | LOW | Created 2026-06-26 |

## Templates

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Templates | `private/templates/production-users.template.json` | User seed template | Templates | TEMPLATE_ONLY | MEDIUM | Not production data |
| Templates | `private/templates/production-site-config.template.json` | Site config template | Templates | TEMPLATE_ONLY | MEDIUM | |
| Templates | `private/erp-config.example.php` | ERP config example | Templates | TEMPLATE_ONLY | HIGH | Pattern only |
| Templates | `public_html/mirror-config.example.php` | Mirror config example | Templates | TEMPLATE_ONLY | LOW | |

## Sensitive/Private

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Sensitive/Private | `private/erp-config.php` | Live DB config | Sensitive | RUNTIME_ONLY | CRITICAL | **Sensitive file exists** — gitignored, content not dumped |
| Sensitive/Private | `private/production-users.json` | Production users | Sensitive | — | CRITICAL | **Sensitive file not found in repo** — expected ignored |
| Sensitive/Private | `private/production-site-config.json` | Site config | Sensitive | — | HIGH | **Not found** — use template |
| Sensitive/Private | `public_html/mirror-config.php` | Mirror live config | Sensitive | RUNTIME_ONLY | HIGH | **Not in repo; exists in runtime** — content not dumped |
| Sensitive/Private | `public_html/config.php` | ERP local config | Sensitive | RUNTIME_ONLY | CRITICAL | **Not in repo; exists in runtime** — content not dumped |

## Local Runtime (XAMPP)

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| Local Runtime | `C:\xampp\htdocs\moghare360\` (۷۹۵ files) | Full local deployment | Local Runtime | ACTIVE | MEDIUM | Superset of public_html |
| Local Runtime | `runtime/PUBLIC_SITE_SYNC_REPORT.md` | Last sync log | Local Runtime Sync | ACTIVE | LOW | ۱۷ files synced |
| Local Runtime | `runtime/_cpanel_public_final_test/` | Package extract test | Local Runtime | TEST_ONLY | LOW | |

## cPanel/Mirror — Runtime-only deltas

| Category | File Path | Purpose | Layer | Status | Risk Level | Notes |
|----------|-----------|---------|-------|--------|------------|-------|
| cPanel/Mirror | `htdocs/moghare360/index.php` | Local root index | Local Runtime | NEEDS_REVIEW | MEDIUM | Master ERP — not cPanel model |
| cPanel/Mirror | `htdocs/moghare360/service-worker.js` | PWA worker | Local Runtime | — | MEDIUM | **MISSING in runtime** — present in repo |
| cPanel/Mirror | `release/_cpanel_public_final_stage/index.php` | cPanel index | cPanel/Mirror | PACKAGE_OUTPUT | LOW | From cpanel-public-index.php |

---

## Summary Counts by Category (public_html source only)

| Category | Approx Count | Primary Status |
|----------|--------------|----------------|
| Core PHP (erp-*) | 179 pages + submit routes | ACTIVE |
| Includes | ~90 unique helpers | ACTIVE |
| API | 11 endpoints | ACTIVE / NEEDS_REVIEW (sync) |
| Public Site mirror | 8 pages + PWA | ACTIVE |
| Assets CSS (public) | 2 + ERP UI set | ACTIVE |
| Assets JS (public) | 3 | ACTIVE |
| SQL Server canonical | 32 | ACTIVE |
| Legacy MySQL | 22 | LEGACY_DO_NOT_USE |
| Tools tests | 88 | TEST_ONLY |
| Release ZIPs | 26 (incl duplicates) | PACKAGE_OUTPUT |
| Docs (source) | 1087 | ACTIVE |

---

*Inventory generated by read-only audit — no file contents from sensitive paths included.*
