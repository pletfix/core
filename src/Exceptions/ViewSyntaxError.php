<?php

namespace Core\Exceptions;

use ErrorException;

class ViewSyntaxError extends ErrorException
{
    /**
     * Constructs the exception
     *
     * @param string $message The Exception message to throw.
     * @param string $view The view where the exception is thrown.
     * @param int $lineno [optional] The line number where the exception is thrown.
     * @param \Exception $previous [optional] The previous exception used for the exception chaining.
     */
    public function __construct($message, $view, $lineno = 0, $previous = null)
    {
        parent::__construct($message, 0, E_PARSE, $view, $lineno, $previous);
    }
}