<?php

//namespace Psr\Http\ServerMiddleware;
namespace Core\Middleware\Contracts;

use Core\Services\Contracts\Delegate;

//use Psr\Http\Message\ServerRequestInterface as Request;
use Core\Services\Contracts\Request;

//use Psr\Http\Message\ResponseInterface as Response;
use Core\Services\Contracts\Response;

/**
 * This interface is prepared for the PSR-13 standard of the PHP Framework Interop Group. The specification is still
 * in the draft state, so the original interfaces can not yet be obtained from fig.org.
 *
 * @see http://www.php-fig.org/psr/#draft PHP Standards Recommendations
 * @see https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware.md HTTP Server Middleware (PSR-13)
 */
interface Middleware
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param Delegate $delegate
     * @return Response
     */
    public function process(Request $request, Delegate $delegate);
}
