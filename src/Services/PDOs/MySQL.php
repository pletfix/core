<?php

namespace Core\Services\PDOs;

use Core\Services\Database;
use Core\Services\PDOs\Builders\MySQLBuilder;
use Core\Services\PDOs\Schemas\MySQLSchema;
use PDO;

/**
 * MySQL Access Layer
 *
 * This class based on Laravel's 5.3 MySqlConnector (License MIT)
 *
 * @see http://php.net/manual/en/ref.pdo-mysql.php Installing PDO Driver PDO_MYSQL
 * @see https://github.com/illuminate/database/blob/5.3/Connectors/MySqlConnector.php Laravel's 5.3 MySqlConnector on GitHub by Taylor Otwell
 */
class MySQL extends Database
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        // set default configuration
        parent::__construct(array_merge([
            'port'         => 3306,
            'charset'      => 'utf8',
            'collation'    => 'utf8_unicode_ci',
            //'collation_cs' => 'latin1_general_cs',
            'persistent'   => false,
            'strict'       => true,
        ], $config));
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * @inheritdoc
     */
    protected function createSchema()
    {
        return new MySQLSchema($this);
    }

    /**
     * @inheritdoc
     */
    protected function createBuilder()
    {
        return new MySQLBuilder($this);
    }

    /**
     * @inheritdoc
     */
    protected function makePDO(array $config, array $options)
    {
        $options[PDO::ATTR_EMULATE_PREPARES] = false; // fetch int, float and boolean as numeric value (decimal/numeric is still a string)

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $username = $config['username'];
        $password = $config['password'];
        $pdo      = $this->createPDO($dsn, $username, $password, $options);

        $statement = "USE `{$config['database']}`;";
        $pdo->exec($statement);

        if (isset($config['charset'])) {
            $statement = "SET NAMES '{$config['charset']}'" . (isset($config['collation']) ? " COLLATE '{$config['collation']}'" : '');
            $pdo->exec($statement);
        }

        if (isset($config['timezone'])) {
            $statement = "SET time_zone='{$config['timezone']}'";
            $pdo->exec($statement);
        }

        if (isset($config['strict'])) {
            if ($config['strict']) {
                $statement = "SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
            } else {
                $statement = "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'";
            }
            $pdo->exec($statement);
        }

        return $pdo;
    }
}