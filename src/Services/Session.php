<?php

namespace Core\Services;

use Closure;
use Core\Exceptions\SessionException;
use Core\Services\Contracts\Session as SessionContract;

/**
 * The Session Object is an adapter of the [PHP Session](http://php.net/manual/en/session.examples.basic.php).
 */
class Session implements SessionContract
{
    /**
     * Determine if the session is started and therefore writable.
     *
     * @var bool
     */
    private $writable = false;

    /**
     * Determines if the session has been opened at least once and therefore the data can be read.
     *
     * @var bool
     */
    private $readable = false;

    /**
     * Lifetime of the session cookie, defined in seconds.
     *
     * The value 0 means "until the browser is closed."
     *
     * @var int
     */
    private $lifetime;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/');

        $config = array_merge([
            'name'      => 'pletfix_session',
            'lifetime'  => 0,
            'path'      => $path, // '/',
            'domain'    => null,
            'secure'    => false,
            'http_only' => true,
        ], config('session', []));

        $this->lifetime = $config['lifetime'] * 60;

        session_set_cookie_params(
            $this->lifetime,
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['http_only']
        );

        if (isset($config['save_path'])) {
            session_save_path($config['save_path']);
        }

        session_name($config['name']);
    }

    /**
     * @inheritdoc
     */
    public function isStarted()
    {
        return $this->writable;
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        if (!$this->writable) {
            if (!session_start()) {
                throw new SessionException('Start of session failed!'); // @codeCoverageIgnore
            }

            // Reset the cookie with a new expiration date, every time the user interacts with the backend.
            // (PHP doesn't automatically update the cookie on session_start().)
            if ($this->lifetime > 0 && ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                if (!setcookie(session_name(), session_id(), time() + $this->lifetime, $params['path'], $params['domain'], $params['secure'], $params['httponly'])) {
                    throw new SessionException('Refresh session cookies failed!'); // @codeCoverageIgnore
                }
            }

            $this->readable = true;
            $this->writable = true;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        if ($this->writable) {
            session_write_close();
            $this->writable = false;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function abort()
    {
        if ($this->writable) {
            session_abort();
            $this->readable = false;
            $this->writable = false;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function kill()
    {
        if (!$this->writable) {
            return $this->lock(function(Session $session) {
                $session->kill();
            });
        }

        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            if (!setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly'])) {
                throw new SessionException('Deleting session cookies failed!'); // @codeCoverageIgnore
            }
        }

        // Finally, destroy the session.
        if (!session_destroy()) {
            throw new SessionException('Killing the session failed!'); // @codeCoverageIgnore
        };

        $this->writable = false;
        $this->readable = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function regenerate()
    {
        if (!$this->writable) {
            return $this->lock(function(Session $session) {
                $session->regenerate();
            });
        }

        if (!session_regenerate_id(true)) {
            throw new SessionException('Regeneration of session failed!'); // @codeCoverageIgnore
        };

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function lock(Closure $callback)
    {
        $explicitStarted = false;
        if (!$this->writable) {
            $this->start();
            $explicitStarted = true;
        }
        try {
            $callback($this);
        }
        finally {
            if ($explicitStarted) {
                $this->commit();
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        if (!$this->readable) {
            $this->start()->commit();
        }

        if (strpos($key, '.') === false) {
            return isset($_SESSION[$key]);
        }

        $subitems = $_SESSION;
        foreach (explode('.', $key) as $subkey) {
            if (!isset($subitems[$subkey])) {
                return false;
            }
            $subitems = $subitems[$subkey];
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function get($key = null, $default = null)
    {
        if (!$this->readable) {
            $this->start()->commit();
        }

        if ($key === null) {
            return $_SESSION;
        }

        if (strpos($key, '.') === false) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
        }

        $subitems = $_SESSION;
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
        if (!$this->writable) {
            return $this->lock(function(Session $session) use ($key, $value) {
                $session->set($key, $value);
            });
        }

        if ($key === null) {
            $_SESSION = $value;
            return $this;
        }

        if (strpos($key, '.') === false) {
            $_SESSION[$key] = $value;
            return $this;
        }

        $keys = explode('.', $key);
        $subitems = &$_SESSION;
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
    public function delete($key)
    {
        if (!$this->writable) {
            return $this->lock(function(Session $session) use ($key) {
                $session->delete($key);
            });
        }

        if (strpos($key, '.') === false) {
            unset($_SESSION[$key]);
            return $this;
        }

        $keys = explode('.', $key);
        $subitems = &$_SESSION;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($subitems[$key]) || !is_array($subitems[$key])) {
                $subitems[$key] = [];
            }
            $subitems = &$subitems[$key];
        }

        unset($subitems[array_shift($keys)]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        if (!$this->writable) {
            return $this->lock(function(Session $session) {
                $session->clear();
            });
        }

        $_SESSION = [];

        return $this;
    }

//    /**
//     * Get the previous URL from the session.
//     *
//     * @return string|null
//     */
//    public function previousUrl()
//    {
//        return $this->get('_previous_url');
//    }
//
//    /**
//     * Set the "previous" URL in the session.
//     *
//     * @param  string  $url
//     * @return void
//     */
//    public function setPreviousUrl($url)
//    {
//        $this->set('_previous_url', $url);
//    }
}
