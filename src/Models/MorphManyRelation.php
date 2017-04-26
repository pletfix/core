<?php

namespace Core\Models;

use Core\Services\PDOs\Builder\Contracts\Builder;

class MorphManyRelation extends Relation
{
    /**
     * Attribute name for the foreign type (typical "&lt;prefix&gt;_type").
     *
     * @var string
     */
    protected $typeAttribute;

    /**
     * Foreign Key (typical "&lt;prefix&gt;_id").
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * Create a new Relation instance.
     *
     * @param Model $model The local model, e.g. App\Models\Employee.
     * @param Builder $builder A Builder to get the foreign entities, e.g. pictures.
     * @param string $typeAttribute Typical "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string $foreignKey Typical "&lt;prefix&gt;_id", e.g. "imageable_id".
     */
    public function __construct(Model $model, Builder $builder, $typeAttribute, $foreignKey)
    {
        $this->typeAttribute = $typeAttribute;
        $this->foreignKey    = $foreignKey;

        parent::__construct($model, $builder);
    }

    /**
     * @inheritdoc
     */
    protected function addConstraints()
    {
        $type = get_class($this->model);
        $id   = $this->model->getId();
        $this->builder->whereIs($this->typeAttribute, $type)->whereIs($this->foreignKey, $id);
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $entities)
    {
        $ids = [];
        foreach ($entities as $i => $entity) {
            $ids[] = $entity->getId();
        }

        $hash = [];
        foreach ($entities as $entity) {
            $hash[] = $entity->getId();
        }

        $type = get_class($this->model);

        return $this->builder->whereIs($this->typeAttribute, $type)->whereIn($this->foreignKey, $hash);
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
        // get the foreign entities, group by local primary identity
        $foreignEntities = [];
        foreach ($this->builder->all() as $foreignEntity) {
            /** @var Model $foreignEntity */
            $id = $foreignEntity->getAttribute($this->foreignKey);
            if (!isset($foreignEntities[$id])) {
                $foreignEntities[$id] = [];
            }
            $foreignEntities[$id][] = $foreignEntity;
        }

        return $foreignEntities;
    }
}