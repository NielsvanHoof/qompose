<?php

declare(strict_types=1);

namespace App\Queries\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laravel\Scout\Builder as ScoutBuilder;
use Spatie\QueryBuilder\AllowedFilter;

use function is_callable;
use function is_int;
use function is_string;

/**
 * Spatie AllowedFilter that constrains an Eloquent query to Scout search hits.
 *
 * Works with Scout's database engine (pgsql/mysql) and collection engine (tests/sqlite).
 */
final class ScoutSearchFilter
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function make(string $modelClass, string $name = 'q'): AllowedFilter
    {
        return AllowedFilter::callback(
            $name,
            function (Builder $query, mixed $value) use ($modelClass): void {
                if (! is_string($value) || mb_trim($value) === '') {
                    return;
                }

                // Scout returns matching primary keys; apply them to the current builder
                // so Spatie filters/sorts/pagination keep working on the same query.
                $ids = self::searchKeys($modelClass, $value);

                // whereKey([]) yields an empty result set (no matching primary keys).
                $query->whereKey($ids);
            },
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<int|string>
     */
    private static function searchKeys(string $modelClass, string $value): array
    {
        $search = [$modelClass, 'search'];

        if (! is_callable($search)) {
            throw new InvalidArgumentException("{$modelClass} is not searchable via Scout.");
        }

        $scoutBuilder = $search($value);

        if (! $scoutBuilder instanceof ScoutBuilder) {
            throw new InvalidArgumentException("{$modelClass}::search() must return a Scout builder.");
        }

        $keys = [];

        foreach ($scoutBuilder->keys() as $key) {
            if (is_int($key) || is_string($key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
