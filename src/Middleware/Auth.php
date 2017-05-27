<?php

namespace Core\Middleware;

use Core\Services\Contracts\Delegate;
use Core\Middleware\Contracts\Middleware as MiddlewareContract;
use Core\Services\Contracts\Request;

class Auth implements MiddlewareContract
{
    /**
     * @inheritdoc
     */
    public function process(Request $request, Delegate $delegate)
    {
        if (!auth()->isVerified()) {
            return abort(HTTP_STATUS_FORBIDDEN);
        }

        return $delegate->process($request);
    }
}
