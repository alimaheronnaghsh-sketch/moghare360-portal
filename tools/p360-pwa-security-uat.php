<?php
declare(strict_types=1);

/**
 * Phase 6 — PWA security / installability static validation (no secrets).
 * Exit 0 when all assertions pass.
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public_html';
$fail = 0;
$pass = 0;

function assert_true(string $name, bool $ok, string $detail = ''): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo "PASS {$name}" . ($detail !== '' ? " {$detail}" : '') . PHP_EOL;
    } else {
        $fail++;
        echo "FAIL {$name}" . ($detail !== '' ? " {$detail}" : '') . PHP_EOL;
    }
}

$manifestPath = $root . DIRECTORY_SEPARATOR . 'manifest.webmanifest';
$swPath = $root . DIRECTORY_SEPARATOR . 'service-worker.js';
$offlinePath = $root . DIRECTORY_SEPARATOR . 'offline.html';
$pwaJs = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'm360-pwa.js';
$icon192 = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'icon-192.png';
$icon512 = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . 'icon-512.png';

assert_true('manifest_exists', is_file($manifestPath));
assert_true('sw_exists', is_file($swPath));
assert_true('offline_exists', is_file($offlinePath));
assert_true('pwa_js_exists', is_file($pwaJs));
assert_true('icon_192', is_file($icon192));
assert_true('icon_512', is_file($icon512));

$manifestRaw = is_file($manifestPath) ? (string)file_get_contents($manifestPath) : '';
$manifest = json_decode($manifestRaw, true);
assert_true('manifest_json', is_array($manifest), json_last_error_msg());
if (is_array($manifest)) {
    assert_true('manifest_name', isset($manifest['name']) && $manifest['name'] !== '');
    assert_true('manifest_short_name', isset($manifest['short_name']) && $manifest['short_name'] !== '');
    assert_true('manifest_start_url', isset($manifest['start_url']));
    assert_true('manifest_scope', isset($manifest['scope']));
    assert_true('manifest_display', ($manifest['display'] ?? '') === 'standalone');
    assert_true('manifest_theme', isset($manifest['theme_color']));
    assert_true('manifest_bg', isset($manifest['background_color']));
    assert_true('manifest_icons', isset($manifest['icons']) && is_array($manifest['icons']) && count($manifest['icons']) >= 2);
}

$sw = is_file($swPath) ? (string)file_get_contents($swPath) : '';
assert_true('sw_has_version', str_contains($sw, 'm360-pwa-static-v'));
assert_true('sw_no_index_php_precache', !preg_match("/PRECACHE_URLS[\\s\\S]*index\\.php/", $sw));
assert_true('sw_blocks_php', str_contains($sw, '.php'));
assert_true('sw_blocks_api', str_contains($sw, '/api/'));
assert_true('sw_offline_shell', str_contains($sw, 'offline.html'));
assert_true('sw_clear_caches_msg', str_contains($sw, 'CLEAR_CACHES'));
assert_true('sw_activate_cleanup', str_contains($sw, 'caches.delete'));
assert_true('sw_no_store_sensitive', str_contains($sw, 'no-store'));

// Forbidden: caching erp HTML patterns in precache list
$precacheBlock = '';
if (preg_match('/const PRECACHE_URLS\s*=\s*\[(.*?)\];/s', $sw, $m)) {
    $precacheBlock = $m[1];
}
assert_true('sw_no_erp_precache', $precacheBlock !== '' && !str_contains($precacheBlock, 'erp-'));
assert_true('sw_no_customer_precache', $precacheBlock !== '' && !str_contains($precacheBlock, 'customer-'));

$pwa = is_file($pwaJs) ? (string)file_get_contents($pwaJs) : '';
assert_true('client_registers_sw', str_contains($pwa, 'serviceWorker.register'));
assert_true('client_install_prompt', str_contains($pwa, 'beforeinstallprompt'));
assert_true('client_logout_clear', str_contains($pwa, 'CLEAR_CACHES') || str_contains($pwa, 'onLogout'));

$indexHtml = (string)@file_get_contents($root . DIRECTORY_SEPARATOR . 'index.html');
assert_true('index_has_manifest', str_contains($indexHtml, 'manifest.webmanifest'));
assert_true('index_registers_pwa', str_contains($indexHtml, 'm360-pwa.js'));
assert_true('index_no_unregister_all', !str_contains($indexHtml, 'unregister()'));

$logout = (string)@file_get_contents($root . DIRECTORY_SEPARATOR . 'staff-logout.php');
assert_true('logout_clears_cache', str_contains($logout, 'onLogout') || str_contains($logout, 'CLEAR_CACHES'));

// Binary / secret exclusion markers in repo templates
$gitignore = (string)@file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.gitignore');
assert_true('gitignore_apk', str_contains($gitignore, '*.apk'));
assert_true('gitignore_aab', str_contains($gitignore, '*.aab'));
assert_true('gitignore_keystore', str_contains($gitignore, '*.keystore') || str_contains($gitignore, 'keystore'));

echo 'SUMMARY pass=' . $pass . ' fail=' . $fail . PHP_EOL;
exit($fail > 0 ? 1 : 0);
