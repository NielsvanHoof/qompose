<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\UploadedDocument;
use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

use function is_string;
use function parse_url;

/**
 * Build a short-lived signed URL for a private stored document.
 * Rewrites Docker-internal MinIO hosts so browsers can open the link.
 */
final class ResolveDocumentTemporaryUrl
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    /**
     * S3-compatible disks support temporary URLs; local disk keeps streaming downloads in tests.
     */
    public function supportsTemporaryUrl(UploadedDocument $document): bool
    {
        $driver = config('filesystems.disks.'.$document->disk.'.driver');

        return $driver === 's3';
    }

    public function handle(
        UploadedDocument $document,
        DateTimeInterface $expiresAt,
    ): string {
        // disk() is typed as the Filesystem contract; narrow to the adapter that owns temporaryUrl().
        $disk = $this->filesystems->disk($document->disk);

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Disk does not support temporary URLs.');
        }

        $url = $disk->temporaryUrl(
            $document->path,
            $expiresAt,
            [
                // Encourage browsers to download with the original filename.
                'ResponseContentDisposition' => 'attachment; filename="'.$document->original_filename.'"',
            ],
        );

        return $this->rewriteInternalStorageHost($url);
    }

    /**
     * Sail apps talk to http://minio:9000, but browsers need AWS_URL (e.g. localhost:9000).
     */
    private function rewriteInternalStorageHost(string $url): string
    {
        $endpoint = config('filesystems.disks.s3.endpoint');
        $publicBase = config('filesystems.disks.s3.url');

        if (! is_string($endpoint) || $endpoint === '' || ! is_string($publicBase) || $publicBase === '') {
            return $url;
        }

        $internalOrigin = $this->originFromUrl($endpoint);
        $publicOrigin = $this->originFromUrl($publicBase);

        if ($internalOrigin === null || $publicOrigin === null || $internalOrigin === $publicOrigin) {
            return $url;
        }

        return str_replace($internalOrigin, $publicOrigin, $url);
    }

    private function originFromUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
