<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
use Core\Services\PDOs\Builders\Contracts\Builder;

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
     * @param ModelContract $model The local model, e.g. App\Models\Author.
     * @param Builder $builder A Builder to get the foreign entities, e.g. books.
     * @param string $foreignKey Typical "&lt;local_model&gt;_id", e.g. "author_id"
     * @param string $localKey Typical "id", e.g. the primary key of App\Models\Author.
     */
    public function __construct(ModelContract $model, Builder $builder, $foreignKey, $localKey)
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
        foreach ($this->builder->all() as $foreignEntity) { // todo testen, ob cursor schneller ist
            /** @var ModelContract $foreignEntity */
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

    /**
     * @inheritdoc
     */
    public function associate(ModelContract $model)
    {
        $localId = $this->model->getAttribute($this->localKey);

        $success = $model->setAttribute($this->foreignKey, $localId)->save();

        if ($success) {
            $this->model->clearRelationCache();
            $model->clearRelationCache();
        }

        return $success;
    }

    /**
     * @inheritdoc
     */
    public function disassociate(ModelContract $model = null)
    {
        if ($model === null) {
            $result = $this->builder->update([$this->foreignKey => null]);
            if ($result !== false) {
                $this->model->clearRelationCache();
            }
            return $result !== false;
        }

        $localId = $this->model->getAttribute($this->localKey);
        $foreignId = $model->getAttribute($this->foreignKey);
        if ($localId != $foreignId) {
            return true;
        }

        $success = $model->setAttribute($this->foreignKey, null)->save();

        if ($success) {
            $this->model->clearRelationCache();
            $model->clearRelationCache();
        }

        return $success;
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
        if (!$this->associate($model)) { // the model is saved here
            return false;
        }

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function delete(ModelContract $model)
    {
        $localId = $this->model->getAttribute($this->localKey);
        $foreignId = $model->getAttribute($this->foreignKey);

        if (!$model->delete()) {
            return false;
        }

        $model->clearRelationCache();
        $model->setAttribute($this->foreignKey, null)->sync();

        if ($localId == $foreignId) {
            $this->model->clearRelationCache();
        }

        return true;
    }
}