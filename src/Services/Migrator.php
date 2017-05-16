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
        ksort($migrations);

        foreach ($migrations as $name => $file) {
            $this->db->transaction(function (Database $db) use ($name, $file, $batch) {
                $this->makeMigrationClass($name, $file)->up($db);
                $db->table('_migrations')->insert([
                    'name'  => $name,
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
        krsort($migrations);

        foreach ($migrations as $name => $file) {
            $this->db->transaction(function (Database $db) use ($name, $file, $batch) {
                $this->makeMigrationClass($name, $file)->down($db);
                $db->table('_migrations')->where('name=?', [$name])->delete();
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        /** @noinspection SqlDialectInspection */
        while ($this->db->table('_migrations')->count() > 0) {
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
        $batch = $this->db->table('_migrations')->max('batch');

        return $batch;
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
        $rows = $this->db->table('_migrations')->select('name')->all();
        foreach ($rows as $row) {
            $migrated[] = $row['name'];
        }

        // Load the outstanding migrations

        $migrations = [];

        // read migrations from folder
        $files = [];
        list_files($files, $this->migrationPath, ['php']);
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $migrations[$name] = $file;
        }

        // read migrations included by plugins
        $migrations = array_merge($migrations, $this->pluginMigrations());

        // remove migrations already performed
        foreach ($migrations as $name => $file) {
            if (in_array($name, $migrated)) {
                unset($migrations[$name]);
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

        $pluginMigrations = $this->pluginMigrations();

        /** @noinspection SqlDialectInspection */
        $rows = $this->db->table('_migrations')->select('name')->where('batch = ?', [$batch])->all();
        foreach ($rows as $row) {
            $name = $row['name'];
            $migrations[$name] = isset($pluginMigrations[$name]) ? $pluginMigrations[$name] : $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';
        }

        return $migrations;
    }

    /**
     * Read migrations included by plugins.
     *
     * @return array
     */
    private function pluginMigrations()
    {
        $files = [];

        $pluginManifest = manifest_path('plugins/migrations.php');
        if (file_exists($pluginManifest)) {
            $basePath = base_path();
            /** @noinspection PhpIncludeInspection */
            $migrations = include $pluginManifest;
            foreach ($migrations as $name => $file) {
                $files[$name] = $basePath . DIRECTORY_SEPARATOR . $file;
            }
        }

        return $files;
    }

    /**
     * Create a new migration class.
     *
     * @param string $name
     * @param string $file
     * @return Migration
     * @throws \Exception
     */
    private function makeMigrationClass($name, $file)
    {
        if (($pos = strpos($name, '_')) === false) {
            throw new MigrationException('Migration file "' . $name . '.php" is invalid. Format "<timestamp>_<classname>.php" expected.');
        }
        $class = substr($name, $pos + 1);

        /** @noinspection PhpIncludeInspection */
        require $file;

        if (!class_exists($class, false)) {
            throw new MigrationException('Migration class "' . $class . '" is not defined in file "' . $name . '.php".');
        }

        if ($class instanceof Migration) {
            throw new MigrationException('Migration "' . $name . '" is invalid. The Class have to implements the interface \Services\Contracts\Migration.');
        }

        return new $class;
    }
}