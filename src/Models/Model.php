<?php

namespace Core\Models;

use Core\Exceptions\MassAssignmentException;
use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\DI;
use Core\Services\PDOs\Builders\Contracts\Builder;
use LogicException;

/**
 * The basic functionality (attribute handling) was inspired by Laravel's Model class called Eloquent ([MIT License](https://github.com/laravel/laravel/tree/5.3)).
 * The methods originalIsNumericallyEquivalent(), replicate(), getDirty() and isDirty() are adapted from Laravel's Model class.
 * The relationship methods based on CakePHP's ORM ([MIT License](https://cakephp.org/)).
 *
 * @see https://github.com/illuminate/database/blob/5.3/Eloquent/Model.php Laravel's Model Class 5.3 on GitHub
 * @see https://github.com/cakephp/orm/blob/3.2/Table.php CakePHP's Table Class 3.2 on GitHub
 */
class Model implements ModelContract
{
    /**
     * The name of the database store.
     *
     * If the store name is not set, the default store will be connected.
     *
     * @var string
     */
    protected $store;

    /**
     * The table name associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $key = 'id';

//    /**
//     * Indicates if the model should be timestamped.
//     *
//     * @var bool
//     */
//    public $timestamps = true;
//
//    /**
//     * The name of the "created at" column.
//     *
//     * @var string
//     */
//    const CREATED_AT = 'created_at';
//
//    /**
//     * The name of the "updated at" column.
//     *
//     * @var string
//     */
//    const UPDATED_AT = 'updated_at';
//
//    /**
//     * Indicates if the model should store the user who created the model and who updated it last.
//     *
//     * @var bool
//     */
//    protected $creatorAndUpdater = true;
//
//    /**
//     * The name of the "created by" column.
//     *
//     * @var string
//     */
//    const CREATED_BY = 'created_by';
//
//    /**
//     * The name of the "updated by" column.
//     *
//     * @var string
//     */
//    const UPDATED_BY = 'updated_by';

//    /**
//     * The attributes that are mass assignable.
//     *
//     * @var array
//     */
//    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected static $guarded = ['id', 'created_by', 'created_at', 'updated_by', 'updated_at'];

    /**
     * Searchable fields.
     *
     * @var array
     */
    protected $searchable = [];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Database Access Layer.
     *
     * @var Database
     */
    private $db;

    ///////////////////////////////////////////////////////////////////
    // Attribute Handling

    /**
     * Create a new Model instance.
     */
    public function __construct()
    {
        $this->original = $this->attributes;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->getAttribute($name) !== null;
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->attributes[$name], $this->relations[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->attributes)) { // || $this->hasGetMutator($key)) {
            return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
        }

        // Prevent the magic invocation of methods of this base class.
        if (method_exists(self::class, $name)) {
            return null;
        }

        // If the key already exists in the relationships array, it just means the relationship has already been loaded,
        // so we'll just return it out of here because there is no need to query within the relations twice.
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        // If the "attribute" exists as a method on the model, we will just assume it is a relationship and will load
        // and return results from the query and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, $name)) {
            $relation = $this->$name();
            if (!$relation instanceof Relation) {
                throw new LogicException('Relationship method "' . $name . '" must return an object of type Core\Models\Contracts\Relation.');
            }
            $result = $relation->get();
            $this->setRelationEntities($name, $result);
            return $result;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOriginal($name = null) //, $default = null)
    {
        if ($name === null) {
            return $this->original;
        }

        return $this->original[$name];
    }

    /**
     * @inheritdoc
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $name => $value) {
            if (!array_key_exists($name, $this->original) || ($value !== $this->original[$name] && !$this->originalIsNumericallyEquivalent($name))) {
                $dirty[$name] = $value;
            }
        }

        return $dirty;
    }

    /**
     * @inheritdoc
     */
    public function isDirty($names = null)
    {
        if ($names === null) {
            $names = array_keys($this->attributes);
        }

        foreach ((array)$names as $name) {
            if (!array_key_exists($name, $this->original) || ($this->attributes[$name] !== $this->original[$name] && !$this->originalIsNumericallyEquivalent($name))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function reload()
    {
        if (isset($this->original[$this->key])) {
            $id = $this->original[$this->key];
            $this->attributes = $this->database()->table($this->getTable())->find($id, $this->key);
        }
        else {
            $this->attributes = [];
        }
        $this->original = $this->attributes;
        $this->relations = [];

        return $this;
    }


    /**
     * @inheritdoc
     */
    public function sync()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Determine if the new and old attribute values are numerically equivalent.
     *
     * @param string $name Name of the attribute
     * @return bool
     */
    private function originalIsNumericallyEquivalent($name)
    {
        $current  = $this->attributes[$name];
        $original = $this->original[$name];

        return is_numeric($current) && is_numeric($original) && strcmp((string)$current, (string)$original) === 0;
    }

    ///////////////////////////////////////////////////////////////////
    // Database Table Access

    /**
     * @inheritdoc
     */
    public function database()
    {
        if ($this->db === null) {
            $this->db = DI::getInstance()->get('database-factory')->store($this->store);
        }

        return $this->db;
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        if (!isset($this->table)) {
            $this->table = snake_case(plural($this->getBaseClass()));
        }

        return $this->table;
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey()
    {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return isset($this->original[$this->key]) ? $this->original[$this->key] : null;
    }

    ///////////////////////////////////////////////////////////////////
    // Gets a Query Builder

    /**
     * Create a new QueryBuilder instance.
     *
     * @return Builder
     */
    protected function createBuilder()
    {
        return $this->database()->table($this->getTable())->asClass(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function builder()
    {
        return (new static)->createBuilder();
    }

    /**
     * @inheritdoc
     */
    public static function with($method)
    {
        return static::builder()->with($method);
    }

    /**
     * @inheritdoc
     */
    public static function select($columns, array $bindings = [])
    {
        return static::builder()->select($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function distinct()
    {
        return static::builder()->distinct();
    }

    /**
     * @inheritdoc
     */
    public static function from($source, $alias = null, array $bindings = [])
    {
        return static::builder()->from($source, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function join($source, $on, $alias = null, array $bindings = [])
    {
        return static::builder()->join($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function leftJoin($source, $on, $alias = null, array $bindings = [])
    {
        return static::builder()->leftJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function rightJoin($source, $on, $alias = null, array $bindings = [])
    {
        return static::builder()->rightJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function where($column, $value, $operator = '=')
    {
        return static::builder()->where($column, $value, $operator);
    }

    /**
     * @inheritdoc
     */
    public static function whereCondition($condition, array $bindings = [])
    {
        return static::builder()->whereCondition($condition, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function whereSubQuery($column, $query, $operator = '=', array $bindings = [])
    {
        return static::builder()->whereSubQuery($column, $query, $operator, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function whereExists($query, array $bindings = [])
    {
        return static::builder()->whereExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function whereNotExists($query, array $bindings = [])
    {
        return static::builder()->whereNotExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function whereIn($column, array $values)
    {
        return static::builder()->whereIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public static function whereNotIn($column, array $values)
    {
        return static::builder()->whereNotIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public static function whereBetween($column, $lowest, $highest)
    {
        return static::builder()->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public static function whereNotBetween($column, $lowest, $highest)
    {
        return static::builder()->whereNotBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public static function whereNull($column)
    {
        return static::builder()->whereNull($column);
    }

    /**
     * @inheritdoc
     */
    public static function whereNotNull($column)
    {
        return static::builder()->whereNotNull($column);
    }

    /**
     * @inheritdoc
     */
    public static function orderBy($columns, array $bindings = [])
    {
        return static::builder()->orderBy($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function limit($limit)
    {
        return static::builder()->limit($limit);
    }

    /**
     * @inheritdoc
     */
    public static function offset($offset)
    {
        return static::builder()->offset($offset);
    }

    /**
     * @inheritdoc
     */
    public static function find($id, $key = 'id')
    {
        return static::builder()->find($id, $key);
    }

    /**
     * @inheritdoc
     */
    public static function all()
    {
        return static::builder()->all();
    }

    /**
     * @inheritdoc
     */
    public static function cursor()
    {
        return static::builder()->cursor();
    }

    /**
     * @inheritdoc
     */
    public static function first()
    {
        return static::builder()->first();
    }

    /**
     * @inheritdoc
     */
    public static function count()
    {
        return static::builder()->count();
    }

    /**
     * @inheritdoc
     */
    public static function max($attribute = null)
    {
        return static::builder()->max($attribute);
    }

    /**
     * @inheritdoc
     */
    public static function min($attribute = null)
    {
        return static::builder()->min($attribute);
    }

    /**
     * @inheritdoc
     */
    public static function avg($attribute = null)
    {
        return static::builder()->avg($attribute);
    }

    /**
     * @inheritdoc
     */
    public static function sum($attribute = null)
    {
        return static::builder()->sum($attribute);
    }

    ///////////////////////////////////////////////////////////////////
    // Modification Methods

    /**
     * @inheritdoc
     */
    public function save()
    {
        if (isset($this->original[$this->key])) {

            // Update...

            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true;
            }

            $id = $this->original[$this->key];
            $builder = $this->builder()->disableHooks()->where($this->key, $id);

            // Invoke the "before" hook if exists.
            $hook = 'beforeUpdate';
            if (method_exists($this, $hook)) {
                if ($this->$hook() === false) {
                    return false;
                }
                $dirty = $this->getDirty();
                if (empty($dirty)) {
                    return true;
                }
            }

            // Execute the database operation and invoke the "after" hook if exist.
            $hook = 'afterUpdate';
            if (method_exists($this, $hook)) {
                // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                // the hook returns FALSE or the hook throw an exception.
                return $this->database()->transaction(function (Database $db) use ($dirty, $builder, $hook) {

                    // Execute the database operation.
                    $builder->update($dirty);

                    // Invoke the "after" hook.
                    if ($this->$hook() === false) {
                        $db->rollback();
                        return false;
                    }

                    $this->original = $this->attributes;

                    return true;
                });
            }
            else {
                // A "after" hook does not exist, so we don't need a transaction.
                $builder->update($dirty);
                $this->original = $this->attributes;

                return true;
            }
        }
        else {
            // Insert...

            $builder = $this->builder()->disableHooks();

            // Invoke the "before" hook if exists.
            $hook = 'beforeInsert';
            if (method_exists($this, $hook)) {
                if ($this->$hook() === false) {
                    return false;
                }
            }

            // Execute the database operation and invoke the "after" hook if exist.
            $hook = 'afterInsert';
            if (method_exists($this, $hook)) {
                // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
                // the hook returns FALSE or the hook throw an exception.
                return $this->database()->transaction(function (Database $db) use ($builder, $hook) {

                    // Execute the database operation.
                    $this->attributes[$this->key] = $builder->insert($this->attributes);

                    // Invoke the "after" hook.
                    if ($this->$hook() === false) {
                        $db->rollback();
                        unset($this->attributes[$this->key]);
                        return false;
                    }

                    $this->original = $this->attributes;

                    return true;
                });
            }
            else {
                // A "after" hook does not exist, so we don't need a transaction.
                $this->attributes[$this->key] = $builder->insert($this->attributes);
                $this->original = $this->attributes;

                return true;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function create(array $attributes = [])
    {
        $model = new static;
        $model->attributes = $attributes;

        return $model->save() !== false ? $model : false;
    }

    /**
     * @inheritdoc
     */
    public function update(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $id = $this->original[$this->key];
        $builder = $this->builder()->disableHooks()->where($this->key, $id);

        // Invoke the "before" hook if exists.
        $hook = 'beforeDelete';
        if (method_exists($this, $hook)) {
            if ($this->$hook() === false) {
                return false;
            }
        }

        // Execute the database operation and invoke the "after" hook if exist.
        $hook = 'afterDelete';
        if (method_exists($this, $hook)) {
            // A "after" hook exist. We will open a transaction, so we are able to rollback the database operation if
            // the hook returns FALSE or the hook throw an exception.
            return $this->database()->transaction(function (Database $db) use ($builder, $hook) {

                // Execute the database operation.
                $builder->delete();
                unset($this->attributes[$this->key]);

                // Invoke the "after" hook.
                if ($this->$hook() === false) {
                    $db->rollback();
                    $this->attributes[$this->key] = $this->original[$this->key];
                    return false;
                }

                $this->original = [];

                return true;
            });
        }
        else {
            // A "after" hook does not exist, so we don't need a transaction.
            $builder->delete();
            unset($this->attributes[$this->key]);
            $this->original = [];

            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function replicate(array $except = [])
    {
        /** @var Model $instance */
        $instance = new static;

        $instance->attributes = $this->attributes;
        foreach ($except as $name) {
            unset($instance->attributes[$name]);
        }
        unset($instance->attributes[$this->key]);
//        unset($instance->attributes[$this->getCreatedAtColumn()]);
//        unset($instance->attributes[$this->getUpdatedAtColumn()]);

        return $instance;
    }

    ///////////////////////////////////////////////////////////////////
    // Mass Assignment Protection

//    /**
//     * @inheritdoc
//     */
//    public function getFillable()
//    {
//        $attributes = $this->attributes;
//        foreach ($this->guarded as $name) {
//            unset($attributes[$name]);
//        }
//
//        return $attributes;
//    }

    /**
     * @inheritdoc
     */
    public function getGuarded()
    {
        return static::$guarded;
    }

    /**
     * @inheritdoc
     */
    public static function checkMassAssignment(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if (in_array($name, static::$guarded)) {
                throw new MassAssignmentException($name);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////
    // Relationships

    /**
     * @inheritdoc
     */
    public function setRelationEntities($name, $entities)
    {
        $this->relations[$name] = $entities;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearRelationCache()
    {
        $this->relations = [];

        return $this;
    }

    /**
     * Get the basename of class.
     *
     * @param string|null $class
     * @return string
     */
    private function getBaseClass($class = null)
    {
        return basename(str_replace('\\', '/', $class ?: static::class));
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    private function getForeignKey()
    {
        return snake_case($this->getBaseClass()) . '_' . $this->key;
    }

    /**
     * Get the join table name for a many-to-many relation.
     *
     * @param string $class
     * @return string
     */
    private function getJoinTable($class)
    {
        $model1 = snake_case($this->getBaseClass());
        $model2 = snake_case($this->getBaseClass($class));
        $models = [$model1, $model2];
        sort($models);

        return implode('_', $models);
    }

    /**
     * @inheritdoc
     */
    public function hasOne($class, $foreignKey = null, $localKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($foreignKey === null) {
            $foreignKey = $this->getForeignKey();
        }

        if ($localKey === null) {
            $localKey = $this->key;
        }

        return new HasOneRelation($this, $model->builder(), $foreignKey, $localKey);
    }

    /**
     * @inheritdoc
     */
    public function hasMany($class, $foreignKey = null, $localKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($foreignKey === null) {
            $foreignKey = $this->getForeignKey();
        }

        if ($localKey === null) {
            $localKey = $this->key;
        }

        return new HasManyRelation($this, $model->builder(), $foreignKey, $localKey);
    }

    /**
     * @inheritdoc
     */
    public function belongsTo($class, $foreignKey = null, $otherKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($foreignKey === null) {
            $foreignKey = $model->getForeignKey();
        }

        if ($otherKey === null) {
            $otherKey = $model->key;
        }

        return new BelongsToRelation($this, $model->builder(), $foreignKey, $otherKey);
    }

    /**
     * @inheritdoc
     */
    public function belongsToMany($class, $joinTable = null, $localForeignKey = null, $otherForeignKey = null, $localKey = null, $otherKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($joinTable === null) {
            $joinTable = $this->getJoinTable($class);
        }

        if ($localForeignKey === null) {
            $localForeignKey = $this->getForeignKey();
        }

        if ($otherForeignKey === null) {
            $otherForeignKey = $model->getForeignKey();
        }

        if ($localKey === null) {
            $localKey = $this->key;
        }

        if ($otherKey === null) {
            $otherKey = $model->key;
        }

        return new BelongsToManyRelation($this, $model->builder(), $joinTable, $localForeignKey, $otherForeignKey, $localKey, $otherKey);
    }

    /**
     * @inheritdoc
     */
    public function morphOne($class, $prefix, $typeAttribute = null, $foreignKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($typeAttribute === null) {
            $typeAttribute = $prefix . '_type';
        }

        if ($foreignKey === null) {
            $foreignKey = $prefix . '_id';
        }

        return new MorphOneRelation($this, $model->builder(), $typeAttribute, $foreignKey);
    }

    /**
     * @inheritdoc
     */
    public function morphMany($class, $prefix, $typeAttribute = null, $foreignKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($typeAttribute === null) {
            $typeAttribute = $prefix . '_type';
        }

        if ($foreignKey === null) {
            $foreignKey = $prefix . '_id';
        }

        return new MorphManyRelation($this, $model->builder(), $typeAttribute, $foreignKey);
    }

    /**
     * @inheritdoc
     */
    public function morphTo($prefix, $typeAttribute = null, $foreignKey = null)
    {
        if ($typeAttribute === null) {
            $typeAttribute = $prefix . '_type';
        }

        if ($foreignKey === null) {
            $foreignKey = $prefix . '_id';
        }

        /** @var Model $model */
        $class = isset($this->attributes[$typeAttribute]) ? $this->attributes[$typeAttribute] : static::class;
        $model = new $class;

        $otherKey = $model->getPrimaryKey();

        return new MorphToRelation($this, $model->builder(), $typeAttribute, $foreignKey, $otherKey);
    }

    ///////////////////////////////////////////////////////////////////
    // Accessors and Mutators

//    /**
//     * Get the user's first name.
//     *
//     * @return string
//     */
//    public function getFirstNameAttribute()
//    {
//        return ucfirst($this->attributes['firstname']);
//    }
//
//    /**
//     * Set the user's first name.
//     *
//     * @param  string  $value
//     */
//    public function setFirstNameAttribute($value)
//    {
//        $this->attributes['firstname'] = strtolower($value);
//    }

    ///////////////////////////////////////////////////////////////////
    // Arrayable, Jsonable and JsonSerializable Implementation

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributes, $this->relations);
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    ///////////////////////////////////////////////////////////////////
    // ArrayAccess Implementation

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
