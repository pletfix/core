<?php

namespace Core\Services\Contracts;

use Core\Services\PDOs\Schemas\Contracts\Schema as SchemaContract;
use Closure;
use Exception;
use Generator;
use PDO;

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
     * @return SchemaContract
     */
    public function schema();

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
     * This differs from `PDO::quote()` in that it will convert an array into a string of comma-separated quoted values.
     *
     * @param mixed $value The value to quote.
     * @param int $type PDO type
     * @return string The quoted value.
     */
    public function quote($value, $type = PDO::PARAM_STR);

    /**
     * Quotes a single identifier name (e.g. table, column or index name).
     * @param string $name
     * @return string
     */
    public function quoteName($name);

    /**
     * Connects to the database.
     */
    public function connect();

    /**
     * Disconnects from the database.
     */
    public function disconnect();

    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect();

    /**
     * Gets the most recent error code.
     *
     * @return mixed
     */
    public function errorCode();

    /**
     * Gets the most recent error info.
     *
     * @return array
     */
    public function errorInfo();

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
    public function rollBack();

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
     * Find a single record by the primary key of given table.
     *
     * @param string $table Name of the Table
     * @param int $id Value of the primary Key
     * @param string $key Name of the primary Key
     * @param string|null $class Name of the class where the data are mapped to
     * @return mixed
     */
    public function find($table, $id, $key = 'id', $class = null);

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
     * Insert rows to the given table and returns the number of affected rows.
     *
     * @param string $table The table to update rows from
     * @param array $data Values to be updated
     * @return int
     */
    public function insert($table, array $data);

    /**
     * Update a table with th given data and returns the number of affected rows.
     *
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $bindings
     * @return int
     */
    public function updateWhere($table, array $data, $where = null, array $bindings = []);

    /**
     * Update a table with th given data and returns the number of affected rows.
     *
     * @param string $table The table to update rows from
     * @param array $data Values to be updated
     * @param array $conditions Conditions to be set for update statement
     * @return int
     */
    public function update($table, array $data, array $conditions = []);

    /**
     * Delete rows from a table and returns the number of affected rows.
     *
     * @param string $table
     * @param string $where
     * @param array $bindings
     * @return int
     */
    public function deleteWhere($table, $where = null, array $bindings = []);

    /**
     * Delete rows from a table and returns the number of affected rows.
     *
     * @param string $table The table to delete rows from.
     * @param array $conditions Conditions to be set for delete statement
     * @return int
     */
    public function delete($table, $conditions = []);

    /**
     * Truncate a table.
     *
     * @param string $table
     */
    public function truncate($table);

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