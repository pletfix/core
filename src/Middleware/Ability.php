<?php

namespace Core\Middleware;

use Core\Services\Contracts\Delegate;
use Core\Middleware\Contracts\Middleware as MiddlewareContract;
use Core\Services\Contracts\Request;

class Ability implements MiddlewareContract
{
    /**
     * @inheritdoc
     */
    public function process(Request $request, Delegate $delegate, $abilities = null)
    {
        $auth = auth();
        $pass = false;
        foreach (explode('|', $abilities) as $ability) {
            if ($auth->can($ability)) {
                $pass = true;
                break;
            }
        }

        if (!$pass) {
            return abort(HTTP_STATUS_FORBIDDEN);
        }

        return $delegate->process($request);
    }
}