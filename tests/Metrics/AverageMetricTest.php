<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Metrics\AverageMetric;

class AverageMetricTest extends AbstractMetricTest
{
    protected function buildMetric(): Metric
    {
        return AverageMetric::create('average', 'Average')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select AVG(column) as `average` from "foo"';
    }
}
