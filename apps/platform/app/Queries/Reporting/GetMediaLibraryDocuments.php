<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

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
final class GetMediaLibraryDocuments extends PaginatedIndexQuery
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
     * @return Builder<DocumentRequest>
     */
    protected function subject(): Builder
    {
        return DocumentRequest::query()
            ->where('type', QuestionnaireItemType::File)
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

        return [
            'id' => $model->id,
            'title' => $model->title,
            'status' => $model->status->value,
            'updated_at' => $model->updated_at?->toIso8601String(),
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
