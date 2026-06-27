# MOGHARE360 V1 Route Map

**Canonical source:** `public_html/includes/m360-navigation-registry.php`  
**Live UI:** `erp-route-map.php`  
**Audit helper:** `m360_route_audit_summary()` in `m360-route-audit-helper.php`

## Phase Summary

| Phase | Description | Route count (registry) |
|-------|-------------|------------------------|
| P1 | Public / Intake | 5 |
| P1.5 | Contract signature | 10 |
| P2 | Reception JobCards | 3 |
| P3 | Technical operations | 3 |
| P4 | Estimate / approval | 8 |
| P5 | Work execution | 3 |
| P6 | QC | 3 |
| P7 | Final invoice / delivery | 10 |
| P8 | Management dashboard | 10 |
| P9 | Soft run / demo | 7 |
| P10 | Release / RC | 5 |

> Route counts reflect `m360_nav_registry()` at RC lock. See live `erp-route-map.php` for file_exists status.

## P10 Routes (Release / RC)

| route_key | title_en | url | method | staff | owner | demo |
|-----------|----------|-----|--------|-------|-------|------|
| `p10_product_home` | Product Home | `erp-product-home.php` | GET | ✓ | ✓ | ✓ |
| `p10_demo_package_rc` | Demo Package RC | `erp-demo-package-rc.php` | GET | ✓ | | ✓ |
| `p10_release_readiness` | Release Readiness | `erp-release-readiness.php` | GET | ✓ | ✓ | |
| `p10_route_map` | Route Map | `erp-route-map.php` | GET | ✓ | | |
| `p10_link_audit` | Link Audit | `erp-link-audit.php` | GET | ✓ | | |

## Flag Legend

| Flag | Meaning |
|------|---------|
| `is_api` | API endpoint under `api/` |
| `is_customer_entry` | Customer-facing portal page |
| `is_staff_entry` | Staff ERP page (staff-login required) |
| `is_demo_entry` | Recommended demo walkthrough entry |
| `is_owner_entry` | Owner/management visibility entry |

## Audit Method

P10 link audit uses **`is_file()` / `file_exists` only** — no HTTP requests, no `curl`, no remote `file_get_contents`.

## Full Registry Access

```php
require_once 'public_html/includes/m360-navigation-registry.php';
$routes = m360_nav_registry();
$byKey = m360_nav_registry_by_key();
$phases = m360_nav_phases(); // P1, P1.5, P2 … P10
```

For the complete live table with file status badges, open `erp-route-map.php` after staff login.
