<?php

namespace Core\Services\Contracts;

use Closure;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Schemas\Contracts\Schema;
use Exception;
use Generator;

/**
 * Database Access Layer
 */
interface Database
{
    /**
     * Gets the database configuration.
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function config($key = null);

    /**
     * Gets the database schema.
     *
     * @return Schema
     */
    public function schema();

    /**
     * Create a new QueryBuilder instance.
     *
     * @return Builder
     */
    public function builder();

    /**
     * Gets the QueryBuilder for the given table.
     *
     * @param string $name Name of the table
     * @param string|null $alias [optional] The alias name for the table.
     * @return Builder
     */
    public function table($name, $alias = null);

    /**
     * Return server version.
     *
     * Returns FALSE if the driver does not support getting attributes.
     *
     * @return string|false
     */
    public function version();

    /**
     * Quotes a value for use in an SQL statement.
     *
     * @param mixed $value The value to quote.
     * @return string The quoted value.
     */
    public function quote($value);

    /**
     * Quotes a single identifier name (e.g. table, column or index name).
     * @param string $name
     * @return string
     */
    public function quoteName($name);

    /**
     * Connects to the database.
     *
     * @return $this;
     */
    public function connect();

    /**
     * Disconnects from the database.
     *
     * @return $this
     */
    public function disconnect();

    /**
     * Reconnect to the database.
     *
     * @return $this
     */
    public function reconnect();

//    /**
//     * Gets the most recent error code.
//     *
//     * @return mixed
//     */
//    public function errorCode();
//
//    /**
//     * Gets the most recent error info.
//     *
//     * @return array
//     */
//    public function errorInfo();

    /**
     * Dump SQL
     *
     * The method binds the given values to a SQL statement and print it out without executing.
     *
     * @param string $statement The SQL statement..
     * @param array $bindings [optional] Values to bind to the statement
     * @param bool|null $return [optional] If used and set to true, dump will return the variable representation instead of outputing it.
     * @return string|null The SQL statement when the return parameter is true. Otherwise, this function will return null.
     */
    public function dump($statement, array $bindings = [], $return = null);

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure $callback
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback);

    /**
     * Start a new database transaction.
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     */
    public function rollback();

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel();

    /**
     * Determines if the driver can marks the current point within a transaction.
     *
     * @return bool
     */
    public function supportsSavepoints();

    /**
     * Fetches a sequential array of objects from the database.
     *
     * If the class name is not set, the rows are returned as associative arrays.
     *
     * @param string $query The SQL statement to prepare and execute.
     * @param array $bindings Values to bind to the query
     * @param string|null $class Name of the class where the data are mapped to
     * @return array
     */
    public function query($query, array $bindings = [], $class = null);

    /**
     * Run a select statement and return a single object.
     *
     * If the class name is not set, an associative array is returned.
     * If the record is not exist, NULL is returned.
     *
     * @param string $query The SQL statement to prepare and execute.
     * @param array $bindings Values to bind to the query
     * @param string|null $class Name of the class where the data are mapped to
     * @return mixed
     */
    public function single($query, array $bindings = [], $class = null);

    /**
     * Fetches the first value (first column of the first row).
     *
     * @param string $query The SQL statement to prepare and execute.
     * @param array $bindings Values to bind to the query.
     * @return mixed
     */
    public function scalar($query, array $bindings = []);

    /**
     * Run a select statement against the database and returns a generator.
     *
     * With the cursor you could iterate the rows (via foreach) without fetch all the data at one time.
     * This method is useful to handle big data.
     *
     * @see http://php.net/manual/de/language.generators.syntax.php PHP Generator
     *
     * @param string $query
     * @param array $bindings
     * @param string|null $class Name of the class where the data are mapped to
     * @return Generator
     */
    public function cursor($query, array $bindings = [], $class = null);

    /**
     * Executes a statement and returns the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     * @param array $bindings Values to bind to the statement
     * @return int
     * @throws Exception
     */
    public function exec($statement, array $bindings = []);

    /**
     * Returns the last inserted autoincrement sequence value.
     *
     * If you don't insert any values before, the function returns 0.
     *
     * If you insert multiple rows using a single insert() call, lastInsertId() returns dependency of the driver
     * the first or last inserted row.
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     *
     * @return int
     */
    public function lastInsertId();
}