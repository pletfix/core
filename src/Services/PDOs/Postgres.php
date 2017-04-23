<?php

namespace Core\Services\PDOs;

use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Builder\PostgresBuilder;
use Core\Services\PDOs\Schemas\PostgresSchema;
use PDO;

/**
 * PostgreSQL Access Layer
 *
 * This class based on Laravel's 5.3 PostgresConnector (License MIT)
 *
 * @see http://php.net/manual/en/ref.pdo-pgsql.php Installing PDO Driver PDO_PGSQL
 * @see https://github.com/illuminate/database/blob/5.3/Connectors/PostgresConnector.php Laravel's 5.3 PostgresConnector on GitHub by Taylor Otwell
 */
class Postgres extends AbstractDatabase
{
    /**
     * Name of the table which the last record was inserted.
     *
     * @var string|null
     */
    private $lastInsertTo;

    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        // set default configuration
        parent::__construct(array_merge([
            'port'    => 5432,
            'charset' => 'utf8',
            'schema'  => 'public',
            'sslmode' => 'prefer',
        ], $config));
    }

    /**
     * @inheritdoc
     */
    protected function createSchema()
    {
        return new PostgresSchema($this);
    }

    /**
     * @inheritdoc
     */
    protected function createBuilder()
    {
        return new PostgresBuilder($this);
    }

    /**
     * @inheritdoc
     */
    protected function makePDO(array $config, array $options)
    {
        $dsn      = $this->getDsn($config);
        $username = $config['username'];
        $password = $config['password'];
        $pdo      = new PDO($dsn, $username, $password, $options);

        $schema = $this->formatSchema(isset($config['schema']) ? $config['schema'] : 'public');
        $statement = "SET search_path to {$schema}";
        $pdo->prepare($statement)->execute();

        $charset = isset($config['charset']) ? $config['charset'] : 'utf8';
        $statement = "SET names '{$charset}'";
        $pdo->prepare($statement)->execute();

        if (isset($config['timezone'])) {
            $statement = "SET time_zone='{$config['timezone']}'";
            $pdo->prepare($statement)->execute();
        }

        if (isset($config['application_name'])) {
            $statement = "SET application_name to '{$config['application_name']}'";
            $pdo->prepare($statement)->execute();
        }

        return $pdo;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    private function getDsn(array $config)
    {
        $host = isset($config['host']) ? "host={$config['host']};" : '';
        $dsn  = "pgsql:{$host}dbname={$config['database']}";

        if (isset($config['port'])) {
            $dsn .= ";port={$config['port']}";
        }
        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }
        if (isset($config['sslcert'])) {
            $dsn .= ";sslcert={$config['sslcert']}";
        }
        if (isset($config['sslkey'])) {
            $dsn .= ";sslkey={$config['sslkey']}";
        }
        if (isset($config['sslrootcert'])) {
            $dsn .= ";sslrootcert={$config['sslrootcert']}";
        }

        return $dsn;
    }

    /**
     * Format the schema for the DSN.
     *
     * @param  array|string  $schema
     * @return string
     */
    private function formatSchema($schema)
    {
        if (is_array($schema)) {
            return '"'.implode('", "', $schema).'"';
        }
        else {
            return '"'.$schema.'"';
        }
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
        if (strncasecmp($statement, 'INSERT INTO ', 12) === 0) {
            $this->lastInsertTo = trim(substr($statement, 12, strpos($statement, ' ', 12)), '"'); //todo testen
        }

        return parent::exec($statement, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId()
    {
        if (is_null($this->lastInsertTo)) {
            return 0;
        }

        $schema = $this->config['schema'];

        // Get the name of the sequence to check; typically it takes the form of `<table>_<column>_seq`.
        /** @noinspection SqlDialectInspection */

        $default = $this->scalar("
            SELECT column_default
            FROM information_schema.columns
            WHERE table_schema = ?
            AND table_name = ?
            AND column_default LIKE 'nextval(%'
        ", [$schema, $this->lastInsertTo]);

        if (!preg_match('/^nextval\(\'(\w+)\'::regclass\)$/s', $default, $match)) {
            return 0;
        }

        return (int)$this->pdo->lastInsertId($match[1]);
    }
}