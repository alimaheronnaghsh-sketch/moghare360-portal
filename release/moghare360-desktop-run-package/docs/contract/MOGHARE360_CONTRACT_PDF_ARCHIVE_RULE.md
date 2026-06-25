# MOGHARE360 — Contract PDF Archive Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

Every **APPLIED** service contract must have a **contract PDF archive** on the local server. PDFs are versioned, immutable after registration, and never stored on domain or cloud.

---

## Contract PDF Archive Requirement

| Rule | Requirement |
|------|-------------|
| **Contract PDF archive requirement** | PDF generated from template + contract data at APPLIED |
| One PDF per contract version | Initial + each amendment |
| Index row | MOGHARE360_ERP links `jobcard_id`, `contract_id`, `version`, local path |
| Customer copy | Printed or handed locally — not hosted on moghareh360.ir |

**No PDF generation implementation in Phase 19** — rule only.

---

## Versioned PDF Archive

| Field | Rule |
|-------|------|
| `version` | Monotonic per contract |
| `template_version` | Template used at generation |
| `generated_at` | Server timestamp |
| `generated_by` | Staff user or system job |
| Prior versions | Retained when amended |

---

## Immutable After Registration

| State | Rule |
|-------|------|
| After registration | PDF bytes **immutable** — no overwrite |
| **Correction/replacement only through workflow and audit** | New version row + `contract_pdf_replaced` audit |
| In-place edit | FORBIDDEN |
| Silent delete | FORBIDDEN |

Aligned with Phase 18 media immutability principles.

---

## Local-Only Storage

| Rule | Requirement |
|------|-------------|
| **Local-only storage** | Owner laptop server filesystem |
| Path | Outside public web root by default |
| **No contract PDF storage on moghareh360.ir** | LOCKED |
| **No cloud contract storage** | LOCKED |
| Git | Must not contain contract PDFs |

---

## Backup Requirement

| Rule | Detail |
|------|--------|
| **Backup must be owner-controlled** | Include contract PDF directory in backup set |
| Restore | Joint restore with MOGHARE360_ERP index |
| Encryption | Recommended for backups containing PII |

Per Phase 16 network and Phase 18 media storage boundaries.

---

## Validation Integration

| Check | Error |
|-------|-------|
| Contract APPLIED without PDF | E-01 / workflow block (policy) |
| Upload from file picker | E-07 if attempted — generation pipeline only (future) |
| Orphan PDF | E-01 — must link jobcard_id |

---

## Audit Requirement

| Event | Detail |
|-------|--------|
| `contract_pdf_generated` | version, path hash |
| `contract_pdf_registered` | immutable lock |
| `contract_pdf_replaced` | amendment version |
| `contract_pdf_access_logged` | admin view (future) |

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF CONTRACT PDF ARCHIVE RULE**
