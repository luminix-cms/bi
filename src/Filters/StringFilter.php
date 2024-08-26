<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

class StringFilter extends BaseFilter
{
    public $component = 'string';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        return $builder->whereIn($this->column, $filterData);
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [
            'options' => $dashboard->model::query()->select($this->column)->distinct()->pluck($this->column)
        ];
    }
}
