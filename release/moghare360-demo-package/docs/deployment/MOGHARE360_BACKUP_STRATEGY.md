# MOGHARE360 Backup Strategy

## Scope

Planning document only — **no real backup generated in Phase 14**.

## Backup Types

### DB Backup

- Full backup of `moghare360_ERP` before any migration
- Daily backup suggested for production
- Retention: minimum 7 daily, 4 weekly

### File Backup

- Snapshot of `public_html/` deployed to runtime
- Include `private/` config (stored securely, not in repo)
- Weekly full file backup suggested

### Release Snapshot

- Tagged snapshot before each release candidate promotion
- Immutable copy for rollback reference

### Rollback Copy

- Maintained alongside each production deploy attempt
- Verified restorable before go-live

## Rules

| Rule | Detail |
|------|--------|
| Before migration backup | Mandatory — no migration without verified backup |
| Backup owner | DBA / system owner |
| Backup verification | Restore test on non-production periodically |
| Sensitive storage | Encrypted storage — no backup in public repo |

## Warnings

- No real customer data export in Phase 14
- No backup files with sensitive data committed to git
- Backup paths must not expose credentials
