<?php

namespace Core\Services\Contracts;

interface Flash
{
    /**
     * Determine if the given value exist in the flash.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key);

    /**
     * Get the specified value from the flash
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * Flash a value.
     *
     * The value is only be available during the subsequent HTTP request.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value);

    /**
     * Merge the given values to the flash.
     *
     * The values are only be available during the subsequent HTTP request.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $values
     * @return $this
     */
    public function merge($key, array $values);

    /**
     * Flash a value for immediate use.
     *
     * @param string $key|null Key using "dot" notation.
     * @param mixed $value
     * @return $this
     */
    public function setNow($key, $value);

    /**
     * Remove a value from the flash.
     *
     * @param string $key
     * @return $this
     */
    public function delete($key);

    /**
     * Remove all items from the flash.
     *
     * @return $this
     */
    public function clear();

    /**
     * Keep the flash data for an additional request.
     *
     * Omit the keys to keep all flash data.
     *
     * @param array $keys
     * @return $this
     */
    public function reflash(array $keys = null);

    /**
     * Age the flash data.
     *
     * @return $this
     */
    public function age();

//    /**
//     * Flash an input array to the session.
//     *
//     * The values will be available in the next session via the `old` method.
//     *
//     * @param array $value
//     * @return $this
//     */
//    public function flashInput(array $value);
//
//    /**
//     * Determine if the session contains old input.
//     *
//     * @param string $key
//     * @return bool
//     */
//    public function hasOldInput($key = null);
//
//    /**
//     * Get the requested item from the flashed old input array.
//     *
//     * @param string $key
//     * @param mixed $default
//     * @return mixed
//     */
//    public function old($key = null, $default = null);
}
