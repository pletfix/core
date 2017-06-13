<?php

namespace Core\Middleware;

use Core\Exceptions\AuthenticationException;
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
        $locale = cookie('locale');

        if ($locale !== config('app.locale')) {
            locale($locale);
        }

        return $delegate->process($request);
    }
}
