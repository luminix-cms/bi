<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
            $query = $this->scope->call($this, $query);
            $query->whereIn($primaryKey, $filterData);
        });
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
        $relation = $this->getRelation($dashboard);

        if ($relation instanceof MorphTo) {
            $this->cannotListMorphTo($this->relation);
        }

        $related = $relation->getRelated();

        $query = QueryService::scoped(QueryService::create($related), $this->scope->bindTo($this, $this));

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
