<?php

namespace Core\Middleware;

use Core\Exceptions\AuthenticationException;
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
        if (!auth()->isLoggedIn()) {
            session()->set('origin_url', request()->fullUrl());
            return redirect('auth/login');
        }

        return $delegate->process($request);
    }
}
