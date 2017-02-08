<?php

namespace Core\Services;

use Core\Exceptions\HttpException;
use Core\Services\Contracts\Request;
use Core\Services\Contracts\Response;
use Core\Services\Contracts\Route as RouteContract;

class Route implements RouteContract
{
    /**
     * The route collection instance.
     * 
     * @var object[]
     */
    protected $routes = [];

//    /**
//     * Create a new Route instance.
//     */
//    public function __construct()
//    {
//    }

    /**
     * Dispatch the request to the application.
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $route = $this->findRoute($request);
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
    private function findRoute(Request $request)
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
        return '/^' . preg_replace('/\\\{(\w+)\\\}/', '(\w+)', preg_quote($route->path, '/')) . '$/';
    }

    /**
     * Adds a GET route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function get($path, $action)
    {
        $this->addRoute('GET', $path, $action);
    }

    /**
     * Adds a HEAD route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function head($path, $action)
    {
        $this->addRoute('HEAD', $path, $action);
    }

    /**
     * Adds a POST route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function post($path, $action)
    {
        $this->addRoute('POST', $path, $action);
    }

    /**
     * Adds a PUT route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function put($path, $action)
    {
        $this->addRoute('PUT', $path, $action);
    }

    /**
     * Adds a PATCH route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function patch($path, $action)
    {
        $this->addRoute('PATCH', $path, $action);
    }

    /**
     * Adds a DELETE route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function delete($path, $action)
    {
        $this->addRoute('DELETE', $path, $action);
    }

    /**
     * Adds routes for each method.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function any($path, $action = null)
    {
        $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $action);
    }

    /**
     * Route a resource to a controller.
     *
     * Example : $route->resource('articles', 'ArticleController');
     *
     * Method	 | Path	                    | Name             | Action                    | Used for
     * ---------------------------------------------------------------------------------------------------------------------------
     * GET|HEAD  | articles                 | articles.index   | ArticleController@index   | Display a list of all articles.
     * GET|HEAD  | articles/create          | articles.create  | ArticleController@create  | Show the form to create a new article.
     * POST      | articles                 | articles.store   | ArticleController@store   | Store a new article to the database.
     * DELETE    | articles/{article}       | articles.destroy | ArticleController@destroy | Delete an article from the database.
     * GET|HEAD  | articles/{article}/edit  | articles.edit    | ArticleController@edit    | Show the form to edit an article.
     * PUT|PATCH | articles/{article}       | articles.update  | ArticleController@update  | Update an article to the database.
     * GET|HEAD  | articles/{article}       | articles.show    | ArticleController@show    | Show a single article (readonly).
     *
     * todo Name wird nicht unterstützt!
     *
     * @param string $path
     * @param string $controller
     */
    public function resource($path, $controller /*, array $options = [] */)
    {

        $var = 'id';
        $this->get(   $path,                          $controller . '@index');
        $this->get(   $path . '/create',              $controller . '@create');
        $this->post(  $path,                          $controller . '@store');
        $this->delete($path . '/{' . $var . '}',      $controller . '@destroy');
        $this->get(   $path . '/{' . $var . '}/edit', $controller . '@edit');
        $this->put(   $path . '/{' . $var . '}',      $controller . '@update');
        $this->get(   $path . '/{' . $var . '}',      $controller . '@show');
    }

    /**
     * Add a route to the underlying route collection.
     *
     * @param string|string[] $method HTTP method (GET, POST, PUT, PATCH or DELETE)
     * @param string $path Path of the route, e.g. "/photos/:id/edit"
     * @param \Closure|string $action, e.g. "PhotoController@edit"
     */
    protected function addRoute($method, $path, $action)
    {
        $this->routes[] = (object)[
            'method' => $method,
            'path'   => $path,
            'action' => $action
        ];
    }
}