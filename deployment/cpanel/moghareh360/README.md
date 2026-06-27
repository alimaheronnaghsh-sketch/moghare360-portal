# MOGHARE360 — cPanel Online Lead Bridge Templates

Placeholder-only deployment files for moghareh360.ir → laptop secure intake.

## Files

| File | Purpose |
|------|---------|
| `lead-form.php` | Public RTL form (customer-friendly messages only) |
| `forward-lead.php.example` | Signed JSON forwarder — copy to `forward-lead.php` on cPanel |

## cPanel deployment

1. Upload `lead-form.php` to cPanel `public_html/` (or site root).
2. Copy `forward-lead.php.example` → `forward-lead.php` on cPanel only.
3. Edit `forward-lead.php` on server:
   - `LAPTOP_ENDPOINT_URL` → your laptop API URL (placeholder: `https://LAPTOP_HOST_PLACEHOLDER:8080/api/online-intake-secure-receive.php`)
   - `BRIDGE_SECRET` → same secret as `private/m360-online-bridge-config.php` on laptop
4. **Do not commit** `forward-lead.php` with real endpoint or secret.

## Laptop deployment

1. Copy `private/m360-online-bridge-config.example.php` → `private/m360-online-bridge-config.php`
2. Set `bridge_secret` (long random string, same as cPanel)
3. Ensure Apache/XAMPP serves `public_html/api/online-intake-secure-receive.php`
4. Run P1 migration if not applied; verify `erp-reception-online-requests.php`

## Test with DEMO data

- Customer name: `DEMO ONLINE TEST`
- Mobile: controlled test mobile (09xxxxxxxxx)
- Vehicle: `DEMO VEHICLE`

Customer should see:

- Success: «درخواست شما ثبت شد»
- Error: «خطا در ثبت درخواست، لطفاً دوباره تلاش کنید»

Customer must **never** see raw JSON, tokens, or internal errors.

## Security

- HMAC: `hash_hmac('sha256', timestamp + raw_json_body, bridge_secret)`
- Headers: `X-M360-Source`, `X-M360-Timestamp`, `X-M360-Signature`
- Logs: `private/logs/online-bridge/bridge-intake.log` (masked on laptop)

## Rollback

- Disable bridge: set `bridge_enabled => false` in laptop private config
- Remove or rename `forward-lead.php` on cPanel
- Keep `lead-form.php` hidden or show maintenance message
