<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;

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
     * @param ModelContract $model The local model, e.g. App\Models\Book.
     * @param Builder $builder A Builder to get the foreign entity, e.g. author.
     * @param string $foreignKey Typical "&lt;foreign_model&gt;_id", e.g. "author_id".
     * @param string $otherKey Typical "id", e.g. the primary key of App\Models\Author.
     */
    public function __construct(ModelContract $model, Builder $builder, $foreignKey, $otherKey)
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
        $this->builder->where($this->otherKey, $id);
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
        foreach ($this->builder->cursor() as $foreignEntity) {
            /** @var ModelContract $foreignEntity */
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

    /**
     * @inheritdoc
     */
    public function associate(ModelContract $model)
    {
        $otherId = $model->getAttribute($this->otherKey);

        $success = $this->model->setAttribute($this->foreignKey, $otherId)->save();

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
            $success = $this->model->setAttribute($this->foreignKey, null)->save();
            if ($success) {
                $this->model->clearRelationCache();
            }
            return $success;
        }

        $foreignId = $this->model->getAttribute($this->foreignKey);
        $otherId = $model->getAttribute($this->otherKey);
        if ($foreignId != $otherId) {
            return true;
        }

        $success = $this->model->setAttribute($this->foreignKey, null)->save();

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
        return $this->model->database()->transaction(function(Database $db) use($attributes) {
            /** @var ModelContract $class */
            $class = $this->builder->getClass();
            $model = $class::create($attributes);
            if ($model === false) {
                return false;
            }
            if (!$this->associate($model)) {
                $db->rollback();
                return false;
            }

            return $model;
        });
    }

    /**
     * @inheritdoc
     */
    public function delete(ModelContract $model)
    {
        return $this->model->database()->transaction(function(Database $db) use($model) {
            $foreignId = $this->model->getAttribute($this->foreignKey);
            $otherId = $model->getAttribute($this->otherKey);
            if (!$model->delete()) {
                return false;
            }

            $model->clearRelationCache();

            $db->table($this->model->getTable())
                ->where($this->foreignKey, $otherId)
                ->update([$this->foreignKey => null]);

            if ($foreignId == $otherId) {
                $this->model->clearRelationCache();
                $this->model->setAttribute($this->foreignKey, null)->sync();
            }

            return true;
        });
    }
}