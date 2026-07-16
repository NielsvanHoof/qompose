<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\QuestionnaireItemType;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use RuntimeException;

final class GetMediaLibraryDocuments
{
    /**
     * @return array<int, array{
     *     id: int,
     *     title: string,
     *     status: string,
     *     updated_at: string|null,
     *     dossier: array{id: int, title: string, reference: string|null},
     *     client_name: string,
     *     uploaded_document: array{
     *         id: int,
     *         original_filename: string,
     *         size_bytes: int,
     *         uploaded_at: string
     *     }|null
     * }>
     */
    public function __invoke(): array
    {
        return DocumentRequest::query()
            ->where('type', QuestionnaireItemType::File)
            ->with([
                'uploadedDocument',
                'dossier:id,client_id,title,reference',
                'dossier.client:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->map(function (DocumentRequest $documentRequest): array {
                $dossier = $this->resolveDossier($documentRequest);
                $client = $this->resolveClient($dossier);
                $uploaded = $documentRequest->uploadedDocument;

                return [
                    'id' => $documentRequest->id,
                    'title' => $documentRequest->title,
                    'status' => $documentRequest->status->value,
                    'updated_at' => $documentRequest->updated_at?->toIso8601String(),
                    'dossier' => [
                        'id' => $dossier->id,
                        'title' => $dossier->title,
                        'reference' => $dossier->reference,
                    ],
                    'client_name' => $client->name,
                    'uploaded_document' => $uploaded === null ? null : [
                        'id' => $uploaded->id,
                        'original_filename' => $uploaded->original_filename,
                        'size_bytes' => $uploaded->size_bytes,
                        'uploaded_at' => $uploaded->uploaded_at->toIso8601String(),
                    ],
                ];
            })
            ->all();
    }

    private function resolveDossier(DocumentRequest $documentRequest): Dossier
    {
        $dossier = $documentRequest->dossier;

        if (! $dossier instanceof Dossier) {
            throw new RuntimeException('Document request dossier is missing.');
        }

        return $dossier;
    }

    private function resolveClient(Dossier $dossier): Client
    {
        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        return $client;
    }
}
