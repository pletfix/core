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
     * Adds routes for each method.
     *
     * @param string $path
     * @param string|\Closure $action Could by a method name or a function.
     */
    public function any($path, $action = null);

    /**
     * Route a resource to a controller.
     *
     * Example : $route->resource('articles', 'ArticleController');
     *
     * Method     | Path                        | Name             | Action                    | Used for
     * ---------------------------------------------------------------------------------------------------------------------------
     * GET|HEAD  | articles                 | articles.index   | ArticleController@index   | Display a list of all articles.
     * GET|HEAD  | articles/create          | articles.create  | ArticleController@create  | Show the form to create a new article.
     * POST      | articles                 | articles.store   | ArticleController@store   | Store a new article to the database.
     * DELETE    | articles/{article}       | articles.destroy | ArticleController@destroy | Delete an article from the database.
     * GET|HEAD  | articles/{article}/edit  | articles.edit    | ArticleController@edit    | Show the form to edit an article.
     * PUT|PATCH | articles/{article}       | articles.update  | ArticleController@update  | Update an article to the database.
     * GET|HEAD  | articles/{article}       | articles.show    | ArticleController@show    | Show a single article (readonly).
     *
     * @param string $path
     * @param string $controller
     */
    public function resource($path, $controller);
}
