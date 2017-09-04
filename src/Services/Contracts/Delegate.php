<?php

namespace Core\Services\Contracts;

interface Delegate
{
    /**
     * Set the list of Middleware classes.
     *
     * @param array $classes
     * @return $this
     */
    public function setMiddleware($classes);

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
