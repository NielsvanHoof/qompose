<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Data\Reporting\MediaLibraryDocumentRowData;
use App\Data\Reporting\MediaLibraryDossierSummaryData;
use App\Data\Reporting\MediaLibraryUploadedDocumentData;
use App\Enums\QuestionnaireItemType;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends PaginatedIndexQuery<DocumentRequest>
 */
final class FetchMediaLibraryDocumentsQuery extends PaginatedIndexQuery
{
    /**
     * @return LengthAwarePaginator<int, array{
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
    public function handle(): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, array{id: int, title: string, status: string, updated_at: string|null, dossier: array{id: int, title: string, reference: string|null}, client_name: string, uploaded_document: array{id: int, original_filename: string, size_bytes: int, uploaded_at: string}|null}> */
        return $this->paginate();
    }

    /**
     * @return array{
     *     filters: list<array<string, mixed>>,
     *     sorts: list<array{key: string, label: string}>,
     *     defaults: array{sort: string, per_page: int}
     * }
     */
    public function toolbarMetadata(): array
    {
        return [
            'filters' => [
                ['key' => 'q', 'type' => 'search', 'label' => __('Search')],
                [
                    'key' => 'status',
                    'type' => 'select',
                    'label' => __('Status'),
                    'options' => [
                        ['value' => 'pending', 'label' => __('Pending')],
                        ['value' => 'submitted', 'label' => __('Submitted')],
                        ['value' => 'accepted', 'label' => __('Approved')],
                        ['value' => 'rejected', 'label' => __('Rejected')],
                    ],
                ],
            ],
            'sorts' => [
                ['key' => '-updated_at', 'label' => __('Recently updated')],
                ['key' => 'updated_at', 'label' => __('Oldest updated')],
                ['key' => 'title', 'label' => __('Title (A–Z)')],
                ['key' => '-title', 'label' => __('Title (Z–A)')],
                ['key' => 'status', 'label' => __('Status (A–Z)')],
            ],
            'defaults' => [
                'sort' => '-updated_at',
                'per_page' => 15,
            ],
        ];
    }

    /**
     * @return Builder<DocumentRequest>
     */
    protected function subject(): Builder
    {
        return DocumentRequest::query()
            ->where('type', QuestionnaireItemType::File)
            ->whereHas('dossier')
            ->with([
                'uploadedDocument',
                'dossier:id,client_id,title,reference',
                'dossier.client:id,name',
            ]);
    }

    protected function allowedFilters(): array
    {
        return [
            ScoutSearchFilter::make(DocumentRequest::class),
            AllowedFilter::exact('status'),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('title'),
            AllowedSort::field('status'),
            AllowedSort::field('updated_at'),
        ];
    }

    protected function defaultSort(): string
    {
        return '-updated_at';
    }

    /**
     * @return array{
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
     * }
     */
    protected function mapModel(Model $model): array
    {
        /** @var DocumentRequest $model */
        $dossier = $this->resolveDossier($model);
        $client = $this->resolveClient($dossier);
        $uploaded = $model->uploadedDocument;

        return (new MediaLibraryDocumentRowData(
            id: $model->id,
            title: $model->title,
            status: $model->status->value,
            updatedAt: $model->updated_at?->toIso8601String(),
            dossier: new MediaLibraryDossierSummaryData(
                id: $dossier->id,
                title: $dossier->title,
                reference: $dossier->reference,
            ),
            clientName: $client->name,
            uploadedDocument: $uploaded === null ? null : new MediaLibraryUploadedDocumentData(
                id: $uploaded->id,
                originalFilename: $uploaded->original_filename,
                sizeBytes: $uploaded->size_bytes,
                uploadedAt: $uploaded->uploaded_at->toIso8601String(),
            ),
        ))->toArray();
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
