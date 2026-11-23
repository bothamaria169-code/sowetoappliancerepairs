<?php
declare(strict_types=1);

require_once BASE_DIR . '/classes/ImageKit.php';

/**
 * Checks an uploaded photo, re-encodes it as WebP (which also strips any hidden
 * data such as the GPS position) and saves it in uploads/media.
 *
 * @return array{ok: bool, path?: string, file?: string, w?: int, h?: int, error?: string}
 */
function saveUploadedPhoto(array $f, string $prefix = 'photo', int $max = 1600): array
{
    if (($f['error'] ?? 4) !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'The photo did not upload.'];
    if ($f['size'] > MAX_IMG_SIZE) return ['ok' => false, 'error' => 'The photo is larger than 8 MB.'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] * $info[1] > 60_000_000) return ['ok' => false, 'error' => 'That file is not a photo we can use.'];
    $im = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($f['tmp_name']),
        default        => false,
    };
    if (!$im) return ['ok' => false, 'error' => 'Use a JPG, PNG or WebP photo.'];
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($f['tmp_name'])['Orientation'] ?? 1);
        $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($deg) $im = imagerotate($im, $deg, 0);
    }
    imagepalettetotruecolor($im);
    if (max(imagesx($im), imagesy($im)) > $max) {
        $r = $max / max(imagesx($im), imagesy($im));
        $im = imagescale($im, (int) round(imagesx($im) * $r), (int) round(imagesy($im) * $r), IMG_BICUBIC);
    }
    if (!is_dir(MEDIA_DIR)) @mkdir(MEDIA_DIR, 0755, true);
    $name = $prefix . '-' . date('ymd') . '-' . bin2hex(random_bytes(8)) . '.webp';
    if (!imagewebp($im, MEDIA_DIR . $name, 80)) return ['ok' => false, 'error' => 'The photo could not be saved (check the uploads/media folder).'];
    return ['ok' => true, 'path' => 'uploads/media/' . $name, 'file' => MEDIA_DIR . $name, 'w' => imagesx($im), 'h' => imagesy($im)];
}
