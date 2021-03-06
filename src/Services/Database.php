<?php

namespace Core\Services;

use Closure;
use Core\Exceptions\QueryException;
use Core\Services\Contracts\Database as DatabaseContract;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Schemas\Contracts\Schema;
use DateTimeInterface;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Abstract Database Access Layer
 *
 * The basic methods as perform(), exec() and quote() based on Aura.Sql Extended PDO ([MIT License](https://github.com/auraphp/Aura.Sql/blob/3.x/LICENSE)).
 * The Transaction Handling and cursor() based on Laravel's Connection Class 5.3 ([MIT License](https://github.com/laravel/laravel/tree/5.3)).
 *
 * @see https://github.com/auraphp/Aura.Sql/blob/3.x/src/AbstractExtendedPdo.php Aura.Sql Extended PDO on GitHub
 * @see https://github.com/illuminate/database/blob/5.3/Connection.php Laravel's Connection Class 5.3 on GitHub
 * @see http://php.net/manual/en/class.pdo.php The PDO class
 * @see http://php.net/manual/en/class.pdostatement.php The PDOStatement class
 * @see https://phpdelusions.net/pdo#query PDO Tutorial
 * @see https://phpdelusions.net/pdo/objects Fetching objects with PDO
 */
abstract class Database implements DatabaseContract
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
     * @var Schema
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
    protected $dateFormat = 'Y-m-d H:i:s';

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
        if ($key === null) {
            return $this->config;
        }

        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function schema()
    {
        if ($this->schema === null) {
            $this->schema = $this->createSchema();
        }

        return $this->schema;
    }

    /**
     * Make a new Schema instance.
     *
     * @return Schema
     */
    abstract protected function createSchema();

    /**
     * @inheritdoc
     */
    public function builder()
    {
        return $this->createBuilder();
    }

    /**
     * @inheritdoc
     */
    public function table($name, $alias = null)
    {
        return $this->createBuilder()->from($name, $alias);
    }

    /**
     * Create a new QueryBuilder instance.
     *
     * @return Builder
     */
    abstract protected function createBuilder();

    /**
     * @inheritdoc
     */
    public function version()
    {
        $this->connect();

        try {
            return $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }
        // @codeCoverageIgnoreStart
        catch (PDOException $e) {
            if ($e->getCode() == 'IM001') { // driver does not support getting attributes (sql server)
                return false;
            }
            throw $e;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @inheritdoc
     */
    public function quote($value)
    {
        $this->connect();

        return $this->pdo->quote($value);
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /*
     * ----------------------------------------------------------------
     * Connect / Disconnect
     * ----------------------------------------------------------------
     */

    /**
     * Make the connection DSN and create a new PDO instance.
     *
     * @param array $config
     * @param array $options
     * @return PDO
     */
    abstract protected function makePDO(array $config, array $options);

    /**
     * Create a new PDO instance.
     *
     * This method creates the internal PDO instance.
     * @see http://php.net/manual/en/pdo.construct.php
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     * @return PDO
     */
    protected function createPDO($dsn, $username, $password, array $options)
    {
        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        if (isset($this->pdo)) {
            return $this;
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

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        if (!isset($this->pdo)) {
            return $this;
        }

        $this->transactions = 0;
        $this->pdo = null;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function reconnect()
    {
        $this->disconnect();
        $this->connect();

        return $this;
    }

//    /*
//     * ----------------------------------------------------------------
//     * Error Handling
//     * ----------------------------------------------------------------
//     */
//
//    /**
//     * @inheritdoc
//     */
//    public function errorCode()
//    {
//        $this->connect();
//
//        return $this->pdo->errorCode();
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function errorInfo()
//    {
//        $this->connect();
//
//        return $this->pdo->errorInfo();
//    }
//
//    /*
//     * ----------------------------------------------------------------
//     * Event Handling
//     * ----------------------------------------------------------------
//     */
//
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
     * @inheritdoc
     */
    public function dump($statement, array $bindings = [], $return = null)
    {
        foreach ($bindings as $key => $value) {
            if (is_string($value)) {
                $value = $this->quote($value);
            }
            if (is_int($key)) {
                $statement = preg_replace('/\?/', $value, $statement, 1);
            }
            else {
                $statement = str_replace($key, $value, $statement);
            }
        }

        if ($return) {
            return $statement;
        }

        echo $statement;

        return null;
    }

    /*
     * ----------------------------------------------------------------
     * Transaction Handling
     * ----------------------------------------------------------------
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
        // @codeCoverageIgnoreStart
        catch (Throwable $e) { // Error or Exception (executed only in PHP 7, will not match in PHP 5)
            $this->rollback();
            throw $e;
        }
        catch (Exception $e) { // Once PHP 5 support is no longer needed, this block can be removed.
            $this->rollback();
            throw $e;
        }
        // @codeCoverageIgnoreEnd

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
    public function rollback()
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
     * ----------------------------------------------------------------
     * Data Query
     * ----------------------------------------------------------------
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
        $result = $sth->fetch();

        return $result !== false ? $result : null;
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
            try {
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
                    $sth->bindValue($key, $value, $type);
                }

                if ($sth->execute() === false) {
                    throw new PDOException('Execute SQL query failed!');
                }
            }
            catch (PDOException $e) {
                throw new QueryException($statement, $bindings, $this->dump($statement, $bindings, true), $e);
            }
        }

        if ($class !== null) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $sth->setFetchMode(PDO::FETCH_CLASS, $class);
        }

        return $sth;
    }

    /*
     * ----------------------------------------------------------------
     * Data Manipulation
     * ----------------------------------------------------------------
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

        $sth = $this->perform($statement, $bindings);

        return $sth->rowCount();
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