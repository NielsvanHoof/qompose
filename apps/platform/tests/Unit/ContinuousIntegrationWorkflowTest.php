<?php

declare(strict_types=1);

test('the complete quality gate includes the production frontend build', function () {
    /** @var array{scripts: array<string, list<string>>} $composer */
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['ci:check'])
        ->toContain('npm run build');
});

test('the repository-level platform workflow runs quality migrations and an immutable image build', function () {
    $workflow = (string) file_get_contents(
        dirname(__DIR__, 4).'/.github/workflows/platform.yml',
    );

    expect($workflow)
        ->toContain('pull_request:')
        ->toContain('working-directory: apps/platform')
        ->toContain('apps/platform/**')
        ->toContain('composer ci:check')
        ->toContain('postgres:17')
        ->toContain('artisan migrate:fresh')
        ->toContain('artisan migrate:rollback')
        ->toContain('Dockerfile.production')
        ->toContain('type=oci')
        ->toContain('actions/upload-artifact@v7')
        ->toContain('github.event.pull_request.head.sha || github.sha');
});

test('the repository-level deployment workflow runs from the platform application directory', function () {
    $workflow = (string) file_get_contents(
        dirname(__DIR__, 4).'/.github/workflows/deploy.yml',
    );

    expect($workflow)
        ->toContain('working-directory: apps/platform/infra')
        ->toContain('--file apps/platform/Dockerfile.production')
        ->toContain('              apps/platform');
});
