# MOGHARE360 Rollback Plan

## Scope

Planning only — **no rollback executed in Phase 14**.

## Rollback Types

### File Rollback

- Restore `public_html/` from pre-deploy snapshot
- Sync to runtime path
- Verify PHP syntax and key pages load

### DB Rollback

- Restore `moghare360_ERP` from verified pre-migration backup
- Re-run post-check queries only — no partial manual fixes without approval

### Config Rollback

- Restore `private/erp-config.php` from secure backup
- Never commit restored config to repo

### Emergency Freeze

- Disable write routes at web server or maintenance flag
- Communicate freeze to all users
- Investigate before unfreezing

## Approval

| Action | Approver |
|--------|----------|
| Rollback decision | Product owner + technical lead |
| DB restore | DBA |
| Production unfreeze | Owner sign-off |

## Rollback Trigger Conditions

- Critical data corruption detected
- Auth/permission widespread failure
- Migration partial failure with data inconsistency
- Security incident requiring immediate revert

## Rollback Communication

- Internal notification to stakeholders
- Document incident timeline
- No public customer portal messaging (portal not active)

## Rollback Validation

- Smoke test: login, dashboard, one read route per module
- Verify backup integrity post-rollback
- Sign-off before resuming operations
