# MOGHARE360 V1 — Online Test Security Lock

## Secret policy

- Real `bridge_secret` only in `private/m360-online-bridge-config.php` (gitignored)
- cPanel `forward-lead.php` holds secret on server only — never commit
- Readiness page shows masked secret only

## HMAC policy

- Algorithm: HMAC-SHA256
- Input: `timestamp + raw_json_body` (timestamp as string from header)
- Headers: `X-M360-Source`, `X-M360-Timestamp`, `X-M360-Signature`
- TTL: configurable (default 300 seconds)

## Log masking

- Mobile masked in logs
- IP masked/hashed in logs
- Signatures truncated in logs
- Path: `private/logs/online-bridge/`

## Customer safety

- No raw JSON to customer
- Friendly Persian messages only
- No PHP errors/stack traces on public form
- No token/secret in HTML

## Test data policy

- Use DEMO prefix names/vehicles in tests
- No real customer mobile/plate in repo tests
- No production deployment claim from this patch

## Forbidden

- Public debug endpoints
- Unsecured test APIs as production path
- Auth/Login changes
- Workflow mutation
- New operational schema without P1 discovery
