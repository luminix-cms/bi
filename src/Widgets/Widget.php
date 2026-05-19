<?php

namespace Luminix\Bi\Widgets;

use Closure;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;

interface Widget extends \JsonSerializable
{
    public function width($width);

    public function scope(Closure $scope): static;

    public function data(Dashboard $dashboard, BiRequest $request);
}
