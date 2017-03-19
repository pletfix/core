<?php

namespace Core\Services;

use Core\Exceptions\MigrationException;
use Core\Services\Contracts\Database;
use Core\Services\Contracts\Migration;
use Core\Services\Contracts\Migrator as MigratorContract;

class Migrator implements MigratorContract
{
    /**
     * Database Access Layer.
     *
     * @var Database;
     */
    private $db;

    /**
     * Migration Path.
     *
     * @var string
     */
    private $migrationPath;

    /**
     * Create a new instance.
     *
     * @param string|null $store Name of the database store
     * @param string|null $path Subfolder in the migration directory
     */
    public function __construct($store = null, $path = null)
    {
        $this->db = database($store);
        $this->migrationPath = migration_path($path);

        $tables = $this->db->schema()->tables();
        if (!isset($tables['_migrations'])) {
            $this->db->schema()->createTable('_migrations', [
                'id'    => ['type' => 'identity'],
                'name'  => ['type' => 'string'],
                'batch' => ['type' => 'integer'],
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $batch      = $this->getLastBatchNumber() + 1;
        $migrations = $this->loadMigrations();
        sort($migrations);

        foreach ($migrations as $migration) {
            $this->db->transaction(function (Database $db) use ($migration, $batch) {
                $this->makeMigrationClass($migration)->up($db);
                $db->insert('_migrations', [
                    'name'  => $migration,
                    'batch' => $batch,
                ]);
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function rollback()
    {
        $batch      = $this->getLastBatchNumber();
        $migrations = $this->loadPerformedMigrations($batch);
        rsort($migrations);

        foreach ($migrations as $migration) {
            $this->db->transaction(function (Database $db) use ($migration, $batch) {
                $this->makeMigrationClass($migration)->down($db);
                $db->delete('_migrations', 'name=?', [$migration]);
//                $db->delete('_migrations', [
//                    'name' => $migration
//                ]);
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        /** @noinspection SqlDialectInspection */
        while ($this->db->scalar('SELECT COUNT(*) FROM _migrations') > 0) {
            $this->rollback();
        }
    }

    /**
     * Get the last batch number.
     *
     * @return int
     */
    private function getLastBatchNumber()
    {
        /** @noinspection SqlDialectInspection */
        $batch = $this->db->scalar('SELECT MAX(batch) FROM _migrations');

        return (int)$batch;
    }

    /**
     * Load the outstanding migrations.
     *
     * @return array
     */
    private function loadMigrations()
    {
        // Load the migrations already performed

        $migrated = [];
        $rows = $this->db->query('SELECT name FROM _migrations');
        foreach ($rows as $row) {
            $migrated[] = $row['name'];
        }

        // Load the outstanding migrations

        $migrations = [];

        // read migrations from folder
        $files = [];
        list_files($files, $this->migrationPath, ['php']);

        // read migrations included by plugins
        $pluginManifest = manifest_path('plugins/migrations.php');
        if (file_exists($pluginManifest)) {
            $basePath = base_path();
            /** @noinspection PhpIncludeInspection */
            $pluginMigrations = include $pluginManifest;
            foreach ($pluginMigrations as $pluginMigration) {
                $files[] = $basePath . DIRECTORY_SEPARATOR . $pluginMigration;
            }
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $migrated)) {
                $migrations[] = $name;
            }
        }

        return $migrations;
    }

    /**
     * Load the migrations already performed by batch number
     *
     * @param int $batch
     * @return array
     */
    private function loadPerformedMigrations($batch)
    {
        $migrations = [];

        /** @noinspection SqlDialectInspection */
        $rows = $this->db->query('SELECT name FROM _migrations WHERE batch = ?', [$batch]);
        foreach ($rows as $row) {
            $migrations[] = $row['name'];
        }

        return $migrations;
    }

    /**
     * Create a new migration class.
     *
     * @param string $name
     * @return Migration
     * @throws \Exception
     */
    private function makeMigrationClass($name)
    {
        if (($pos = strpos($name, '_')) === false) {
            throw new MigrationException('Migration file "' . $name . '.php" is invalid. Format "<timestamp>_<classname>.php" expected.');
        }
        $class = substr($name, $pos + 1);

        /** @noinspection PhpIncludeInspection */
        require $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';

        if (!class_exists($class, false)) {
            throw new MigrationException('Migration class "' . $class . '" is not defined in file "' . $name . '.php".');
        }

        if ($class instanceof Migration) {
            throw new MigrationException('Migration "' . $name . '" is invalid. The Class have to implements the interface \Services\Contracts\Migration.');
        }

        return new $class;
    }
}