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
    public function get()
    {
        return $this->builder->first();
    }
}