<?php

namespace Core\Services\PDOs\Builders;

use Closure;
use Core\Models\Contracts\Model;
use Core\Models\Contracts\Relation;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\Contracts\Builder as BuilderContract;
use Core\Services\PDOs\Builders\Contracts\Hookable;
use InvalidArgumentException;
use LogicException;

/**
 * Query Builder
 *
 * The class based on the Aura's QueryFactory ([BSD 2-clause "Simplified" License](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/LICENSE))
 * and the Laravel's QueryBuilder ([MIT License](https://github.com/laravel/laravel/tree/5.3)).
 * The insert, update and delete methods were inspired by the CakePHP's Database Library ([MIT License](https://cakephp.org/)).
 *
 * @see https://github.com/auraphp/Aura.SqlQuery Aura.SqlQuery
 * @see https://github.com/illuminate/database/blob/5.3/Query/Builder.php Laravel's Query Builder on GitHub
 * @see https://github.com/cakephp/database/tree/3.2 CakePHP's Database Library
 */
class Builder implements BuilderContract
{
    /**
     * Database Access Layer.
     *
     * @var Database
     */
    protected $db;

    /**
     * Name of the class where the data are mapped to.
     *
     * @var string
     */
    protected $class;

    /**
     * Determines if the hooks should be invoked.
     *
     * @var bool
     */
    protected $enableHooks = true;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The list of flags (at time only DISTINCT).
     *
     * @var array
     */
    protected $flags = [];

    /**
     * Selected columns
     *
     * @var array
     */
    protected $columns = [];

    /**
     * FROM clauses
     *
     * @var array
     */
    protected $from = [];

    /**
     * JOIN clauses
     *
     * @var array
     */
    protected $join = [];

    /**
     * WHERE clauses
     *
     * @var array
     */
    protected $where = [];

    /**
     * GROUP BY clauses
     *
     * @var array
     */
    protected $groupBy = [];

    /**
     * HAVING clauses
     *
     * @var array
     */
    protected $having = [];

    /**
     * ORDER BY clauses
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * The number of rows to select
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Return rows after this offset.
     *
     * @var int|null
     */
    protected $offset;

    /**
     * Data to be bound to the query.
     *
     * @var array
     */
    protected $bindings = [
        'select' => [],
        'from'   => [],
        'join'   => [],
        'where'  => [],
        'group'  => [],
        'having' => [],
        'order'  => [],
    ];

    /**
     * Tables and fields with this name will not be quoted automatically.
     *
     * @see https://www.w3schools.com/sql/sql_operators.asp) Logical operators
     * @var array
     */
    protected static $keywords = [
        'ALL', 'AND', 'ANY', 'BETWEEN', 'EXISTS', 'IN', 'LIKE', 'NOT', 'OR', 'SOME',
        'AS', // alias
        'IS', 'NULL', 'TRUE', 'FALSE', // literal
        //'MOD', 'DIV', // no standard (supported by MySQL only)
        'CASE', 'WHEN', 'THEN', 'ELSE', 'END', // if then else
        'ASC', 'DESC', // ordering
    ];

//    /**
//     * All of the available clause operators.
//     *
//     * @var array
//     */
//    protected $operators = [
//        '=', '<', '>', '<=', '>=', '<>', '!=', 'EXISTS', 'NOT EXISTS', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE'
//    ];

    /**
     * Create a new Database Schema instance.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Reset the query.
     */
    public function reset()
    {
        $this->class    = null;
        $this->with     = [];
        $this->flags    = [];
        $this->columns  = [];
        $this->from     = [];
        $this->join     = [];
        $this->where    = [];
        $this->groupBy  = [];
        $this->having   = [];
        $this->orderBy  = [];
        $this->limit    = null;
        $this->offset   = null;
        $this->bindings = [
            'select' => [],
            'from'   => [],
            'join'   => [],
            'where'  => [],
            'group'  => [],
            'having' => [],
            'order'  => [],
        ];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toSql();
    }

    /**
     * @inheritdoc
     */
    public function asClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @inheritdoc
     */
    public function enableHooks()
    {
        $this->enableHooks = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableHooks()
    {
        $this->enableHooks = false;

        return $this;
    }

    ///////////////////////////////////////////////////////////////////
    // Clauses

    /**
     * @inheritdoc
     */
    public function with($method)
    {
        if (is_array($method)) {
            $this->with = array_merge($this->with, $method);
        }
        else {
            $this->with[] = $method;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function select($columns, array $bindings = [])
    {
        $this->columns = array_merge($this->columns, $this->compileColumns($columns, 'select'));
        $this->putBindings('select', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function distinct()
    {
        if (!in_array('DISTINCT', $this->flags)) {
            $this->flags[] = 'DISTINCT';
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function from($source, $alias = null, array $bindings = [])
    {
        if (is_callable($source)) {
            $source = $source(new static($this->db));
        }

        if ($source instanceof BuilderContract) {
            $this->putBindings('from', $source->bindings());
            $source = $source->toSql();
        }

        if (is_string($source) && preg_match('/^([0-9a-zA-Z$_]+)$/s', trim($source), $match)) { // for maximum performance, first check the most common case.
            // yes, the source is just a ordinary table name!
            $source = $this->db->quoteName($source);
        }
        else {
            $source = $this->compileExpression($source);
        }

        if ($alias !== null) {
            if (strncasecmp($source, 'SELECT ', 7) === 0) {
                $source = "($source)";
            }
            $source .= ' AS ' . $this->db->quoteName($alias);
        }

        $this->from[] = $source;
        $this->putBindings('from', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function table($table)
    {
        $this->from[0] = $this->db->quoteName($table);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        return !empty($this->from) && strpos($this->from[0], '(') === false ? $this->from[0] : null;
    }

    /**
     * @inheritdoc
     */
    public function join($source, $on, $alias = null, array $bindings = [])
    {
        return $this->joinTable('INNER', $source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->joinTable('LEFT', $source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->joinTable('RIGHT', $source, $on, $alias, $bindings);
    }

    /**
     * Adds a JOIN clause to the query.
     *
     * See the <pre>join</pre> method for details and examples.
     *
     * @param string $type The join type ("INNER", "LEFT" or "RIGHT")).
     * @param string|BuilderContract|Closure $source A table name or a subquery.
     * @param string $on Join on this condition, e.g.: "foo.id = d.foo_id"
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return $this
     */
    protected function joinTable($type, $source, $on, $alias, array $bindings)
    {
        if (is_callable($source)) {
            $source = $source(new static($this->db));
        }

        if ($source instanceof BuilderContract) {
            $this->putBindings('join', $source->bindings());
            $source = $source->toSql();
        }

        if (is_string($source) && preg_match('/^([0-9a-zA-Z$_]+)$/s', trim($source), $match)) { // for maximum performance, first check the most common case.
            // yes, the source is just a ordinary table name!
            $source = $this->db->quoteName($source);
        }
        else {
            $source = $this->compileExpression($source);
        }

        if ($alias !== null) {
            if (strncasecmp($source, 'SELECT ', 7) === 0) {
                $source = "($source)";
            }
            $source .= ' AS ' . $this->db->quoteName($alias);
        }

        $this->join[] = $type . ' JOIN ' . $source . ' ON ' . $this->compileExpression($on);
        $this->putBindings('join', $bindings);

        return $this;
    }

    /**
     * Quote a column.
     *
     * @param $column
     * @return string
     */
    protected function quoteColumn($column)
    {
        if (($pos = strpos($column, '.')) !== false) {
            return $this->db->quoteName(substr($column, 0, $pos)) . '.' . $this->db->quoteName(substr($column, $pos + 1));
        }

        return $this->db->quoteName($column);
    }

    /**
     * @inheritdoc
     */
    public function where($column, $value, $operator = '=', $or = false)
    {
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->quoteColumn($column) . ' ' . strtoupper($operator) . ' ?';
        $this->putBindings('where', [$value]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhere($column, $value, $operator = '=')
    {
        return $this->where($column, $value, $operator, true);
    }

    /**
     * @inheritdoc
     */
    public function whereCondition($condition, array $bindings = [], $or = false)
    {
        if (is_callable($condition)) {
            $condition = $condition(new static($this->db));
            if ($condition instanceof static) {
                $this->putBindings('where', $condition->bindings['where']);
                $condition = '(' . implode(' ', $condition->where) . ')';
            }
        }

        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->compileExpression($condition);
        $this->putBindings('where', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereCondition($condition, array $bindings = [])
    {
        return $this->whereCondition($condition, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = [], $or = false)
    {
        if (is_callable($query)) {
            $query = $query(new static($this->db));
        }

        if ($query instanceof BuilderContract) {
            $this->putBindings('where', $query->bindings());
            $query = $query->toSql();
        }

        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->quoteColumn($column) . ' ' . strtoupper($operator) . ' (' . $query . ')';
        $this->putBindings('where', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereSubQuery($column, $query, $operator = '=', array $bindings = [])
    {
        return $this->whereSubQuery($column, $query, $operator, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function whereExists($query, array $bindings = [], $or = false, $not = false)
    {
        if (is_callable($query)) {
            $query = $query(new static($this->db));
        }

        if ($query instanceof BuilderContract) {
            $this->putBindings('where', $query->bindings());
            $query = $query->toSql();
        }

        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . ($not ? 'NOT ' : '') . 'EXISTS (' . $query . ')';
        $this->putBindings('where', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereExists($query, array $bindings = [])
    {
        return $this->whereExists($query, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function whereNotExists($query, array $bindings = [], $or = false)
    {
        return $this->whereExists($query, $bindings, $or, true);
    }

    /**
     * @inheritdoc
     */
    public function orWhereNotExists($query, array $bindings = [])
    {
        return $this->whereNotExists($query, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function whereIn($column, array $values, $or = false, $not = false)
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->quoteColumn($column) . ($not ? ' NOT' : '') . ' IN (' . $placeholders . ')';
        $this->putBindings('where', $values);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereIn($column, array $values)
    {
        return $this->whereIn($column, $values, true);
    }

    /**
     * @inheritdoc
     */
    public function whereNotIn($column, array $values, $or = false)
    {
        return $this->whereIn($column, $values, $or, true);
    }

    /**
     * @inheritdoc
     */
    public function orWhereNotIn($column, array $values)
    {
        return $this->whereNotIn($column, $values, true);
    }

    /**
     * @inheritdoc
     */
    public function whereBetween($column, $lowest, $highest, $or = false, $not = false)
    {
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->quoteColumn($column) . ($not ? ' NOT' : '') . ' BETWEEN ? AND ?';
        $this->putBindings('where', [$lowest, $highest]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereBetween($column, $lowest, $highest)
    {
        return $this->whereBetween($column, $lowest, $highest, true);
    }

    /**
     * @inheritdoc
     */
    public function whereNotBetween($column, $lowest, $highest, $or = false)
    {
        return $this->whereBetween($column, $lowest, $highest, $or, true);
    }

    /**
     * @inheritdoc
     */
    public function orWhereNotBetween($column, $lowest, $highest)
    {
        return $this->whereNotBetween($column, $lowest, $highest, true);
    }

    /**
     * @inheritdoc
     */
    public function whereNull($column, $or = false, $not = false)
    {
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->quoteColumn($column) . ($not ? ' IS NOT NULL' : ' IS NULL');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, true);
    }

    /**
     * @inheritdoc
     */
    public function whereNotNull($column, $or = false)
    {
        return $this->whereNull($column, $or, true);
    }

    /**
     * @inheritdoc
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, true);
    }

    /**
     * @inheritdoc
     */
    public function groupBy($columns, array $bindings = [])
    {
        $this->groupBy = array_merge($this->groupBy, $this->compileColumns($columns, 'group'));
        $this->putBindings('group', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function having($condition, array $bindings = [], $or = false)
    {
        if (is_callable($condition)) {
            $condition = $condition(new static($this->db));
            if ($condition instanceof static) {
                $this->putBindings('having', $condition->bindings['having']);
                $condition = '(' . implode(' ', $condition->having) . ')';
            }
        }

        $op = !empty($this->having) ? ($or ? 'OR ' : 'AND ') : '';
        $this->having[] = $op . $this->compileExpression($condition);
        $this->putBindings('having', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orHaving($condition, array $bindings = [])
    {
        return $this->having($condition, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function orderBy($columns, array $bindings = [])
    {
        $this->orderBy = array_merge($this->orderBy, $this->compileColumns($columns, 'order'));
        $this->putBindings('order', $bindings);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        if ($this->limit === null) {
            $this->limit = 18446744073709551615; // BIGINT, 2^64-1
        }

        return $this;
    }

    ///////////////////////////////////////////////////////////////////
    // Execute the SQL Query

    /**
     * @inheritdoc
     */
    public function toSql()
    {
        return 'SELECT'
            . $this->buildFlags()
            . $this->buildColumns()
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildGroupBy()
            . $this->buildHaving()
            . $this->buildOrderBy()
            . $this->buildLimit();
    }

    /**
     * @inheritdoc
     */
    public function bindings()
    {
        $bindings = [];
        foreach (array_keys($this->bindings) as $clause) {
            if (!empty($this->bindings[$clause])) {
                $bindings = array_merge($bindings, $this->bindings[$clause]);
            }
        }

        return $bindings;
    }

    /**
     * @inheritdoc
     */
    public function dump($return = null)
    {
        return $this->db->dump($this->toSql(), $this->bindings(), $return);
    }

    /**
     * @inheritdoc
     */
    public function find($id, $key = 'id')
    {
        $columns = !empty($this->columns) ? implode(', ', $this->columns) : '*';
        $table   = implode(', ', $this->from);
        $key     = $this->db->quoteName($key);

        /** @noinspection SqlDialectInspection */
        $entity = $this->db->single("SELECT $columns FROM $table WHERE $key = ?", [$id], $this->class);

        if ($entity !== null && !empty($this->with)) {
            $this->eagerLoadRelations([$entity]);
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        $result = $this->db->query($this->toSql(), $this->bindings(), $this->class);

        if (!empty($this->with)) {
            $this->eagerLoadRelations($result);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function cursor()
    {
        $result = $this->db->cursor($this->toSql(), $this->bindings(), $this->class);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function first()
    {
        $entity = $this->db->single($this->toSql(), $this->bindings(), $this->class);

        if ($entity !== null && !empty($this->with)) {
            $this->eagerLoadRelations([$entity]);
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function value()
    {
        $result = $this->db->scalar($this->toSql(), $this->bindings());

        return $result;
    }

    /**
     * Eager load the relationships.
     *
     * @param Model[] $entities Local entities
     */
    protected function eagerLoadRelations(array $entities)
    {
        if (empty($entities)) {
            return;
        }

        if ($this->class === null) {
            throw new LogicException('A class must be specified for Eager Loading using the `asClass` method.');
        }

        foreach ($this->with as $method) {
            $relation = $this->getRelation($entities[0], $method);
            $relation->addEagerConstraints($entities);
            $relationEntities = $relation->getEager();
            /** @var Model $entity */
            foreach ($entities as $entity) {
                $id = $entity->getId();
                $entity->setRelationEntities($method, isset($relationEntities[$id]) ? $relationEntities[$id] : null);
            }
        }
    }

    /**
     * Get the relation instance for the given relationship method.
     *
     * @param Model $entity Instance of the local entity.
     * @param string $method Name of the relationship method.
     * @return Relation
     */
    protected function getRelation($entity, $method)
    {
        // We need to check the relationship method only at the first local entity, because all entities must be of the same class.
        if (!method_exists($entity, $method)) {
            throw new LogicException('Call to undefined relationship "' . $method . '" on model "' . get_class($entity) . '".');
        }

        // Create a Relation without any constrains.
        $relation = \Core\Models\Relation::noConstraints(function() use ($entity, $method) {
            return $entity->$method();
        });

        if (!$relation instanceof Relation) {
            throw new LogicException('Relationship method "' . $method . '" must return an object of type Core\Models\Contracts\Relation.');
        }

        return $relation;
    }

    ///////////////////////////////////////////////////////////////////
    // Aggregate Functions

    /**
     * Determine if the statement has exactly one column.
     *
     * @return bool
     */
    private function hasExactlyOneColumn()
    {
        if (count($this->columns) != 1 || strpos($this->columns[0], '*') !== false) {
            return false;
        }

        if (strpos($this->columns[0], ',') === false) {
            return true;
        }

        // The column expression includes a comma. Is this a separator for columns or for function arguments?
        $column = $this->columns[0];
        $n = strlen($column);
        $braces = 0;
        for ($i = 0; $i < $n; $i++) {
            $ch = $column[$i];
            if ($braces == 0 && $ch == ',') {
                return false; // comma separator found!
            }
            else if ($ch == '(') {
                $braces++;
            }
            else if ($ch == ')') {
                $braces--;
            }
        }

        return true;
    }

    /**
     * Execute an aggregate function.
     *
     * Note, that ORDER BY and LIMIT generally have no effect on the calculation of aggregate functions.
     * Furthermore, GROUP BY and HAVING BY are omit too, because we wish calculate a aggregate value about all records.
     *
     * @param string $function
     * @param string|null $column
     * @return int|float
     */
    protected function aggregate($function, $column = null)
    {
        if ($column === null) {
            if ($this->hasExactlyOneColumn()) {
                if (($pos = strpos($this->columns[0], ' AS ')) !== false) {
                    $column = substr($this->columns[0], 0, $pos);
                }
                else {
                    $column = $this->columns[0];
                }
                $bindings = $this->bindings['select'];
            }
            else {
                $column   = '*';
                $bindings = [];
            }
        }
        else {
            $column   = $this->db->quoteName($column);
            $bindings = [];
        }

        $flags = !empty($this->flags) ? implode(' ', $this->flags) . ' ' : '';

        $query = 'SELECT ' . $function . '(' . $flags . $column . ')'
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere();

        foreach (['from', 'join', 'where'] as $clause) {
            if (!empty($this->bindings[$clause])) {
                $bindings = array_merge($bindings, $this->bindings[$clause]);
            }
        }

        $result = $this->db->scalar($query, $bindings);

        return $result ?: 0;
    }

    /**
     * @inheritdoc
     */
    public function count($column = null)
    {
        return (int)$this->aggregate('COUNT', $column);
    }

    /**
     * @inheritdoc
     */
    public function max($column = null)
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * @inheritdoc
     */
    public function min($column = null)
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * @inheritdoc
     */
    public function avg($column = null)
    {
        return $this->aggregate('AVG', $column);
    }

    /**
     * @inheritdoc
     */
    public function sum($column = null)
    {
        return $this->aggregate('SUM', $column);
    }

    ///////////////////////////////////////////////////////////////////
    // Modification Methods

    /**
     * @inheritdoc
     */
    public function insert(array $data = [])
    {
        if ($this->hasHooks('Insert')) {
            if (!is_int(key($data))) {
                // Single record!

                /** @var Hookable $instance */
                $instance = new $this->class;

                // Invoke the "before" hook for each instance if exist.
                // If a hook returns FALSE, return FALSE immediately.
                $hook = 'beforeInsert';
                if (method_exists($this->class, $hook)) {
                    $instance->setAttributes($data);
                    if ($instance->$hook() === false) {
                        return false;
                    }
                    $data = $instance->getAttributes();
                }

                // Execute the database operation and invoke the "after" hook for each instance if exist.
                $hook = 'afterInsert';
                if (method_exists($this->class, $hook)) {
                    // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                    // the hook returns FALSE or the hook throw an exception.
                    return $this->db->transaction(function(Database $db) use($data, $instance, $hook) {

                        $result = $this->doInsert($data); // new id
                        $instance->setAttributes(array_merge($data, [$instance->getPrimaryKey() => $result]));

                        if ($instance->$hook() === false) {
                            $db->rollback();
                            return false;
                        }

                        return $result;
                    });
                }
                else {
                    // A "after" hook does not exist, so we don't need a transaction.
                    return $this->doInsert($data);
                }
            }
            else {
                // Bulk insert!

                /** @var Hookable[] $instances */
                $instances = array_fill(0, count($data), new $this->class);

                // Invoke the "before" hook for each instance if exist.
                // If a hook returns FALSE, return FALSE immediately.
                $hook = 'beforeInsert';
                if (method_exists($this->class, $hook)) {
                    foreach ($instances as $i => $instance) {
                        $instance->setAttributes($data[$i]);
                        if ($instance->$hook() === false) {
                            return false;
                        }
                        $data[$i] = $instance->getAttributes();
                    }
                }

                // Execute the database operation and invoke the "after" hook for each instance if exist.
                $hook = 'afterInsert';
                if (method_exists($this->class, $hook)) {
                    // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                    // the hook returns FALSE or the hook throw an exception.
                    return $this->db->transaction(function(Database $db) use($data, $instances, $hook) {

                        $result = null;
                        foreach ($instances as $i => $instance) {
                            $result = $this->doInsert($data[$i]); // new id
                            $instance->setAttributes(array_merge($data[$i], [$instance->getPrimaryKey() => $result]));
                        }

                        // Invoke the "after" hook for each instance if exists.
                        // If a hook returns FALSE, roll back the transaction and return FALSE immediately.
                        foreach ($instances as $i => $instance) {
                            if ($instance->$hook() === false) {
                                $db->rollback();
                                return false;
                            }
                        }

                        return $result;
                    });
                }
                else {
                    // A "after" hook does not exist, so we don't need a transaction.
                    return $this->doInsert($data);
                }
            }
        }
        else {
            // Execute the operation without hooks.
            return $this->doInsert($data);
        }
    }

    /**
     * Insert an empty record.
     */
    protected function insertEmptyRecord()
    {
        $table = implode(', ', $this->from);

        $this->db->exec("INSERT INTO $table DEFAULT VALUES");
    }

    /**
     * Insert rows to the table and return the inserted autoincrement sequence value.
     *
     * If you insert multiple rows, the method returns dependent to the driver the first or last inserted id!.
     *
     * @param array $data Values to be updated
     * @return int|false
     */
    protected function doInsert(array $data = [])
    {
        if (empty($data)) {
            $this->insertEmptyRecord();
            return $this->db->lastInsertId();
        }

        if (!is_int(key($data))) {
            // single record is inserted
            $columns  = implode(', ', array_map([$this->db, 'quoteName'], array_keys($data)));
            $params   = implode(', ', array_fill(0, count($data), '?'));
            $bindings = array_values($data);
        }
        else {
            // Bulk insert...
            $keys = [];
            foreach ($data as $row) {
                if (empty($row)) {
                    throw new InvalidArgumentException('Cannot insert an empty record on bulk mode.');
                }
                $keys = array_merge($keys, $row);
            }
            $keys     = array_keys($keys);
            $columns  = implode(', ', array_map([$this->db, 'quoteName'], $keys));
            $params   = implode(', ', array_fill(0, count($keys), '?'));
            $bindings = [];
            $temp     = [];
            foreach ($data as $i => $row) {
                foreach ($keys as $key) {
                    $bindings[] = isset($row[$key]) ? $row[$key] : null;
                }
                $temp[] = $params;
            }
            $params = implode('), (', $temp);
        }

        $table = implode(', ', $this->from);

        /** @noinspection SqlDialectInspection */
        $query = "INSERT INTO $table ($columns) VALUES ($params)";

        $this->db->exec($query, $bindings);

        return $this->db->lastInsertId();
    }

//    /**
//     * Determine if the new and old attribute values are numerically equivalent.
//     *
//     * @param mixed $v1
//     * @param mixed $v2
//     * @return bool
//     */
//    private function isNumericallyEquivalent($v1, $v2)
//    {
//        return is_numeric($v1) && is_numeric($v2) && strcmp((string)$v1, (string)$v2) === 0;
//    }

    /**
     * @inheritdoc
     */
    public function update(array $data)
    {
        if ($this->hasHooks('Update')) {

            /** @var Hookable[] $instances */
            $instances = $this->all();

            // Invoke the "before" hook for each instance if exist.
            // If a hook returns FALSE, return FALSE immediately.
            $hook = 'beforeUpdate';
            if (method_exists($this->class, $hook)) {
                foreach ($instances as $i => $instance) {
                    $original = $instance->getAttributes();
                    $instance->setAttributes(array_merge($original, $data));
                    if ($instance->$hook() === false) {
                        return false;
                    }
                    foreach ($instance->getAttributes() as $name => $value) {
                        if (!array_key_exists($name, $original) || ($value !== $original[$name] /*&& !$this->isNumericallyEquivalent($value, $original[$name])*/)) {
                            $data[$name] = $value;
                        }
                    }
                }
            }

            // Execute the database operation and invoke the "after" hook for each instance if exist.
            $hook = 'afterUpdate';
            if (method_exists($this->class, $hook)) {
                // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                // the hook returns FALSE or the hook throw an exception.
                return $this->db->transaction(function(Database $db) use($data, $instances, $hook) {

                    $result = $this->doUpdate($data);

                    // Invoke the "after" hook for each instance if exists.
                    // If a hook returns FALSE, roll back the transaction and return FALSE immediately.
                    foreach ($instances as $i => $instance) {
                        $instance->setAttributes(array_merge($instance->getAttributes(), $data));
                        if ($instance->$hook() === false) {
                            $db->rollback();
                            return false;
                        }
                    }

                    return $result;
                });
            }
            else {
                // A "after" hook does not exist, so we don't need a transaction.
                return $this->doUpdate($data);
            }
        }
        else {
            // Execute the operation without hooks.
            return $this->doUpdate($data);
        }
    }

    /**
     * Update all records of the query result with th given data and return the number of affected rows.
     *
     * @param array $data Values to be updated
     * @return int
     */
    protected function doUpdate(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Cannot update an empty record.');
        }

        $bindings = array_merge($this->bindings['join'], $this->bindings['where']);
        $settings = [];
        $values   = [];
        if (empty($bindings) || is_int(key($bindings))) { // Prevent PDO-Error: "Invalid parameter number: mixed named and positional parameters"
            // bindings not used or question mark placeholders used
            foreach ($data as $column => $value) {
                $settings[] = $this->quoteColumn($column) . ' = ?';
                $values[]   = $value;
            }
        }
        else {
            // named placeholders are used
            foreach ($data as $column => $value) {
                $key = $column;
                if (isset($bindings[$key])) {
                    $i = 0;
                    do {
                        $key = $column . '_' . (++$i);
                    } while (isset($bindings[$key]) || isset($data[$key]));
                }
                $settings[] = $this->quoteColumn($column) . ' = :' . $key;
                $values[$key] = $value;
            }
        }

        $from     = ' ' . implode(', ', $this->from);
        $bindings = array_merge($this->bindings['join'], $values, $this->bindings['where']);
        $settings = ' SET ' . implode(', ', $settings);

        $query = 'UPDATE'
            . $from
            . $this->buildJoin()
            . $settings
            . $this->buildWhere();

        return $this->db->exec($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        if ($this->hasHooks('Delete')) {

            /** @var Hookable[] $instances */
            $instances = $this->all();

            // Invoke the "before" hook for each instance if exist.
            // If a hook returns FALSE, return FALSE immediately.
            $hook = 'beforeDelete';
            if (method_exists($this->class, $hook)) {
                foreach ($instances as $i => $instance) {
                    if ($instance->$hook() === false) {
                        return false;
                    }
                }
            }

            // Execute the database operation and invoke the "after" hook for each instance if exist.
            $hook = 'afterDelete';
            if (method_exists($this->class, $hook)) {
                // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                // the hook returns FALSE or the hook throw an exception.
                return $this->db->transaction(function(Database $db) use($instances, $hook) {

                    $result = $this->doDelete();

                    // Invoke the "after" hook for each instance if exists.
                    // If a hook returns FALSE, roll back the transaction and return FALSE immediately.
                    foreach ($instances as $i => $instance) {
                        $attributes = $instance->getAttributes();
                        unset($attributes[$instance->getPrimaryKey()]);
                        $instance->setAttributes($attributes);
                        if ($instance->$hook() === false) {
                            $db->rollback();
                            return false;
                        }
                    }

                    return $result;
                });
            }
            else {
                // A "after" hook does not exist, so we don't need a transaction.
                return $this->doDelete();
            }

        }
        else {
            // Execute the operation without hooks.
            return $this->doDelete();
        }
    }

    /**
     * Delete all records of the query result and return the number of affected rows.
     *
     * @return int
     */
    protected function doDelete()
    {
        $bindings = array_merge($this->bindings['join'], $this->bindings['where']);

        $query = 'DELETE'
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere();

        return $this->db->exec($query, $bindings);
    }

    /**
     * Determine whether the class you specified by asClass() implements the Hookable contract and provides hooks for
     * the given operation.
     *
     * @param string $operation "Insert", "Update" or "Delete"
     * @return bool
     */
    protected function hasHooks($operation)
    {
        return $this->enableHooks &&
            is_subclass_of($this->class, Hookable::class) &&
            (method_exists($this->class, "before$operation") || method_exists($this->class, "after$operation"));
    }

    ///////////////////////////////////////////////////////////////////
    // Build the Parts of the SQL Statement

    /**
     * Put the bindings into the list.
     *
     * @param string $clause
     * @param array $bindings
     */
    protected function putBindings($clause, array $bindings)
    {
        if (!empty($bindings)) {
            $this->bindings[$clause] = array_merge($this->bindings[$clause], $bindings);
        }
    }

    /**
     * Render the column list.
     *
     * @param string|array $columns One or more columns or subquery.
     * @param string $clause Key for the binding list: "select", "group" or "order"
     * @return array
     */
    protected function compileColumns($columns, $clause)
    {
        $list = [];
        foreach ((array)$columns as $alias => $column) {
            if (is_string($column) && preg_match('/^(?:([0-9a-zA-Z$_]+)\.)?([0-9a-zA-Z$_]+)$/s', trim($column), $match)) { // for maximum performance, first check the most common case..
                // yes, the expression is just a column name: "col1" or "t1.col1"!
                $column = (!empty($match[1]) ? $this->db->quoteName($match[1]) . '.' : '') . $this->db->quoteName($match[2]);
            }
            else {
                if (is_callable($column)) {
                    $column = $column(new static($this->db));
                }

                if ($column instanceof BuilderContract) {
                    $this->putBindings($clause, $column->bindings());
                    $column = $column->toSql();
                }

                $column = $this->compileExpression($column);
            }

            if (is_string($alias)) {
                if (strncasecmp($column, 'SELECT ', 7) === 0) {
                    $column = "($column)";
                }
                $column .= ' AS ' . $this->db->quoteName($alias);
            }

            $list[] = $column;
        }

        return $list;
    }

    /**
     * Compile the expression.
     *
     * @param string $expr Column, subquery or any other expression (e.g. "t1.c1", "SELECT ...", "c1 > sum(c2)")
     * @return string
     */
    protected function compileExpression($expr)
    {
        $expr = trim($expr);

        if (strncasecmp($expr, 'SELECT ', 7) === 0) {
            // It is as a raw subquery. Skip this, because the Builder of the subquery has should quoted this already.
            return  $expr;
        }

        $n = strlen($expr);
        $i = 0;
        while ($i < $n) {
            $ch = $expr[$i];
            if (stripos('abcdefghijklmnopqrstuvwxyz_', $ch) !== false) {
                // Is it a table/field/alias or a function?
                $k = $i; // first index of this part
                while (++$i < $n && stripos('abcdefghijklmnopqrstuvwxyz0123456789$_', $expr[$i]) !== false);
                $name = substr($expr, $k, $i - $k);
                $j = $i; // first index of the next part
                if (in_array(strtoupper($name), static::$keywords)) {
                    $expr = substr($expr, 0, $k) . strtoupper($name) . substr($expr, $j);
                }
                else {
                    while ($i < $n && $expr[$i] == ' ') ++$i; // skip spaces
                    if ($i < $n && $expr[$i] == '(') {
                        // It's a function! Take the arguments an translate the function if needed...
                        $args = [];
                        $braces = 1;
                        $j = $i + 1; // first index of the argument
                        while (++$i < $n && $braces > 0) {
                            $ch = $expr[$i];
                            if ($ch == ')') {
                                $braces--;
                            }
                            else if ($ch == '(') {
                                $braces++;
                            }
                            else if ($braces == 1 && $ch == ',') {
                                $args[] = substr($expr, $j, $i - $j);
                                $j = $i + 1;
                            }
                            else if ($ch == '"' || $ch == "'" || $ch == '`' || $ch == '[') { // masked literal
                                $c = $ch == '[' ? ']' : $ch;
                                while (++$i < $n && $expr[$i] != $c);
                            }
                            else if ($ch == '-' && $i + 2 < $n && $expr[$i + 1] == '-' && $expr[$i + 2] == ' ') { // comment
                                $i += 2;
                                while (++$i < $n && $expr[$i] != PHP_EOL);
                            }
                        }
                        if ($i - $j - 1 > 0) {
                            $args[] = substr($expr, $j, $i - $j - 1);
                        }

                        $args = array_map([$this, 'compileExpression'], $args);
//                        $args = array_map(function ($arg) {
//                            return $this->compileExpression($arg);
//                        }, $args);
                        $repl = $this->compileFunction($name, $args);
                        $j = $i; // first index of the next part
                    }
                    else {
                        // It's a table, field, or alias! Quote this one!
                        $repl = $this->db->quoteName($name);
                    }
                    // Replace the original term with the quoted part.
                    $expr = substr($expr, 0, $k) . $repl . substr($expr, $j);
                    $j = strlen($repl) - $j + $k; // difference of characters after replacing
                    $i += $j;
                    $n += $j;
                }
            }
            else if ($ch == '(') {
                // Is it a embedded subquery?
                while (++$i < $n && $expr[$i] == ' '); // skip spaces
                if (strcasecmp(substr($expr, $i, 7), 'SELECT ') === 0) {
                    // Yes, it is a embedded subquery! Skip this, because the Builder of the subquery has should quoted this already.
                    $i += 6;
                    $braces = 1;
                    while (++$i < $n && $braces > 0) {
                        $ch = $expr[$i];
                        if ($ch == ')') {
                            $braces--;
                        }
                        else if ($ch == '(') {
                            $braces++;
                        }
                        else if ($ch == '"' || $ch == "'" || $ch == '`' || $ch == '[') { // masked literal
                            $c = $ch == '[' ? ']' : $ch;
                            while (++$i < $n && $expr[$i] != $c);
                        }
                        else if ($ch == '-' && $i + 2 < $n && $expr[$i + 1] == '-' && $expr[$i + 2] == ' ') { // comment
                            $i += 2;
                            while (++$i < $n && $expr[$i] != PHP_EOL);
                        }
                    }
                }
            }
            else if ($ch == ':') { // named placeholder
                while (++$i < $n && stripos('abcdefghijklmnopqrstuvwxyz0123456789$_', $expr[$i]) !== false);
                ++$i;
            }
            else if ($ch == '"' || $ch == "'" || $ch == '`' || $ch == '[') { // masked literal
                $c = $ch == '[' ? ']' : $ch;
                while (++$i < $n && $expr[$i] != $c);
                ++$i;
            }
            else if ($ch == '-' && $i + 2 < $n && $expr[$i + 1] == '-' && $expr[$i + 2] == ' ') { // comment
                $j = $i + 2;
                while (++$j < $n && $expr[$j] != PHP_EOL);
                ++$j;
                // Remove the comment
                $expr = substr($expr, 0, $i) . substr($expr, $j);
                $n -= $j - $i; // difference of characters after replacing
            }
            else {
                ++$i;
            }
        }

        return $expr;
    }

    /**
     * Compile a SQL function.
     *
     * @param string $name
     * @param array $arguments
     * @return string
     */
    protected function compileFunction($name, array $arguments)
    {
        return strtoupper($name) . '(' . implode(', ', $arguments) . ')';
    }

    /**
     * Builds the flags as a space-separated string.
     *
     * @return string
     */
    protected function buildFlags()
    {
        return !empty($this->flags) ? ' ' . implode(' ', $this->flags) : '';
    }

    /**
     * Builds the columns clause.
     *
     * @return string
     */
    protected function buildColumns()
    {
        return !empty($this->columns) ? ' ' . implode(', ', $this->columns) : ' *';
    }

    /**
     * Builds the FROM clause.
     *
     * @return string
     */
    protected function buildFrom()
    {
        return !empty($this->from) ? ' FROM ' . implode(', ', $this->from) : '';
    }

    /**
     * Builds the FROM clause.
     *
     * @return string
     */
    protected function buildJoin()
    {
        return !empty($this->join) ? ' ' . implode(' ', $this->join) : '';
    }

    /**
     * Builds the WHERE clause.
     *
     * @return string
     */
    protected function buildWhere()
    {
        return !empty($this->where) ? ' WHERE ' . implode(' ', $this->where) : '';
    }

    /**
     * Builds the GROUP BY clause.
     *
     * @return string
     */
    protected function buildGroupBy()
    {
        return !empty($this->groupBy) ? ' GROUP BY ' . implode(', ', $this->groupBy) : '';
    }

    /**
     * Builds the HAVING clause.
     *
     * @return string
     */
    protected function buildHaving()
    {
        return !empty($this->having) ? ' HAVING ' . implode(' ', $this->having) : '';
    }

    /**
     * Builds the ORDER BY clause.
     *
     * @return string
     */
    protected function buildOrderBy()
    {
        return !empty($this->orderBy) ? ' ORDER BY ' . implode(', ', $this->orderBy) : '';
    }

    /**
     * Builds the `LIMIT ... OFFSET` clause.
     *
     * @return string
     */
    protected function buildLimit()
    {
        return $this->limit !== null ? ' LIMIT ' . $this->limit . ($this->offset ? ' OFFSET ' . $this->offset : '') : '';
    }
}