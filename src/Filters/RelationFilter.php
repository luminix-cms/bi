<?php

namespace Luminix\Bi\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Bi\Dashboard;

abstract class RelationFilter extends BaseFilter
{

    protected $relation;
    protected $otherColumn;

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);
        $this->relation = $key;
    }

    public function relation($relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    public function otherColumn($otherColumn): self
    {
        $this->otherColumn = $otherColumn;

        return $this;
    }


    public function getRelatedModel($origin): Model
    {
        if ($origin instanceof Dashboard) {
            //(new $dashboard->model())->{$this->relation}()->getRelated()
            $stack = explode('.', $this->relation);
            $model = new $origin->model();

            foreach ($stack as $relation) {
                $model = $model->{$relation}()->getRelated();
            }

            return $model;
        }

        if ($origin instanceof Builder) {
            $stack = explode('.', $this->relation);
            $model = $origin->getModel();

            foreach ($stack as $relation) {
                $model = $model->{$relation}()->getRelated();
            }

            return $model;
        }

        throw new \Exception('Invalid origin type');
    }

}
