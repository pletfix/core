<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;

class BelongsToManyRelation extends Relation
{
    /**
     * Name of the join table (typical &lt;model1&gt;_&lt;model2&gt;)
     *
     * @var string
     */
    protected $joinTable;

    /**
     * Local foreign key (typical "&lt;local_model&gt;_id").
     *
     * @var string
     */
    protected $localForeignKey;

    /**
     * Other foreign key (typical "&lt;foreign_model&gt;_id").
     *
     * @var string
     */
    protected $otherForeignKey;

    /**
     * Local key (typical "id").
     *
     * @var string
     */
    protected $localKey;

    /**
     * Other key (typical "id").
     *
     * @var string
     */
    protected $otherKey;

    /**
     * Hash with eager loaded IDs.
     *
     * @var array
     */
    protected $eagerHash = [];

    /**
     * Create a new Relation instance.
     *
     * @param ModelContract $model The local model, e.g. App\Models\Genre.
     * @param Builder $builder A Builder to get the foreign entities, e.g. movies.
     * @param string $joinTable Name of the join table. Typical &lt;model1&gt;_&lt;model2&gt; (in alphabetical order of models), e.g. "genre_movie".
     * @param string $localForeignKey Typical "&lt;local_model&gt;_id", e.g. "genre_id".
     * @param string $otherForeignKey Typical "&lt;foreign_model&gt;_id", e.g. "movie_id".
     * @param string $localKey Typical "id", e.g. the primary key of App\Models\Genre.
     * @param string $otherKey Typical "id", e.g. the primary key of App\Models\Movie.
     */
    public function __construct(ModelContract $model, Builder $builder, $joinTable, $localForeignKey, $otherForeignKey, $localKey, $otherKey)
    {
        $this->joinTable       = $joinTable;
        $this->localForeignKey = $localForeignKey;
        $this->otherForeignKey = $otherForeignKey;
        $this->localKey        = $localKey;
        $this->otherKey        = $otherKey;

        parent::__construct($model, $builder);
    }

    /**
     * @inheritdoc
     */
    protected function addConstraints()
    {
        $id = $this->model->getAttribute($this->localKey);
        $otherTable = $this->builder()->getTable();

        $this->builder
            ->select([$otherTable . '.*'])
            ->join($this->joinTable, $otherTable . '.' . $this->otherKey . ' = ' . $this->joinTable . '.' .$this->otherForeignKey)
            ->where($this->joinTable . '.' . $this->localForeignKey, $id);
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $entities)
    {
        // SELECT `departments`.*,
        // `department_employee`.`employee_id` AS `___id`
        // FROM `departments`
        // INNER JOIN `department_employee` ON $table.`id` = `department_employee`.`department_id`
        // WHERE `department_employee`.`employee_id` = ?

        $this->eagerHash = [];
        foreach ($entities as $entity) {
            $this->eagerHash[$entity->getId()] = $entity->getAttribute($this->localKey);
        }

        $otherTable = $this->builder()->getTable();

        return $this->builder
            ->select([$otherTable . '.*', '___id' => $this->joinTable . '.' . $this->localForeignKey])
            ->join($this->joinTable, $otherTable . '.' . $this->otherKey . ' = ' . $this->joinTable . '.' .$this->otherForeignKey)
            ->whereIn($this->joinTable . '.' . $this->localForeignKey, array_values($this->eagerHash));
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->builder->all();
    }

    /**
     * @inheritdoc
     */
    public function getEager()
    {
        // get the foreign entities, group by "local key"
        $foreignEntities = [];
        foreach ($this->builder->cursor() as $foreignEntity) {
            /** @var ModelContract $foreignEntity */
            $localId = $foreignEntity->getAttribute('___id');
            unset($foreignEntity->___id);
            if (!isset($foreignEntities[$localId])) {
                $foreignEntities[$localId] = [];
            }
            $foreignEntities[$localId][] = $foreignEntity;
        }

        // group the foreign entities by local primary identity
        $result = [];
        foreach ($this->eagerHash as $id => $localId) {
            if (isset($foreignEntities[$localId])) {
                $result[$id] = $foreignEntities[$localId];
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function associate(ModelContract $model)
    {
        $localId = $this->model->getAttribute($this->localKey);
        $foreignId = $model->getAttribute($this->otherKey);

        $this->model->database()->table($this->joinTable)->insert([
            $this->localForeignKey => $localId,
            $this->otherForeignKey => $foreignId,
        ]);

        $this->model->clearRelationCache();
        $model->clearRelationCache();

        return true;
    }

        /**
     * @inheritdoc
     */
    public function disassociate(ModelContract $model = null)
    {
        $localId = $this->model->getAttribute($this->localKey);

        if ($model === null) {
            $this->model->database()->table($this->joinTable)
                ->where($this->localForeignKey, $localId)
                ->delete();
            $this->model->clearRelationCache();
            return true;
        }

        $foreignId = $model->getAttribute($this->otherKey);

        $this->model->database()->table($this->joinTable)
            ->where($this->localForeignKey, $localId)
            ->where($this->otherForeignKey, $foreignId)
            ->delete();

        $this->model->clearRelationCache();
        $model->clearRelationCache();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function create(array $attributes = [])
    {
        return $this->model->database()->transaction(function(Database $db) use($attributes) {
            /** @var ModelContract $class */
            $class = $this->builder->getClass();
            $model = $class::create($attributes);
            if ($model === false) {
                return false;
            }

            $this->associate($model);

            return $model;
        });
    }

    /**
     * @inheritdoc
     */
    public function delete(ModelContract $model)
    {
        return $this->model->database()->transaction(function(Database $db) use($model) {
            $foreignId = $model->getAttribute($this->otherKey);
            if (!$model->delete()) {
                return false;
            }

            $model->clearRelationCache();
            $this->model->clearRelationCache();

            $db->table($this->joinTable)
                ->where($this->otherForeignKey, $foreignId)
                ->delete();

            return true;
        });
    }
}