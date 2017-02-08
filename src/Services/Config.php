<?php

namespace Core\Services;

use Core\Services\Contracts\Config as ConfigContract;

class Config implements ConfigContract
{
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

//    /**
//     * Create a new configuration repository.
//     */
//    public function __construct()
//    {
//    }

    /**
     * Get the specified configuration value.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->items;
        }

        $subitems = $this->items;
        foreach (explode('.', $key) as $subkey) {
            if (!isset($subitems[$subkey])) {
                return $default;
            }
            $subitems = $subitems[$subkey];
        }

        return $subitems;
    }

    /**
     * Set a given configuration value.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     */
    public function set($key, $value)
    {
        if (is_null($key)) {
            $this->items = $value;
            return;
        }

        if (strpos($key, '.') === false) {
            $this->items[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $subitems = &$this->items;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($subitems[$key]) || !is_array($subitems[$key])) {
                $subitems[$key] = [];
            }
            $subitems = &$subitems[$key];
        }
        $subitems[array_shift($keys)] = $value;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key)
    {
        $subitems = $this->items;
        foreach (explode('.', $key) as $subkey) {
            if (!isset($subitems[$subkey])) {
                return false;
            }
            $subitems = $subitems[$subkey];
        }

        return true;
    }

//    /**
//     * Merge the given array to the current configuration.
//     *
//     * @param array $array
//     */
//    public function merge($array)
//    {
//        foreach ($array as $key => $value) {
//            $this->items[$key] = $value;
//        }
//    }
}
