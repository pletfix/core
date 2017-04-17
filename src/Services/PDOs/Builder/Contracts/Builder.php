<?php

namespace Core\Services\PDOs\Builder\Contracts;

/**
 * Query Builder
 */
interface Builder
{
    /**
     * Reset the query.
     *
     * @return $this
     */
    public function reset();

    /**
     * Get a copy of the instance
     *
     * @return static
     */
    public function copy();

    /**
     * Set the name of the class where the data are mapped to.
     *
     * if null is passed, the data will be returned as an array (the default).
     *
     * @param string|null $class
     * @return $this
     */
    public function asClass($class);

    /**
     * Adds columns to the query.
     *
     * Multiple calls to select() will append to the list of columns, not overwrite the previous columns.
     *
     * For computed columns, you should only use standard SQL operators and functions, so that the database drivers can
     * translate the expression correctly.
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
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
     * @param string|array|Builder|\Closure $columns One or more columns or subquery.
     * @param array $bindings
     * @return $this
     */
    public function select($columns, array $bindings = []);

    /**
     * Makes the select DISTINCT.
     *
     * @return $this
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
     * from($builder->from('table1')->where('column1 > ?'), 't1', [$foo])
     * </pre>
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return $this
     */
    public function from($source, $alias = null, array $bindings = []);

    /**
     * Adds a INNER JOIN clause to the query.
     *
     * You should only use standard SQL operators and functions for the ON clause, so that the database drivers can
     * translate the expression correctly.
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
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
     * join($builder->from('table2')->where('column1 > ?'), 't1.id = t2.table1_id', 't2', [$foo])
     * </pre>
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string $on Join on this condition, e.g.: "foo.id = d.foo_id"
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = []);

    /**
     * Adds a WHERE condition to the query.
     *
     * You may use also an array if you wish just filter specific values: ["column" => $value]
     *
     * You should only use standard SQL operators and functions for the ON clause, so that the database drivers can
     * translate the expression correctly.
     * @see https://www.w3schools.com/sql/sql_operators.asp Standard SQL Operators
     * @see https://www.w3schools.com/sql/sql_functions.asp Standard SQL Aggregate Functions
     * @see https://www.w3schools.com/sql/sql_isnull.asp Standard SQL NULL Functions
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * where('column1 = ? OR t1.column2 LIKE "%?%"', [$foo, $bar])
     * where('column1 = (SELECT MAX(i) FROM table2 WHERE c1 = ?)', [$foo])
     * where(function(Builder $builder) { return $builder->where('c1 = ?')->orWhere('c2 = ?'); }, [$foo, $bar])
     * </pre>
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return $this
     */
    public function where($condition, array $bindings = [], $or = false);

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return $this
     */
    public function orWhere($condition, array $bindings = []);

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
     * @param string|array $columns The columns to group by.
     * @param array $bindings
     * @return $this
     */
    public function groupBy($columns, array $bindings = []);

    /**
     * Adds a HAVING condition to the query.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return $this
     */
    public function having($condition, array $bindings = [], $or = false);

    /**
     * Adds a HAVING condition to the query by OR.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return $this
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
     * </pre>
     *
     * // todo prüfen, ob auch subqueries möglich sind
     * @param string|array $columns The columns to order by, possible with the direction key word "ASC" (default) or "DESC".
     * @param array $bindings
     * @return $this
     */
    public function orderBy($columns, array $bindings = []);

    /**
     * Sets a limit count on the query.
     *
     * @param int $limit The number of rows to select.
     * @return $this
     */
    public function limit($limit);

    /**
     * Sets a limit offset on the query.
     *
     * @param int $offset Start returning after this many rows.
     * @return $this
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
     * Execute the query as a "select" statement and returns the result.
     *
     * @return array
     */
    public function all();

    /**
     * Execute the query as a "SELECT" statement and returns the first record.
     *
     * @return mixed
     */
    public function first();

    /**
     * Execute the query as a "SELECT" statement and returns a single column's value from the first record.
     *
     * @return mixed
     */
    public function value();

    /**
     * Execute the query as a "SELECT" statement and returns a generator.
     *
     * With the cursor you could iterate the rows (via foreach) without fetch all the data at one time.
     * This method is useful to handle big data.
     *
     * @return mixed
     */
    public function cursor();

    /**
     * Update the table with th given data and returns the number of affected rows.
     *
     * @param array $data Values to be updated
     * @return int
     */
    public function update(array $data);

    /**
     * Delete rows from the table and returns the number of affected rows.
     *
     * @return int
     */
    public function delete();
}