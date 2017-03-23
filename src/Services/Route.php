<?php

namespace Core\Services;

use Core\Exceptions\HttpException;
use Core\Services\Contracts\Request;
use Core\Services\Contracts\Response;
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
     * Dispatch the request to the application.
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $route = $this->find($request);
        if (is_null($route)) {
            throw new HttpException(404, 'No matching route found!');
        }

        $action = $route->action;
        $params = $this->getParameters($request, $route);

        if (is_string($action)) {
            list($class, $method) = explode('@', $action);
            if ($class[0] != '\\') {
                $class = '\\App\\Controllers\\' . $class;
            }
            $controller = new $class;
            $result = $controller->$method(...$params);
        }
        else if (is_callable($action)) {
            $result = $action(...$params);
        }
        else {
            throw new \RuntimeException('Malformed route definition!!');
        }

        if ($result instanceof Response) {
            return $result;
        }

        /** @var Response $response */
        $response = DI::getInstance()->get('response');
        $response->output($result);

        return $response;
    }

    /**
     * Find the route matching the given request.
     *
     * @param Request $request
     * @return object|null
     */
    private function find(Request $request)
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
     * @param Request $request
     * @param object $route
     * @return array
     */
    private function getParameters(Request $request, $route)
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
    public function get($path, $action)
    {
        $this->add('GET',  $path, $action);
        $this->add('HEAD', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function head($path, $action)
    {
        $this->add('HEAD', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function post($path, $action)
    {
        $this->add('POST', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function put($path, $action)
    {
        $this->add('PUT', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function patch($path, $action)
    {
        $this->add('PATCH', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function delete($path, $action)
    {
        $this->add('DELETE', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function options($path, $action)
    {
        $this->add('OPTIONS', $path, $action);
    }

    /**
     * @inheritdoc
     */
    public function multi($methods, $path, $action = null)
    {
        foreach ($methods as $method) {
            if (!in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])) {
                throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
            }
            $this->add($method, $path, $action);
        }
    }

    /**
     * @inheritdoc
     */
    public function any($path, $action = null)
    {
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->add($method, $path, $action);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function resource($path, $controller /*, array $options = [] */)
    {
        $var = 'id';
        $this->add('GET',    $path,                          $controller . '@index');
        $this->add('GET',    $path . '/create',              $controller . '@create');
        $this->add('POST',   $path,                          $controller . '@store');
        $this->add('DELETE', $path . '/{' . $var . '}',      $controller . '@destroy');
        $this->add('GET',    $path . '/{' . $var . '}/edit', $controller . '@edit');
        $this->add('PUT',    $path . '/{' . $var . '}',      $controller . '@update');
        $this->add('GET',    $path . '/{' . $var . '}',      $controller . '@show');
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param string $method HTTP method (either 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' or 'OPTIONS')
     * @param string $path Path of the route, e.g. "/photos/{id}/edit"
     * @param \Closure|string $action, e.g. "PhotoController@edit"
     */
    private function add($method, $path, $action)
    {
        $this->routes[] = (object)[
            'method' => $method,
            'path'   => $path,
            'action' => $action
        ];
    }
}