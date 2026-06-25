# Mirror Deployment Checklist

- [ ] Upload public_html to moghareh360.ir
- [ ] Configure mirror-config.php (not in ZIP)
- [ ] Set MASTER_SERVER_BASE_URL
- [ ] Verify MIRROR_MODE=true
- [ ] Verify HOST_DATABASE_ALLOWED=false
- [ ] Test mirror-health.php
- [ ] Test customer-request form (no local DB)
- [ ] Verify PWA install on mobile
- [ ] Confirm no uploads/ or private/ on host
