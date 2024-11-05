<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

// feature_usages -> accounts -> tags || FeatureUsageDashboard
class BelongsToManyFilter extends RelationFilter
{
    public $component = 'belongs-to';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        $primaryKey = $this->getRelatedModel($builder)->getKeyName(); 

        return $builder->whereHas($this->relation, function ($query) use ($filterData, $primaryKey) {
            $query->whereIn($primaryKey, $filterData);
        });
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [
            'options'     => $this->getRelatedModel($dashboard)->newQuery()->select('id', $this->otherColumn ?? 'name')->get(),
            'otherColumn' => $this->otherColumn
        ];
    }
}
