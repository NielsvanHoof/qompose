<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Dossier;
use App\Queries\Clients\FetchClientIndexQuery;
use Illuminate\Http\Request;

test('fetch client index maps rows and exposes toolbar metadata', function () {
    ['tenant' => $tenant] = provisionWorkspace();

    $client = Client::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jane Client',
        'email' => 'jane@example.com',
    ]);
    Dossier::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);

    // Bind an empty request so Spatie Query Builder can read filters/sorts.
    app()->instance('request', Request::create('/clients', 'GET'));

    $paginator = app(FetchClientIndexQuery::class)->handle();
    $toolbar = app(FetchClientIndexQuery::class)->toolbarMetadata();

    expect($paginator->total())->toBe(1)
        ->and($paginator->items()[0])->toMatchArray([
            'id' => $client->id,
            'name' => 'Jane Client',
            'email' => 'jane@example.com',
            'dossiers_count' => 2,
        ])
        ->and($toolbar['defaults']['sort'])->toBe('name')
        ->and($toolbar['filters'][0]['key'])->toBe('q')
        ->and(collect($toolbar['sorts'])->pluck('key')->all())->toContain('name', '-name');
});
