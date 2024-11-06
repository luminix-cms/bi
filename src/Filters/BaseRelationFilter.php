<?php

namespace Luminix\Bi\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Luminix\Bi\Dashboard;

abstract class BaseRelationFilter extends BaseFilter
{

    public $relation;
    public $otherColumn;

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);
        $this->relation = $key;
    }

    public function relation($relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    public function otherColumn($otherColumn): static
    {
        $this->otherColumn = $otherColumn;

        return $this;
    }


    /**
     * 
     * Get the related model from the builder or dashboard
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Luminix\Bi\Dashboard $origin 
     * @return Model 
     * @throws Exception
     */
    public function getRelatedModel($origin): Model
    {
        return $this->getRelation($origin)->getRelated();
    }


    /**
     * 
     * Get an instance of the given relation
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Luminix\Bi\Dashboard $origin
     * @return Relation
     * @throws Exception
     */
    public function getRelation($origin): Relation
    {
        if ($origin instanceof Dashboard) {
            $stack = explode('.', $this->relation);
            $model = new $origin->model();

            foreach ($stack as $relation) {
                $model = $model->{$relation}();
            }

            return $model;
        }

        if ($origin instanceof Builder) {
            $stack = explode('.', $this->relation);
            $model = $origin->getModel();

            foreach ($stack as $relation) {
                $model = $model->{$relation}();
            }

            return $model;
        }

        throw new \Exception('Invalid origin type');
    }

}
