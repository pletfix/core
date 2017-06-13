<?php

namespace Core\Services;

use Closure;
use Core\Exceptions\HttpException;
use Core\Services\Contracts\Route as RouteContract;

/**
 * The Route class represents a HTTP Router.
 *
 * @package Core\Services
 */
class Route implements RouteContract
{
    /**
     * The route collection instance.
     * 
     * @var object[]
     */
    private $routes = [];

    /**
     * List of Middleware classes.
     *
     * @var array
     */
    private $middleware = [];

    /**
     * PLugin's controllers.
     *
     * @var array|null
     */
    private $pluginControllers;

    /**
     * Dispatch the request to the application.
     *
     * @param Contracts\Request $request
     * @return Response
     */
    public function dispatch(Contracts\Request $request)
    {
        $route = $this->find($request);
        if (is_null($route)) {
            throw new HttpException(404, 'No matching route found!');
        }

        /** @var \Core\Services\Delegate $delegate */
        $delegate = DI::getInstance()->get('delegate');
        $delegate->setMiddleware($route->middleware);

        $action = $route->action;
        $params = $this->getParameters($request, $route);
        if (is_string($action)) {
            list($class, $method) = explode('@', $action);
            if ($class[0] != '\\') {
                if (file_exists(app_path('Controllers/' .  str_replace('\\', '/', $class) . '.php'))) {
                    $class = '\\App\\Controllers\\' . $class;
                }
                else if (($pluginMiddleware = $this->getPluginController($class)) !== null) {
                    $class = $pluginMiddleware;
                }
                else {
                    $class = '\\Core\\Controllers\\' . $class;
                }
            }
            $controller = new $class;
            $delegate->setAction([$controller, $method], $params);
        }
        else if (is_callable($action)) {
            $delegate->setAction($action, $params);
        }
        else {
            throw new \RuntimeException('Malformed route definition!!');
        }

        return $delegate->process($request);
    }

    /**
     * Get the full qualified class name of the plugin's controller.
     *
     * @param string $class
     * @return null|string
     */
    private function getPluginController($class)
    {
        if ($this->pluginControllers === null) {
            $manifest = manifest_path('plugins/classes.php');
            if (file_exists($manifest)) {
                /** @noinspection PhpIncludeInspection */
                $classes = include $manifest;
                $this->pluginControllers = isset($classes['Controllers']) ? $classes['Controllers'] : [];
            }
            else {
                $this->pluginControllers = [];
            }
        }

        return isset($this->pluginControllers[$class]) ? $this->pluginControllers[$class] : null;
    }

    /**
     * Find the route matching the given request.
     *
     * @param Contracts\Request $request
     * @return object|null
     */
    private function find(Contracts\Request $request)
    {
        $method = $request->method();
        $path   = $request->path();
        foreach ($this->routes as $route) {
            if ($route->method == $method) {
                $pattern = $this->pattern($route);
                if (preg_match($pattern, $path)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Get parameters of the request.
     *
     * @param Contracts\Request $request
     * @param object $route
     * @return array
     */
    private function getParameters(Contracts\Request $request, $route)
    {
        $path    = $request->path();
        $pattern = $this->pattern($route);
        if (preg_match_all($pattern, $path, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        unset($matches[0][0]);
        $matches = array_values($matches[0]);

        return $matches;
    }

    /**
     * Convert the route path to a regular expression pattern.
     *
     * @param $route
     * @return string
     */
    private function pattern($route)
    {
        return '/^' . preg_replace('/\\\{([A-Za-z0-9-._]+)\\\}/', '([A-Za-z0-9-._]+)', preg_quote($route->path, '/')) . '$/';
    }

    /**
     * @inheritdoc
     */
    public function middleware($middleware, Closure $nested = null)
    {
        $previous = $this->middleware;
        $this->middleware = $this->mergeMiddleware((array)$middleware);
        if ($nested !== null) {
            try {
                $nested($this);
            }
            finally {
                $this->middleware = $previous;
            }
        }
    }

    /**
     * Merge one or more Middleware classes with the current middleware list.
     *
     * @param array $middleware
     * @return array
     */
    private function mergeMiddleware(array $middleware)
    {
//        foreach ($middleware as $i => $class) {
//            if ($class[0] != '\\') {
//                if (($pos = strpos($class, ':')) !== false) {
//                    $class = substr($class, 0, $pos);
//                }
//                if (file_exists(app_path('Middleware/' .  $class . '.php'))) {
//                    $middleware[$i] = '\\App\\Middleware\\' . $middleware[$i];
//                }
//                // todo plugin berücksichtigen
//                else {
//                    $middleware[$i] = '\\Core\\Middleware\\' . $middleware[$i];
//                }
//            }
//        }

        return array_unique(array_merge($this->middleware, $middleware));
    }

    /**
     * @inheritdoc
     */
    public function get($path, $action, $middleware = null)
    {
        $this->add('GET',  $path, $action, $middleware);
        $this->add('HEAD', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function head($path, $action, $middleware = null)
    {
        $this->add('HEAD', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function post($path, $action, $middleware = null)
    {
        $this->add('POST', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function put($path, $action, $middleware = null)
    {
        $this->add('PUT', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function patch($path, $action, $middleware = null)
    {
        $this->add('PATCH', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function delete($path, $action, $middleware = null)
    {
        $this->add('DELETE', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function options($path, $action, $middleware = null)
    {
        $this->add('OPTIONS', $path, $action, $middleware);
    }

    /**
     * @inheritdoc
     */
    public function multi(array $methods, $path, $action, $middleware = null)
    {
        foreach ($methods as $method) {
            if (!in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])) {
                throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
            }
            $this->add($method, $path, $action, $middleware);
        }
    }

    /**
     * @inheritdoc
     */
    public function any($path, $action = null, $middleware = null)
    {
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->add($method, $path, $action, $middleware);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function resource($path, $controller, $middleware = null /*, array $options = [] */)
    {
        $var = 'id';
        $this->add('GET',    $path,                          $controller . '@index',   $middleware);
        $this->add('HEAD',   $path,                          $controller . '@index',   $middleware);
        $this->add('GET',    $path . '/create',              $controller . '@create',  $middleware);
        $this->add('HEAD',   $path . '/create',              $controller . '@create',  $middleware);
        $this->add('POST',   $path,                          $controller . '@store',   $middleware);
        $this->add('DELETE', $path . '/{' . $var . '}',      $controller . '@destroy', $middleware);
        $this->add('GET',    $path . '/{' . $var . '}/edit', $controller . '@edit',    $middleware);
        $this->add('HEAD',   $path . '/{' . $var . '}/edit', $controller . '@edit',    $middleware);
        $this->add('PUT',    $path . '/{' . $var . '}',      $controller . '@update',  $middleware);
        $this->add('PATCH',  $path . '/{' . $var . '}',      $controller . '@update',  $middleware);
        $this->add('GET',    $path . '/{' . $var . '}',      $controller . '@show',    $middleware);
        $this->add('HEAD',   $path . '/{' . $var . '}',      $controller . '@show',    $middleware);
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param string $method HTTP method (either 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' or 'OPTIONS')
     * @param string $path Path of the route, e.g. "/photos/{id}/edit"
     * @param Closure|string $action , e.g. "PhotoController@edit"
     * @param string|array|null $middleware
     */
    private function add($method, $path, $action, $middleware = null)
    {
        $this->routes[] = (object)[
            'method'     => $method,
            'path'       => $path,
            'action'     => $action,
            'middleware' => $middleware !== null ? $this->mergeMiddleware((array)$middleware) : $this->middleware
        ];
    }
}