<?php
declare(strict_types=1);

/**
 * MOGHARE360 V1 — Master console shared render helpers (local master entry).
 */

function v1mc_h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function v1mc_page_exists(string $relative): bool
{
    return is_file(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
}

function v1mc_unit_status(string $primaryPage): string
{
    return v1mc_page_exists($primaryPage) ? 'READY' : 'CHECK';
}

function v1mc_render_head(string $title): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . v1mc_h($title) . '</title>';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-design-tokens.css">';
    echo '<link rel="stylesheet" href="assets/moghare360-ui/moghare360-rtl.css">';
    echo '<style>
.v1mc-hero{background:linear-gradient(135deg,#0f1714,#1a2e24);color:#e8f5ec;padding:1.25rem 1.5rem;border-radius:12px;margin-bottom:1rem}
.v1mc-banner{background:#14532d;color:#ecfdf5;padding:.5rem .75rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem}
.v1mc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.85rem;margin:1rem 0}
.v1mc-card{background:#fff;border:1px solid #d8e2dc;border-radius:10px;padding:1rem}
.v1mc-card h3{margin:.2rem 0 .45rem;font-size:1rem}
.v1mc-card p{margin:0;color:#475569;font-size:.88rem;line-height:1.55}
.v1mc-badge{display:inline-block;padding:.15rem .5rem;border-radius:6px;font-size:.75rem;font-weight:700;margin-bottom:.45rem}
.v1mc-badge-ready{background:#14532d;color:#bbf7d0}
.v1mc-badge-check{background:#713f12;color:#fde68a}
.v1mc-links{margin-top:.65rem;display:flex;flex-wrap:wrap;gap:.45rem}
.v1mc-links a{font-size:.84rem;padding:.25rem .55rem;border:1px solid #cbd5e1;border-radius:6px;text-decoration:none;color:#0f172a;background:#f8fafc}
.v1mc-links a:hover{background:#e2e8f0}
.v1mc-nav{margin:.75rem 0 1rem;display:flex;flex-wrap:wrap;gap:.5rem}
.v1mc-nav a{padding:.35rem .7rem;border-radius:8px;background:#1a2e24;color:#e8f5ec;text-decoration:none;font-size:.88rem}
.v1mc-table{width:100%;border-collapse:collapse;font-size:.86rem}
.v1mc-table th,.v1mc-table td{border:1px solid #e2e8f0;padding:.45rem .55rem;text-align:right;vertical-align:top}
.v1mc-table th{background:#f1f5f9}
.v1mc-muted{color:#64748b;font-size:.82rem}
.v1mc-footer{margin-top:1.25rem;font-size:.86rem;color:#475569}
</style></head><body class="m360-rtl"><div style="max-width:1140px;margin:0 auto;padding:1rem">';
}

function v1mc_render_foot(): void
{
    echo '<p class="v1mc-footer">';
    echo '<a href="index.php">ورود اصلی</a> · ';
    echo '<a href="erp-v1-master-console.php">Master Console</a> · ';
    echo '<a href="erp-v1-unit-access-console.php">Unit Access Console</a> · ';
    echo '<a href="erp-moghare-ready.php">Moghare Ready</a> · ';
    echo '<a href="erp-v1-production-signoff.php">Production Signoff</a>';
    echo '</p></div></body></html>';
}
