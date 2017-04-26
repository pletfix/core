<?php

namespace Core\Exceptions;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param mixed $model
     * @param string $relation
     * @return static
     */
    public static function make($model, $relation) // todo warum nicht direkt den Konstruktor aufrufen?
    {
        $class = get_class($model);

        return new static("Call to undefined relationship [{$relation}] on model [{$class}].");
    }

//    /**
//     * Create a new exception instance.
//     *
//     * @param string $statement The SQL statement.
//     * @param array $bindings Values to bind to the statement
//     * @param string $dump The SQL statement after binding.
//     * @param \Exception $previous
//     */
//    public function __construct($model, $relation, $previous)
//    {
//        $class = get_class($model);
//
//        parent::__construct("Call to undefined relationship [{$relation}] on model [{$class}].", 0, $previous);
//    }
}
