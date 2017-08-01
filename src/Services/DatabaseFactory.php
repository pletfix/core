<?php

namespace Core\Services;

use Core\Services\Contracts\DatabaseFactory as DatabaseFactoryContract;
use Core\Services\PDOs\MySql;
use Core\Services\PDOs\Postgres;
use Core\Services\PDOs\SQLite;
use Core\Services\PDOs\SqlServer;
use InvalidArgumentException;

/**
 * Database Factory
 *
 * Supported Driver:
 * - "mysql"     (MySql)
 * - "pgsql"     (PostgreSQL)
 * - "sqlite"    (SQLite)
 * - "sqlsrv"    (Microsoft SQL Server)
 */
class DatabaseFactory implements DatabaseFactoryContract
{
    /**
     * Instances of Databases.
     *
     * @var \Core\Services\Contracts\Database[]
     */
    private $databases = [];

    /**
     * Name of the default store.
     *
     * @var string
     */
    private $defaultStore;

    /**
     * Create a new factory instance.
     */
    public function __construct()
    {
        $this->defaultStore = config('database.default');
    }

    /**
     * @inheritdoc
     */
    public function store($name = null)
    {
        if ($name === null) {
            $name = $this->defaultStore;
        }

        if (isset($this->databases[$name])) {
            return $this->databases[$name];
        }

        $config = config('database.stores.' . $name);
        if ($config === null) {
            throw new InvalidArgumentException('Database store "' . $name . '" is not defined.');
        }

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('Database driver for store "' . $name . '" is not specified.');
        }

        switch ($config['driver']) { // todo use class name such like "MySQL"
            case 'mysql':
                $db = new MySql($config);
                break;
            case 'pgsql':
                $db = new Postgres($config);
                break;
            case 'sqlite':
                $db = new SQLite($config);
                break;
            case 'sqlsrv':
                $db = new SqlServer($config);
                break;
            default:
                throw new InvalidArgumentException('Database driver "' . $config['driver'] . '" is not supported.');
        }

        return $this->databases[$name] = $db;
    }
}