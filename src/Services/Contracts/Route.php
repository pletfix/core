<?php

namespace Core\Services\Contracts;

interface Route
{
    /**
     * Dispatch the request to the application.
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request);

    /**
     * Adds a GET route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function get($path, $action);

    /**
     * Adds a HEAD route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function head($path, $action);

    /**
     * Adds a POST route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function post($path, $action);

    /**
     * Adds a PUT route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function put($path, $action);

    /**
     * Adds a PATCH route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function patch($path, $action);

    /**
     * Adds a DELETE route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function delete($path, $action);

    /**
     * Adds a OPTIONS route.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function options($path, $action);

    /**
     * Adds routes for the method 'GET', 'HEAD', 'POST', 'PUT', 'PATCH' and 'DELETE'.
     *
     * @param string[] $methods Array of HTTP methods ('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE' and 'OPTIONS')
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function multi($methods, $path, $action = null);

    /**
     * Adds routes for the method 'GET', 'HEAD', 'POST', 'PUT', 'PATCH' and 'DELETE'.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function any($path, $action = null);

    /**
     * Route a resource to a CRUD controller.
     *
     * Example : $route->resource('articles', 'ArticleController');
     *
     * Method	 | Path	               | Name             | Action                    | Used for
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
     */
    public function resource($path, $controller);
}
