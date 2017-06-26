<?php

namespace Core\Services\Contracts;

interface Config
{
    /**
     * Get the specified configuration value.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * Set a given configuration value.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value);

    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key);
}
