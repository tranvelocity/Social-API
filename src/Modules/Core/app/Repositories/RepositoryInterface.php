<?php

declare(strict_types=1);

namespace Modules\Core\app\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface RepositoryInterface
{
    /**
     * Soft delete a resource.
     *
     * @param $module
     */
    public function delete($module): void;

    /**
     * Hard delete a resource.
     *
     * @param $module
     */
    public function forceDelete($module): void;

    /**
     * Update a resource.
     *
     * @param $module
     * @param array $attributes
     * @return mixed
     */
    public function update($module, array $attributes): mixed;

    /**
     * Create a new resource.
     *
     * @param $module
     * @param array $attributes
     * @return mixed
     */
    public function create($module, array $attributes): mixed;

    /**
     * Apply a filter to the query if the filter key exists in the parameters.
     *
     * @param Builder|HasMany $query The query builder instance.
     * @param string $filterKey The key of the filter parameter.
     * @param string $operator The comparison operator.
     * @param array $params The filtering parameters.
     * @param string|null $paramKey The key to fetch the value from in $params.
     *
     * @return void
     */
    public function applyFilterIfExists(Builder|HasMany $query, string $filterKey, string $operator, array $params, ?string $paramKey = null): void;

    /**
     * Apply a date and time filter to the query if the filter parameter exists in the input parameters.
     *
     * @param Builder|HasMany; $query The query builder instance to which the filter should be applied.
     * @param string $filterKey The column name or filter key in the database table.
     * @param string $operator The comparison operator for the filter (e.g., '>=', '<=').
     * @param array $params The array of input parameters containing the filter values.
     * @param string|null $paramKey (Optional) If specified, the key in the $params array to fetch the filter value.
     *                             If not specified, the $filterKey will be used as the key.
     * @return void
     */
    public function applyDateTimeFilterIfExists(Builder|HasMany $query, string $filterKey, string $operator, array $params, ?string $paramKey = null): void;

    /**
     * Begin DB transaction.
     */
    public function beginTransaction(): void;

    /**
     * DB transaction rollback.
     */
    public function rollback(): void;

    /**
     * DB transaction commit.
     */
    public function commit(): void;
}
