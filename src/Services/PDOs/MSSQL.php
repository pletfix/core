<?php

namespace Core\Services\PDOs;

use Core\Services\Database;
use Core\Services\PDOs\Builders\MSSQLBuilder;
use Core\Services\PDOs\Schemas\MSSQLSchema;
use PDO;

/**
 * Microsoft SQL Server Access Layer
 *
 * This class based on Laravel's 5.3 SqlServerConnector (License MIT)
 *
 * @see http://php.net/manual/en/ref.pdo-sqlsrv.php Installing PDO Driver PDO_SQLSRV
 * @see https://github.com/illuminate/database/blob/5.3/Connectors/SqlServerConnector.php Laravel's 5.3 SqlServerConnector on GitHub by Taylor Otwell
 */
class MSSQL extends Database
{
    /**
     * @inheritdoc
     */
    protected $supportsSavepoints = false;

    /**
     * @inheritdoc
     */
    protected $dateFormat = 'Y-m-d H:i:s.000';

//    /**
//     * Standard Case Insensitive Collation (default by SQL Server)
//     *
//     * @var string
//     */
//    private $collateCI = 'Latin1_General_CI_AS';
//
//    /**
//     * Standard Case Sensitive Collation
//     *
//     * @var string
//     */
//    private $collateCS = 'Latin1_General_CS_AS';

    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        // set default configuration
        parent::__construct(array_merge([
            'port'       => 1433,
            'dateformat' => 'Y-m-d H:i:s.000',
        ], $config));
    }

    /**
     * @inheritdoc
     * @see https://www.mssqltips.com/sqlservertip/1140/how-to-tell-what-sql-server-version-you-are-running/
     */
    public function version()
    {
        static $version;
        if ($version === null) {
            $v = $this->scalar('SELECT @@VERSION');
            $version = (strpos($v, 'Microsoft SQL Server ') === 0) ? substr($v, 21, 4) : $v;
        }

        return $version;
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '[' . str_replace(']', ']]', $name) . ']';
    }

    /**
     * @inheritdoc
     */
    protected function createSchema()
    {
        return new MSSQLSchema($this);
    }

    /**
     * @inheritdoc
     */
    protected function createBuilder()
    {
        return new MSSQLBuilder($this);
    }

    /**
     * @inheritdoc
     */
    protected function makePDO(array $config, array $options)
    {
        //$options[PDO::ATTR_EMULATE_PREPARES] = false; // driver does not support this attribute

        $drivers = $this->getAvailableDrivers();
        if (in_array('dblib', $drivers)) {
            // Convert the GUIDs from binary to a string (see https://github.com/php/php-src/pull/2001)
            if (defined('PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER')) { // attribute was added since PHP 7.0.11
                /** @noinspection PhpUndefinedClassConstantInspection */
                $options[PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER] = true; // @codeCoverageIgnore
            }
            $dsn = $this->getDblibDsn($config);
        }
        elseif (isset($config['odbc']) && in_array('odbc', $drivers)) {
            $dsn = $this->getOdbcDsn($config);
        }
        else {
            $dsn = $this->getSqlSrvDsn($config);
        }

        $username = $config['username'];
        $password = $config['password'];

        return $this->createPDO($dsn, $username, $password, $options);
    }

    /**
     * Return an array of available PDO drivers.
     *
     * @return array
     */
    protected function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * Get the DSN string for a DbLib connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getDblibDsn(array $config)
    {
        $arguments = [
            'host'   => $config['host'] . ':' . $config['port'],
            'dbname' => $config['database'],
        ];

        if (isset($config['appname'])) {
            $arguments['appname'] = $config['appname'];
        }

        if (isset($config['charset'])) {
            $arguments['charset'] = $config['charset'];
        }

        return $this->buildConnectString('dblib', $arguments);
    }

    /**
     * Get the DSN string for an ODBC connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getOdbcDsn(array $config)
    {
        return 'odbc:' . $config['odbc'];
    }

    /**
     * Get the DSN string for a SqlSrv connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getSqlSrvDsn(array $config)
    {
        $arguments = [
            'Server' => $config['host'] . ',' . $config['port'],
        ];

        $arguments['Database'] = $config['database'];

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        if (isset($config['readonly'])) {
            $arguments['ApplicationIntent'] = 'ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] === false) {
            $arguments['ConnectionPooling'] = '0';
        }

        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    private function buildConnectString($driver, array $arguments)
    {
        $options = array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments));

        return $driver.':'.implode(';', $options);
    }
}