<?php

namespace Core\Models;

use Core\Services\PDOs\Builder\Contracts\Builder;

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
     * @param Model $model The local model, e.g. App\Models\Genre.
     * @param Builder $builder A Builder to get the foreign entities, e.g. movies.
     * @param string $joinTable Name of the join table. Typical &lt;model1&gt;_&lt;model2&gt; (in alphabetical order of models), e.g. "genre_movie".
     * @param string $localForeignKey Typical "&lt;local_model&gt;_id", e.g. "genre_id".
     * @param string $otherForeignKey Typical "&lt;foreign_model&gt;_id", e.g. "movie_id".
     * @param string $localKey Typical "id", e.g. the primary key of App\Models\Genre.
     * @param string $otherKey Typical "id", e.g. the primary key of App\Models\Movie.
     */
    public function __construct(Model $model, Builder $builder, $joinTable, $localForeignKey, $otherForeignKey, $localKey, $otherKey)
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
        // SELECT *
        // FROM $table
        // WHERE $otherKey IN (
        //   SELECT $otherForeignKey
        //   FROM $joinTable
        //   WHERE $localForeignKey = ?
        // )

        $id = $this->model->getAttribute($this->localKey);
        $otherTable = $this->builder()->getTable();

        return $this->builder
            ->select([$otherTable . '.*'])
            ->join($this->joinTable, $otherTable . '.' . $this->otherKey . ' = ' . $this->joinTable . '.' .$this->otherForeignKey)
            ->whereIs($this->joinTable . '.' . $this->localForeignKey, $id);

//        $this->builder->whereSubQuery($this->otherKey, function(Builder $builder) {
//            $id = $this->model->getAttribute($this->localKey);
//            return $builder->select($this->otherForeignKey)->from($this->joinTable)->whereIs($this->localForeignKey, $id);
//        }, 'IN');  // todo prüfen, ob es performanter geht (per join)
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
        foreach ($this->builder->all() as $foreignEntity) {
            /** @var Model $foreignEntity */
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
}