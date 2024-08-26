<?php

namespace Luminix\Bi\Http\Controllers\Apis;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Http\Controllers\BaseController;

class FilterController extends BaseController
{
    public function getFilter(Dashboard $dashboard, string $filterKey, BiRequest $request)
    {
        $filter = $dashboard->findFilterOrFail($filterKey);

        return [
            'status' => 200,
            'extra'  => $filter->extra($dashboard, $request)
        ];
    }
}
