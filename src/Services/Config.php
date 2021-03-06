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
    private $items = [];

    /**
     * @inheritdoc
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->items;
        }

        if (strpos($key, '.') === false) {
            return isset($this->items[$key]) ? $this->items[$key] : $default;
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
     * @inheritdoc
     */
    public function set($key, $value)
    {
        if ($key === null) {
            $this->items = $value;
            return $this;
        }

        if (strpos($key, '.') === false) {
            $this->items[$key] = $value;
            return $this;
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

        return $this;
    }

    /**
     * @inheritdoc
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
}
