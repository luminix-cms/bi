<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

class RelationFilter extends BaseRelationFilter
{
    public $component = 'belongs-to';
    public $scope;

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);
        $this->scope = function (Builder $builder) {
            return $builder;
        };
        
        
    }

    public function scope(\Closure $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        $primaryKey = $this->getRelatedModel($builder)->getKeyName(); 

        return $builder->whereHas($this->relation, function ($query) use ($filterData, $primaryKey) {
            $query = $this->scope->call($this, $query);
            $query->whereIn($primaryKey, $filterData);
        });
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        $related = $this->getRelatedModel($dashboard);

        return [
            'options'     => $this->scope->call($this, $related->newQuery())->select($related->getKeyName(), $this->otherColumn ?? 'name')->get(),
            'otherColumn' => $this->otherColumn,
            'primaryKey' => $related->getKeyName(),
        ];
    }
}
