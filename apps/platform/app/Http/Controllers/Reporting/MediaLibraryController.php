<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\Tenant;
use App\Queries\Reporting\GetMediaLibraryDocuments;
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
        GetMediaLibraryDocuments $getMediaLibraryDocuments,
    ): Response {
        $this->authorize('viewAny', DocumentRequest::class);

        $user = $request->user();
        $canDownload = $user !== null && $user->can(Permission::DownloadDocuments->value);

        return Inertia::render('workspaces/media/index', [
            'can_download' => $canDownload,
            'documents' => $getMediaLibraryDocuments->handle(),
            // Current Spatie query-string values for the shared IndexQuery UI.
            'filters' => request()->input('filter', []),
            'sort' => request()->query('sort'),
            // Toolbar metadata for shared IndexQuery UI (filters / sorts / defaults).
            'indexQuery' => [
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
            ],
        ]);
    }
}
