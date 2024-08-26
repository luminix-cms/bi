<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Metrics\CountMetric;

class CountMetricTest extends AbstractMetricTest
{
    protected function buildMetric(): Metric
    {
        return CountMetric::create('count', 'Count');
    }

    protected function getQuery(): string
    {
        return 'select COUNT(*) as `count` from `foo`';
    }
}
