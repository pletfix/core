<?php

namespace Core\Services;

use Core\Services\Contracts\Delegate as DelegateContract;
use Core\Middleware\Contracts\Middleware;

class Delegate implements DelegateContract
{
    /**
     * List of Middleware classes.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The index of the next middleware to invoke.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * The target action which is at the end of the delegation list.
     *
     * @var callable
     */
    protected $action;

    /**
     * The parameters of the target action.
     *
     * @var array
     */
    protected $parameters = [];

//    /**
//     * Create a new delegate instance.
//     */
//    public function __construct()
//    {
//    }

    /**
     * @inheritdoc
     */
    public function setMiddleware($classes)
    {
        $this->middleware = $classes;
    }

//    /**
//     * @inheritdoc
//     */
//    public function addMiddleware($class)
//    {
//        $this->middleware[] = $class;
//    }

    /**
     * @inheritdoc
     */
    public function setAction($action, array $parameters)
    {
        $this->action     = $action;
        $this->parameters = $parameters;
    }

    /**
     * @inheritdoc
     */
    public function process(Contracts\Request $request)
    {
        if (isset($this->middleware[$this->index])) {
            /** @var Middleware $middleware */
            $middleware = new $this->middleware[$this->index++];
            return $middleware->process($request, $this);
        }

        $result = call_user_func_array($this->action, $this->parameters);

        if ($result instanceof Response) {
            return $result;
        }

        /** @var Response $response */
        $response = DI::getInstance()->get('response');
        $response->output($result);

        return $response;
    }
}
