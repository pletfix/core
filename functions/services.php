<?php

use Core\Services\DI;

if (!function_exists('asset_manager')) {
    /**
     * Get the Asset Manager.
     *
     * @return \Core\Services\Contracts\AssetManager
     */
    function asset_manager()
    {
        return DI::getInstance()->get('asset-manager');
    }
}

if (!function_exists('auth')) {
    /**
     * Get the authentication object.
     *
     * @return \Core\Services\Contracts\Auth
     */
    function auth()
    {
        return DI::getInstance()->get('auth');
    }
}

if (!function_exists('cache')) {
    /**
     * Get the cache by given store name.
     *
     * @param string|null $store
     * @return \Core\Services\Contracts\Cache
     */
    function cache($store = null)
    {
        return DI::getInstance()->get('cache-factory')->store($store);
    }
}

if (! function_exists('collect')) {
    /**
     * Create a collection from the given array.
     *
     * @param array $items
     * @return \Core\Services\Contracts\Collection
     */
    function collect($items)
    {
        return DI::getInstance()->get('collection', [$items]);
    }
}

if (!function_exists('cookie')) {
    /**
     * Return a cookie
     *
     * @param string|Closure|null $name The name of the cookie.
     * @param mixed $default
     * @return \Core\Services\Contracts\Cookie|string
     */
    function cookie($name = null, $default = null)
    {
        $cookie = DI::getInstance()->get('cookie');
        if ($name === null) {
            return $cookie;
        }

        return $cookie->get($name, $default);
    }
}

if (!function_exists('database')) {
    /**
     * Get the database by given connection name.
     *
     * @param string|null $store
     * @return \Core\Services\Contracts\Database
     */
    function database($store = null)
    {
        return DI::getInstance()->get('database-factory')->store($store);
    }
}

if (!function_exists('datetime')) {
    /**
     * Returns a new DateTime object.
     *
     * @param DateTimeInterface|array|int|string|null $dateTime
     * @param DateTimeZone|string|null $timezone
     * @param string|null $format
     * @return \Core\Services\Contracts\DateTime
     */
    function datetime($dateTime = null, $timezone = null, $format = null)
    {
        /** @var \Core\Services\Contracts\DateTime $service */
        static $service;
        if ($service === null) { // promote fast access...
            $service = DI::getInstance()->get('date-time');
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $service::instance($dateTime);
        }

        if (is_array($dateTime)) {
            return $service::createFromParts($dateTime, $timezone);
        }

        if (is_int($dateTime)) {
            return $service::createFromTimestamp($dateTime, $timezone);
        }

        if ($format !== null) {
            if ($format == 'locale') {
                return $service::createFromLocaleFormat($dateTime, $timezone);
            }
            else if ($format == 'locale.date') {
                return $service::createFromLocaleDateFormat($dateTime, $timezone);
            }
            else if ($format == 'locale.time') {
                return $service::createFromLocaleTimeFormat($dateTime, $timezone);
            }
            return $service::createFromFormat($format, $dateTime, $timezone);
        }

        return new $service($dateTime, $timezone);
    }
}

if (!function_exists('di')) {
    /**
     * Get the available service instance (or the Dependency Injector if no key is specified).
     *
     * @param string $name Name of the service
     * @param  array $arguments Arguments of the constructor
     * @return \Core\Services\Contracts\DI|object
     */
    function di($name = null, $arguments = [])
    {
        if (is_null($name)) {
            return DI::getInstance();
        }

        return DI::getInstance()->get($name, $arguments);
    }
}

if (!function_exists('flash')) {
    /**
     * Get the Flash object.
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return \Core\Services\Contracts\Flash|mixed
     */
    function flash($key = null, $default = null)
    {
        $flash = DI::getInstance()->get('flash');
        if ($key === null) {
            return $flash;
        }

        return $flash->get($key, $default);
    }
}

if (!function_exists('logger')) {
    /**
     * Get the Logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    function logger()
    {
        return DI::getInstance()->get('logger');
    }
}

if (!function_exists('mailer')) {
    /**
     * Get the Mailer.
     *
     * @return \Core\Services\Contracts\Mailer
     */
    function mailer()
    {
        return DI::getInstance()->get('mailer');
    }
}

if (!function_exists('migrator')) {
    /**
     * Get the Migrator for the given store.
     *
     * @param string|null $store Name of the database store
     * @param string|null $path Subfolder in the migration directory
     * @return \Core\Services\Contracts\Migrator
     */
    function migrator($store = null, $path = null)
    {
        return DI::getInstance()->get('migrator', [$store, $path]);
    }
}

if (!function_exists('plugin_manager')) {
    /**
     * Get the Plugin Manager.
     *
     * @param string $package Name of the plugin with vendor, e.g. foo/bar.
     * @return \Core\Services\Contracts\PluginManager
     */
    function plugin_manager($package)
    {
        return DI::getInstance()->get('plugin-manager', [$package]);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get a redirect response to the given path.
     *
     * @param string $path
     * @param array $parameters
     * @param array $flash
     * @param int $status
     * @param array $headers
     * @return \Core\Services\Contracts\Response
     */
    function redirect($path = '', $parameters = [], $flash = [], $status = 302, $headers = [])
    {
        if (!empty($flash)) {
            DI::getInstance()->get('flash')->merge(null, $flash);
        }

        $url = DI::getInstance()->get('request')->baseUrl() . (!empty($path) ? '/' . $path : '') . (!empty($parameters) ? '?' . http_build_query($parameters) : '');

        return DI::getInstance()->get('response')->redirect($url, $status, $headers);
    }
}

if (!function_exists('request')) {
    /**
     * Get the Request Object
     *
     * @return \Core\Services\Contracts\Request
     */
    function request()
    {
        return DI::getInstance()->get('request');
    }
}

if (!function_exists('response')) {
    /**
     * Get the Response Object
     *
     * @return \Core\Services\Contracts\Response
     */
    function response()
    {
        return DI::getInstance()->get('response');
    }
}

//if (!function_exists('route')) {
//    /**
//     * Get the Route Service
//     *
//     * @return \Core\Services\Contracts\Route
//     */
//    function route()
//    {
//        return DI::getInstance()->get('route');
//    }
//}

if (!function_exists('session')) {
    /**
     * Get the Session.
     *
     * @param string|Closure|null $keyOrCallback Key using "dot" notation or callback function to writing data.
     * @param mixed $default
     * @return \Core\Services\Contracts\Session|mixed
     */
    function session($keyOrCallback = null, $default = null)
    {
        /** @var \Core\Services\Contracts\Session $session */
        $session = DI::getInstance()->get('session');
        if ($keyOrCallback === null) {
            return $session;
        }

        if (is_callable($keyOrCallback)) {
            return $session->lock($keyOrCallback);
        }

        return $session->get($keyOrCallback, $default);
    }
}

if (!function_exists('stdio')) {
    /**
     * Get the standard input/output streams.
     *
     * @param resource $stdin  Standard input stream
     * @param resource $stdout Standard output stream.
     * @param resource $stderr Standard error stream.
     * @return \Core\Services\Contracts\Stdio
     */
    function stdio($stdin = null, $stdout = null, $stderr = null)
    {
        return DI::getInstance()->get('stdio', [$stdin, $stdout, $stderr]);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string|null $name Name of the view
     * @param array $variables
     * @return string|\Core\Services\Contracts\View
     */
    function view($name = null, $variables = [])
    {
        $view = DI::getInstance()->get('view');
        if ($name === null) {
            return $view;
        }

        return $view->render($name, $variables); // todo ein Response zurück geben
    }
}