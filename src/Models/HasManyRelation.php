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
    public function get()
    {
        return $this->builder->all();
    }
}