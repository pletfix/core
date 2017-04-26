<?php

namespace Core\Models;

use Core\Services\PDOs\Builder\Contracts\Builder;

class BelongsToRelation extends Relation
{
    /**
     * Foreign key (typical "&lt;foreign_model&gt;_id").
     *
     * @var string
     */
    protected $foreignKey;

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
     * @param Model $model The local model, e.g. App\Models\Book.
     * @param Builder $builder A Builder to get the foreign entity, e.g. author.
     * @param string $foreignKey Typical "&lt;foreign_model&gt;_id", e.g. "author_id"
     * @param string $otherKey Typical "id", e.g. the primary key of App\Models\Author.
     */
    public function __construct(Model $model, Builder $builder, $foreignKey, $otherKey)
    {
        $this->foreignKey = $foreignKey;
        $this->otherKey   = $otherKey;

        parent::__construct($model, $builder);
    }

    /**
     * @inheritdoc
     */
    protected function addConstraints()
    {
        $id = $this->model->getAttribute($this->foreignKey);
        $this->builder->whereIs($this->otherKey, $id);
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $entities)
    {
        $this->eagerHash = [];
        foreach ($entities as $entity) {
            $this->eagerHash[$entity->getId()] = $entity->getAttribute($this->foreignKey); // eg. eagerHash[$book_id] = $author_id
        }

        return $this->builder->whereIn($this->otherKey, array_values($this->eagerHash));
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->builder->first();
    }

    /**
     * @inheritdoc
     */
    public function getEager()
    {
        // get the foreign entities, group by foreign id
        $foreignEntities = [];
        foreach ($this->builder->all() as $foreignEntity) {
            /** @var Model $foreignEntity */
            $foreignId = $foreignEntity->getAttribute($this->otherKey); // e.g. id of the author
            $foreignEntities[$foreignId] = $foreignEntity;
        }

        // group the foreign entities by local primary identity
        $result = [];
        foreach ($this->eagerHash as $id => $foreignId) {
            if (isset($foreignEntities[$foreignId])) {
                $result[$id] = $foreignEntities[$foreignId];
            }
        }

        return $result;
    }
}