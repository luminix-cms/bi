<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Metrics\RawMetric;

class RawMetricTest extends AbstractMetricTest
{
    protected function buildMetric(): Metric
    {
        return RawMetric::create('raw', 'Raw')->raw('SUM(column) / 10');
    }

    protected function getQuery(): string
    {
        return 'select SUM(column) / 10 as `raw` from `foo`';
    }
}
