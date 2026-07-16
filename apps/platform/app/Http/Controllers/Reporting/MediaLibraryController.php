<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Enums\Permission;
use App\Enums\QuestionnaireItemType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class MediaLibraryController extends Controller
{
    /**
     * Cross-dossier list of file document requests (pending and submitted).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DocumentRequest::class);

        $user = $request->user();
        // Reviewers can browse the library but cannot download files.
        $canDownload = $user !== null && $user->can(Permission::DownloadDocuments->value);

        $documentRequests = DocumentRequest::query()
            ->where('type', QuestionnaireItemType::File)
            ->with([
                'uploadedDocument',
                'dossier:id,client_id,title,reference',
                'dossier.client:id,name',
            ])
            ->latest('updated_at')
            ->get();

        return Inertia::render('workspaces/media/index', [
            'can_download' => $canDownload,
            'documents' => $documentRequests->map(function (DocumentRequest $documentRequest): array {
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
            }),
        ]);
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
