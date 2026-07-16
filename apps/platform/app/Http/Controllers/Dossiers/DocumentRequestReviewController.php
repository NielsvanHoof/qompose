<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossiers;

use App\Actions\Dossiers\ReviewDocumentRequest;
use App\Enums\DocumentRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dossiers\ReviewDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function is_string;

final class DocumentRequestReviewController extends Controller
{
    public function store(
        Tenant $tenant,
        ReviewDocumentRequestRequest $request,
        Dossier $dossier,
        DocumentRequest $documentRequest,
        ReviewDocumentRequest $reviewDocumentRequest,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $decision = DocumentRequestStatus::from((string) $request->validated('decision'));
        $rejectionReason = $request->validated('rejection_reason');

        $reviewDocumentRequest->handle(
            $documentRequest,
            $user,
            $decision,
            is_string($rejectionReason) ? $rejectionReason : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $decision === DocumentRequestStatus::Accepted
                ? 'Item approved.'
                : 'Changes requested. The client notification has been queued.',
        ]);

        return to_route(
            'workspaces.dossiers.show',
            $this->workspaceRouteParameters(['dossier' => $dossier]),
        );
    }
}
