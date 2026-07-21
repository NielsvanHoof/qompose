<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\Tenant;
use App\Queries\Reporting\FetchMediaLibraryDocumentsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MediaLibraryController extends Controller
{
    /**
     * Cross-dossier list of file document requests (pending and submitted).
     */
    public function index(
        Tenant $tenant,
        Request $request,
        FetchMediaLibraryDocumentsQuery $fetchMediaLibraryDocuments,
    ): Response {
        $this->authorize('viewAny', DocumentRequest::class);

        $user = $request->user();
        $canDownload = $user !== null && $user->can(Permission::DownloadDocuments->value);

        return Inertia::render('workspaces/media/index', [
            'can_download' => $canDownload,
            'documents' => $fetchMediaLibraryDocuments->handle(),
            ...$fetchMediaLibraryDocuments->indexQueryProps(),
        ]);
    }
}
