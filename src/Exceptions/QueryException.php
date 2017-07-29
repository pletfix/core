<?php

namespace Core\Exceptions;

use PDOException;

/**
 * SQL Error
 *
 * This class based on Laravl's QueryException
 * @see https://github.com/illuminate/database/blob/5.3/QueryException.php
 */
class QueryException extends PDOException
{
    /**
     * The SQL statement.
     *
     * @var string
     */
    private $statement;

    /**
     * Values to bind to the statement
     *
     * @var array
     */
    private $bindings;

    /**
     * The SQL statement after binding.
     *
     * @var string
     */
    private $dump;

    /**
     * Create a new query exception instance.
     *
     * @param string $statement The SQL statement.
     * @param array $bindings Values to bind to the statement
     * @param string $dump The SQL statement after binding.
     * @param \Exception $previous
     */
    public function __construct($statement, $bindings, $dump, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->message   = $previous->getMessage();
        $this->code      = $previous->getCode();
        $this->statement = $statement;
        $this->bindings  = $bindings;
        $this->dump      = $dump;

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Get the SQL statement.
     *
     * @return string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Get the bindings.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the SQL statement after binding.
     *
     * @return array
     */
    public function getDump()
    {
        return $this->dump;
    }
}
