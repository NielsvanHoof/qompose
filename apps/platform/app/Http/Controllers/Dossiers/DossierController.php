<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\StoreDossierRequest;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

use function is_string;

final class DossierController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Dossier::class);

        $dossiers = Dossier::query()
            ->with('client:id,name')
            ->latest()
            ->get(['id', 'client_id', 'title', 'reference', 'status']);

        return Inertia::render('dossiers/index', [
            'dossiers' => $dossiers->map(function (Dossier $dossier): array {
                $client = $this->resolveClient($dossier);

                return [
                    'id' => $dossier->id,
                    'client_name' => $client->name,
                    'title' => $dossier->title,
                    'reference' => $dossier->reference,
                    'status' => $dossier->status->value,
                ];
            }),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Dossier::class);

        return Inertia::render('dossiers/create', [
            'clients' => Client::query()
                ->get(['id', 'name', 'email'])
                ->sortBy('name')
                ->values()
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                ]),
        ]);
    }

    public function store(StoreDossierRequest $request): RedirectResponse
    {
        $dossier = Dossier::query()->create($request->validated());

        return to_route('workspaces.dossiers.show', $dossier);
    }

    public function show(Request $request, Dossier $dossier): Response
    {
        $this->authorize('view', $dossier);

        $dossier->load([
            'client:id,name,email',
            'documentRequests' => fn ($query) => $query
                ->with('uploadedDocument')
                ->oldest('sort_order'),
            'clientAccessGrants' => fn ($query) => $query->latest(),
        ]);

        app(LogAuditActivity::class)(
            AuditEvent::DossierViewed,
            $dossier,
        );

        $client = $this->resolveClient($dossier);

        // oldest('name') instead of orderBy — Eloquent orderBy trips phpstan-strict-rules.
        $templates = QuestionnaireTemplate::queryVisibleToCurrentTenant()
            ->withCount('items')
            ->oldest('name')
            ->get(['id', 'name', 'category', 'tenant_id']);

        return Inertia::render('dossiers/show', [
            // One-time plain token after creating a grant (never stored in plaintext).
            'access_grant_token' => $this->flashedAccessGrantToken($request),
            'access_grant_portal_url' => $this->flashedAccessGrantPortalUrl($request),
            'templates' => $templates->map(fn (QuestionnaireTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'category_label' => $template->category->label(),
                'items_count' => $template->items_count,
                'is_system' => $template->isSystem(),
            ]),
            'dossier' => [
                'id' => $dossier->id,
                'title' => $dossier->title,
                'reference' => $dossier->reference,
                'status' => $dossier->status->value,
                'client' => [
                    'name' => $client->name,
                    'email' => $client->email,
                ],
                'document_requests' => $dossier->documentRequests
                    ->map(function ($documentRequest): array {
                        $uploaded = $documentRequest->uploadedDocument;

                        return [
                            'id' => $documentRequest->id,
                            'type' => $documentRequest->type->value,
                            'title' => $documentRequest->title,
                            'instructions' => $documentRequest->instructions,
                            'status' => $documentRequest->status->value,
                            'answer_text' => $documentRequest->answer_text,
                            'answer_boolean' => $documentRequest->answer_boolean,
                            'answered_at' => $documentRequest->answered_at?->toIso8601String(),
                            'sort_order' => $documentRequest->sort_order,
                            'uploaded_document' => $uploaded === null ? null : [
                                'id' => $uploaded->id,
                                'original_filename' => $uploaded->original_filename,
                                'size_bytes' => $uploaded->size_bytes,
                                'uploaded_at' => $uploaded->uploaded_at->toIso8601String(),
                            ],
                        ];
                    }),
                'access_grants' => $dossier->clientAccessGrants
                    ->map(fn (ClientAccessGrant $grant): array => [
                        'id' => $grant->id,
                        'expires_at' => $grant->expires_at->toIso8601String(),
                        'revoked_at' => $grant->revoked_at?->toIso8601String(),
                        'last_used_at' => $grant->last_used_at?->toIso8601String(),
                        'is_valid' => $grant->isValid(),
                    ]),
            ],
        ]);
    }

    private function flashedAccessGrantToken(Request $request): ?string
    {
        $token = $request->session()->pull('access_grant_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function flashedAccessGrantPortalUrl(Request $request): ?string
    {
        $url = $request->session()->pull('access_grant_portal_url');

        return is_string($url) && $url !== '' ? $url : null;
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
