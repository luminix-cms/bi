<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Metrics\SumMetric;

class SumMetricTest extends AbstractMetricTest
{
    protected function buildMetric(): Metric
    {
        return SumMetric::create('sum', 'Sum')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select SUM(column) as `sum` from `foo`';
    }
}
