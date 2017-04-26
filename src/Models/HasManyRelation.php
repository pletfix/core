<?php

namespace Core\Models;

use Core\Services\PDOs\Builder\Contracts\Builder;

class HasManyRelation extends Relation
{
    /**
     * Foreign key (typical "&lt;local_model&gt;_id").
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * Local key (typical "id").
     *
     * @var string
     */
    protected $localKey;

    /**
     * Hash with eager loaded IDs.
     *
     * @var array
     */
    protected $eagerHash = [];

    /**
     * Create a new Relation instance.
     *
     * @param Model $model The local model, e.g. App\Models\Author.
     * @param Builder $builder A Builder to get the foreign entities, e.g. books.
     * @param string $foreignKey Typical "&lt;local_model&gt;_id", e.g. "author_id"
     * @param string $localKey Typical "id", e.g. the primary key of App\Models\Author.
     */
    public function __construct(Model $model, Builder $builder, $foreignKey, $localKey)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;

        parent::__construct($model, $builder);
    }

    /**
     * @inheritdoc
     */
    protected function addConstraints()
    {
        $id = $this->model->getAttribute($this->localKey);
        $this->builder->whereIs($this->foreignKey, $id);
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $entities)
    {
        $this->eagerHash = [];
        foreach ($entities as $entity) {
            $this->eagerHash[$entity->getId()] = $entity->getAttribute($this->localKey);
        }

        return $this->builder->whereIn($this->foreignKey, array_values($this->eagerHash));
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
        // get the foreign entities, group by local id
        $foreignEntities = [];
        foreach ($this->builder->all() as $foreignEntity) {
            /** @var Model $foreignEntity */
            $localId = $foreignEntity->getAttribute($this->foreignKey);
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