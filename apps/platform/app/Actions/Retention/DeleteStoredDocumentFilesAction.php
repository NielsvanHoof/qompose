<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\UploadedDocument;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

final class DeleteStoredDocumentFilesAction
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    /**
     * @param  Collection<int, UploadedDocument>  $uploadedDocuments
     */
    public function handle(Collection $uploadedDocuments): void
    {
        foreach ($uploadedDocuments as $uploadedDocument) {
            try {
                $deleted = $this->filesystems
                    ->disk($uploadedDocument->disk)
                    ->delete($uploadedDocument->path);
            } catch (Throwable $exception) {
                report(new RuntimeException(
                    'Failed to remove a stored file during legal retention purge.',
                    previous: $exception,
                ));

                throw $exception;
            }

            if (! $deleted) {
                report(new RuntimeException(
                    'Failed to remove a stored file during legal retention purge.',
                ));

                throw new RuntimeException(
                    'Failed to remove a stored file during legal retention purge.',
                );
            }
        }
    }
}
