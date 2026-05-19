<?php

namespace Luminix\Bi\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Facades\Finder;

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

}
