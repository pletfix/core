<?php

namespace Core\Services\Contracts;

use Closure;
use Core\Exceptions\SessionException;

interface Session
{
    /**
     * Determine if the session is started and therefore writable.
     *
     * @return boolean
     */
    public function isStarted();

    /**
     * Start new or resume existing session.
     *
     * Simultaneous requests must now wait until the session is committed or aborted.
     *
     * @return $this
     * @throws SessionException
     */
    public function start();

    /**
     * Write session data and end session.
     *
     * From here on out, concurrent requests are no longer blocked.
     *
     * @return $this
     */
    public function commit();

    /**
     * Discard the changes made during the current request.
     *
     * From here on out, concurrent requests are no longer blocked.
     *
     * @return $this
     */
    public function abort();

    /**
     * Kill the session completely (both values as well as session cookie).
     *
     * @see http://php.net/manual/en/function.session-destroy.php
     *
     * @return $this
     */
    public function kill();

    /**
     * Regenerate the session id.
     *
     * Any time a user has a change in privilege be sure to regenerate the session id!
     *
     * @return $this
     */
    public function regenerate();

    /**
     * Closure for writing session data.
     *
     * The session is started and committed automatically.
     *
     * @param Closure $callback
     * @return $this
     */
    public function lock(Closure $callback);

    /**
     * Determine if the given value exist in the session.
     *
     * @param string $key Key using "dot" notation.
     * @return bool
     */
    public function has($key);

    /**
     * Get the specified value from the session
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * Set a given value into the session.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value);

    /**
     * Remove a value from the session.
     *
     * @param string $key
     * @return $this
     */
    public function delete($key);

    /**
     * Remove all items from the session.
     *
     * @return $this
     */
    public function clear();
}
