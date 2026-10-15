<?php

namespace Luminix\Bi\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Services\QueryService;

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

            foreach ($stack as $i => $relation) {
                $model = $model->{$relation}();

                if ($i < count($stack) - 1) {
                    $model = $model->getRelated();
                }
                
            }

            return $model;
        }

        if ($origin instanceof Builder) {
            $stack = explode('.', $this->relation);
            $model = $origin->getModel();

            foreach ($stack as $i => $relation) {
                $model = $model->{$relation}();
                
                if ($i < count($stack) - 1) {
                    $model = $model->getRelated();
                }
            }

            return $model;
        }

        throw new \Exception('Invalid origin type');
    }

    /**
     * Restrict a query on the related model to the records the dashboard's rows reach
     *
     * Each segment of the relation path is a hop: the next model's records whose key is among
     * the keys the previous hop reaches, and the last hop constrains the given query itself.
     * Every hop is a subquery that references nothing outside itself, so the dashboard's own
     * columns stay qualified by their real table name. Only the dashboard's base query carries
     * allowed(): whereHas() doesn't apply it to the models it crosses either, so the records
     * reached are the ones apply() can match.
     *
     * @param \Illuminate\Database\Eloquent\Builder $options a query on the model the relation ends at
     * @param \Luminix\Bi\Dashboard $dashboard
     * @return Builder
     * @throws LogicException when a segment is a relation type that can't be followed
     */
    protected function narrowToDashboard(Builder $options, Dashboard $dashboard): Builder
    {
        $names = explode('.', $this->relation);
        $last  = array_pop($names);
        $from  = QueryService::forDashboard($dashboard);

        foreach ($names as $name) {
            $from = $this->hop($from, $name);
        }

        return $this->hop($from, $last, $options);
    }

    private function hop(Builder $from, string $name, ?Builder $into = null): Builder
    {
        $relation = $from->getModel()->{$name}();

        if ($relation instanceof MorphTo || $relation instanceof MorphToMany) {
            $this->cannotNarrow($relation, $name);
        }

        $related = $into ?? $relation->getRelated()->newQuery();

        if ($relation instanceof BelongsTo) {
            return $related->whereIn(
                $relation->getQualifiedOwnerKeyName(),
                $this->keysOf($from, $relation->getQualifiedForeignKeyName())
            );
        }

        if ($relation instanceof HasOneOrMany) {
            if ($relation instanceof MorphOneOrMany) {
                $related->where($relation->getQualifiedMorphType(), $relation->getMorphClass());
            }

            return $related->whereIn(
                $relation->getQualifiedForeignKeyName(),
                $this->keysOf($from, $relation->getQualifiedParentKeyName())
            );
        }

        if ($relation instanceof BelongsToMany) {
            $pivot = $relation->newPivotStatement()
                ->whereIn($relation->getQualifiedForeignPivotKeyName(), $this->keysOf($from, $relation->getQualifiedParentKeyName()))
                ->select($relation->getQualifiedRelatedPivotKeyName());

            return $related->whereIn($relation->getQualifiedRelatedKeyName(), $pivot);
        }

        $this->cannotNarrow($relation, $name);
    }

    private function keysOf(Builder $from, string $column): Builder
    {
        return $from->reorder()->select($column);
    }

    private function cannotNarrow(Relation $relation, string $name): never
    {
        throw new LogicException(sprintf(
            'The options of filter "%s" cannot follow %s relation "%s" from the dashboard rows; call ->allOptions() to list every related record.',
            $this->key,
            class_basename($relation),
            $name
        ));
    }

}
