<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

test('models require explicit eager loading outside production', function () {
    expect(app()->isProduction())->toBeFalse()
        ->and(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::isAutomaticallyEagerLoadingRelationships())->toBeFalse()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});
