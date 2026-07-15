<?php

declare(strict_types=1);

namespace App\Actions\Workspace;

use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\UploadedDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

use function is_int;
use function is_string;

final class UploadDocumentForRequest
{
    /**
     * Store a file for a document request and mark the request as uploaded.
     * Replaces any previous upload for the same request.
     */
    public function __invoke(DocumentRequest $documentRequest, UploadedFile $file): UploadedDocument
    {
        $disk = (string) config('filesystems.default', 'local');
        $directory = sprintf(
            'tenants/%d/dossiers/%d',
            $documentRequest->tenant_id,
            $documentRequest->dossier_id,
        );

        return DB::transaction(function () use ($documentRequest, $file, $disk, $directory): UploadedDocument {
            $existing = $documentRequest->uploadedDocument;

            if ($existing instanceof UploadedDocument) {
                Storage::disk($existing->disk)->delete($existing->path);
                $existing->delete();
            }

            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
            $path = $file->storeAs($directory, $filename, $disk);

            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Failed to store the uploaded document.');
            }

            $sizeBytes = $file->getSize();

            $uploadedDocument = $documentRequest->uploadedDocument()->create([
                'disk' => $disk,
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size_bytes' => is_int($sizeBytes) ? $sizeBytes : 0,
                'uploaded_at' => now(),
            ]);

            $documentRequest->update([
                'status' => DocumentRequestStatus::Uploaded,
            ]);

            return $uploadedDocument;
        });
    }
}
