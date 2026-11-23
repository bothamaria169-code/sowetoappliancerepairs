<?php
declare(strict_types=1);

/**
 * ImageKit: photos uploaded in the admin also go to the ImageKit CDN, which
 * then serves them resized and as WebP/AVIF. If ImageKit is not set up or an
 * upload fails, the site simply uses the copy in uploads/media.
 *
 * Keys: config/secrets.php (imagekit.io → Developer options → API keys).
 */
final class ImageKit
{
    private const UPLOAD = 'https://upload.imagekit.io/api/v1/files/upload';
    private const API    = 'https://api.imagekit.io/v1/files';
    public const FOLDER  = '/sowetoappliancerepairs';

    private static function key(): string
    {
        return trim((string) secret('imagekit_private_key', ''));
    }

    public static function ready(): bool
    {
        return self::key() !== '' && self::key() !== 'CHANGE_ME';
    }

    /** @return array{ok: bool, url: string, id: string, error: string} */
    public static function upload(string $file, string $name): array
    {
        if (!self::ready()) return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'ImageKit keys are missing in config/secrets.php.'];
        $r = self::call(self::UPLOAD, [
            'file' => new CURLFile($file, null, $name), 'fileName' => $name,
            'folder' => self::FOLDER, 'useUniqueFileName' => 'false',
        ]);
        return ['ok' => !empty($r['json']['url']), 'url' => (string) ($r['json']['url'] ?? ''), 'id' => (string) ($r['json']['fileId'] ?? ''), 'error' => $r['error']];
    }

    public static function delete(string $fileId): bool
    {
        return self::call(self::API . '/' . rawurlencode($fileId), null, 'DELETE')['status'] === 204;
    }

    /** Lists one file to prove the key works. Returns '' when fine. */
    public static function test(): string
    {
        if (!self::ready()) return 'ImageKit keys are missing in config/secrets.php.';
        $r = self::call(self::API . '?limit=1');
        return $r['status'] === 200 ? '' : $r['error'];
    }

    /** A resized version of an ImageKit photo. */
    public static function sized(string $url, int $w): string
    {
        return explode('?', $url)[0] . '?tr=w-' . $w . ',q-75,f-auto';
    }

    /** @return array{status: int, json: array, error: string} */
    private static function call(string $url, ?array $post = null, string $method = 'GET'): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => self::key() . ':', CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 10,
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post] : [CURLOPT_CUSTOMREQUEST => $method]));
        $raw  = (string) curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode($raw, true);
        $json = is_array($json) ? $json : [];
        $bad  = $err !== '' || $code >= 400;
        return ['status' => $code, 'json' => $json, 'error' => $bad ? ($err ?: (string) ($json['message'] ?? 'HTTP ' . $code)) : ''];
    }
}
