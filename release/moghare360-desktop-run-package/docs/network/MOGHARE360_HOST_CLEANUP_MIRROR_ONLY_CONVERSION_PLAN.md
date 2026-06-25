# MOGHARE360 — Host Cleanup & Mirror-Only Conversion Plan

**Domain:** moghareh360.ir  
**Status:** Planning only — PHASE 16  
**SQL:** No SQL required

---

## Objective

Convert moghareh360.ir to **mirror-only** state: no business data, no files, no business logic, no database — only approved static/gateway interface after future sign-off.

---

## Phase 16 Constraints

| Rule | Status |
|------|--------|
| **Inspect current host files before any change** | Required first step (future execution) |
| **No deletion in PHASE 16** | LOCKED |
| **No deployment in PHASE 16** | LOCKED |
| Document plan only | This file |

---

## Step 1 — Inspect (Before Any Change)

| Action | Detail |
|--------|--------|
| Full file listing | cPanel File Manager or SFTP inventory |
| Database listing | phpMyAdmin / host DB panel — list all DBs |
| Cron jobs | Document scheduled tasks |
| Email / subdomains | Note active services |
| SSL certs | Note expiry and coverage |
| Access logs | Sample for unexpected ERP traffic |

**Output:** Host inventory report (future deliverable — not created in Phase 16 execution on host).

---

## Step 2 — Identify Old Portal / cPanel Remnants

| Category | Examples to flag |
|----------|------------------|
| Legacy PHP portal | Old `public_html` apps predating MOGHARE360 local pivot |
| Test installs | WordPress, phpMyAdmin exposed paths |
| Staging copies | `staging/`, `test/`, `old/` directories |
| SQL dumps | `.sql`, `.bak` on web root |
| Upload folders | `uploads/`, `media/` with customer files |
| Config leaks | `config.php` with real credentials on host |

---

## Step 3 — Classify Files

| Class | Action (future) |
|-------|-----------------|
| **Archive / reference** | Download to owner local archive; remove from host after backup |
| **Mirror-approved static** | Keep — marketing, contact, status page |
| **Business data** | **Remove from host** — migrate to local if not already local-only |
| **Business logic (PHP ERP)** | **Remove from host** — ERP runs local only |
| **Unknown** | Hold — owner review before delete |

---

## Step 4 — Remove Business Data from Host (Future)

If inspection finds:

| Finding | Remediation |
|---------|-------------|
| Customer rows in host MySQL | Export to owner archive; drop host DB — **local is system of record** |
| Uploaded photos on host | Copy to local server paths; delete from host |
| Diagnostic PDFs on host | Same |
| HR documents on host | Same |
| MOGHARE360 PHP on host | Remove after local go-live confirmed |

**Not executed in Phase 16.**

---

## Step 5 — Keep Only Mirror/Gateway-Approved Files (Future)

After explicit approval:

| Permitted on host | Forbidden on host |
|-------------------|-------------------|
| `index.html` / static marketing | MOGHARE360_ERP database |
| SSL landing page | Customer PII |
| Optional reverse-proxy gateway config | Workshop media |
| robots.txt, favicon | ERP PHP business logic |
| "ERP runs locally" status | Payment/accounting modules |

---

## Conversion Checklist (Future Execution)

- [ ] Host inventory completed
- [ ] Owner signed archive of removed files
- [ ] No MOGHARE360_ERP on host
- [ ] No customer data on host
- [ ] No upload directories with workshop files
- [ ] Mirror-only enforcement audit passed
- [ ] DNS/SSL points to static mirror only
- [ ] Network security audit passed

---

## Rollback

- Owner local archive of all removed host files before deletion
- DNS revert documented in deployment plan

---

## Relation to Other Phase 16 Docs

- `MOGHARE360_MIRROR_DOMAIN_ARCHITECTURE.md` — role definition
- `MOGHARE360_NO_CLOUD_STORAGE_BOUNDARY.md` — data boundary
- `MOGHARE360_NETWORK_SECURITY_AUDIT_PLAN.md` — audit before cleanup execution

---

**END OF HOST CLEANUP & MIRROR-ONLY CONVERSION PLAN**
