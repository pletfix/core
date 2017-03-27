<?php

namespace Core\Services;

use Core\Services\Contracts\Session as SessionContract;

/**
 * The Session Object is an adapter of the [PHP Session](http://php.net/manual/en/session.examples.basic.php).
 *
 * This class based on Aura.Session (https://github.com/auraphp/Aura.Session/blob/3.x/LICENSE BSD 2-clause License)
 *
 * @see https://github.com/auraphp/Aura.Session/tree/3.x
 */
class Session implements SessionContract
{
    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->start();
    }

    /**
     * Start new or resume existing session.
     */
    private function start()
    {

    }

    /**
     * End the current session and store session data.
     *
     * This function calls `session_write_close()`internally.
     */
    public function save()
    {

    }

    /**
     * Discard the changes made during the current request.
     *
     * This function calls `session_abort()` internally.
     *
     * @see session_abort()
     */
    public function cancel()
    {

    }

    /**
     * Regenerate the session id.
     *
     * Any time a user has a change in privilege be sure to regenerate the session id!
     * This method also regenerates the CSRF token value.
     */
    public function regenerate()
    {

    }

    /**
     * Set the session lifetime in minutes.
     *
     * @param int $minutes
     */
    public function lifetime($minutes)
    {

    }

    /**
     * Removes all items from the session.
     */
    public function clear()
    {

    }

    /**
     * Get all session items.
     *
     * @return array
     */
    public function all()
    {
        return [];
    }

    /**
     * Get the specified value from the session
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        return null;
    }

    /**
     * Set a given value into the session.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     */
    public function set($key, $value)
    {

    }

    /**
     * Store a value only be available during the subsequent HTTP request.
     *
     * @param $key
     * @param string $value
     */
    public function flash($key, $value)
    {

    }

    /**
     * Keep the flash data for an additional request.
     *
     * Omit the keys to keep all flash data.
     *
     * @param array $keys
     */
    public function reflash(array $keys = null)
    {

    }

    /**
     * Remove a value from the session.
     *
     * @param string $key
     */
    public function delete($key)
    {

    }

    /**
     * Determine if the given value exist in the session.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key)
    {
        return false;
    }

    /**
     * Get a CSRF token value.
     *
     * @return string
     */
    public function csrf()
    {
        return '';
    }
}
