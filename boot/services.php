<?php

$di = \Core\Services\DI::getInstance();

/*
 * Multiple Instance (not shared)
 */

$di->set('collection',              \Core\Services\Collection::class, false);
$di->set('date-time',               \Core\Services\DateTime::class, false);
$di->set('mailer',                  \Core\Services\Mailer::class, false);
$di->set('migrator',                \Core\Services\Migrator::class, false);
$di->set('paginator',               \Core\Services\Paginator::class, false);
$di->set('plugin-manager',          \Core\Services\PluginManager::class, false);
$di->set('view',                    \Core\Services\View::class, false);
$di->set('view-compiler',           \Core\Services\ViewCompiler::class, false);

/*
 * Singleton Instance (shared)
 */

$di->set('asset-manager',           \Core\Services\AssetManager::class, true);
$di->set('auth',                    \Core\Services\Auth::class, true);
$di->set('cache-factory',           \Core\Services\CacheFactory::class, true);
$di->set('command-factory',         \Core\Services\CommandFactory::class, true);
$di->set('config',                  \Core\Services\Config::class, true);
$di->set('cookie',                  \Core\Services\Cookie::class, true);
$di->set('database-factory',        \Core\Services\DatabaseFactory::class, true);
$di->set('delegate',                \Core\Services\Delegate::class, true);
$di->set('exception-handler',       \Core\Handler\ExceptionHandler::class, true);
$di->set('flash',                   \Core\Services\Flash::class, true);
$di->set('logger',                  \Core\Services\Logger::class, true);
$di->set('request',                 \Core\Services\Request::class, true);
$di->set('response',                \Core\Services\Response::class, true);
$di->set('router',                  \Core\Services\Router::class, true);
$di->set('session',                 \Core\Services\Session::class, true);
$di->set('shutdown-handler',        \Core\Handler\ShutdownHandler::class, true);
$di->set('stdio',                   \Core\Services\Stdio::class, true);
$di->set('translator',              \Core\Services\Translator::class, true);
