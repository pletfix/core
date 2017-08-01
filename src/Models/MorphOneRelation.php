<?php

namespace Core\Models;

class MorphOneRelation extends MorphManyRelation
{
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
        // get the foreign entities, group by local primary identity
        $foreignEntities = [];
        foreach ($this->builder->cursor() as $foreignEntity) {
            /** @var Contracts\Model $foreignEntity */
            $id = $foreignEntity->getAttribute($this->foreignKey);
            $foreignEntities[$id] = $foreignEntity;
        }

        return $foreignEntities;
    }
}