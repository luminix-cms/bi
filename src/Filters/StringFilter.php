<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Services\QueryService;
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
        // Same base as BaseWidget::getBaseBuilder(): options can't list values outside the rows' scope.
        $baseQuery = QueryService::create($dashboard->model);

        $builder = method_exists($dashboard, 'scope')
            ? $dashboard->scope($baseQuery)
            : $baseQuery;

        $column = $builder->qualifyColumn($this->column);

        return [
            'options' => $builder->select($column)->distinct()->pluck($column)
        ];
    }
}
