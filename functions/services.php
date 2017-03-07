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
     * @param string $dateTime
     * @return \Core\Services\Contracts\DateTime
     */
    function datetime($dateTime = 'now')
    {
        return DI::getInstance()->get('date-time', [$dateTime]);
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

if (!function_exists('query_builder')) {
    /**
     * Get a SQL Query Builder.
     *
     * @param string|null $store Name of Database store which the sql statement is for
     * @return \Core\Services\Contracts\QueryBuilder
     */
    function query_builder($store = null)
    {
        return DI::getInstance()->get('query-builder-factory')->store($store);
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

        return $view->render($name, $variables);
    }
}


//////////////////////////////////////////////////////
// Baustelle

if (!function_exists('moment')) {
    /**
     * Get a moment.
     *
     * todo Ich hab mich noch nicht entschieden, diese Lib einzusetzen. Wenn ja, muss ein Adapter darauf gesetzt werden.
     * todo Eignes Interface verwenden!
     *
     * @param string $dateTime
     * @return \Moment\Moment
     */
    function moment($dateTime = 'now')
    {
        return new \Moment\Moment($dateTime);
    }
}
