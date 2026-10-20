<?php

namespace Luminix\Bi\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Facades\Finder;
use Luminix\Bi\Dashboard;

class QueryService
{

    public static function create(string|Model $object): Builder
    {
        $connection = config('luminix.bi.connection');

        if ($object instanceof Model) {
            $object = get_class($object);
        }

        $query = $connection
            ? $object::on($connection)
            : $object::query();

        if (config('luminix.backend.security.gates_enabled', true) && Finder::isLuminixModel($object)) {
            $query->allowed(
                config(
                    'luminix.backend.security.permissions.index',
                    'read'
                )
            );
        }

        return $query;

    }

    public static function forDashboard(Dashboard $dashboard): Builder
    {
        $query = static::create($dashboard->model);

        if (!method_exists($dashboard, 'scope')) {
            return $query;
        }

        return static::scoped($query, [$dashboard, 'scope']);
    }

    /**
     * Runs a scope over the query, grouping the wheres it adds
     *
     * callScope() groups them like a named scope's, so an orWhere there can't widen the rows
     * past allowed() or the constraints applied after it.
     */
    public static function scoped(Builder $query, callable $scope): Builder
    {
        return (function () use ($scope) {
            return $this->callScope($scope);
        })->call($query);
    }

}
