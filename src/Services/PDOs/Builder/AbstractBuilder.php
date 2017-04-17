<?php

namespace Core\Services\PDOs\Builder;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builder\Contracts\Builder as BuilderContract;
use Core\Services\PDOs\Builder\Contracts\Builder;

/**
 * Abstract Query Builder
 *
 * The class based on the Aura's QueryFactory (https://github.com/auraphp/Aura.SqlQuery/blob/3.x/LICENSE BSD 2-clause "Simplified" License)
 *
 * @see https://github.com/auraphp/Aura.SqlQuery Aura.SqlQuery
 */
abstract class AbstractBuilder implements BuilderContract
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
     * The list of flags (at time only DISTINCT).
     *
     * @var array
     */
    protected $flags = []; // todo evtl ditinct als boolean verwednen

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
     * This words are Logical operators + "AS", "IS", "NULL", "TRUE, "FALSE", ASC" and "DESC.
     * Tables and fields with this name will not be quoted automatically.
     *
     * @see https://www.w3schools.com/sql/sql_operators.asp)
     * @var array
     */
    protected static $keywords = [
        'ALL', 'AND', 'ANY', 'AS', 'ASC', 'BETWEEN', 'DESC', 'EXISTS', 'FALSE', 'IN',
        'IS', 'LIKE', 'NOT', 'NULL', 'OR', 'SOME', 'TRUE',
    ];

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
    public function select($columns, array $bindings = [])
    {
        $this->columns = array_merge($this->columns, $this->compileColumns($columns));
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
        return $this->joinTable('INNER', $source, $on, $alias);
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->joinTable('LEFT', $source, $on, $alias);
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->joinTable('RIGHT', $source, $on, $alias);
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
    protected function joinTable($type, $source, $on, $alias, array $bindings = [])
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

        $operator = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $operator . $this->compileExpression($condition);
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

//    public function whereEqualArray(array $values)
//    {
//        foreach ($values as $column => $value) {
//            $this->where[] = [$this->db->quoteName($column) . ' = ?'];
//            $this->bindings['where'][] = $value;
//        }
//    }

//    /**
//     * Add a "where is equal" statement to the query.
//     *
//     * @param string $column.
//     * @param mixed $value
//     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
//     * @param bool $not If true the condition is negated.
//     * @return Builder
//     */
//    public function whereEqual($column, $value, $or = false, $not = false)
//    {
//        $operator = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
//        $this->where[] = $operator . $this->db->quoteName($column) . ($not ? ' <> ?' : '= ?');
//        $this->putBindings('where', [$value]);
//
//        return $this;
//    }
//
//    // todo orWhereEqual, whereNotEqual, orWhereNotEqual

//    /**
//     * Add a full subquery to the query.
//     *
//     * @param  string   $column
//     * @param  string   $operator
//     * @param  \Closure $callback
//     * @param  string   $boolean
//     * @return $this
//     */
//    protected function whereQuery($column, $query, $operator = '=', $or = false, array $bindings = [])
//    {
//        if (is_callable($values)) {
//            $values = $values(new static($this->db));
//        }
//
//        if ($values instanceof Builder) {
//            $values = $values->toSql();
//        }
//
//        return $this;
//    }

//    /**
//     * Add an exists clause to the query.
//     *
//     * @param  \Closure $callback
//     * @param  string   $boolean
//     * @param  bool     $not
//     * @return $this
//     */
//    public function whereExists(Closure $callback, $boolean = 'and', $not = false) // todo
//    {
//
//    }

    /**
     * Add a "where in" clause to the query.
     *
     * Examples:
     * <pre>
     * whereIn('column1', [1, 2, 3])
     * </pre>
     *
     * @param string $column.
     * @param array $values
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereIn($column, array $values, $or = false, $not = false)
    {
        $operator = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->where[] = $operator . $this->db->quoteName($column) . ($not ? ' NOT' : '') . ' IN (' . $placeholders . ')';
        $this->putBindings('where', $values);

        return $this;
    }

    /**
     * Add a "or where in" clause to the query.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public function orWhereIn($column, array $values)
    {
        return $this->whereIn($column, $values, true);
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotIn($column, array $values, $or = false)
    {
        return $this->whereIn($column, $values, $or, true);
    }

    /**
     * Add a "or where not in" clause to the query.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public function orWhereNotIn($column, array $values)
    {
        return $this->whereNotIn($column, $values, true);
    }

    /**
     * Add a "where between" clause to the query.
     *
     * @param string $column.
     * @param mixed $lowest
     * @param mixed $highest
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereBetween($column, $lowest, $highest, $or = false, $not = false)
    {
        $operator = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $operator . $this->db->quoteName($column) . ($not ? ' NOT' : '') . ' BETWEEN ? AND ?';
        $this->putBindings('where', [$lowest, $highest]);

        return $this;
    }

    /**
     * Add a "or where between" clause to the query.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @return Builder
     */
    public function orWhereBetween($column, $lowest, $highest)
    {
        return $this->whereBetween($column, $lowest, $highest, true);
    }

    /**
     * Add a "where not between" clause to the query.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotBetween($column, $lowest, $highest, $or = false)
    {
        return $this->whereBetween($column, $lowest, $highest, $or, true);
    }

    /**
     * Add a "or where not between" clause to the query.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @return Builder
     */
    public function orWhereNotBetween($column, $lowest, $highest)
    {
        return $this->whereNotBetween($column, $lowest, $highest, true);
    }

    /**
     * Add a "where is null" clause to the query.
     *
     * @param string $column.
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereIsNull($column, $or = false, $not = false)
    {
        $operator = !empty($this->where) ? ($or ? 'OR ' : 'AND ') : '';
        $this->where[] = $operator . $this->db->quoteName($column) . ($not ? ' IS NOT NULL' : ' IS NULL');

        return $this;
    }

    /**
     * Add a "or where is null" clause to the query.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereIsNull($column)
    {
        return $this->whereIsNull($column, true);
    }

    /**
     * Add a "where not is null" clause to the query.
     *
     * @param string $column
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotIsNull($column, $or = false)
    {
        return $this->whereIsNull($column, $or, true);
    }

    /**
     * Add a "or where not is null" clause to the query.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereNotIsNull($column)
    {
        return $this->whereNotIsNull($column, true);
    }

    /**
     * @inheritdoc
     */
    public function groupBy($columns, array $bindings = [])
    {
        $this->groupBy = array_merge($this->groupBy, $this->compileColumns($columns));
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

        $operator = !empty($this->having) ? ($or ? 'OR ' : 'AND ') : '';
        $this->having[] = $operator . $this->compileExpression($condition);
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
        $this->orderBy = array_merge($this->orderBy, $this->compileColumns($columns));
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
        foreach (array_keys($bindings) as $clause) {
            if (!empty($this->bindings[$clause])) {
                $bindings = array_merge($bindings, $this->bindings[$clause]);
            }
        }

        return $bindings;
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

    /**
     * @inheritdoc
     */
    public function cursor()
    {
        $result = $this->db->cursor($this->toSql(), $this->bindings(), $this->class);

        return $result;
    }

    ///////////////////////////////////////////////////////////////////
    // Data Manipulation

    /**
     * @inheritdoc
     */
    public function update(array $data)
    {
        $bindings = array_merge($this->bindings['join'], $this->bindings['where']);
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

        $bindings = array_merge($this->bindings['join'], $values, $this->bindings['where']);
        $settings = ' SET ' . implode(', ', $settings);

        $query = 'UPDATE'
            . $this->buildFrom()
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
        $bindings = array_merge($this->bindings['join'], $this->bindings['where']);

        $query = 'DELETE FROM'
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildOrderBy()
            . $this->buildLimit();

        return $this->db->exec($query, $bindings);
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
     * @return array
     */
    protected function compileColumns($columns)
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
                            else if ($ch == '-' && $i + 1 < $n && $expr[$i + 1] == '-') { // comment
                                ++$i;
                                while (++$i < $n && $expr[$i] != PHP_EOL);
                            }
                        }
                        if ($i - $j - 1 > 0) {
                            $args[] = substr($expr, $j, $i - $j - 1);
                        }
                        $args = array_map(function ($arg) {
                            return $this->compileExpression($arg);
                        }, $args);
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
                        else if ($ch == '-' && $i + 1 < $n && $expr[$i + 1] == '-') { // comment
                            ++$i;
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
            else if ($ch == '-' && $i + 1 < $n && $expr[$i + 1] == '-') { // comment
                ++$i;
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