<?php

namespace Core\Models;

use Core\Exceptions\MassAssignmentException;
use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\DI;
use Core\Services\PDOs\Builder\Contracts\Builder;

/**
 * The basic functionality (attribute handling) was inspired by Laravel's Active Record called Eloquent.
 * The methods originalIsNumericallyEquivalent(), replicate(), getDirty() and isDirty() are adapted from Laravel's Model class.
 * The relationship methods based on CakePHP's ORM.
 *
 * @see https://github.com/illuminate/database/blob/5.3/Eloquent/Model.php Laravel's Model Class 5.3 on GitHub
 * @see https://github.com/cakephp/orm/blob/3.2/Table.php CakePHP's Table Class 3.2 on GitHub
 */
class Model implements ModelContract
{
    // todo
    // attribute cast
    // events
    // relationship
    // timestamps
    // setXYZAttribute, getXYZAttribute
    // Validation Rules

    /**
     * The name of the database store.
     *
     * If the store name is not set, the default store will be connected.
     *
     * @var string
     */
    protected $store;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table; // todo static besser?

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $key = 'id'; // todo: testen, ob es bzgl Speicherverbrauch etwas bringt, wenn das statisch ist
                            // oder als const KEY_FIELD = 'id'

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
    protected $guarded = ['id', 'created_by', 'created_at', 'updated_by', 'updated_at'];

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
    protected $relations;

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
        return $this->attributes[$name];
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->attributes[$name]);
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
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
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
//
//        return isset($this->original[$name]) ? $this->original[$name] : $default;
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
    public function isDirty($attributes = null)
    {
        if ($attributes === null) {
            $attributes &= $this->attributes;
        }

        foreach ((array)$attributes as $name => $value) {
            if (!array_key_exists($name, $this->original) || ($value !== $this->original[$name] && !$this->originalIsNumericallyEquivalent($name))) {
                return true;
            }
        }

        return false;
    }

//    /**
//     * Determine if the model or given attribute(s) have remained the same.
//     *
//     * @param array|string|null $attributes
//     * @return bool
//     */
//    public function isClean($attributes = null)
//    {
//        return !$this->isDirty($attributes);
//    }

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
        if ($this->db === null) { // todo evtl statisch?
            $this->db = DI::getInstance()->get('database-factory')->store($this->store);
        }

        return $this->db;
    }

    /**
     * @inheritdoc
     */
    public function getTable() // todo evtl umbennene: "table()"
    {
        if (!isset($this->table)) {
            $this->table = snake_case(plural($this->getBaseClass()));
        }

        return $this->table;
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey() // todo evtl umbennene: "primaryKey()"
    {
        return $this->key;
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
    public static function builder() // todo evtl umbenennen in query()
    {
        /** @var Model $model */
        $model = new static;

        return $model->database()->table($model->getTable())->asClass(static::class);
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
    public static function where($condition, array $bindings = [])
    {
        return static::builder()->where($condition, $bindings);
    }

    /**
     * @inheritdoc
     */
    public static function whereIs($column, $value, $operator = '=')
    {
        return static::builder()->whereIs($column, $value, $operator);
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
        return static::builder()->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public static function whereIsNull($column)
    {
        return static::builder()->whereIsNull($column);
    }

    /**
     * @inheritdoc
     */
    public static function whereIsNotNull($column)
    {
        return static::builder()->whereIsNotNull($column);
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

    ///////////////////////////////////////////////////////////////////
    // Modification Methods

    /**
     * @inheritdoc
     */
    public function save()
    {
        $dirty = $this->getDirty();

        $db = $this->database();
        if (isset($this->original[$this->key])) {
            if (empty($dirty)) {
                return $this;
            }
            $id = $this->original[$this->key];
            $this->builder()->whereIs($this->key, $id)->update($dirty);
        }
        else {
            $this->builder()->insert($dirty);
            $this->attributes[$this->key] = $db->lastInsertId();
        }

        $this->original = $this->attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function create(array $attributes = [])
    {
        /** @var Model $model */
        $model = new static;

        // is fillable? // todo wenn property guarded static wäre, könnte dass als allererstes erfolgen
        foreach ($attributes as $name => $value) {
            if (in_array($name, $model->guarded)) {
                throw new MassAssignmentException($name);
            }
        }

        $model->attributes = $attributes;

        return $model->save(); // todo performace testen, getDirty() wird in save aufgerufen, ist nicht notwendig.
    }

    /**
     * @inheritdoc
     */
    public function update(array $attributes = [])
    {
        // is fillable?
        foreach ($attributes as $name => $value) {
            if (in_array($name, $this->guarded)) {
                throw new MassAssignmentException($name);
            }
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $db = $this->database();
        $id = $this->original[$this->key];
        $this->builder()->whereIs($this->key, $id)->delete();
        unset($this->attributes[$this->key]); // todo oder null setzen?
        $this->original = [];

        return $this;
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
//        unset($instance->attributes[$this->getCreatedAtColumn()]);  // todo
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
        return $this->guarded;
    }

    /**
     * @inheritdoc
     */
    public function isFillable($attribute)
    {
        foreach ((array)$attribute as $name) {
            if (in_array($name, $this->guarded)) {
                false;
            }
        }

        return true;
    }

    ///////////////////////////////////////////////////////////////////
    // Relationships

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
     * Get the joining table name for a many-to-many relation.
     *
     * @param string $class
     * @return string
     */
    private function getJoiningTable($class)
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
    public function belongsToMany($class, $joiningTable = null, $localForeignKey = null, $otherForeignKey = null, $localKey = null, $otherKey = null)
    {
        /** @var Model $model */
        $model = new $class;

        if ($joiningTable === null) {
            $joiningTable = $this->getJoiningTable($class);
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

        return new BelongsToManyRelation($this, $model->builder(), $joiningTable, $localForeignKey, $otherForeignKey, $localKey, $otherKey);
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
        $class = $this->attributes[$typeAttribute ?: $prefix . '_type'];

        /** @var Model $model */
        $model = new $class;

        if ($foreignKey === null) {
            $foreignKey = $prefix . '_id';
        }

        $otherKey = $model->getPrimaryKey();

        return new BelongsToRelation($this, $model->builder(), $foreignKey, $otherKey);
    }

    ///////////////////////////////////////////////////////////////////
    // Validation Rules

    // todo

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
    // Events

    // todo

    ///////////////////////////////////////////////////////////////////
    // Arrayable, Countable and Jsonable Implementation

//    /**
//     * Convert the model to its string representation.
//     *
//     * @return string
//     */
//    public function __toString()
//    {
//        return $this->toJson();
//    }
//
//    /**
//     * Convert the model instance to an array.
//     *
//     * @return array
//     */
//    public function toArray()
//    {
//        $attributes = $this->attributesToArray();
//
//        return array_merge($attributes, $this->relationsToArray());
//    }
//
//    /**
//     * Count the number of entities.
//     *
//     * @return int
//     */
//    public function count()
//    {
//        return count($this->items); //todo
//    }
//
//    /**
//     * Convert the model instance to JSON.
//     *
//     * @param  int  $options
//     * @return string
//     */
//    public function toJson($options = 0)
//    {
//        return json_encode($this->jsonSerialize(), $options);
//    }
//
//    /**
//     * ----------------------------------------------------------------
//     * ArrayAccess Implementation
//     * ----------------------------------------------------------------
//     */
//
//    /**
//     * Determine if the given attribute exists.
//     *
//     * @param  mixed  $offset
//     * @return bool
//     */
//    public function offsetExists($offset)
//    {
//        return isset($this->$offset);
//    }
//
//    /**
//     * Get the value for a given offset.
//     *
//     * @param  mixed  $offset
//     * @return mixed
//     */
//    public function offsetGet($offset)
//    {
//        return $this->$offset;
//    }
//
//    /**
//     * Set the value for a given offset.
//     *
//     * @param  mixed  $offset
//     * @param  mixed  $value
//     * @return void
//     */
//    public function offsetSet($offset, $value)
//    {
//        $this->$offset = $value;
//    }
//
//    /**
//     * Unset the value for a given offset.
//     *
//     * @param  mixed  $offset
//     * @return void
//     */
//    public function offsetUnset($offset)
//    {
//        unset($this->$offset);
//    }
//
//    /**
//     * ----------------------------------------------------------------
//     * IteratorAggregate Implementation
//     * ----------------------------------------------------------------
//     */
//
//    /**
//     * Get an iterator for the items.
//     *
//     * @return \ArrayIterator
//     */
//    public function getIterator()
//    {
//        return new ArrayIterator([]); // todo
//    }
//
//    /**
//     * ----------------------------------------------------------------
//     *  JsonSerializable Implementation
//     * ----------------------------------------------------------------
//     */
//
//    /**
//     * Convert the object into something JSON serializable.
//     *
//     * @return array
//     */
//    public function jsonSerialize()
//    {
//        return $this->toArray();
//    }

}
