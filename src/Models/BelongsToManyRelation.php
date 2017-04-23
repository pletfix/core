<?php

namespace Core\Models;

use Core\Services\PDOs\Builder\Contracts\Builder;

class BelongsToManyRelation extends Relation
{
    /**
     * Name of the joining table (typical &lt;model1&gt;_&lt;model2&gt;)
     *
     * @var string
     */
    protected $joiningTable;

    /**
     * Local foreign key (typical "&lt;local_model&gt;_id").
     *
     * @var string
     */
    protected $localForeignKey;

    /**
     * Other foreign key (typical "&lt;foreign_model&gt;_id").
     *
     * @var string
     */
    protected $otherForeignKey;

    /**
     * Local key (typical "id").
     *
     * @var string
     */
    protected $localKey;

    /**
     * Other key (typical "id").
     *
     * @var string
     */
    protected $otherKey;

    /**
     * Create a new Relation instance.
     *
     * @param Model $model The local model, e.g. App\Models\Genre.
     * @param Builder $builder A Builder to get the foreign entities, e.g. movies.
     * @param string $joiningTable Name of the joining table. Typical &lt;model1&gt;_&lt;model2&gt; (in alphabetical order of models), e.g. "genre_movie".
     * @param string $localForeignKey Typical "&lt;local_model&gt;_id", e.g. "genre_id".
     * @param string $otherForeignKey Typical "&lt;foreign_model&gt;_id", e.g. "movie_id".
     * @param string $localKey Typical "id", e.g. the primary key of App\Models\Genre.
     * @param string $otherKey Typical "id", e.g. the primary key of App\Models\Movie.
     */
    public function __construct(Model $model, Builder $builder, $joiningTable, $localForeignKey, $otherForeignKey, $localKey, $otherKey)
    {
        $this->joiningTable    = $joiningTable;
        $this->localForeignKey = $localForeignKey;
        $this->otherForeignKey = $otherForeignKey;
        $this->localKey        = $localKey;
        $this->otherKey        = $otherKey;

        parent::__construct($model, $builder);
    }

    /**
     * @inheritdoc
     */
    protected function addConstraints()
    {
        // SELECT *
        // FROM $table
        // WHERE $otherKey IN (
        //   SELECT $otherForeignKey
        //   FROM $joiningTable
        //   WHERE $localForeignKey = ?
        // )

        $this->builder->whereSubQuery($this->otherKey, function(Builder $builder) {
            $id = $this->model->getAttribute($this->localKey);
            return $builder->select($this->otherForeignKey)->from($this->joiningTable)->whereIs($this->localForeignKey, $id);
        }, 'IN');  // todo prüfen, ob es performanter geht (per join)
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->builder->all();
    }
}