<?php

namespace Core\Middleware;

use Core\Exceptions\AuthenticationException;
use Core\Services\Contracts\Delegate;
use Core\Middleware\Contracts\Middleware as MiddlewareContract;
use Core\Services\Contracts\Request;
use Core\Services\Contracts\Response;

class Ability implements MiddlewareContract
{
    /**
     * @inheritdoc
     */
    public function process(Request $request, Delegate $delegate, $abilities = null)
    {
        $auth = auth();

        if (!$auth->isLoggedIn()) {
            session()->set('origin_url', request()->fullUrl());
            return redirect('auth/login');
        }

        $pass = false;
        foreach (explode('|', $abilities) as $ability) {
            if ($auth->can($ability)) {
                $pass = true;
                break;
            }
        }

        if (!$pass) {
            abort(Response::HTTP_FORBIDDEN);
        } // @codeCoverageIgnore

        return $delegate->process($request);
    }
}