<?php

namespace Core\Models;

use Core\Models\Contracts\Model as ModelContract;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;

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
    public function associate(ModelContract $model)
    {
        $type = get_class($model);
        if ($this->builder()->getClass() != $type) {
            $this->builder()->table($model->getTable())->asClass($type);
            $this->otherKey = $model->getPrimaryKey();
        }

        $otherId = $model->getAttribute($this->otherKey);

        $this->model
            ->clearRelationCache()
            ->setAttribute($this->typeAttribute, $type)
            ->setAttribute($this->foreignKey, $otherId)
            ->save();

        $model->clearRelationCache();
    }

    /**
     * @inheritdoc
     */
    public function disassociate(ModelContract $model = null)
    {
        if ($model === null) {
            $this->model
                ->clearRelationCache()
                ->setAttribute($this->typeAttribute, null)
                ->setAttribute($this->foreignKey, null)
                ->save();
            return;
        }

        $foreignId = $this->model->getAttribute($this->foreignKey);
        $otherId   = $model->getAttribute($this->otherKey);
        if ($foreignId == $otherId) {
            $this->model
                ->clearRelationCache()
                ->setAttribute($this->typeAttribute, null)
                ->setAttribute($this->foreignKey, null)
                ->save();
        }

        $model->clearRelationCache();
    }

    /**
     * @inheritdoc
     */
    public function create(array $attributes = [])
    {
        return $this->model->database()->transaction(function() use($attributes) {
            /** @var ModelContract $class */
            $class = $this->builder->getClass();
            if ($class === null) {
                throw new \Exception('The type of the polymorphic relationship is not defined.');
            }
            $model = $class::create($attributes);
            $this->associate($model);

            return $model;
        });
    }

    /**
     * @inheritdoc
     */
    public function delete(ModelContract $model)
    {
        $this->model->database()->transaction(function(Database $db) use($model) {
            $type      = get_class($model);
            $otherId   = $model->getAttribute($this->otherKey);
            $foreignId = $this->model->getAttribute($this->foreignKey);

            $model->delete();
            $model->clearRelationCache();

            $db->table($this->model->getTable())
                ->whereIs($this->typeAttribute, $type)
                ->whereIs($this->foreignKey, $otherId)
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
        });
    }
}