<?php

namespace Core\Services\Contracts;

interface Cookie
{
    /**
     * Determine if an cookie exists.
     *
     * @param string $name The name of the cookie.
     * @return bool
     */
    public function has($name);

    /**
     * Get a cookie by name.
     *
     * @param string $name The name of the cookie.
     * @param mixed $default
     * @return string
     */
    public function get($name, $default = null);

    /**
     * Set a cookie.
     *
     * Cookies must be sent before any output from your script (this is a protocol restriction).
     *
     * Note, this value is stored on the clients computer; do not store sensitive information!
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param float|int $minutes Number of minutes the cookie expires.
     *      If set to 0, the cookie will expire when the browser closes.
     * @param string|null $path The path on the server in which the cookie will be available on.
     *      If set to '/', the cookie will be available within the entire domain.
     *      If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     *      The default value is the base directory of the application.
     * @param string|null $domain The (sub)domain that the cookie is available to.
     *      Setting this (such as 'example.com') will make the cookie available to that domain and all other
     *      sub-domains of it (i.e. www.example.com). The default is the current domain.
     * @param bool $secure When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param bool $httpOnly When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return $this
     */
    public function set($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = false);


    /**
     * Set a cookie that lasts "forever" (five years).
     *
     * Cookies must be sent before any output from your script (this is a protocol restriction).
     *
     * Note, this value is stored on the clients computer; do not store sensitive information!
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param string|null $path The path on the server in which the cookie will be available on.
     *      If set to '/', the cookie will be available within the entire domain.
     *      If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     *      The default value is the base directory of the application.
     * @param string|null $domain The (sub)domain that the cookie is available to.
     *      Setting this (such as 'example.com') will make the cookie available to that domain and all other
     *      sub-domains of it (i.e. www.example.com). The default is the current domain.
     * @param bool $secure When set to TRUE, the cookie will only be set if a secure connection exists.
     * @param bool $httpOnly When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return $this
     */
    public function setForever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = false);

    /**
     * Remove a cookie.
     *
     * @param string $name The name of the cookie.
     * @param string|null $path The path on the server in which the cookie will be available on.
     *      If set to '/', the cookie will be available within the entire domain.
     *      If set to '/foo/', the cookie will only be available within the /foo/ directory and all sub-directories such as /foo/bar/ of domain.
     *      The default value is the base directory of the application.
     * @param string|null $domain The (sub)domain that the cookie is available to.
     *      Setting this (such as 'example.com') will make the cookie available to that domain and all other
     *      sub-domains of it (i.e. www.example.com). The default is the current domain.
     * @return $this
     */
    public function delete($name, $path = null, $domain = null);
}
