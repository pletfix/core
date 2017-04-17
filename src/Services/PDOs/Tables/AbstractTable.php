<?php

namespace Core\Services\PDOs\Tables;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builder\Contracts\Builder;
use Core\Services\PDOs\Tables\Contracts\Table as TableContract;
use InvalidArgumentException;

abstract class AbstractTable implements TableContract // todo ist nicht abstract!
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
    public function all($class = null)
    {
        $table = $this->db->quoteName($this->table);

        /** @noinspection SqlDialectInspection */
        return $this->db->query("SELECT * FROM $table", [], $class);
    }

    /**
     * @inheritdoc
     */
    public function cursor($class = null)
    {
        $table = $this->db->quoteName($this->table);

        /** @noinspection SqlDialectInspection */
        return $this->db->cursor("SELECT * FROM $table", [], $class);
    }

    /**
     * @inheritdoc
     */
    public function first($class = null)
    {
        $table = $this->db->quoteName($this->table);

        /** @noinspection SqlDialectInspection */
        return $this->db->single("SELECT * FROM $table LIMIT 1", [], $class);
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

    /**
     * @inheritdoc
     */
    public function count()
    {
        $table = $this->db->quoteName($this->table);

        /** @noinspection SqlDialectInspection */
        return $this->db->scalar("SELECT COUNT(*) FROM $table");
    }

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
            $columns  = implode(', ', array_map([$this->db, 'quoteName'], array_keys($data)));
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
            $columns  = implode(', ', array_map([$this->db, 'quoteName'], $keys));
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
    public function update(array $data)
    {
        return $this->createBuilder()->update($data);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        return $this->createBuilder()->delete();
    }

    /**
     * @inheritdoc
     */
    public function truncate()
    {
        $table = $this->db->quoteName($this->table);

        $this->db->exec("TRUNCATE TABLE $table");
    }

    ///////////////////////////////////////////////////////////////////
    // Gets a Query Builder

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
    public function asClass($class)
    {
        return $this->createBuilder()->asClass($class);
    }

    /**
     * @inheritdoc
     */
    public function select($columns, array $bindings = [])
    {
        return $this->createBuilder()->select($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function distinct()
    {
        return $this->createBuilder()->distinct();
    }

    /**
     * @inheritdoc
     */
    public function from($source, $alias = null, array $bindings = [])
    {
        return $this->createBuilder()->from($source, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function join($source, $on, $alias = null, array $bindings = [])
    {
        return $this->createBuilder()->join($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->createBuilder()->leftJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($source, $on, $alias = null, array $bindings = [])
    {
        return $this->createBuilder()->rightJoin($source, $on, $alias, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function where($condition, array $bindings = [])
    {
        return $this->createBuilder()->where($condition, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereIs($column, $value, $operator = '=')
    {
        return $this->createBuilder()->whereIs($column, $value, $operator);
    }

    /**
     * @inheritdoc
     */
    public function whereSubQuery($column, $query, $operator = '=', array $bindings = [])
    {
        return $this->createBuilder()->whereSubQuery($column, $query, $operator, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereExists($query, array $bindings = [])
    {
        return $this->createBuilder()->whereExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereNotExists($query, array $bindings = [])
    {
        return $this->createBuilder()->whereNotExists($query, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function whereIn($column, array $values)
    {
        return $this->createBuilder()->whereIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public function whereNotIn($column, array $values)
    {
        return $this->createBuilder()->whereNotIn($column, $values);
    }

    /**
     * @inheritdoc
     */
    public function whereBetween($column, $lowest, $highest)
    {
        return $this->createBuilder()->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public function whereNotBetween($column, $lowest, $highest)
    {
        return $this->createBuilder()->whereBetween($column, $lowest, $highest);
    }

    /**
     * @inheritdoc
     */
    public function whereIsNull($column)
    {
        return $this->createBuilder()->whereIsNull($column);
    }

    /**
     * @inheritdoc
     */
    public function whereIsNotNull($column)
    {
        return $this->createBuilder()->whereIsNotNull($column);
    }

    /**
     * @inheritdoc
     */
    public function orderBy($columns, array $bindings = [])
    {
        return $this->createBuilder()->orderBy($columns, $bindings);
    }

    /**
     * @inheritdoc
     */
    public function limit($limit)
    {
        return $this->createBuilder()->limit($limit);
    }

    /**
     * @inheritdoc
     */
    public function offset($offset)
    {
        return $this->createBuilder()->offset($offset);
    }
}