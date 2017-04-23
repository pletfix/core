<?php

namespace Core\Services\PDOs\Builder;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builder\Contracts\Builder as BuilderContract;
use Core\Services\PDOs\Builder\Contracts\Builder;
use InvalidArgumentException;

/**
 * Abstract Query Builder
 *
 * The class based on the Aura's QueryFactory (https://github.com/auraphp/Aura.SqlQuery/blob/3.x/LICENSE BSD 2-clause "Simplified" License)
 * and the Laravel's QueryBuilder (https://github.com/laravel/laravel MIT License).
 * The insert, update and delete methods were inspired by the CakePHP's Database Library.
 *
 * @see https://github.com/auraphp/Aura.SqlQuery Aura.SqlQuery
 * @see https://github.com/illuminate/database/blob/5.3/Query/Builder.php Laravel's Query Builder on GitHub
 * @see https://github.com/cakephp/database/tree/3.2 CakePHP's Database Library
 */
abstract class AbstractBuilder implements BuilderContract // todo ist kein AbstractBuilder!
{
    /**
     * Database Access Layer.
     *
     * @var Database
     */
    protected $db; // todo evtl getter anbieten

    /**
     * Name of the class where the data are mapped to.
     *
     * @var string
     */
    protected $class;

    /**
     * The list of flags (at time only DISTINCT).
     *
     * @var array
     */
    protected $flags = []; // todo evtl ditinct als boolean verwenden

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

    // todo union clause

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
        //'MOD', 'DIV', //kein Standard, sollte nicht verwendet werden! (nur MySQL?)
        'CASE', 'WHEN', 'THEN', 'ELSE', 'END', // if then else
        'ASC', 'DESC', // ordering
    ];

    // todo PostgreSQL: ILIKE und CURRENT_DATE hinzufügen

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

        if ($source instanceof Builder) {
            $this->putBindings('from', $source->bindings());
            $source = $source->toSql();
        }

        // todo u.u. prüfen, ob es ein tabellenname ist und nur compileExpression aufrufen, wenn es ein subquery ist
        // todo ebenso bei join

        $source = $this->compileExpression($source);

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
     * @param string|Builder|\Closure $source A table name or a subquery.
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

        if ($source instanceof Builder) {
            $this->putBindings('join', $source->bindings());
            $source = $source->toSql();
        }

        $source = $this->compileExpression($source);

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
     * @inheritdoc
     */
    public function where($condition, array $bindings = [], $or = false)
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
    public function orWhere($condition, array $bindings = [])
    {
        return $this->where($condition, $bindings, true);
    }

    /**
     * @inheritdoc
     */
    public function whereIs($column, $value, $operator = '=', $or = false)
    {
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->db->quoteName($column) . ' ' . strtoupper($operator) . ' ?';
        $this->putBindings('where', [$value]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereIs($column, $value, $operator = '=')
    {
        return $this->whereIs($column, $value, $operator, true);
    }

    /**
     * @inheritdoc
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = [], $or = false)
    {
        if (is_callable($query)) {
            $query = $query(new static($this->db));
        }

        if ($query instanceof Builder) {
            $this->putBindings('where', $query->bindings());
            $query = $query->toSql();
        }

        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->db->quoteName($column) . ' ' . strtoupper($operator) . ' (' . $query . ')';
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

        if ($query instanceof Builder) {
            $this->putBindings('where', $query->bindings());
            $query = $query->toSql();
        }

        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . 'EXISTS (' . $query . ')';
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
        $this->where[] = $op . $this->db->quoteName($column) . ($not ? ' NOT' : '') . ' IN (' . $placeholders . ')';
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
        $this->where[] = $op . $this->db->quoteName($column) . ($not ? ' NOT' : '') . ' BETWEEN ? AND ?';
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
    public function whereIsNull($column, $or = false, $not = false)
    {
        $op = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $op . $this->db->quoteName($column) . ($not ? ' IS NOT NULL' : ' IS NULL');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function orWhereIsNull($column)
    {
        return $this->whereIsNull($column, true);
    }

    /**
     * @inheritdoc
     */
    public function whereIsNotNull($column, $or = false)
    {
        return $this->whereIsNull($column, $or, true);
    }

    /**
     * @inheritdoc
     */
    public function orWhereIsNotNull($column)
    {
        return $this->whereNotIsNull($column, true);
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
        return $this->db->single("SELECT $columns FROM $table WHERE $key = ?", [$id], $this->class);
    }

    /**
     * @inheritdoc
     */
    public function all()
    {
        $result = $this->db->query($this->toSql(), $this->bindings(), $this->class);

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
        $result = $this->db->single($this->toSql(), $this->bindings(), $this->class);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function value()
    {
        $result = $this->db->scalar($this->toSql(), $this->bindings());

        return $result;
    }

    ///////////////////////////////////////////////////////////////////
    // Aggregate Functions

    /**
     * @inheritdoc
     */
    public function count()
    {
        $query = 'SELECT COUNT(' . $this->buildFlags() . $this->buildColumns() . ')'
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildGroupBy()
            . $this->buildHaving()
            . $this->buildOrderBy()
            . $this->buildLimit();

        $result = $this->db->scalar($query, $this->bindings());

        return $result;
    }

    // todo avg($column), sum($count), etc

    ///////////////////////////////////////////////////////////////////
    // Modification Methods

    /**
     * @inheritdoc
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Cannot insert an empty record.');
        }

        $table = implode(', ', $this->from);

        if (is_string(key($data))) {
            // single record will be inserted
            $columns  = implode(', ', array_map([$this->db, 'quoteName'], array_keys($data)));
            $params   = implode(', ', array_fill(0, count($data), '?'));
            $bindings = array_values($data);
        }
        else {
            // Bulk insert...
            $keys = [];
            foreach ($data as $row) {
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

        /** @noinspection SqlDialectInspection */
        $this->db->exec("INSERT INTO $table ($columns) VALUES ($params)", $bindings);

        return $this->db->lastInsertId();
    }

    /**
     * @inheritdoc
     */
    public function update(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Cannot update an empty record.');
        }

        $bindings = array_merge($this->bindings['join'], $this->bindings['where'], $this->bindings['order']);
        $settings = [];
        $values   = [];
        if (empty($bindings) || is_int(key($bindings))) { // Prevent PDO-Error: "Invalid parameter number: mixed named and positional parameters"
            // bindings not used or question mark placeholders used
            foreach ($data as $column => $value) {
                $settings[] = $this->db->quoteName($column) . ' = ?';
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
                $settings[] = $this->db->quoteName($column) . ' = :' . $key;
                $values[$key] = $value;
            }
        }

        $from     = ' ' . implode(', ', $this->from);
        $bindings = array_merge($this->bindings['join'], $values, $this->bindings['where'], $this->bindings['order']);
        $settings = ' SET ' . implode(', ', $settings);

        $query = 'UPDATE'
            . $from
            . $this->buildJoin()
            . $settings
            . $this->buildWhere()
            . $this->buildOrderBy()
            . $this->buildLimit();

        return $this->db->exec($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $bindings = array_merge($this->bindings['join'], $this->bindings['where'], $this->bindings['order']);

        $query = 'DELETE'
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildOrderBy()
            . $this->buildLimit();

        return $this->db->exec($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function truncate()
    {
        $table = implode(', ', $this->from);

        $this->db->exec("TRUNCATE TABLE $table");
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
     * @param string|array|Builder|\Closure $columns One or more columns or subquery.
     * @param string $clause Key for the binding list: "select", "group" or "order"
     * @return array
     */
    protected function compileColumns($columns, $clause)
    {
        $list = [];
        foreach ((array)$columns as $alias => $column) {
//            // todo testen, ob das Prüfen auf normale Spalte etwas bringt, sonst raus damit
//            if (is_string($column) && preg_match('/^(?:([0-9a-zA-Z$_]+)\.)?([0-9a-zA-Z$_]+)$/s', trim($column), $match)) { // for maximum performance, first check the most common case..
//                // yes, the expression is just a column name: "col1" or "t1.col1"!
//                $column = (!empty($match[1]) ? $this->db->quoteName($match[1]) . '.' : '') . $this->db->quoteName($match[2]);
//            }
//            else {
                if (is_callable($column)) {
                    $column = $column(new static($this->db));
                }

                if ($column instanceof Builder) {
                    $this->putBindings($clause, $column->bindings());
                    $column = $column->toSql();
                }

                $column = $this->compileExpression($column);
//            }

            if (is_string($alias)) {
                //if (stripos($column, ' ') !== false) {
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
            if (stripos('abcdefghijklmnopqrstuvwxyz', $ch) !== false) {
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
            else if ($ch == '"' || $ch == "'" || $ch == '`' || $ch == '[') { // masked literal
                $c = $ch == '[' ? ']' : $ch;
                while (++$i < $n && $expr[$i] != $c);
                ++$i;
            }
            else if ($ch == '-' && $i + 2 < $n && $expr[$i + 1] == '-' && $expr[$i + 2] == ' ') { // comment
                $i += 2;
                while (++$i < $n && $expr[$i] != PHP_EOL);
                ++$i;
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
     * Builds the `LIMIT ... OFFSET` clause of the statement.
     *
     * @return string
     */
    protected function buildLimit()
    {
        return $this->limit !== null ? ' LIMIT ' . $this->limit . ($this->offset ? ' OFFSET ' . $this->offset : '') : '';
    }
}