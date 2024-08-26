<?php

namespace Luminix\Bi\Http\Controllers;

use Luminix\Bi\DashboardResolver;

abstract class BaseController
{
    protected $dashboardResolver;

    public function __construct(DashboardResolver $dashboardResolver)
    {
        $this->dashboardResolver = $dashboardResolver;
    }
}
