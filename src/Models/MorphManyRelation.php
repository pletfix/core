<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
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
     * @param ModelContract $model The local model, e.g. App\Models\Employee.
     * @param Builder $builder A Builder to get the foreign entities, e.g. pictures.
     * @param string $typeAttribute Typical "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string $foreignKey Typical "&lt;prefix&gt;_id", e.g. "imageable_id".
     */
    public function __construct(ModelContract $model, Builder $builder, $typeAttribute, $foreignKey)
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
        foreach ($this->builder->all() as $foreignEntity) { // todo testen, ob cursor schneller ist
            /** @var ModelContract $foreignEntity */
            $id = $foreignEntity->getAttribute($this->foreignKey);
            if (!isset($foreignEntities[$id])) {
                $foreignEntities[$id] = [];
            }
            $foreignEntities[$id][] = $foreignEntity;
        }

        return $foreignEntities;
    }

    /**
     * @inheritdoc
     */
    public function associate(ModelContract $model)
    {
        $type = get_class($this->model);
        $id   = $this->model->getId();
        $model
            ->clearRelationCache()
            ->setAttribute($this->typeAttribute, $type)
            ->setAttribute($this->foreignKey, $id)
            ->save();
        $this->model->clearRelationCache();
    }

    /**
     * @inheritdoc
     */
    public function disassociate(ModelContract $model = null)
    {
        if ($model === null) {
            $this->builder->update([
                $this->typeAttribute => null,
                $this->foreignKey => null,
            ]);
            $this->model->clearRelationCache();
            return;
        }

        $type = get_class($this->model);
        $id   = $this->model->getId();
        $foreignType = $model->getAttribute($this->typeAttribute);
        $foreignId   = $model->getAttribute($this->foreignKey);
        if ($type == $foreignType && $id == $foreignId) {
            $model
                ->clearRelationCache()
                ->setAttribute($this->typeAttribute, null)
                ->setAttribute($this->foreignKey, null)
                ->save();
            $this->model->clearRelationCache();
        }
    }

    /**
     * @inheritdoc
     */
    public function create(array $attributes = [])
    {
        $class = $this->builder->getClass();
        /** @var ModelContract $model */
        $model = new $class;
        $model->checkMassAssignment($attributes);
        $model->setAttributes($attributes);
        $this->associate($model); // the model is saved here

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function delete(ModelContract $model)
    {
        $type        = get_class($this->model);
        $id          = $this->model->getId();
        $foreignType = $model->getAttribute($this->typeAttribute);
        $foreignId   = $model->getAttribute($this->foreignKey);

        $model
            ->delete()
            ->clearRelationCache()
            ->setAttribute($this->typeAttribute, null)
            ->setAttribute($this->foreignKey, null)
            ->sync();

        if ($type == $foreignType && $id == $foreignId) {
            $this->model->clearRelationCache();
        }
    }
}