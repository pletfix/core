<?php

namespace Core\Services;

use Core\Services\Contracts\Cookie as CookieContract;

/**
 * The Cookie Object is a very simple adapter of the [HTTP-Cookies](http://php.net/manual/en/features.cookies.php).
 */
class Cookie implements CookieContract
{
    /**
     * @inheritdoc
     */
    public function has($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * @inheritdoc
     */
    public function get($name, $default = null)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }

    /**
     * @inheritdoc
     */
    public function set($name, $value, $minutes = 0, $path = '', $domain = '', $secure = false, $httpOnly = false)
    {
        $expire = $minutes > 0 ? time() + $minutes * 60 : 0;
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);

        $_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function delete($name)
    {
        setcookie($name, '', time() - 3600);

        unset($_COOKIE[$name]);

        return $this;
    }
}