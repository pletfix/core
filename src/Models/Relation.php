<?php

namespace Core\Models;

use Closure;
use Core\Models\Contracts\Model as ModelContract;
use Core\Models\Contracts\Relation as RelationContract;
use Core\Services\PDOs\Builders\Contracts\Builder;

abstract class Relation implements RelationContract
{
    /**
     * Local model.
     *
     * @var ModelContract
     */
    protected $model;

    /**
     * Query Builder.
     *
     * @var Builder
     */
    protected $builder;

    /**
     * Indicates if the relation is adding constraints.
     *
     * @var bool
     */
    private static $constraints = true;

    /**
     * Create a new Relation instance.
     *
     * @param ModelContract $model The local model.
     * @param Builder $builder A Builder to get the foreign entities.
     */
    public function __construct(ModelContract $model, Builder $builder)
    {
        $this->model   = $model;
        $this->builder = $builder;

        if (self::$constraints) {
            $this->addConstraints();
        }
    }

    /**
     * @inheritdoc
     */
    public static function noConstraints(Closure $callback)
    {
        $previous = self::$constraints;
        self::$constraints = false;

        try {
            $results = $callback();
        }
        finally {
            self::$constraints = $previous;
        }

        return $results;
    }

    /**
     * Set the base constraints on the relation query.
     */
    abstract protected function addConstraints();

    /**
     * @inheritdoc
     */
    abstract public function addEagerConstraints(array $entities);

    /**
     * @inheritdoc
     */
    abstract public function get();

    /**
     * @inheritdoc
     */
    abstract public function getEager();

    /**
     * @inheritdoc
     */
    public function model()
    {
        return $this->model;
    }

    ///////////////////////////////////////////////////////////////////
    // Gets a Query Builder

    /**
     * Get the QueryBuilder instance.
     *
     * @return Builder
     */
    public function builder()
    {
        return $this->builder;
    }

    /**
     * @inheritdoc
     */
    public function select($columns, array $bindings = [])
    {
        return $this->builder->select($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function distinct()
    {
        return $this->builder->distinct();
    }

    /**
     * @inheritdoc
     */
    public function from($source, $alias = null, array $bindings = [])
    {
        return $this->builder->from($source, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function join($source, $on, $alias = null, array $bindings = [])
    {
        return $this->builder->join($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->builder->leftJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->builder->rightJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function where($condition, array $bindings = [])
    {
        return $this->builder->where($condition, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereIs($column, $value, $operator = '=')
    {
        return $this->builder->whereIs($column, $value, $operator);
    }

    /**
     * @inheritdoc
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = [])
    {
        return $this->builder->whereSubQuery($column, $query, $operator, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereExists($query, array $bindings = [])
    {
        return $this->builder->whereExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereNotExists($query, array $bindings = [])
    {
        return $this->builder->whereNotExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereIn($column, array $values)
    {
        return $this->builder->whereIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public function whereNotIn($column, array $values)
    {
        return $this->builder->whereNotIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public function whereBetween($column, $lowest, $highest)
    {
        return $this->builder->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public function whereNotBetween($column, $lowest, $highest)
    {
        return $this->builder->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public function whereIsNull($column)
    {
        return $this->builder->whereIsNull($column);
    }

    /**
     * @inheritdoc
     */
    public function whereIsNotNull($column)
    {
        return $this->builder->whereIsNotNull($column);
    }

    /**
     * @inheritdoc
     */
    public function orderBy($columns, array $bindings = [])
    {
        return $this->builder->orderBy($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function limit($limit)
    {
        return $this->builder->limit($limit);
    }

    /**
     * @inheritdoc
     */
    public function offset($offset)
    {
        return $this->builder->offset($offset);
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        return $this->builder->all();
    }

    /**
     * @inheritdoc
     */
    public function cursor()
    {
        return $this->builder->cursor();
    }

    /**
     * @inheritdoc
     */
    public function first()
    {
        return $this->builder->first();
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->builder->count();
    }

    /**
     * @inheritdoc
     */
    public function max($attribute = null)
    {
        return $this->builder->max($attribute);
    }

    /**
     * @inheritdoc
     */
    public function min($attribute = null)
    {
        return $this->builder->min($attribute);
    }

    /**
     * @inheritdoc
     */
    public function avg($attribute = null)
    {
        return $this->builder->avg($attribute);
    }

    /**
     * @inheritdoc
     */
    public function sum($attribute = null)
    {
        return $this->builder->sum($attribute);
    }

    /**
     * @inheritdoc
     */
    abstract public function associate(ModelContract $model);

    /**
     * @inheritdoc
     */
    abstract public function disassociate(ModelContract $model = null);

    /**
     * @inheritdoc
     */
    abstract public function create(array $attributes = []);

    /**
     * @inheritdoc
     */
    public function update(array $attributes)
    {
        return $this->builder->update($attributes);
    }

    /**
     * @inheritdoc
     */
    abstract public function delete(ModelContract $model);
}