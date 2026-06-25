# MOGHARE360 Mirror Site Deployment Guide

1. Build package: `powershell -ExecutionPolicy Bypass -File tools\package-moghare360-mirror-site.ps1`
2. Upload `public_html/` from extracted ZIP to domain root.
3. Copy `mirror-config.example.php` → `mirror-config.php`
4. Set `MASTER_SERVER_BASE_URL` to laptop/master URL (HTTPS recommended).
5. Test pages and PWA install.

See also: `release/moghare360-mirror-site-package/docs/README_MIRROR_INSTALL_FA.md`
