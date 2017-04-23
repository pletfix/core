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
}