<?php

declare(strict_types=1);

test('backend business code is organized by domain', function () {
    $root = dirname(__DIR__, 2);

    foreach ([
        'app/Actions/Dossiers',
        'app/Actions/Portal',
        'app/Actions/Questionnaires',
        'app/Actions/Tenancy',
        'app/Http/Controllers/Clients',
        'app/Http/Controllers/Dossiers',
        'app/Http/Controllers/Portal',
        'app/Http/Controllers/Questionnaires',
        'app/Http/Requests/Clients',
        'app/Http/Requests/Dossiers',
        'app/Http/Requests/Portal',
        'app/Http/Requests/Questionnaires',
        'app/Queries/Clients',
        'app/Queries/Dossiers',
        'app/Queries/Portal',
        'app/Queries/Questionnaires',
        'app/Queries/Reporting',
        'app/Queries/Tenancy',
    ] as $directory) {
        expect(is_dir($root.'/'.$directory))->toBeTrue();
    }

    foreach ([
        'app/Actions/Workspace',
        'app/Http/Controllers/Workspace',
        'app/Http/Requests/Workspace',
        'app/Queries/Workspace',
    ] as $legacyDirectory) {
        expect(glob($root.'/'.$legacyDirectory.'/*.php') ?: [])->toBeEmpty();
    }
});

test('frontend business code is organized by feature domain', function () {
    $root = dirname(__DIR__, 2);

    foreach ([
        'resources/js/pages/clients',
        'resources/js/pages/dossiers',
        'resources/js/pages/questionnaires',
        'resources/js/pages/portal',
        'resources/js/features/clients',
        'resources/js/features/dossiers',
        'resources/js/features/document-requests/staff',
        'resources/js/features/document-requests/portal',
        'resources/js/features/questionnaires',
        'resources/js/features/portal',
        'resources/js/components/app-shell',
        'resources/js/components/ui',
    ] as $directory) {
        expect(is_dir($root.'/'.$directory))->toBeTrue();
    }

    // Domain types are co-located with their feature, not in the global barrel.
    foreach ([
        'resources/js/features/clients/types.ts',
        'resources/js/features/dossiers/types.ts',
        'resources/js/features/document-requests/types.ts',
        'resources/js/features/questionnaires/types.ts',
        'resources/js/features/portal/types.ts',
    ] as $typeFile) {
        expect(is_file($root.'/'.$typeFile))->toBeTrue();
    }

    // Legacy pre-feature layout must not come back.
    foreach ([
        'resources/js/components/clients',
        'resources/js/components/dossiers',
        'resources/js/components/questionnaires',
        'resources/js/components/portal',
        'resources/js/types/clients.ts',
        'resources/js/types/dossiers.ts',
        'resources/js/types/questionnaires.ts',
        'resources/js/types/portal.ts',
    ] as $legacyPath) {
        expect(file_exists($root.'/'.$legacyPath))->toBeFalse();
    }
});

test('controllers do not resolve dependencies through service locators', function () {
    $controllers = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            dirname(__DIR__, 2).'/app/Http/Controllers',
            FilesystemIterator::SKIP_DOTS,
        ),
    );

    foreach ($controllers as $controller) {
        if (! $controller->isFile() || $controller->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($controller->getPathname());

        expect($contents)->not->toBeFalse()
            ->and(preg_match('/\b(?:app|resolve)\s*\(/', (string) $contents))->toBe(0);
    }
});
