<?php

namespace Core\Middleware;

use Core\Services\Contracts\Delegate;
use Core\Middleware\Contracts\Middleware as MiddlewareContract;
use Core\Services\Contracts\Request;

class Role implements MiddlewareContract
{
    /**
     * @inheritdoc
     */
    public function process(Request $request, Delegate $delegate, $roles = null)
    {
        $auth = auth();

        if (!$auth->isLoggedIn()) {
            session()->set('origin_url', request()->fullUrl());
            return redirect('auth/login');
        }

        $pass = false;
        foreach (explode('|', $roles) as $role) {
            if ($auth->is($role)) {
                $pass = true;
                break;
            }
        }

        if (!$pass) {
            abort(HTTP_STATUS_FORBIDDEN);
        }

        return $delegate->process($request);
    }
}
