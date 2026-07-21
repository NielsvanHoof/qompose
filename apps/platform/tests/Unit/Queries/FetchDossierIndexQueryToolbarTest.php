<?php

declare(strict_types=1);

use App\Queries\Dossiers\FetchDossierIndexQuery;

test('dossier index toolbar metadata includes status and client filters', function () {
    $toolbar = app(FetchDossierIndexQuery::class)->toolbarMetadata();

    expect($toolbar['defaults']['sort'])->toBe('-updated_at')
        ->and(collect($toolbar['filters'])->pluck('key')->all())->toBe(['q', 'status', 'client'])
        ->and(collect($toolbar['filters'])->firstWhere('key', 'status')['options'] ?? [])
        ->toHaveCount(4)
        ->and(collect($toolbar['sorts'])->pluck('key')->all())
        ->toContain('-updated_at', 'title', 'status');
});
