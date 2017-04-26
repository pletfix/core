<?php

namespace Core\Models;

class HasOneRelation extends HasManyRelation
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
        // get the foreign entities, group by local id
        $foreignEntities = [];
        foreach ($this->builder->all() as $foreignEntity) {
            /** @var Model $foreignEntity */
            $localId = $foreignEntity->getAttribute($this->foreignKey);
            $foreignEntities[$localId] = $foreignEntity;
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
}