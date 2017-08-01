<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;
use LogicException;

class MorphToRelation extends BelongsToRelation
{
    /**
     * Attribute name for the foreign type (typical "&lt;prefix&gt;_type").
     *
     * @var string
     */
    protected $typeAttribute;

    /**
     * Create a new Relation instance.
     *
     * @param ModelContract $model The local model, e.g. App\Models\Picture.
     * @param Builder $builder A Builder to get the foreign entity, e.g. employee.
     * @param string $typeAttribute Typical "&lt;prefix&gt;_type", e.g. "imageable_type".
     * @param string $foreignKey Typical "&lt;prefix&gt;_id", e.g. "imageable_id".
     * @param string $otherKey The primary key of the foreign entity, e.g. "id" of App\Models\Employee.
     */
    public function __construct(ModelContract $model, Builder $builder, $typeAttribute, $foreignKey, $otherKey)
    {
        $this->typeAttribute = $typeAttribute;

        parent::__construct($model, $builder, $foreignKey, $otherKey);
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $entities)
    {
        $this->eagerHash = [];
        $class = $this->builder->getClass();
        foreach ($entities as $entity) {
            if ($entity->getAttribute($this->typeAttribute) !== $class) {
                throw new LogicException('Cannot load several types eagerly.');
            }
            $this->eagerHash[$entity->getId()] = $entity->getAttribute($this->foreignKey); // eg. eagerHash[$book_id] = $author_id
        }

        return $this->builder->whereIn($this->otherKey, array_values($this->eagerHash));
    }

    /**
     * @inheritdoc
     */
    public function associate(ModelContract $model)
    {
        $type = get_class($model);
        if ($this->builder()->getClass() != $type) {
            $this->builder()->table($model->getTable())->asClass($type);
            $this->otherKey = $model->getPrimaryKey();
        }

        $otherId = $model->getAttribute($this->otherKey);

        $success = $this->model
            ->clearRelationCache()
            ->setAttribute($this->typeAttribute, $type)
            ->setAttribute($this->foreignKey, $otherId)
            ->save();

        if ($success) {
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
            $success = $this->model
                ->setAttribute($this->typeAttribute, null)
                ->setAttribute($this->foreignKey, null)
                ->save();
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

        $success = $this->model
                ->setAttribute($this->typeAttribute, null)
                ->setAttribute($this->foreignKey, null)
                ->save();

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
            if ($class === null) {
                throw new LogicException('The type of the polymorphic relationship is not defined.');
            }
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
            $type      = get_class($model);
            $otherId   = $model->getAttribute($this->otherKey);
            $foreignId = $this->model->getAttribute($this->foreignKey);

            if (!$model->delete()) {
                return false;
            }

            $model->clearRelationCache();

            $db->table($this->model->getTable())
                ->where($this->typeAttribute, $type)
                ->where($this->foreignKey, $otherId)
                ->update([
                    $this->typeAttribute => null,
                    $this->foreignKey => null,
                ]);

            if ($foreignId == $otherId) {
                $this->model
                    ->clearRelationCache()
                    ->setAttribute($this->typeAttribute, null)
                    ->setAttribute($this->foreignKey, null)
                    ->sync();
            }

            return true;
        });
    }
}