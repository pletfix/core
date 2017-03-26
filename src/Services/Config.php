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
    private $items = []; // todo oder statisch? bringt das was?

    /**
     * @inheritdoc
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
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
     * @inheritdoc
     */
    public function set($key, $value)
    {
        if ($key === null) {
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
