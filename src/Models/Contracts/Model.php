<?php

namespace Core\Models\Contracts;

use ArrayAccess;
use Core\Exceptions\MassAssignmentException;
use Core\Models\BelongsToManyRelation;
use Core\Models\BelongsToRelation;
use Core\Models\HasManyRelation;
use Core\Models\HasOneRelation;
use Core\Models\MorphManyRelation;
use Core\Models\MorphOneRelation;
use Core\Models\MorphToRelation;
use Core\Services\Contracts\Arrayable;
use Core\Services\Contracts\Database;
use Core\Services\Contracts\Jsonable;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Builders\Contracts\Hookable;
use JsonSerializable;

interface Model extends Arrayable, ArrayAccess, Hookable, Jsonable, JsonSerializable
{
    ///////////////////////////////////////////////////////////////////
    // Attribute Handling

    /**
     * Get the current attributes of the model.
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Set the given attributes to the model.
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes);

    /**
     * Get the attribute (or relationship!) from the model.
     *
     * If neither the attribute nor the relationship exists, null is returned.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getAttribute($name);

    /**
     * Set a given attribute of the model.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * Get the model's original attribute values.
     *
     * @param string|null $name
     * @return mixed|array
     */
    public function getOriginal($name = null);

    /**
     * Get the attributes that have been changed since last save.
     *
     * @return array
     */
    public function getDirty();

    /**
     * Determine if the model or given attribute(s) have been modified.
     *
     * If you omit the argument, all attributes are checked.
     *
     * @param array|string|null The name of one or more $attributes.
     * @return bool
     */
    public function isDirty($names = null);

    /**
     * Reload the attributes from the database.
     */
    public function reload();

    /**
     * Sync the original attributes with the current without reading the database.
     *
     * @return $this
     */
    public function sync();

    ///////////////////////////////////////////////////////////////////
    // Database Table Access

    /**
     * Get the database instance.
     *
     * @return Database
     */
    public function database();

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public function getTable();

    /**
     * Get the name of the primary key for the model's table.
     *
     * @return string
     */
    public function getPrimaryKey();

    /**
     * Get the identity.
     *
     * If the model is just created and not saved yet, the method returns null.
     *
     * @return int|null
     */
    public function getId();

    ///////////////////////////////////////////////////////////////////
    // Gets a Query Builder

//    /**
//     * Create a new QueryBuilder instance.
//     *
//     * @return Builder
//     */
//    public function createBuilder();

    /**
     * Create a new QueryBuilder instance.
     *
     * @return Builder
     */
    public static function builder();

    /**
     * Get the entities of given relationship via eager loading.
     *
     * Note that a class, that provides the given relationship method, must be specified.
     *
     * @param array|string $method
     * @return $this
     */
    public static function with($method);

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
    public static function select($columns, array $bindings = []);

    /**
     * Makes the select DISTINCT.
     *
     * @return Builder
     */
    public static function distinct();

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
    public static function from($source, $alias = null, array $bindings = []);

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
    public static function join($source, $on, $alias = null, array $bindings = []);

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
    public static function leftJoin($source, $on, $alias = null, array $bindings = []);

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
    public static function rightJoin($source, $on, $alias = null, array $bindings = []);

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
     * @return Builder
     */
    public static function where($condition, array $bindings = []);

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
     * @return Builder
     */
    public static function whereIs($column, $value, $operator = '=');

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
     * @return Builder
     */
    public static function whereSubQuery($column, $query, $operator = '=', array $bindings = []);

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
     * @return Builder
     */
    public static function whereExists($query, array $bindings = []);

    /**
     * Add "WHERE NOT EXISTS( SELECT... )" to the query.
     *
     * See the <pre>whereExists</pre> method for details and examples.
     *
     * @param string|Builder|\Closure $query
     * @param array $bindings
     * @return Builder
     */
    public static function whereNotExists($query, array $bindings = []);

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
    public static function whereIn($column, array $values);

    /**
     * Add "WHERE column NOT IN (?,?,...)" to the query.
     *
     * See the <pre>whereIn</pre> method for an example.
     *
     * @param string $column
     * @param array|\Closure|Builder|string $values
     * @return Builder
     */
    public static function whereNotIn($column, array $values);

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
    public static function whereBetween($column, $lowest, $highest);

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
    public static function whereNotBetween($column, $lowest, $highest);

    /**
     * Add "WHERE column IS NULL to the query.
     *
     * @param string $column.
     * @return Builder
     */
    public static function whereIsNull($column);

    /**
     * Add "WHERE column IS NOT NULL to the query.
     *
     * @param string $column
     * @return Builder
     */
    public static function whereIsNotNull($column);

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
    public static function orderBy($columns, array $bindings = []);

    /**
     * Sets a limit count on the query.
     *
     * @param int $limit The number of rows to select.
     * @return Builder
     */
    public static function limit($limit);

    /**
     * Sets a limit offset on the query.
     *
     * @param int $offset Start returning after this many rows.
     * @return Builder
     */
    public static function offset($offset);

    /**
     * Find the model by the primary key.
     *
     * @param int $id Value of the primary Key
     * @param string $key Name of the primary Key
     * @return mixed
     */
    public static function find($id, $key = 'id');

    /**
     * Execute the query as a "select" statement and return the result.
     *
     * @return array
     */
    public static function all();

    /**
     * Execute the query as a "SELECT" statement and return a generator.
     *
     * With the cursor you could iterate the rows (via foreach) without fetch all the data at one time.
     * This method is useful to handle big data.
     *
     * @return mixed
     */
    public static function cursor();

    /**
     * Execute the query as a "SELECT" statement and return the first record.
     *
     * @return mixed
     */
    public static function first();

    /**
     * Calculates the number of entities.
     *
     * @return int
     */
    public static function count();

    /**
     * Calculates the maximum value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public static function max($attribute = null);

    /**
     * Calculates the minimum value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public static function min($attribute = null);

    /**
     * Calculates the average value of a given attribute.
     *
     * @param string|null $attribute
     * @return float
     */
    public static function avg($attribute = null);

    /**
     * Calculates the total value of a given attribute.
     *
     * @param string|null $attribute
     * @return int
     */
    public static function sum($attribute = null);

    ///////////////////////////////////////////////////////////////////
    // Modification Methods

    /**
     * Save the model to the database.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @return bool
     */
    public function save();

    /**
     * Create a new model, save it in the database and return the instance.
     *
     * It returns FALSE if the operation was canceled by a hook.
     *
     * @param array $attributes
     * @return $this|false
     */
    public static function create(array $attributes = []);

    /**
     * Update the model in the database.
     *
     * It returns FALSE if the operation was canceled by a hook, otherwise TRUE.
     *
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes);

    /**
     * Delete the model from the database.
     *
     * It returns FALSE if the operation was canceled by a hook, otherwise TRUE.
     *
     * @return bool
     */
    public function delete();

    /**
     * Clone the model into a new, non-existing instance.
     *
     * Note, the `replicate` method does not save the new model into the database. Therefore, the new model does not
     * have a primary key yet.
     *
     * @param array $except Attributes that will be not copied.
     * @return static
     */
    public function replicate(array $except = []);

    ///////////////////////////////////////////////////////////////////
    // Mass Assignment Protection

//    /**
//     * Get the fillable attributes for the model.
//     *
//     * @return array
//     */
//    public function getFillable();

    /**
     * Get the guarded attributes for the model.
     *
     * @return array
     */
    public function getGuarded();

    /**
     * Throw a MassAssignmentException if one or more of the given attributes are protected for mass assignment.
     *
     * @param array $attributes Attributes (key/vlaue pairs).
     * @throws MassAssignmentException
     */
    public static function checkMassAssignment(array $attributes);

    ///////////////////////////////////////////////////////////////////
    // Relationships

    /**
     * Set the given entities into the relationship cache.
     *
     * @param string $name Name of the relationship method.
     * @param mixed $entities
     * @return $this
     */
    public function setRelationEntities($name, $entities);

    /**
     * Clear the relationship cache.
     *
     * @return $this
     */
    public function clearRelationCache();

    /**
     * Define a one-to-one relationship.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Avatar".
     * @param string|null $foreignKey Default: "&lt;local_model&gt;_id", e.g. "user_id".
     * @param string|null $localKey Default: "id", e.g. the primary key of App\Models\User.
     * @return HasOneRelation
     */
    public function hasOne($class, $foreignKey = null, $localKey = null);

    /**
     * Define a one-to-many relationship.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Books".
     * @param string|null $foreignKey Default: "&lt;local_model&gt;_id", e.g. "author_id".
     * @param string|null $localKey Default: "id", e.g. the primary key of App\Models\Author.
     * @return HasManyRelation
     */
    public function hasMany($class, $foreignKey = null, $localKey = null);

    /**
     * Define inverse one-to-one or inverse one-to-many relationships.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Author".
     * @param string|null $foreignKey Default: "&lt;foreign_model&gt;_id", e.g. "author_id".
     * @param string|null $otherKey Default: "id", e.g. the primary key of App\Models\Author.
     * @return BelongsToRelation
     */
    public function belongsTo($class, $foreignKey = null, $otherKey = null);

    /**
     * Define a many-to-many relationship.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Movie".
     * @param string|null $joinTable Name of the join table. Default: &lt;model1&gt;_&lt;model2&gt; (in alphabetical order of models), e.g. "genre_movie".
     * @param string|null $localForeignKey Default: "&lt;local_model&gt;_id", e.g. "genre_id".
     * @param string|null $otherForeignKey Default: "&lt;foreign_model&gt;_id", e.g. "movie_id".
     * @param string|null $localKey Default: "id", e.g. the primary key of App\Models\Genre.
     * @param string|null $otherKey Default: "id", e.g. the primary key of App\Models\Movie.
     * @return BelongsToManyRelation
     */
    public function belongsToMany($class, $joinTable = null, $localForeignKey = null, $otherForeignKey = null, $localKey = null, $otherKey = null);

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Picture".
     * @param string $prefix Prefix for the type attribute and foreign key, e.g. "imageable".
     * @param string|null $typeAttribute Default: "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string|null $foreignKey Default: "&lt;prefix&gt;_id", e.g. "imageable_id".
     * @return MorphOneRelation
     */
    public function morphOne($class, $prefix, $typeAttribute = null, $foreignKey = null);

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $class Name of the foreign model, e.g. "App\Models\Picture".
     * @param string $prefix Prefix for the type attribute and foreign key, e.g. "imageable".
     * @param string|null $typeAttribute Default: "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string|null $foreignKey Default: "&lt;prefix&gt;_id", e.g. "imageable_id".
     * @return MorphManyRelation
     */
    public function morphMany($class, $prefix, $typeAttribute = null, $foreignKey = null);

    /**
     * Define a polymorphic, inverse one-to-one or inverse one-to-many relationship.
     *
     * If the model has not stored a type, the method defines a relationship with itself.
     *
     * @param string $prefix Prefix for the type attribute and foreign key, e.g. "imageable".
     * @param string|null $typeAttribute Default: "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string|null $foreignKey Default: "&lt;prefix&gt;_id", e.g. "imageable_id".
     * @return MorphToRelation
     */
    public function morphTo($prefix, $typeAttribute = null, $foreignKey = null);
}
