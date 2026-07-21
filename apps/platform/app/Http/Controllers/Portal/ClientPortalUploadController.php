<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\SubmitPortalUpload;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveClientPortalGrant;
use App\Http\Requests\Portal\StorePortalUploadedDocumentRequest;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class ClientPortalUploadController extends Controller
{
    /**
     * Accept a client upload for one document request on the grant's dossier.
     */
    public function store(
        StorePortalUploadedDocumentRequest $request,
        int $documentRequest,
        SubmitPortalUpload $submitPortalUpload,
    ): RedirectResponse {
        $grant = $this->grantFromRequest($request);

        $documentRequestModel = DocumentRequest::query()->findOrFail($documentRequest);

        abort_unless($documentRequestModel->dossier_id === $grant->dossier_id, 404);

        $file = $request->file('document');

        if ($file === null) {
            return back()->withErrors(['document' => __('A document file is required.')]);
        }

        $submitPortalUpload->handle(
            $documentRequestModel,
            $grant,
            $file,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Document uploaded. Thank you.'),
        ]);

        return to_route('portal.show');
    }

    private function grantFromRequest(StorePortalUploadedDocumentRequest $request): ClientAccessGrant
    {
        $grant = $request->attributes->get(ResolveClientPortalGrant::REQUEST_ATTRIBUTE);

        if (! $grant instanceof ClientAccessGrant) {
            abort(404);
        }

        return $grant;
    }
}
