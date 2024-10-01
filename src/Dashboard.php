<?php

namespace Luminix\Bi;

use Luminix\Bi\Filters\Filter;
use Luminix\Bi\Widgets\Widget;

abstract class Dashboard implements \JsonSerializable
{
    abstract public function widgets();

    abstract public function filters();

    public function findWidgetOrFail($widgetKey)
    {
        $widget = collect($this->widgets())->first(function (Widget $widget) use ($widgetKey) {
            return $widget->key == $widgetKey;
        });

        if (!$widget) {
            abort(404);
        }

        return $widget;
    }

    public function findFilterOrFail($filterKey)
    {
        $filter = collect($this->filters())->first(function (Filter $filter) use ($filterKey) {
            return $filter->key == $filterKey;
        });

        if (!$filter) {
            abort(404);
        }

        return $filter;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'uriKey'  => $this->uriKey,
            'name'    => $this->name,
            'widgets' => $this->widgets(),
            'filters' => $this->filters()
        ];
    }

    public function viewable(): bool
    {
        return true;
    }

}
