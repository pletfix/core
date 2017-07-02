<?php

//namespace Psr\Http\ServerMiddleware;
namespace Core\Services\Contracts;

//use Psr\Http\Message\ServerRequestInterface as Request;
//use Core\Services\Contracts\Response;

//use Psr\Http\Message\ResponseInterface as Response;
//use Core\Services\Contracts\Request;

/**
 * This interface is prepared for the PSR-13 standard of the PHP Framework Interop Group. The specification is still
 * in the draft state, so the original interfaces can not yet be obtained from fig.org.
 *
 * @see http://www.php-fig.org/psr/#draft PHP Standards Recommendations
 * @see https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md HTTP Server Middleware (PSR-13)
 */
interface Delegate
{
    /**
     * Set the list of Middleware classes.
     *
     * @param array $classes
     * @return $this
     */
    public function setMiddleware($classes);

//    /**
//     * Add a Middleware class to the delegation list.
//     *
//     * @param string $class
//     */
//    public function addMiddleware($class);

    /**
     * The target action which is at the end of the delegation list.
     *
     * @param callable $action
     * @param array $parameters
     * @return $this
     */
    public function setAction($action, array $parameters);

    /**
     * Dispatch the next available middleware and return the response.
     *
     * If no middleware available, the target action is invoked.
     *
     * @param Request $request
     * @return Response
     */
    public function process(Request $request);
}
