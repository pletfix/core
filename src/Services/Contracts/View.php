<?php

namespace Core\Services\Contracts;

use InvalidArgumentException;

interface View
{
    /**
     * Render a view.
     *
     * @param string $name
     * @param array|Collection $variables
     * @return string
     * @throws InvalidArgumentException
     */
    public function render($name, $variables = []);

    /**
     * Determine if a given view exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists($name);
}
