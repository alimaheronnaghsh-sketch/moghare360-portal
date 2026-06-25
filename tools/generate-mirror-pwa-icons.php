<?php
declare(strict_types=1);

/**
 * Generate simple brand PWA icons for mirror package (no external deps).
 */

$root = dirname(__DIR__);
$iconsDir = $root . DIRECTORY_SEPARATOR . 'release' . DIRECTORY_SEPARATOR . 'moghare360-mirror-site-package'
    . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons';

if (!is_dir($iconsDir) && !mkdir($iconsDir, 0775, true) && !is_dir($iconsDir)) {
    fwrite(STDERR, "Cannot create icons dir\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required for icon generation\n");
    exit(1);
}

function mogh_write_icon(string $path, int $size): void
{
    $img = imagecreatetruecolor($size, $size);
    if ($img === false) {
        throw new RuntimeException('imagecreatetruecolor failed');
    }

    $bg = imagecolorallocate($img, 15, 23, 20);
    $accent = imagecolorallocate($img, 34, 197, 94);
    $white = imagecolorallocate($img, 245, 245, 245);
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);
    imagefilledrectangle($img, (int)($size * 0.12), (int)($size * 0.12), (int)($size * 0.88), (int)($size * 0.88), $accent);

    $font = 5;
    $text = 'M360';
    $tw = imagefontwidth($font) * strlen($text);
    $th = imagefontheight($font);
    imagestring($img, $font, (int)(($size - $tw) / 2), (int)(($size - $th) / 2), $text, $white);

    if (!imagepng($img, $path)) {
        imagedestroy($img);
        throw new RuntimeException('imagepng failed: ' . $path);
    }
    imagedestroy($img);
}

mogh_write_icon($iconsDir . DIRECTORY_SEPARATOR . 'icon-192.png', 192);
mogh_write_icon($iconsDir . DIRECTORY_SEPARATOR . 'icon-512.png', 512);

echo "PWA icons generated in release/moghare360-mirror-site-package/public_html/assets/icons/\n";
