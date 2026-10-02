<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class IeltsWritingImageService
{
    public function load(?string $url): ?array
    {
        if (blank($url)) return null;
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if (str_starts_with($path, '/storage/') && (! $host || $host === $appHost)) {
            $relative = rawurldecode(substr($path, 9));
            if (str_contains($relative, '..') || str_contains($relative, '\\')) throw new RuntimeException('Invalid image path.');
            $disk = Storage::disk('public');
            if (! $disk->exists($relative) || $disk->size($relative) > 5 * 1024 * 1024) throw new RuntimeException('Writing image missing or too large.');
            $bytes = $disk->get($relative);
        } else {
            $parts = parse_url($url);
            if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                || ! $host || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) throw new RuntimeException('Invalid Writing image URL.');
            $addresses = gethostbynamel($host) ?: [];
            if (! $addresses) throw new RuntimeException('Cannot resolve Writing image host.');
            foreach ($addresses as $address) {
                if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw new RuntimeException('Writing image must be publicly accessible.');
            }
            $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
            $response = Http::connectTimeout(5)->timeout(20)->withOptions([
                'allow_redirects' => false, 'stream' => true,
                'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$addresses[0]}"]],
            ])->get($url);
            if (! $response->successful()) throw new RuntimeException('Writing image could not be downloaded.');
            $body = $response->toPsrResponse()->getBody();
            $bytes = '';
            try {
                $deadline = microtime(true) + 20;
                while (! $body->eof()) {
                    if (microtime(true) > $deadline) throw new RuntimeException('Writing image timed out.');
                    $bytes .= $body->read(65536);
                    if (strlen($bytes) > 5 * 1024 * 1024) throw new RuntimeException('Writing image too large.');
                }
            } finally { $body->close(); }
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) || ! @getimagesizefromstring($bytes)) throw new RuntimeException('Writing image format is invalid.');
        return ['data' => base64_encode($bytes), 'mime_type' => $mime];
    }
}
