<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Services\QueryService;
use Luminix\Bi\Support\BiRequest;

class RelationFilter extends BaseRelationFilter
{
    public $component = 'belongs-to';
    public $scope;

    protected bool $narrowOptions = true;

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
            $key = $query->qualifyColumn($primaryKey); // a join or a self-relation has another `id`: this is the table or alias whereHas left

            $this->applyScope($query)->whereIn($key, $filterData);
        });
    }

    /**
     * Runs the filter's ->scope() grouped, so an orWhere in it can't widen the selection or the options
     */
    private function applyScope(Builder $query): Builder
    {
        return QueryService::scoped($query, $this->scope->bindTo($this, $this));
    }

    /**
     * List every related record, whether or not a dashboard row references it
     */
    public function allOptions(): static
    {
        $this->narrowOptions = false;

        return $this;
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        $related = $this->getRelatedModel($dashboard);

        $query = $this->applyScope(QueryService::create($related));

        if ($this->narrowOptions) {
            $this->narrowToDashboard($query, $dashboard);
        }

        return [
            'options'     => $query->select($related->getKeyName(), $this->otherColumn ?? 'name')->get(),
            'otherColumn' => $this->otherColumn,
            'primaryKey'  => $related->getKeyName(),
        ];
    }
}
