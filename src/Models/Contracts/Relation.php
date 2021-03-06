<?php

namespace Core\Models\Contracts;

use Closure;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Countable;

/**
 * Entity Relation
 */
interface Relation extends Countable //extends ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Run a callback with constraints disabled on the relation.
     *
     * @param Closure $callback
     * @return mixed
     */
    public static function noConstraints(Closure $callback);

    /**
     * Add the constraints for an eager loading of the relation.
     *
     * @param Model[] $entities
     * @return Builder
     */
    public function addEagerConstraints(array $entities);

    /**
     * Get the entities of the relationship.
     *
     * @return mixed
     */
    public function get();

    /**
     * Eager load the entities of the relationship.
     *
     * @return array
     */
    public function getEager();

    /**
     * Get the local model.
     *
     * @return Model
     */
    public function model();

    /**
     * Get the QueryBuilder instance.
     *
     * @return Builder
     */
    public function builder();

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
     * join($builder->from('table2')->whereCondition('column1 > ?'), 't1.id = t2.table1_id', 't2', [$foo])
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
     * Add a comparison operation into the WHERE clause.
     *
     * Example:
     * <pre>
     * where('column1', 4711, '>')
     * </pre>
     *
     * @param string $column.
     * @param mixed $value
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE'
     * @return Builder
     */
    public function where($column, $value, $operator = '=');

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
     * whereCondition('column1 = ? OR t1.column2 LIKE "%?%"', [$foo, $bar])
     * whereCondition('column1 = (SELECT MAX(i) FROM table2 WHERE c1 = ?)', [$foo])
     * whereCondition(function(Builder $builder) { return $builder->whereCondition('c1 = ?')->orWhereCondition('c2 = ?'); }, [$foo, $bar])
     * </pre>
     *
     * @param string|\Closure $condition
     * @param array $bindings
     * @return Builder
     */
    public function whereCondition($condition, array $bindings = []);

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
     * @param string $operator '=', '<', '>', '<=', '>=', '<>', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE'
     * @param array $bindings
     * @return Builder
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = []);

    /**
     * Add "WHERE EXISTS( SELECT... )" to the query.
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
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @return Builder
     */
    public function whereExists($query, array $bindings = []);

    /**
     * Add "WHERE NOT EXISTS( SELECT... )" to the query.
     *
     * See the <pre>whereExists</pre> method for details and examples.
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @return Builder
     */
    public function whereNotExists($query, array $bindings = []);

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
     * @return Builder
     */
    public function whereIn($column, array $values);

    /**
     * Add "WHERE column NOT IN (?,?,...)" to the query.
     *
     * See the <pre>whereIn</pre> method for an example.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public function whereNotIn($column, array $values);

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
     * @return Builder
     */
    public function whereBetween($column, $lowest, $highest);

    /**
     * Add "WHERE column NOT BETWEEN ? AND ?" to the query.
     *
     * See the <pre>whereBetween</pre> method for an example.
     *
     * @param string $column
     * @param mixed $lowest
     * @param mixed $highest
     * @return Builder
     */
    public function whereNotBetween($column, $lowest, $highest);

    /**
     * Add "WHERE column IS NULL to the query.
     *
     * @param string $column.
     * @return Builder
     */
    public function whereNull($column);

    /**
     * Add "WHERE column IS NOT NULL to the query.
     *
     * @param string $column
     * @return Builder
     */
    public function whereNotNull($column);

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
     *
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
     * Find the model by the primary key.
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
     * @return mixed
     */
    public function cursor();

    /**
     * Execute the query as a "SELECT" statement and return the first record.
     *
     * @return mixed
     */
    public function first();

    /**
     * Calculates the number of entities.
     *
     * @return int
     */
    public function count();

    /**
     * Calculates the maximum value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public function max($attribute = null);

    /**
     * Calculates the minimum value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public function min($attribute = null);

    /**
     * Calculates the average value of a given attribute.
     *
     * @param string|null $attribute
     * @return float
     */
    public function avg($attribute = null);

    /**
     * Calculates the total value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public function sum($attribute = null);

    /**
     * Add the relation to the given model.
     *
     * It returns FALSE if the operation was canceled by a hook, otherwise TRUE.
     *
     * @param Model $model
     * @return bool
     */
    public function associate(Model $model);

    /**
     * Remove the relation to the given model.
     *
     * It returns FALSE if the operation was canceled by a hook, otherwise TRUE.
     *
     * @param Model|null $model
     * @return bool
     */
    public function disassociate(Model $model = null);

    /**
     * Create a new model, set the relation and save it in the database.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @param array $attributes
     * @return Model|false
     */
    public function create(array $attributes = []);

    /**
     * Update all records of the relation with th given attributes and return the number of affected rows.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @param array $attributes Values to be updated
     * @return int|false
     */
    public function update(array $attributes);

    /**
     * Delete the given model from the database and remove the relation.
     *
     * It returns FALSE if the operation was canceled by a hook, otherwise TRUE.
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model);
}