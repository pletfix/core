<?php

namespace Core\Services\PDOs;

use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Schemas\SQLiteSchema;
use InvalidArgumentException;
use PDO;

/**
 * SQLite Access Layer
 *
 * This class based on Laravel's 5.3 SQLiteConnector (License MIT)
 *
 * @see http://php.net/manual/en/ref.pdo-sqlite.php Installing PDO Driver PDO_SQLITE
 * @see https://github.com/illuminate/database/blob/5.3/Connectors/SQLiteConnector.php Laravel's 5.3 SQLiteConnector on GitHub by Taylor Otwell
 */
class SQLite extends AbstractDatabase
{
    /**
     * Name of the table which the last record was inserted.
     *
     * @var string|null
     */
    private $lastInsertTo;

//    /**
//     * Case Insensitive Collation (default by database access layer)
//     *
//     * @var string
//     */
//    private $collateCI = 'NOCASE';
//
//    /**
//     * Case Sensitive Collation (default by sqlite)
//     *
//     * @var string
//     */
//    private $collateCS = 'BINARY';

    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        // set default configuration
        parent::__construct(array_merge([
            'database' => storage_path('db/sqlite.db'),
        ], $config));
    }

    /**
     * @inheritdoc
     */
    protected function makeSchema()
    {
        return new SQLiteSchema($this);
    }

    /**
     * @inheritdoc
     */
    protected function makePDO(array $config, array $options)
    {
        $options[PDO::ATTR_EMULATE_PREPARES] = false; // fetch int, float and boolean as numeric value (decimal/numeric is still a string)

        if ($config['database'] == ':memory:') {
            $dsn = 'sqlite::memory:';
        }
        else {
            $path = realpath($config['database']);
            if ($path === false) {
                throw new InvalidArgumentException("Database (${config['database']}) does not exist.");
            }
            $dsn = "sqlite:{$path}";
        }

        $username = null; //$config['username'];
        $password = null; //$config['password'];
        $pdo      = new PDO($dsn, $username, $password, $options);

        return $pdo;
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        parent::connect();

        // todo dies erst beim Zugriff auf das Schema ausführen
        /** @noinspection SqlDialectInspection */
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS _comments (
                table_name STRING NOT NULL,
                column_name STRING,
                content TEXT NOT NULL,
                PRIMARY KEY (table_name, column_name)
            )
        ');
    }

    /**
     * @inheritdoc
     */
    public function truncate($table)
    {
        $this->transaction(function() use ($table) {
            $quotedTable = $this->quoteName($table);
            $this->exec("DELETE FROM {$quotedTable}");
            /** @noinspection SqlDialectInspection */
            $this->exec('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
        });
        //$this->exec('VACUUM');
    }

    /**
     * @inheritdoc
     */
    public function insert($table, array $data)
    {
        $affectedRows = parent::insert($table, $data);

        $this->lastInsertTo = $table;

        return $affectedRows;
    }

    /**
     * @inheritdoc
     */
    public function lastInsertId()
    {
        if (is_null($this->lastInsertTo)) {
            // Is there a bug in sqlite?
            // "SELECT last_insert_rowid();" returns "6" if no value was inserted before.
            return 0;
        }

        return (int)$this->pdo->lastInsertId();
    }
}