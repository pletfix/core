<?php

namespace Core\Models;

use Core\Exceptions\MassAssignmentException;
use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\DI;

/**
 * The basic functionality (attribute handling) was inspired by Laravel's Active Record called Eloquent.
 * The methods originalIsNumericallyEquivalent(), getTable(), replicate(), getDirty() and isDirty() are adapted from Laravel's Model class.
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
     * Database Access Layer.
     *
     * @var Database
     */
    private $db;

    ////////////////////////////////////////////////////////////////////
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
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get the model's original attribute values.
     *
     * @param string|null $name
     * @_param mixed $default
     * @return mixed|array
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
     * Get the attributes that have been changed since last save.
     *
     * @return array
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
     * Determine if the model or given attribute(s) have been modified.
     *
     * If you omit the argument, all attributes are checked.
     *
     * @param  array|string|null $attributes
     * @return bool
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

    ////////////////////////////////////////////////////////////////////
    // CRUD

    /**
     * Get the database.
     *
     * @return Database
     */
    public function database()
    {
        if ($this->db === null) { // todo evtl statisch?
            $this->db = DI::getInstance()->get('database-factory')->store($this->store);
        }

        return $this->db;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (!isset($this->table)) {
//            $baseClass = basename(str_replace('\\', '/', static::class));
//            $this->table = snake_case(plural($baseClass));
            $this->table = snake_case(plural($this->getBaseClass()));
        }

        return $this->table;
    }

    /**
     * Get the name of the primary key for the model's table.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->key;
    }

    /**
     * Find the model by id.
     *
     * @param int $id
     * @return static
     */
    public static function find($id)
    {
        /** @var Model $model */
        $model = new static;
        $model->attributes = $model->database()->find($model->getTable(), $id, $model->key);
        $model->original = $model->attributes;

        return $model;

        // todo mit static properties ist das eleganter...
        //return static::database()->find(static::getTable(), $id, static::$key, static::class);
    }

    /**
     * Save the model to the database.
     *
     * @return $this
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
//            $this->database()->update($this->getTable(), $dirty, [$this->key => $id]);
            $db->updateWhere($this->getTable(), $dirty, $db->quoteName($this->key) . ' = ?', [$id]);
        }
        else {
            $db->insert($this->table, $dirty);
            $this->attributes[$this->key] = $db->lastInsertId();
        }

        $this->original = $this->attributes;

        return $this;
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @return static
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

//        $db = $model->database();
//        $db->insert($model->getTable(), $attributes);
//        $model->attributes['id'] = $db->lastInsertId();
//        $model->original = $model->attributes;
//
//        return $model;
    }

    /**
     * Update the model in the database.
     *
     * @param array $attributes
     * @return $this
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

//        $this->database()->update($this->getTable(), $attributes, ['id' => $this->attributes['id']]);
//        $this->original = array_merge($this->original, $attributes);
//
//        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * @return $this
     */
    public function delete()
    {
        $db = $this->database();
        $id = $this->original[$this->key];
        //$db->delete($this->getTable(), [$this->key => $id]);
        $db->deleteWhere($this->getTable(), $db->quoteName($this->key) . ' = ?', [$id]);
        unset($this->attributes[$this->key]); // todo oder null setzen?
        $this->original = [];

        return $this;
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param array $except Attributes that will be not copied.
     * @return $this
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

    ////////////////////////////////////////////////////////////////////
    // Mass Assignment Protection

//    /**
//     * Get the fillable attributes for the model.
//     *
//     * @return array
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
     * Get the guarded attributes for the model.
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param string|array $attribute Name of the attribute/attributes
     * @return bool
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

    ////////////////////////////////////////////////////////////////////
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
        //return snake_case(basename(str_replace('\\', '/', static::class))) . '_' . $this->key;
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
//        $model1 = snake_case(basename(str_replace('\\', '/', static::class)));
//        $model2 = snake_case(basename(str_replace('\\', '/', $class)));
        $model1 = snake_case($this->getBaseClass());
        $model2 = snake_case($this->getBaseClass($class));
        $models = [$model1, $model2];
        sort($models);

        return implode('_', $models);
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $class Name of the related model.
     * @param string $foreignKey Default: "&lt;this_model&gt;_id"
     * @param string $localKey Default: "id" (the primary key of this model)
     * @return Model  // todo Contract verwenden
     */
    public function hasOne($class, $foreignKey = null, $localKey = null)
    {
        /** @var Model $model */
        $model      = new $class;
        $db         = $model->database();
        $table      = $db->quoteName($model->getTable());
        $foreignKey = $db->quoteName($foreignKey ?: $this->getForeignKey());

        if ($localKey === null) {
            $localKey = $this->key;
        }

        /** @noinspection SqlDialectInspection */
        return $db->single("SELECT * FROM $table WHERE $foreignKey = ?", [$this->attributes[$localKey]], $class);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $class Name of the related model.
     * @param string $foreignKey Default: "&lt;this_model&gt;_id"
     * @param string $localKey Default: "id" (the primary key of this model)
     * @return Model[] // todo Collection und Contract verwenden
     */
    public function hasMany($class, $foreignKey = null, $localKey = null)
    {
        /** @var Model $model */
        $model      = new $class;
        $db         = $model->database();
        $table      = $db->quoteName($model->getTable());
        $foreignKey = $db->quoteName($foreignKey ?: $this->getForeignKey());

        if ($localKey === null) {
            $localKey = $this->key;
        }

        /** @noinspection SqlDialectInspection */
        return $db->query("SELECT * FROM $table WHERE $foreignKey = ?", [$this->attributes[$localKey]], $class);
    }

    /**
     * Define inverse one-to-one or inverse one-to-many relationships.
     *
     * @param string $class Name of the related model.
     * @param string $foreignKey Default: "&lt;related_model&gt;_id"
     * @param string $otherKey Default: "id" (the primary key of the related model)
     * @return Model  // todo Contract verwenden
     */
    public function belongsTo($class, $foreignKey = null, $otherKey = null)
    {
        /** @var Model $model */
        $model      = new $class;
        $db         = $model->database();
        $table      = $db->quoteName($model->getTable());
        $otherKey   = $db->quoteName($otherKey ?: $model->key);

        if ($foreignKey === null) {
            $foreignKey = $model->getForeignKey();
        }

        /** @noinspection SqlDialectInspection */
        return $db->single("SELECT * FROM $table WHERE $otherKey = ?", [$this->attributes[$foreignKey]], $class);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string $class Name of the related model.
     * @param string $joiningTable Name of the joining table. Default: <model1>_<model2> (in alphabetical order of models)
     * @param string $localForeignKey Default: "&lt;this_model&gt;_id"
     * @param string $otherForeignKey Default: "&lt;related_model&gt;_id"
     * @param string $localKey Default: "id" (the primary key of this model)
     * @param string $otherKey Default: "id" (the primary key of the related model)
     * @return Model[] // todo Collection und Contract verwenden
     */
    public function belongsToMany($class, $joiningTable = null, $localForeignKey = null, $otherForeignKey = null, $localKey = null, $otherKey = null)
    {
        /** @var Model $model */
        $model           = new $class;
        $db              = $model->database();
        $table           = $db->quoteName($model->getTable());
        $joiningTable    = $db->quoteName($joiningTable    ?: $this->getJoiningTable($class));
        $localForeignKey = $db->quoteName($localForeignKey ?: $this->getForeignKey());
        $otherForeignKey = $db->quoteName($otherForeignKey ?: $model->getForeignKey());
        $otherKey        = $db->quoteName($otherKey        ?: $model->key);

        if ($localKey === null) {
            $localKey = $this->key;
        }

        /** @noinspection SqlDialectInspection */
        return $db->query("
            SELECT * 
            FROM $table 
            WHERE $otherKey IN (
              SELECT $otherForeignKey 
              FROM $joiningTable 
              WHERE $localForeignKey = ?
            )
            ", [$this->attributes[$localKey]], $class); // todo prüfen, ob es performanter geht (per join)
    }

    ////////////////////////////////////////////////////////////////////
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
