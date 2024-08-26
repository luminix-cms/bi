<?php

namespace Luminix\Bi\Widgets;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;

class BigNumber extends BaseWidget
{
    protected $component = 'big-number';

    public function data(Dashboard $dashboard, BiRequest $request)
    {
        $builder = $this->getBaseBuilder($dashboard);
        $builder = $this->applyAttributes($builder);
        $builder = $this->applyFilters($builder, $dashboard, $request);

        $rawModels      = $builder->get();
        $rawModelsArray = $rawModels->toArray();

        return $rawModels->map(function ($rawModel) use ($rawModelsArray) {
            return $this->displayModel($rawModel, $rawModelsArray);
        });
    }
}
