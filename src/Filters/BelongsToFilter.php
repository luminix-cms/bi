<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

class BelongsToFilter extends RelationFilter
{
    public $component = 'belongs-to';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        return $builder->whereIn($builder->getModel()->{$this->relation}()->getForeignKeyName(), $filterData);
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        $related = $this->getRelatedModel($dashboard);
        return [
            'options'     => $related->newQuery()->select($related->getKeyName(), $this->otherColumn ?? 'name')->get(),
            'otherColumn' => $this->otherColumn,
            'primaryKey' => $related->getKeyName(),
        ];
    }
}
