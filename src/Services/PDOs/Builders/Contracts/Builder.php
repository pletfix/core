<?php

namespace Core\Services\PDOs\Builders\Contracts;

use Countable;
use Generator;

/**
 * Query Builder
 */
interface Builder extends Countable
{
    /**
     * Reset the query.
     *
     * @return Builder
     */
    public function reset();

    /**
     * Get a copy of the instance.
     *
     * @return static
     */
    public function copy();

    /**
     * Set the name of the class where the data are mapped to.
     *
     * If null is passed, the data will be returned as an array (the default).
     *
     * If the class implements the Hookable contract, the QueryBuilder bind the hooks that are provided by this
     * class. You may disable this behavior via the `disableHooks` method.
     *
     * @param string|null $class
     * @return Builder
     */
    public function asClass($class);

    /**
     * Get the Class.
     *
     * @return string
     */
    public function getClass();

    /**
     * Enable hooks.
     *
     * The hooks of the class you specified using method `asClass` will be invoke, presupposed that the class
     * implements the Hookable contract.
     *
     * Note, that hooks are enabled by default.
     */
    public function enableHooks();

    /**
     * Disable hooks.
     *
     * You may use this method if you have a class with hooks, but the hooks should be ignored.
     *
     * @return $this
     */
    public function disableHooks();

    /**
     * Get the entities of given relationship via eager loading.
     *
     * Note that a class, that provides the given relationship method, must be specified.
     *
     * @param array|string $method
     * @return $this
     */
    public function with($method);

    /**
     * Adds columns to the query.
     *
     * Multiple calls to select() will append to the list of columns, not overwrite the previous columns.
     *
     * For computed columns, you should only use standard SQL operators and functions, so that the database drivers can
     * translate the expression correctly.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * // as comma-separated string list:
     * select('column1, t1.column2 AS c2')
     *
     * // as array:
     * select(['column1', 't1.column2 AS c2', 'c3' => 't1.column3'])
     *
     * // with calculated columns:
     * select('COUNT(*) AS c1')
     * select(['c1' => 'COUNT(*)'])
     *
     * // with subquery:
     * select(['c1' => 'SELECT MAX(i) FROM table2'])
     * select(['c1' => database()->createBuilder()->from('table2')->select('MAX(i)')])
     * select(['c1' => function(Builder $builder) { return $builder->from('table2')->select('MAX(i)'); }])
     *
     * // with placeholders:
     * select(['c1' => 'column2 * ?'], [$foo])
     * </pre>
     *
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
     *
     * @param string|array $columns One or more columns.
     * @param array $bindings
     * @return Builder
     */
    public function select($columns, array $bindings = []);

    /**
     * Makes the select DISTINCT.
     *
     * @return Builder
     */
    public function distinct();

    /**
     * Adds a FROM clause to the query.
     *
     * Multiple calls to from() will append to the list of sources, not overwrite the previous sources.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * // from table
     * from('table1')
     *
     * // with alias:
     * from('table1', 't1')
     *
     * // from subquery:
     * from('SELECT * FROM table1', 't1')
     * from(database()->createBuilder()->from('table1'), 't1')
     * from(function($builder) { return $builder->from('table1'); }, 't1')
     *
     * // from subquery with placeholders:
     * from($builder->from('table1')->whereCondition('column1 > ?'), 't1', [$foo])
     * </pre>
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return Builder
     */
    public function from($source, $alias = null, array $bindings = []);

    /**
     * Set a table name.
     *
     * @param string $table
     * @return Builder
     */
    public function table($table);

    /**
     * Get the name of the table if specify.
     *
     * @return string|null
     */
    public function getTable();

    /**
     * Adds a INNER JOIN clause to the query.
     *
     * You should only use standard SQL operators and functions for the ON clause, so that the database drivers can
     * translate the expression correctly.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Excursion; the difference between ON and WHERE clause:
     * The ON clause serves for conditions that specify how to join tables, and the WHERE clause restricts which rows
     * to include in the result set.
     *
     * Examples:
     * <pre>
     * // from table:
     * join('table2', 'table1.id = table2.table1_id')
     *
     * // with alias:
     * join('table2', 't1.id = t2.table1_id', 't2')
     *
     * // with subquery:
     * join('SELECT * FROM table2', 't1.id = t2.table1_id', 't2')
     * join(database()->createBuilder()->from('table2'), 't1.id = t2.table1_id', 't2')
     * join(function(Builder $builder) { return $builder->from('table2'); }, 't1.id = t2.table1_id', 't2')
     *
     * // from subquery with placeholders:
     * join($builder->from('table2')->whereCondition('column1 > ?'), 't1.id = t2.table1_id', 't2', [$foo])
     * </pre>
     *
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string $on Join on this condition, e.g.: "foo.id = d.foo_id"
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return Builder
     */
    public function join($source, $on, $alias = null, array $bindings = []);

    /**
     * Adds a LEFT JOIN clause to the query.
     *
     * See the <pre>join</pre>  method for details and examples.
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string $on Join on this condition, e.g.: "foo.id = d.foo_id"
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return Builder
     */
    public function leftJoin($source, $on, $alias = null, array $bindings = []);

    /**
     * Adds a RIGHT JOIN clause to the query.
     *
     * See the <pre>join</pre>  method for details and examples.
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string $on Join on this condition, e.g.: "foo.id = d.foo_id"
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return Builder
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = []);

    /**
     * Add a comparison operation into the WHERE clause.
     *
     * Example:
     * <pre>
     * where('column1', 4711, '>')
     * </pre>
     *
     * @param string $column.
     * @param mixed $value
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function where($column, $value, $operator = '=', $or = false);

    /**
     * Add a comparison operation into the WHERE clause by OR.
     *
     * See the <pre>where</pre> method for an example.
     *
     * @param string $column.
     * @param mixed $value
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @return Builder
     */
    public function orWhere($column, $value, $operator = '=');

    /**
     * Adds a WHERE condition to the query.
     *
     * You should only use standard SQL operators and functions, so that the database drivers can translate the
     * expression correctly.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * whereCondition('column1 = ? OR t1.column2 LIKE "%?%"', [$foo, $bar])
     * whereCondition('column1 = (SELECT MAX(i) FROM table2 WHERE c1 = ?)', [$foo])
     * whereCondition(function(Builder $builder) { return $builder->whereCondition('c1 = ?')->orWhereCondition('c2 = ?'); }, [$foo, $bar])
     * </pre>
     *
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereCondition($condition, array $bindings = [], $or = false);

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return Builder
     */
    public function orWhereCondition($condition, array $bindings = []);

    /**
     * Add a subquery into the WHERE clause.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * whereSubQuery('column1', 'SELECT MAX(i) FROM table2 WHERE c1 = ?', [$foo])
     * whereSubQuery('column1', database()->createBuilder()->select('MAX(i)')->from('table2')->whereCondition('c1 = ?'), [$foo])
     * whereSubQuery('column1', function(Builder $builder) { return $builder->select('MAX(i)')->from('table2')->whereCondition('c1 = ?'); }, [$foo])
     * </pre>
     *
     * @param string $column
     * @param string|Builder|\Closure $query
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = [], $or = false);

    /**
     * Add a subquery into the WHERE clause by OR.
     *
     * See the <pre>whereSubQuery</pre> method for details and examples.
     *
     * @param string $column
     * @param string|Builder|\Closure $query
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @param array $bindings
     * @return Builder
     */
    public function orWhereSubQuery($column, $query, $operator = '=', array $bindings = []);

    /**
     * Add "WHERE EXISTS( SELECT... )" to the query.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * whereExists('column1', 'SELECT MAX(i) FROM table2 WHERE c1 = ?', [$foo])
     * whereExists('column1', database()->createBuilder()->select('MAX(i)')->from('table2')->whereCondition('c1 = ?'), [$foo])
     * whereExists('column1', function(Builder $builder) { return $builder->select('MAX(i)')->from('table2')->whereCondition('c1 = ?'); }, [$foo])
     * </pre>
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereExists($query, array $bindings = [], $or = false, $not = false);

    /**
     * Add "WHERE EXISTS( SELECT... )" to the query by OR.
     *
     * See the <pre>whereExists</pre> method for details and examples.
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @return Builder
     */
    public function orWhereExists($query, array $bindings = []);

    /**
     * Add "WHERE NOT EXISTS( SELECT... )" to the query.
     *
     * See the <pre>whereExists</pre> method for details and examples.
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotExists($query, array $bindings = [], $or = false);

    /**
     * Add "WHERE NOT EXISTS( SELECT... )" to the query by OR.
     *
     * See the <pre>whereExists</pre> method for details and examples.
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @return Builder
     */
    public function orWhereNotExists($query, array $bindings = []);

    /**
     * Add "WHERE column IN (?,?,...)" to the query.
     *
     * Example:
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
    public function whereIn($column, array $values, $or = false, $not = false);

    /**
     * Add "WHERE column IN (?,?,...)" to the query by OR.
     *
     * See the <pre>whereIn</pre> method for an example.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public function orWhereIn($column, array $values);

    /**
     * Add "WHERE column NOT IN (?,?,...)" to the query.
     *
     * See the <pre>whereIn</pre> method for an example.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotIn($column, array $values, $or = false);

    /**
     * Add "WHERE column NOT IN (?,?,...)" to the query by OR.
     *
     * See the <pre>whereIn</pre> method for an example.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public function orWhereNotIn($column, array $values);

    /**
     * Add "WHERE column BETWEEN ? AND ?" to the query.
     *
     * Example:
     * <pre>
     * whereBetween('column1', 123, 789)
     * </pre>
     *
     * @param string $column.
     * @param mixed $lowest
     * @param mixed $highest
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereBetween($column, $lowest, $highest, $or = false, $not = false);

    /**
     * Add "WHERE column BETWEEN ? AND ?" to the query by OR.
     *
     * See the <pre>whereBetween</pre> method for an example.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @return Builder
     */
    public function orWhereBetween($column, $lowest, $highest);

    /**
     * Add "WHERE column NOT BETWEEN ? AND ?" to the query.
     *
     * See the <pre>whereBetween</pre> method for an example.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotBetween($column, $lowest, $highest, $or = false);

    /**
     * Add "WHERE column NOT BETWEEN ? AND ?" to the query by OR.
     *
     * See the <pre>whereBetween</pre> method for an example.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @return Builder
     */
    public function orWhereNotBetween($column, $lowest, $highest);

    /**
     * Add "WHERE column IS NULL to the query.
     *
     * @param string $column.
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @param bool $not If true the condition is negated.
     * @return Builder
     */
    public function whereNull($column, $or = false, $not = false);

    /**
     * Add "WHERE column IS NULL to the query by OR.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereNull($column);

    /**
     * Add "WHERE column IS NOT NULL to the query.
     *
     * @param string $column
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereNotNull($column, $or = false);

    /**
     * Add "WHERE column IS NOT NULL to the query by OR.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereNotNull($column);

    /**
     * Adds GROUP BY clause to the query.
     *
     * Examples:
     * <pre>
     * // as comma-separated string list:
     * groupBy('column1, t2.column2')
     *
     * // as array:
     * groupBy(['column1', 't2.column2'])
     * </pre>
     *
     * @param string|array $columns One or more columns to group by.
     * @param array $bindings
     * @return Builder
     */
    public function groupBy($columns, array $bindings = []);

    /**
     * Adds a HAVING condition to the query.
     *
     * You should only use standard SQL operators and functions, so that the database drivers can translate the
     * expression correctly.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * having('column1 = ? OR t1.column2 LIKE "%?%"', [$foo, $bar])
     * having('column1 = (SELECT MAX(i) FROM table2 WHERE c1 = ?)', [$foo])
     * having(function(Builder $builder) { return $builder->having('c1 = ?')->orHaving('c2 = ?'); }, [$foo, $bar])
     * </pre>
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function having($condition, array $bindings = [], $or = false);

    /**
     * Adds a HAVING condition to the query by OR.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return Builder
     */
    public function orHaving($condition, array $bindings = []);

    /**
     * Adds a ORDER BY clause to the query.
     *
     * Examples:
     * <pre>
     * // as comma-separated string list:
     * orderBy('column1, column2 ASC, t1.column3 DESC')
     *
     * // as array:
     * orderBy(['column1', 'column2 ASC', 't1.column3 DESC'])
     *
     * // as expression:
     * orderBy('column1 > 5')
     * </pre>
     *
     * @param string|array $columns The columns to order by, possible with the direction key word "ASC" (default) or "DESC".
     * @param array $bindings
     * @return Builder
     */
    public function orderBy($columns, array $bindings = []);

    /**
     * Sets a limit count on the query.
     *
     * @param int $limit The number of rows to select.
     * @return Builder
     */
    public function limit($limit);

    /**
     * Sets a limit offset on the query.
     *
     * @param int $offset Start returning after this many rows.
     * @return Builder
     */
    public function offset($offset);

    /**
     * Get the SQL representation of the query.
     *
     * @return string
     */
    public function toSql();

    /**
     * Get the bindings of the query.
     *
     * @return array
     */
    public function bindings();

    /**
     * Dump SQL
     *
     * The function binds the given values to the query and print the SQL statement out without executing.
     *
     * @param bool|null $return [optional] If used and set to true, dump will return the variable representation instead of outputing it.
     * @return string|null The SQL statement when the return parameter is true. Otherwise, this function will return null.
     */
    public function dump($return = null);

    /**
     * Find a single record by the primary key of the table.
     *
     * If the record is not exist, NULL is returned.
     *
     * @param int $id Value of the primary Key
     * @param string $key Name of the primary Key
     * @return mixed
     */
    public function find($id, $key = 'id');

    /**
     * Execute the query as a "select" statement and return the result.
     *
     * @return array
     */
    public function all();

    /**
     * Execute the query as a "SELECT" statement and return a generator.
     *
     * With the cursor you could iterate the rows (via foreach) without fetch all the data at one time.
     * This method is useful to handle big data.
     *
     * Note, this method ignores the "with" clause, because the data could not be eager loaded.
     *
     * @return Generator
     */
    public function cursor();

    /**
     * Execute the query as a "SELECT" statement and return the first record.
     *
     * If the record is not exist, NULL is returned.
     *
     * @return mixed
     */
    public function first();

    /**
     * Execute the query as a "SELECT" statement and return a single column's value from the first record.
     *
     * Note, this method ignores the "with" clause, eager load is deactivate.
     *
     * @return mixed
     */
    public function value();

    /**
     * Calculates the number of records.
     *
     * @param string|null $column
     * @return int
     */
    public function count($column = null);

    /**
     * Calculates the maximum value of a given column.
     *
     * @param string|null $column
     * @return float
     */
    public function max($column = null);

    /**
     * Calculates the minimum value of a given column.
     *
     * @param string|null $column
     * @return float
     */
    public function min($column = null);

    /**
     * Calculates the average value of a given column.
     *
     * @param string|null $column
     * @return float
     */
    public function avg($column = null);

    /**
     * Calculates the total value of a given column.
     *
     * @param string|null $column
     * @return int
     */
    public function sum($column = null);

    /**
     * Insert rows to the table and return the inserted autoincrement sequence value.
     *
     * If you insert multiple rows, the method returns dependent to the driver the first or last inserted id!.
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @param array $data Values to be updated
     * @return int|false
     */
    public function insert(array $data = []);

    /**
     * Update all records of the query result with th given data and return the number of affected rows.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @param array $data Values to be updated
     * @return int|false
     */
    public function update(array $data);

    /**
     * Delete all records of the query result and return the number of affected rows.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @return int|false
     */
    public function delete();
}