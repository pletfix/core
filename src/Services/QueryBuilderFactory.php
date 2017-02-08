<?php

namespace Core\Services;

use Core\Services\Contracts\QueryBuilderFactory as QueryBuilderFactoryContract;
use Aura\SqlQuery\QueryFactory;
use InvalidArgumentException;

/**
 * QueryBuilder Factory
 *
 * Supported Driver:
 * - "mysql"     (MySql)
 * - "pgsql"     (PostgreSQL)
 * - "sqlite"    (SQLite)
 * - "sqlsrv"    (Microsoft SQL Server)
 */
class QueryBuilderFactory implements QueryBuilderFactoryContract
{
    /**
     * Instances of QueryBuilder.
     *
     * @var \Core\Services\Contracts\QueryBuilder[]
     */
    private $builders = [];

    /**
     * Name of the default Database Store.
     *
     * @var string
     */
    private $defaultStore;

    /**
     * Create a new Factory instance.
     */
    public function __construct()
    {
        $this->defaultStore = config('database.default');
    }

    /**
     * Get a QueryBuilder instance by given database store name.
     *
     * @param string|null $name Name of Database store which the QueryBuilder is for
     * @return \Core\Services\Contracts\QueryBuilder
     */
    public function store($name = null)
    {
        if (is_null($name)) {
            $name = $this->defaultStore;
        }

        if (isset($this->builders[$name])) {
            return $this->builders[$name];
        }

        $config = config('database.stores.' . $name);
        if (is_null($config)) {
            throw new InvalidArgumentException('Database store "' . $name . '" is not defined.');
        }

        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('Database Driver for store "' . $name . '" is not specified.');
        }

        // The driver names from Aura.SqlQuery are equal with ours, so we don't need any mapping. :-)
        $driver = $config['driver'];
        if (!in_array($driver, ['mysql', 'pgsql', 'sqlite', 'sqlsrv'])) {
            throw new InvalidArgumentException('Database Driver "' . $config['driver'] . '" is not supported.');
        }

        $auraFactory = new QueryFactory($driver);
        $builder = new QueryBuilder($auraFactory);

        return $this->builders[$name] = $builder;
    }

}