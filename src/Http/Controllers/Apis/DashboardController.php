<?php

namespace Luminix\Bi\Http\Controllers\Apis;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Http\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function getDashboards(BiRequest $request)
    {
        return [
            'status' => 200,
            'data'   => $this->dashboardResolver->all()->values()
        ];
    }

    public function getWidgets(Dashboard $dashboard, BiRequest $request)
    {
        return [
            'status' => 200,
            'data'   => $dashboard
        ];
    }
}
