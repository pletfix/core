<?php

namespace Core\Middleware;

use Core\Services\Contracts\Delegate;
use Core\Middleware\Contracts\Middleware as MiddlewareContract;
use Core\Services\Contracts\Request;

class Locale implements MiddlewareContract
{
    /**
     * @inheritdoc
     */
    public function process(Request $request, Delegate $delegate)
    {
        $firstSegment = request()->segment(0);
        if (is_supported_locale($firstSegment) && $firstSegment !== locale()) {
            locale($firstSegment);
        }

        return $delegate->process($request);
    }
}
