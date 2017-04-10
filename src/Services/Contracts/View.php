<?php

namespace Core\Services\Contracts;

use InvalidArgumentException;

interface View
{
    /**
     * Render a view.
     *
     * @param string $name
     * @param array $variables
     * @return string
     * @throws InvalidArgumentException
     */
    public function render($name, array $variables = []);

    /**
     * Determine if a given view exists.
     *
     * @param string $name
     * @return bool
     */
    public function exists($name);
}
