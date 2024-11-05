<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

// users -> tags || UserDashboard
class BelongsToManyFilter extends BaseFilter
{
    private $relation;
    private $otherColumn;

    public $component = 'belongs-to';

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);
        $this->relation = $key; // tags
    }

    public function relation($relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    public function otherColumn($otherColumn): self
    {
        $this->otherColumn = $otherColumn; // tag_name

        return $this;
    }

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {

        // users.role_id -> 

        // return $builder->whereIn($builder->getModel()->{$this->relation}()->getForeignKeyName(), $filterData);

        $primaryKey = $builder->getModel()->{$this->relation}()->getRelated()->getKeyName(); // Tag:primary_key

        return $builder->whereHas($this->relation, function ($query) use ($filterData, $primaryKey) {
            $query->whereIn($primaryKey, $filterData);
        });
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [
            'options'     => (new $dashboard->model())->{$this->relation}()->getRelated()->newQuery()->select('id', $this->otherColumn ?? 'name')->get(),
            'otherColumn' => $this->otherColumn
        ];
    }
}
