<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class IeltsAudioService
{
    public const MAX_SIZE_KB = 51200;

    public const MIME_EXTENSIONS = [
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/x-mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/vnd.wave' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/x-ogg' => 'ogg',
        'application/ogg' => 'ogg',
        'audio/mp4' => 'm4a',
        'audio/x-m4a' => 'm4a',
    ];

    public function upload(UploadedFile $file): string
    {
        $this->validateSize((int) $file->getSize());
        $extension = $this->extensionForMime((string) $file->getMimeType());

        try {
            $path = $file->storePubliclyAs('ielts/audio', Str::ulid().'.'.$extension, 'public');
        } catch (Throwable $exception) {
            throw new RuntimeException('Không lưu được file audio. Vui lòng thử lại.', previous: $exception);
        }

        if (! $path) {
            throw new RuntimeException('Không lưu được file audio. Vui lòng thử lại.');
        }

        return '/storage/'.$path;
    }

    public function fromLink(string $link): string
    {
        $link = trim($link);
        $this->validateLink($link);

        return $this->isGoogleDriveLink($link) ? $this->importGoogleDrive($link) : $link;
    }

    public function isGoogleDriveLink(string $link): bool
    {
        return in_array(strtolower(parse_url($link, PHP_URL_HOST) ?? ''), ['drive.google.com', 'drive.usercontent.google.com'], true);
    }

    public function validateLink(?string $link): void
    {
        $link = trim($link ?? '');
        if ($link === '') {
            return;
        }

        if (str_starts_with($link, '/storage/') && preg_match('~^/storage/[a-zA-Z0-9_./-]+$~', $link) && ! str_contains($link, '..')) {
            return;
        }

        $parts = parse_url($link);
        if (! filter_var($link, FILTER_VALIDATE_URL) || ! is_array($parts)
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Nhập link audio http:// hoặc https:// hợp lệ, hoặc chọn một file để tải lên.');
        }

        if ($this->isGoogleDriveLink($link)) {
            $this->driveFileParameters($link);
        }
    }

    /** Only public Drive files are imported. No account cookies or credentials are used. */
    private function importGoogleDrive(string $link): string
    {
        $parameters = $this->driveFileParameters($link);
        $url = 'https://drive.google.com/uc?'.http_build_query(['export' => 'download', ...$parameters]);
        $cookies = new CookieJar;
        $temporary = tmpfile();
        if ($temporary === false) {
            throw new RuntimeException('Không tạo được file tạm để nhập audio.');
        }

        // Keep imports below the web server's request timeout.
        $deadline = microtime(true) + 45;
        $confirmed = false;

        try {
            for ($hop = 0; $hop < 8; $hop++) {
                $this->assertGoogleDownloadUrl($url);
                $remaining = $deadline - microtime(true);
                if ($remaining <= 0) {
                    throw new RuntimeException('Tải audio từ Google Drive quá lâu. Hãy tải file về máy và dùng Tải audio lên.');
                }

                try {
                    $response = Http::connectTimeout(10)->timeout(min(45, $remaining))
                        ->withHeaders(['Accept' => 'audio/*, application/octet-stream', 'Accept-Encoding' => 'identity'])
                        ->withOptions(['allow_redirects' => false, 'stream' => true, 'read_timeout' => 10, 'cookies' => $cookies])
                        ->get($url);
                } catch (Throwable $exception) {
                    throw new RuntimeException('Không kết nối được Google Drive. Vui lòng thử lại hoặc tải file lên.', previous: $exception);
                }

                $body = $response->toPsrResponse()->getBody();
                try {
                    if ($response->redirect()) {
                        $location = $response->header('Location');
                        if (! $location) {
                            throw new RuntimeException('Link Google Drive không trả về file audio.');
                        }
                        $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                        if (parse_url($url, PHP_URL_HOST) === 'accounts.google.com') {
                            throw new RuntimeException('File Google Drive đang yêu cầu đăng nhập. Hãy bật "Anyone with the link" và cho phép tải xuống, hoặc tải file lên từ máy.');
                        }
                        continue;
                    }

                    if (! $response->successful()) {
                        throw new RuntimeException('Không tải được file từ Google Drive. Kiểm tra quyền "Anyone with the link", quyền tải xuống và giới hạn truy cập của file.');
                    }

                    $contentType = strtolower($response->header('Content-Type') ?? '');
                    if (str_contains($contentType, 'text/html')) {
                        // A large-file confirmation page is distinct from a downloaded file.
                        $html = $body->read(512 * 1024);
                        $url = $confirmed ? null : $this->confirmationUrl($html, $parameters);
                        if (! $url) {
                            throw new RuntimeException('Google Drive đang yêu cầu đăng nhập, không cho tải file hoặc đã hết lượt tải. Hãy bật "Anyone with the link" và cho phép tải xuống, hoặc tải audio lên từ máy.');
                        }
                        $confirmed = true;
                        continue;
                    }

                    $length = $response->header('Content-Length');
                    if ($length !== null && (int) $length > self::MAX_SIZE_KB * 1024) {
                        throw new RuntimeException('File audio vượt quá 50 MB.');
                    }

                    $size = 0;
                    while (! $body->eof()) {
                        if (microtime(true) > $deadline) {
                            throw new RuntimeException('Tải audio quá lâu. Hãy thử tải file lên từ máy.');
                        }
                        try {
                            $chunk = $body->read(256 * 1024);
                        } catch (Throwable $exception) {
                            throw new RuntimeException('Kết nối bị gián đoạn khi tải audio. Hãy thử lại hoặc tải file lên từ máy.', previous: $exception);
                        }
                        $size += strlen($chunk);
                        if ($size > self::MAX_SIZE_KB * 1024) {
                            throw new RuntimeException('File audio vượt quá 50 MB.');
                        }
                        if (fwrite($temporary, $chunk) !== strlen($chunk)) {
                            throw new RuntimeException('Không ghi được file audio vào storage.');
                        }
                    }

                    $this->validateSize($size);
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file(stream_get_meta_data($temporary)['uri']);
                    $extension = $this->extensionForMime($mime ?: '');
                    $path = 'ielts/audio/'.Str::ulid().'.'.$extension;
                    rewind($temporary);
                    try {
                        $stored = Storage::disk('public')->put($path, $temporary, ['visibility' => 'public']);
                    } catch (Throwable $exception) {
                        throw new RuntimeException('Không lưu được audio vào storage. Vui lòng thử lại.', previous: $exception);
                    }
                    if (! $stored) {
                        throw new RuntimeException('Không lưu được audio từ Google Drive.');
                    }

                    return '/storage/'.$path;
                } finally {
                    $body->close();
                }
            }

            throw new RuntimeException('Google Drive chuyển hướng quá nhiều lần. Hãy tải file về máy và dùng Tải audio lên.');
        } catch (InvalidArgumentException | RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new RuntimeException('Không nhập được audio từ Google Drive. Vui lòng thử lại hoặc tải file lên.', previous: $exception);
        } finally {
            fclose($temporary);
        }
    }

    private function driveFileParameters(string $link): array
    {
        $parts = parse_url($link);
        parse_str($parts['query'] ?? '', $query);
        preg_match('~/file/(?:u/\d+/)?d/([a-zA-Z0-9_-]+)~', $parts['path'] ?? '', $match);
        $id = $match[1] ?? $query['id'] ?? '';
        if (! is_string($id) || ! preg_match('/^[a-zA-Z0-9_-]{10,200}$/', $id)) {
            throw new InvalidArgumentException('Dán link của một file audio Google Drive, không dùng link thư mục.');
        }

        $parameters = ['id' => $id];
        if (isset($query['resourcekey'])) {
            if (! is_string($query['resourcekey']) || ! preg_match('/^[a-zA-Z0-9_-]{1,200}$/', $query['resourcekey'])) {
                throw new InvalidArgumentException('Link Google Drive có resource key không hợp lệ. Hãy sao chép lại link chia sẻ.');
            }
            $parameters['resourcekey'] = $query['resourcekey'];
        }

        return $parameters;
    }

    private function assertGoogleDownloadUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = in_array($host, ['drive.google.com', 'docs.google.com', 'drive.usercontent.google.com'], true)
            || str_ends_with($host, '.googleusercontent.com');

        if (! $allowed || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || ($parts['port'] ?? 443) !== 443) {
            throw new RuntimeException('Link tải file không thuộc Google Drive. Vui lòng sao chép lại link của file audio.');
        }
    }

    private function confirmationUrl(string $html, array $parameters): ?string
    {
        // Rebuild the known Google endpoint; never request an arbitrary HTML form action.
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);
            $form = $xpath->query('//form[@id="download-form"]')->item(0);
            if (! $form) {
                return null;
            }
            $download = ['export' => 'download', ...$parameters];
            foreach ($xpath->query('.//input[@type="hidden"]', $form) as $input) {
                $name = $input->getAttribute('name');
                $value = $input->getAttribute('value');
                if (in_array($name, ['confirm', 'uuid'], true) && preg_match('/^[a-zA-Z0-9_-]{1,200}$/', $value)) {
                    $download[$name] = $value;
                }
            }

            return isset($download['confirm'])
                ? 'https://drive.usercontent.google.com/download?'.http_build_query($download)
                : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function validateSize(int $size): void
    {
        if ($size < 1 || $size > self::MAX_SIZE_KB * 1024) {
            throw new InvalidArgumentException('Chọn file audio không rỗng, dung lượng tối đa 50 MB.');
        }
    }

    private function extensionForMime(string $mime): string
    {
        return self::MIME_EXTENSIONS[$mime]
            ?? throw new InvalidArgumentException('File không phải audio MP3, WAV, OGG hoặc M4A hợp lệ.');
    }
}
