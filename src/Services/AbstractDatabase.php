<?php

namespace Core\Services;

use Core\Exceptions\QueryException;
use Core\Services\Contracts\Database as DatabaseContract;
use Core\Services\PDOs\Schemas\Contracts\Schema as SchemaContract;
use Closure;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Abstract Database Access Layer
 *
 * The basic functions as perform(), exec() and quote() based on Aura.Sql Extended PDO.
 * The Transaction Handling and cursor() based on Laravel's Connection Class 5.3.
 *
 * @see https://github.com/auraphp/Aura.Sql/blob/3.x/src/AbstractExtendedPdo.php Aura.Sql Extended PDO on GitHub
 * @see https://github.com/illuminate/database/blob/5.3/Connection.php Laravel's Connection Class 5.3 on GitHub
 * @see http://php.net/manual/en/class.pdo.php The PDO class
 * @see http://php.net/manual/en/class.pdostatement.php The PDOStatement class
 * @see https://phpdelusions.net/pdo#query PDO Tutorial
 * @see https://phpdelusions.net/pdo/objects Fetching objects with PDO
 */
abstract class AbstractDatabase implements DatabaseContract
{
    /**
     * PHP Data Object representing a connection to a database.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Connection Settings
     *
     * @var array
     */
    protected $config;

    /**
     * Database Schema
     *
     * @var SchemaContract
     */
    protected $schema;

    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * Determine if the database system supports savepoints.
     *
     * @var bool
     */
    protected $supportsSavepoints = true;

    /**
     * Get the format for database stored dates.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s'; // todo evtl. unnötig, wenn "immer direkt aus config lesen" nicht langsamer ist

    /**
     * Create a new Database instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        if (!empty($this->config['dateformat'])) {
            $this->dateFormat = $this->config['dateformat'];
        }
    }

    /**
     * @inheritdoc
     */
    public function config($key = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function schema()
    {
        if (is_null($this->schema)) {
            $this->schema = $this->makeSchema();
        }

        return $this->schema;
    }

    /**
     * Make a new Schema instance.
     *
     * @return SchemaContract // todo use contract
     */
    abstract protected function makeSchema();

    /**
     * @inheritdoc
     */
    public function version()
    {
        $this->connect();

        try {
            return $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }
        catch (PDOException $e) {
            if ($e->getCode() == 'IM001') { // driver does not support getting attributes (sql server)
                return false;
            }
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function quote($value, $type = PDO::PARAM_STR)
    {
        $this->connect();

        if (!is_array($value)) {
            return $this->pdo->quote($value, $type);
        }

        foreach ($value as $k => $v) {
            $value[$k] = $this->pdo->quote($v, $type);
        }

        return implode(',', $value);
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Connect / Disconnect
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function connect()
    {
        if (isset($this->pdo)) {
            return;
        }

        $this->transactions = 0;

        /** @see http://php.net/manual/de/pdo.constants.php */
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // http://php.net/manual/de/pdo.error-handling.php
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_CASE               => PDO::CASE_NATURAL,
            PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        if (isset($this->config['persistent']) && $this->config['persistent'] == true) {
            $options[PDO::ATTR_PERSISTENT] = true; // http://php.net/manual/de/pdo.connections.php
        }

        $this->pdo = $this->makePDO($this->config, $options);
    }

    /**
     * Make a new PDO instance.
     *
     * This method creates the internal PDO instance.
     * @see http://php.net/manual/en/pdo.construct.php
     *
     * @param array $config
     * @param array $options
     * @return PDO
     */
    abstract protected function makePDO(array $config, array $options);

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        if (!isset($this->pdo)) {
            return;
        }

        $this->transactions = 0;
        $this->pdo = null;
    }

    /**
     * @inheritdoc
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Error- und Event Handling
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function errorCode() // todo ein Fehler sollte ein Exception auslösen, daher diese und errorInfo() nicht notwendig
    {
        $this->connect();

        return $this->pdo->errorCode();
    }

    /**
     * @inheritdoc
     */
    public function errorInfo()
    {
        $this->connect();

        return $this->pdo->errorInfo();
    }

    // todo: Event-Handling
//    /**
//     * Register a database query listener with the connection.
//     *
//     * @param  \Closure  $callback
//     * @return void
//     */
//    public function listen(Closure $callback)
//    {
//        if (isset($this->events)) {
//            $this->events->listen(Events\QueryExecuted::class, $callback);
//        }
//    }

    /**
     * Dump SQL
     *
     * @param string $statement The SQL statement to prepare and execute.
     * @param array $bindings Values to bind to the statement
     * @param bool $return If true, dump will return its output, instead of printing it.
     * @return string|null
     */
    private function dump($statement, array $bindings = [], $return = null)
    {
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? $this->pdo->quote($binding) : $binding; // todo oder immer quote?
            $statement = preg_replace('/\?/', $value, $statement, 1);
            // todo ":name" auflösen
        }

        if ($return) {
            return $statement;
        }

        echo $statement;

        return null;
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Transaction Handling
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function transaction(Closure $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
        }
        catch (Throwable $e) { // Error or Exception (executed only in PHP 7, will not match in PHP 5)
            $this->rollBack();
            throw $e;
        }
        catch (Exception $e) { // Once PHP 5 support is no longer needed, this block can be removed.
            $this->rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            // begin level 1
            $this->connect();
            $this->pdo->beginTransaction();
        }
        else {
            // begin level 2 or higher
            if ($this->supportsSavepoints) {
                $this->pdo->exec('SAVEPOINT trans' . ($this->transactions + 1));
            }
        }

        // process was successful, so we are incrementing the counter
        $this->transactions += 1;
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->pdo->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);
    }

    /**
     * @inheritdoc
     */
    public function rollBack()
    {
        if ($this->transactions <= 1) {
            $this->pdo->rollBack();
        }
        else {
            if ($this->supportsSavepoints) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT trans' . $this->transactions);
            }
        }

        $this->transactions -= 1;
    }

    /**
     * @inheritdoc
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }

    /**
     * @inheritdoc
     */
    public function supportsSavepoints()
    {
        return $this->supportsSavepoints;
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Data Query
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function query($query, array $bindings = [], $class = null)
    {
        $sth = $this->perform($query, $bindings, $class);

        return $sth->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function single($query, array $bindings = [], $class = null)
    {
        $sth = $this->perform($query, $bindings, $class);

        return $sth->fetch();
    }

    /**
     * @inheritdoc
     */
    public function scalar($query, array $bindings = [])
    {
        $sth = $this->perform($query, $bindings);

        return $sth->fetchColumn(0);
    }

    /**
     * @inheritdoc
     */
    public function cursor($query, array $bindings = [], $class = null)
    {
        $sth = $this->perform($query, $bindings, $class);

        while ($row = $sth->fetch()) {
            yield $row;
        }
    }

    /**
     * Performs a sql statement with bound values and returns the resulting PDOStatement.
     *
     * * @see http://php.net/manual/en/pdo.prepare.php
     *
     * @param string $statement The SQL statement to perform.
     * @param array $bindings Values to bind to the statement
     * @param string|null $class Name of the class where the data are mapped to
     * @return PDOStatement
     * @throws Exception
     */
    protected function perform($statement, array $bindings = [], $class = null)
    {
        $this->connect();

        if (empty($bindings)) {
            try {
                if (($sth = $this->pdo->query($statement)) === false) {
                    throw new PDOException('Execute SQL query failed!');
                }
            }
            catch (PDOException $e) {
                throw new QueryException($statement, $bindings, $statement, $e);
            }
        }
        else {
            $sth = $this->pdo->prepare($statement);

            foreach ($bindings as $key => $value) {
                if (is_int($key)) {
                    $key += 1;
                }

                if ($value instanceof DateTimeInterface) {
                    $value = $value->format($this->dateFormat);
                }
                else if ($value === false) { // das macht Eloquent!
                    $value = 0;
                }

                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;

//                if ($value instanceof DateTimeInterface) {
//                    $type = PDO::PARAM_STR;
//                    $value = $value->format($this->dateFormat);
//                }
//                else if (is_int($value)) {
//                    $type = PDO::PARAM_INT;
//                }
//                else if (is_bool($value)) {
//                    $type = PDO::PARAM_BOOL;
////                    if ($value === false) { // das macht Eloquent!
////                        $value = 0;
////                    }
//                }
//                else if (is_null($value)) {
//                    $type = PDO::PARAM_NULL;
//                }
//                else if (is_scalar($value)) {
//                    $type = PDO::PARAM_STR;
//                }
////                else if (is_resource($value)) {
////                    $type = PDO::PARAM_LOB;
////                }
//                else {
//                    $type = gettype($value);
//                    throw new Exception("Cannot bind value of type '{$type}' to placeholder '{$key}'");
//                }

                $sth->bindValue($key, $value, $type);
            }

            try {
                if ($sth->execute() === false) {
                    throw new PDOException('Execute SQL query failed!');
                }
            }
            catch (PDOException $e) {
                throw new QueryException($statement, $bindings, $this->dump($statement, $bindings, true), $e);
            }
        }

        if (!is_null($class)) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $sth->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $class);
        }

        return $sth;
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Data Manipulation
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function exec($statement, array $bindings = [])
    {
        if (empty($bindings)) {
            // Executes a raw, unprepared statement...
            $this->connect();
            try {
                return $this->pdo->exec($statement);
            }
            catch (PDOException $e) {
                throw new QueryException($statement, $bindings, $statement, $e);
            }
        }

        try {
            $sth = $this->perform($statement, $bindings);
        }
        catch (PDOException $e) {
            throw new QueryException($statement, $bindings, $this->dump($statement, $bindings, true), $e);
        }

        return $sth->rowCount();
    }

    /**
     * @inheritdoc
     */
    public function insert($table, array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Cannot insert an empty record.');
        }

        $table = $this->quoteName($table);

        if (is_string(key($data))) {
            // single record will be inserted
            $columns  = implode(', ', array_map([$this, 'quoteName'], array_keys($data)));
            $params   = implode(', ', array_fill(0, count($data), '?'));
            $bindings = array_values($data);
        }
        else {
            // Bulk insert...
            $keys = [];
            foreach ($data as $row) {
                $keys = array_merge($keys, $row);
            }
            $keys     = array_keys($keys);
            $columns  = implode(', ', array_map([$this, 'quoteName'], $keys));
            $params   = implode(', ', array_fill(0, count($keys), '?'));
            $bindings = [];
            $temp     = [];
            foreach ($data as $i => $row) {
                foreach ($keys as $key) {
                    $bindings[] = isset($row[$key]) ? $row[$key] : null;
                }
                $temp[] = $params;
            }
            $params = implode('), (', $temp);
        }

        /** @noinspection SqlDialectInspection */
        return $this->exec("INSERT INTO $table ($columns) VALUES ($params)", $bindings);
    }

    /**
     * @inheritdoc
     */
    public function update($table, array $data, /*array $conditions = [],*/ $where = null, array $bindings = null) // todo sginierung ändern:
    {
        $table = $this->quoteName($table);

        $settings = [];
        if (empty($bindings) || is_int(key($bindings))) {
            // without bindings or using question mark placeholders in where condition
            foreach ($data as $column => $value) {
                $settings[] = $this->quoteName($column) . ' = ?';
            }
            $bindings = array_merge(array_values($data), $bindings ?: []);
        }
        else {
            // using named placeholders in where condition
            $values = [];
            foreach ($data as $column => $value) {
                $key = $column;
                if (isset($bindings[$key])) {
                    $i = 0;
                    do {
                        $key = $column . '_' . (++$i);
                    } while (isset($bindings[$key]) || isset($data[$key]));
                }
                $settings[] = $this->quoteName($column) . ' = :' . $key;
                $values[$key] = $value;
            }
            $bindings = array_merge($values, $bindings);
        }
        $settings = implode(', ', $settings);

        $where = !empty($where) ? "WHERE $where" : '';

        return $this->exec(trim("UPDATE $table SET $settings $where"), $bindings);
    }

    /**
     * @inheritdoc
     */
    public function delete($table, $where = null, array $bindings = null)
    {
        $table = $this->quoteName($table);
        $where = !empty($where) ? "WHERE $where" : '';

        return $this->exec(trim("DELETE FROM $table $where"), $bindings ?: []);
    }

    /**
     * @inheritdoc
     */
    public function truncate($table)
    {
        $table = $this->quoteName($table);

        $this->exec("TRUNCATE TABLE $table");
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId()
    {
        $this->connect();
        $n = $this->pdo->lastInsertId();

        return (int)$n;
    }
}