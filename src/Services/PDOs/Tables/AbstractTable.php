<?php

namespace Core\Services\PDOs\Tables;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builder\Contracts\Builder;
use Core\Services\PDOs\Tables\Contracts\Table as TableContract;
use InvalidArgumentException;

abstract class AbstractTable implements TableContract
{
    /**
     * Database Access Layer.
     *
     * @var Database
     */
    protected $db; // todo evtl getter anbieten

    /**
     * Table name.
     *
     * @var string
     */
    protected $table; // todo evtl getter anbieten

    /**
     * Create a new Database Schema instance.
     *
     * @param Database $db
     * @param string $table
     */
    public function __construct(Database $db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function find($id, $key = 'id', $class = null)
    {
        $table = $this->db->quoteName($this->table);
        $key   = $this->db->quoteName($key);

        /** @noinspection SqlDialectInspection */
        return $this->db->single("SELECT * FROM $table WHERE $key = ?", [$id], $class);
    }

    ///////////////////////////////////////////////////////////////////
    // Query Builder

    /**
     * Create a new QueryBuilder instance.
     *
     * @return Builder
     */
    private function createBuilder()
    {
        return $this->db->createBuilder()->from($this->table);
    }

    /**
     * @inheritdoc
     */
    public function select($columns = null, $alias = null)
    {
        $builder = $this->createBuilder();
        if ($columns !== null) {
            $builder->select($columns);
        }

        return $builder;
    }

    // todo weitere Mehtoden des QueryBuilders adaptieren

    ///////////////////////////////////////////////////////////////////
    // Data Manipulation

    /**
     * @inheritdoc
     */
    public function insert(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Cannot insert an empty record.');
        }

        $table = $this->db->quoteName($this->table);

        if (is_string(key($data))) {
            // single record will be inserted
            $columns  = implode(', ', array_map([$this, 'quoteName'], array_keys($data)));
            $params   = implode(', ', array_fill(0, count($data), '?'));
            $bindings = array_values($data);
        }
        else {
            // Bulk insert...
            $keys = [];
            foreach ($data as $row) {
                $keys = array_merge($keys, $row);
            }
            $keys     = array_keys($keys);
            $columns  = implode(', ', array_map([$this, 'quoteName'], $keys));
            $params   = implode(', ', array_fill(0, count($keys), '?'));
            $bindings = [];
            $temp     = [];
            foreach ($data as $i => $row) {
                foreach ($keys as $key) {
                    $bindings[] = isset($row[$key]) ? $row[$key] : null;
                }
                $temp[] = $params;
            }
            $params = implode('), (', $temp);
        }

        /** @noinspection SqlDialectInspection */
        $this->db->exec("INSERT INTO $table ($columns) VALUES ($params)", $bindings);

        return $this->db->lastInsertId();
    }

    /**
     * @inheritdoc
     */
    public function truncate()
    {
        $table = $this->db->quoteName($this->table);

        $this->db->exec("TRUNCATE TABLE $table");
    }
}