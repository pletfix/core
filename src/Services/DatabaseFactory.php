<?php

namespace Core\Services;

use Core\Services\Contracts\DatabaseFactory as DatabaseFactoryContract;
use Core\Services\PDOs\MySQL;
use Core\Services\PDOs\PostgreSQL;
use Core\Services\PDOs\SQLite;
use Core\Services\PDOs\MSSQL;
use InvalidArgumentException;

/**
 * Database Factory
 *
 * Supported Driver:
 * - MSSQL
 * - MySQL
 * - PostgreSQL
 * - SQLite
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

        switch ($config['driver']) {
            case 'MySQL':
                $db = new MySQL($config);
                break;
            case 'PostgreSQL':
                $db = new PostgreSQL($config);
                break;
            case 'SQLite':
                $db = new SQLite($config);
                break;
            case 'MSSQL':
                $db = new MSSQL($config);
                break;
            default:
                throw new InvalidArgumentException('Database driver "' . $config['driver'] . '" is not supported.');
        }

        return $this->databases[$name] = $db;
    }
}