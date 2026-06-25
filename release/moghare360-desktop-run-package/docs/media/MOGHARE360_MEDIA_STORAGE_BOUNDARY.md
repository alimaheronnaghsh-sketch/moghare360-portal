# MOGHARE360 — Media Storage Boundary

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Principle

All workshop media and diagnostic files live **only on the owner laptop server**. The mirror domain and cloud services are **not** storage layers.

---

## Local Laptop Server Only

| Asset | Storage |
|-------|---------|
| Input photos (6) | Local filesystem + MOGHARE360_ERP index |
| Output photos (8) | Local filesystem + MOGHARE360_ERP index |
| During-work photos | Local filesystem + MOGHARE360_ERP index |
| Diagnostic PDFs | Local filesystem + MOGHARE360_ERP index |
| Thumbnails (future) | Local only — generated server-side |

**Local laptop server remains system of record.**

---

## Forbidden Storage Locations

| Location | Rule |
|----------|------|
| **No media storage on moghareh360.ir** | LOCKED |
| **No diagnostic PDF storage on moghareh360.ir** | LOCKED |
| **No cloud media storage** | No S3, Azure Blob, Google Drive API, CDN origin |
| **No host-side customer files** | Host must not hold workshop photos or PDFs |
| Customer phone gallery as system of record | FORBIDDEN — device capture flows to server |
| Email as archive | Not authoritative — local server is authoritative |

---

## Path and Access Rules

| Rule | Requirement |
|------|-------------|
| **Storage path outside public web access** | Default: not under `htdocs` public URL — unless future approved design documents controlled auth proxy |
| Directory listing | Disabled |
| Direct URL guess | Must not resolve without auth (future implementation) |
| Config path | `private/` or owner-defined local root — not in git |

---

## Backup

| Rule | Requirement |
|------|-------------|
| **Backup must be owner-controlled** | Local disk, external drive, encrypted archive |
| Backup includes media files + DB index | Joint restore required |
| Cloud backup | Only with owner explicit approval + encryption |
| Git repository | Must not contain binary media or PDFs |

---

## Mirror Domain Boundary

**Mirror domain cannot become storage layer.**

Even if moghareh360.ir offers disk and PHP upload:

- Do not store MOGHARE360 photos on host
- Do not store diagnostic PDFs on host
- Do not use host as CDN for workshop media
- Gateway (future) may **stream** from local tunnel — not **persist** on host

Aligned with Phase 16 network decisions.

---

## Relation to Validation Flow

Media write path:

**UI → Validation Engine (E-07) → Workflow Engine → Database → Audit Log → Local filesystem**

No branch to domain or cloud.

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED** — storage root path and directory layout defined in future implementation phase.

---

**END OF MEDIA STORAGE BOUNDARY**
