<?php

namespace Core\Services\PDOs\Builders\Contracts;

interface Hookable
{
    /**
     * Get the current attributes of the model.
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Set the given attributes of the model.
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes);

    /**
     * Get the name of the primary key for the model's table.
     *
     * @return string
     */
    public function getPrimaryKey();
}
