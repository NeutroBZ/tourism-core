<?php
/**
 * One-off utility to resize admin menu icons to 20x20 PNG.
 *
 * Usage (from plugin root):
 *   php tools/resize-icons.php
 */

$root = dirname(__DIR__);
$icons = [
    $root . '/assets/ico/tours.svg',
    $root . '/assets/ico/destinations.svg',
    $root . '/assets/ico/bookings.svg',
];

$targetW = 20;
$targetH = 20;

foreach ($icons as $path) {
    if (!file_exists($path)) {
        fwrite(STDERR, "Missing: {$path}\n");
        continue;
    }

    $src = @imagecreatefrompng($path);
    if (!$src) {
        fwrite(STDERR, "Failed to read PNG: {$path}\n");
        continue;
    }

    $dst = imagecreatetruecolor($targetW, $targetH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);

    $srcW = imagesx($src);
    $srcH = imagesy($src);

    // Fit to square (center-crop) to avoid distortion.
    $side = min($srcW, $srcH);
    $srcX = (int) floor(($srcW - $side) / 2);
    $srcY = (int) floor(($srcH - $side) / 2);

    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $side, $side);
    imagepng($dst, $path);

    imagedestroy($src);
    imagedestroy($dst);

    echo "Resized: {$path}\n";
}

