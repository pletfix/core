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
     * @return Builder
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
     * @return Builder
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
     * from($builder->from('table1')->where('column1 > ?'), 't1', [$foo])
     * </pre>
     *
     * @param string|Builder|\Closure $source A table name or a subquery.
     * @param string|null $alias The alias name for the source.
     * @param array $bindings
     * @return Builder
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
     * Adds a WHERE condition to the query.
     *
     * You should only use standard SQL operators and functions, so that the database drivers can translate the
     * expression correctly.
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
     * @return Builder
     */
    public function where($condition, array $bindings = [], $or = false);

    /**
     * Adds a WHERE condition to the query by OR.
     *
     * See the <pre>where</pre> method for details and examples.
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return Builder
     */
    public function orWhere($condition, array $bindings = []);

    /**
     * Add a comparison operation into the WHERE clause.
     *
     * Example:
     * <pre>
     * whereIs('column1', 4711, '>')
     * </pre>
     * 
     * @param string $column.
     * @param mixed $value
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereIs($column, $value, $operator = '=', $or = false);

    /**
     * Add a comparison operation into the WHERE clause by OR.
     *
     * See the <pre>whereIs</pre> method for an example.
     *
     * @param string $column.
     * @param mixed $value
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE' todo '<>' oder '!=' ?
     * @return Builder
     */
    public function orWhereIs($column, $value, $operator = '=');
    
    /**
     * Add a subquery into the WHERE clause.
     *
     * Note, that subqueries are not quoted, because the Builder of the subquery should do this work.
     *
     * Examples:
     * <pre>
     * whereSubQuery('column1', 'SELECT MAX(i) FROM table2 WHERE c1 = ?', [$foo])
     * whereSubQuery('column1', database()->createBuilder()->select('MAX(i)')->from('table2')->where('c1 = ?'), [$foo])
     * whereSubQuery('column1', function(Builder $builder) { return $builder->select('MAX(i)')->from('table2')->where('c1 = ?'); }, [$foo])
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
     * whereSubQuery('column1', 'SELECT MAX(i) FROM table2 WHERE c1 = ?', [$foo])
     * whereSubQuery('column1', database()->createBuilder()->select('MAX(i)')->from('table2')->where('c1 = ?'), [$foo])
     * whereSubQuery('column1', function(Builder $builder) { return $builder->select('MAX(i)')->from('table2')->where('c1 = ?'); }, [$foo])
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
    public function whereIsNull($column, $or = false, $not = false);

    /**
     * Add "WHERE column IS NULL to the query by OR.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereIsNull($column);

    /**
     * Add "WHERE column IS NOT NULL to the query.
     *
     * @param string $column
     * @param bool $or If false, the condition is added by AND (default), otherwise by OR.
     * @return Builder
     */
    public function whereIsNotNull($column, $or = false);

    /**
     * Add "WHERE column IS NOT NULL to the query by OR.
     *
     * @param string $column
     * @return Builder
     */
    public function orWhereIsNotNull($column);

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
     * @return Builder
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
     * </pre>
     *
     * // todo prüfen, ob auch subqueries möglich sind
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
     * Execute the query as a "select" statement and returns the result.
     *
     * @return array
     */
    public function all();

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