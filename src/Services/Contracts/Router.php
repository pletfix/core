<?php

namespace Core\Services\Contracts;

use Closure;

interface Router
{
    /**
     * Dispatch the request to the application.
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request);

    /**
     * Add a prefix for each route in the group with a given URI.
     *
     * If the closure is omit, the prefix is used by all the routes defined below.
     *
     * @param string|array $prefix
     * @param Closure|null $nested
     * @return $this
     */
    public function prefix($prefix, Closure $nested = null);

    /**
     * Define a group of routes embedded in middleware.
     *
     * If the closure is omit, the middleware is used by all the routes defined below.
     *
     * @param string|array $middleware
     * @param Closure|null $nested
     * @return $this
     */
    public function middleware($middleware, Closure $nested = null);

    /**
     * Adds a GET route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function get($path, $action, $middleware = null);

    /**
     * Adds a HEAD route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function head($path, $action, $middleware = null);

    /**
     * Adds a POST route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function post($path, $action, $middleware = null);

    /**
     * Adds a PUT route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function put($path, $action, $middleware = null);

    /**
     * Adds a PATCH route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function patch($path, $action, $middleware = null);

    /**
     * Adds a DELETE route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function delete($path, $action, $middleware = null);

    /**
     * Adds a OPTIONS route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function options($path, $action, $middleware = null);

    /**
     * Adds routes for the method 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' or 'OPTIONS.
     *
     * @param string[] $methods Array of HTTP methods ('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' and 'OPTIONS')
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function multi(array $methods, $path, $action, $middleware = null);

    /**
     * Adds routes for the method 'GET', 'HEAD', 'POST', 'PUT', 'PATCH' and 'DELETE'.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     * @param string|array|null $middleware
     * @return $this
     */
    public function any($path, $action, $middleware = null);

    /**
     * Route a resource to a CRUD controller.
     *
     * Example : $router->resource('articles', 'ArticleController');
     *
     * Method     | Path                   | Name             | Action                    | Used for
     * -----------------------------------------------------------------------------------------------------------------------
     * GET|HEAD  | articles            | articles.index   | ArticleController@index   | Display a list of all articles.
     * GET|HEAD  | articles/create     | articles.create  | ArticleController@create  | Show the form to create a new article.
     * POST      | articles            | articles.store   | ArticleController@store   | Store a new article to the database.
     * DELETE    | articles/{id}       | articles.destroy | ArticleController@destroy | Delete an article from the database.
     * GET|HEAD  | articles/{id}/edit  | articles.edit    | ArticleController@edit    | Show the form to edit an article.
     * PUT|PATCH | articles/{id}       | articles.update  | ArticleController@update  | Update an article to the database.
     * GET|HEAD  | articles/{id}       | articles.show    | ArticleController@show    | Show a single article (readonly).
     *
     * @param string $path
     * @param string $controller
     * @param string|array|null $middleware
     * @return $this
     */
    public function resource($path, $controller, $middleware = null);

    /**
     * Get all routing items.
     *
     * @return object[]
     */
    public function getRoutes();
}
