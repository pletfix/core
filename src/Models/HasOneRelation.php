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
}