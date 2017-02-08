<?php

namespace Core\Services\PDOs\Schemas;

use InvalidArgumentException;

/**
 * MySql Database Schema
 *
 * Functions tables() and columns() are based on Aura.SqlSchema
 * Function compileFieldType() based on Dontrine's Mapping Matrix.
 *
 * @see https://github.com/auraphp/Aura.SqlSchema/blob/2.x/src/MysqlSchema.php Aura.SqlSchema on GitHub
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix Doctrine's Mapping Matrix
 */
class MySqlSchema extends AbstractSchema
{
    /**
     * @inheritdoc
     */
    public function tables()
    {
        $tables = [];

        $sql = 'SHOW TABLE STATUS';
        $info = $this->db->query($sql);

        foreach ($info as $val) {
            $name = $val['Name'];
            $tables[$name] = [
                'name'      => $name,
                'collation' => $val['Collation'],
                'comment'   => $val['Comment'],
            ];
        }

        return $tables;
    }

    /**
     * @inheritdoc
     */
    public function columns($table)
    {
        $table = $this->db->quoteName($table);
        $columns = [];

        $sql = "SHOW FULL COLUMNS FROM $table";
        $info = $this->db->query($sql);

        foreach ($info as $val) {
            $name      = $val['Field'];
            $nullable  = $val['Null'] == 'YES';
            $default   = $val['Default'] != 'NULL' ? $val['Default'] : null;
            $collation = $val['Collation'];

            list($dbType, $size, $scale, $unsigned) = $this->extractFieldType($val['Type']);
            list($type, $comment) = $this->extractTypeHintFromComment($val['Comment']);

            if (is_null($type)) {
                $autoinc = strpos($val['Extra'], 'auto_increment') !== false;
                if ($autoinc) {
                    $type = $dbType == 'BIGINT' ? 'bigidentity' : 'identity';
                }
                else {
                    $type = $this->convertFieldType($dbType);
                    if ($type == 'integer' && $unsigned) {
                        $type = 'unsigned';
                    }
                }
            }

            if (!in_array($type, ['numeric', 'string', 'binary'])) {
                $size = null; // 0 -> null
            }

            if (!in_array($type, ['numeric'])) {
                $scale = null; // 0 -> null
            }

            if (!is_null($default)) {
                if (in_array($type, ['smallint', 'integer', 'unsigned', 'bigint'])) {
                    $default = (int)$default;
                }
                else if ($type == 'float') {
                    $default = (float)$default;
                }
                else if ($type == 'boolean') {
                    $default = (boolean)$default;
                }
            }

            if (!in_array($type, ['string', 'text'])) {
                $collation = null;
            }

            $columns[$name] = [
                'name'      => $name,
                'type'      => $type,
                'size'      => $size,
                'scale'     => $scale,
                'nullable'  => $nullable,
                'default'   => $default,
                'collation' => $collation,
                'comment'   => $comment
            ];
        }

        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function indexes($table)
    {
        $table = $this->db->quoteName($table);
        $indexes = [];

        $sql = "SHOW INDEX FROM $table";
        $info = $this->db->query($sql);

        foreach ($info as $val) {
            $name    = $val['Key_name'];
            $column  = $val['Column_name'];
            $unique  = $val['Non_unique'] == 0;
            $primary = $name == 'PRIMARY';
            if (isset($indexes[$name])) {
                $indexes[$name]['columns'][] = $column;
            }
            else {
                $indexes[$name] = [
                    'name'    => $name,
                    'columns' => [$column],
                    'unique'  => $unique,
                    'primary' => $primary,
                ];
            }
        }

        return $indexes;
    }

    /**
     * @inheritdoc
     */
    public function createTable($table, array $columns, array $options = [])
    {
        // this is the same as the parent part:

        if (empty($columns)) {
            throw new InvalidArgumentException("Cannot create tabble without columns.");
        }

        foreach ($columns as $column => $attr) {
            $columns[$column] = $this->db->quoteName($column) . ' ' . $this->compileColumnDefinition($attr);
        }
        $definition = implode(', ', $columns);

        $quotedTable = $this->db->quoteName($table);
        $temporary = isset($options['temporary']) ? $options['temporary'] : false;
        if ($temporary) {
            /** @noinspection SqlNoDataSourceInspection */
            $sql = "CREATE TEMPORARY TABLE {$quotedTable} ({$definition})";
        }
        else {
            /** @noinspection SqlNoDataSourceInspection */
            $sql = "CREATE TABLE {$quotedTable} ({$definition})";
        }

        // and this is MySql specific...

        $options   = array_merge($this->db->config(), $options);
        $charset   = !empty($options['charset']) ? $options['charset'] : null;
        $collation = !empty($options['collation']) ? $options['collation'] : null;
        $engine    = !empty($options['engine']) ? $options['engine'] : null;
        $comment   = !empty($options['comment']) ? $options['comment'] : null;

        if (!empty($charset)) {
            $sql .= ' DEFAULT CHARACTER SET ' . $charset;
        }

        if (!empty($collation)) {
            $sql .= ' COLLATE ' . $collation;
        }

        if (!empty($engine)) {
            $sql .= ' ENGINE = ' . $engine;
        }

        if (!empty($comment)) {
            $sql .= ' COMMENT ' . $this->db->quote($comment);
        }

        $this->db->exec($sql);
    }

    /**
     * @inheritdoc
     */
    public function renameTable($from, $to)
    {
        $from = $this->db->quoteName($from);
        $to = $this->db->quoteName($to);

        /** @noinspection SqlNoDataSourceInspection */
        $sql = "RENAME TABLE {$from} TO {$to}";
        $this->db->exec($sql);
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $from, $to)
    {
        $options = $this->columns($table);
        if (!isset($options[$from])) {
            throw new InvalidArgumentException("Column '{$from}' not exists in table {$table}.");
        }
        $definition = $this->compileColumnDefinition($options[$from]);

        $table = $this->db->quoteName($table);
        $from  = $this->db->quoteName($from);
        $to    = $this->db->quoteName($to);

        /** @noinspection SqlNoDataSourceInspection */
        $sql = "ALTER TABLE {$table} CHANGE COLUMN {$from} {$to} {$definition}";

        $this->db->exec($sql);
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Type Mapping
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    protected function compileFieldType($type, $size, $scale)
    {
        switch (strtolower($type)) {
            case 'json':   // needs type-hint by MySql < '5.7.8'
                $version = $this->db->version();
                return $version >= '5.7.8' ? 'JSON' : 'TEXT';

            case 'numeric':
                return 'DECIMAL(' . ($size ?: 10) . ', ' . ($scale ?: 0) . ')'; // size in digits, // NUMERIC is an alias

            case 'guid': // needs type-hint!
                return 'VARCHAR(36)';

            case 'boolean':
                return 'TINYINT(1)';

//            case 'timestamp':
//                // Extra: ON UPDATE CURRENT_TIMESTAMP // todo in Options einführen (onupdate: CURRENT_TIMESTAMP)
//                return 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';

            default:
                return parent::compileFieldType($type, $size, $scale);
        }
    }

    /**
     * @inheritdoc
     */
    protected function needATypeHint($type)
    {
        $version = $this->db->version();
        if ($version >= '5.7.8') {
            return in_array($type, ['array', 'object', 'guid']);
        }

        return in_array($type, ['array', 'json', 'object', 'guid']);
    }

    /**
     * Convert a database field type to a column type supported by Database Access Layer.
     *
     * @see http://www.w3schools.com/sql/sql_datatypes.asp SQL Data Types for Various DBs
     * @see https://www.tutorialspoint.com/mysql/mysql-data-types.htm MySQL Data Types
     * @see http://dev.mysql.com/doc/refman/5.7/en/data-types.html MySQL 5.7 Reference Manual
     *
     * @param string $dbType Native database field type
     * @return string
     */
    private function convertFieldType($dbType)
    {
        switch (strtoupper($dbType)) {
            case 'SMALLINT':
                return 'smallint';

            case 'INT':
                return 'integer';

            case 'BIGINT':
                return 'bigint';

            case 'DECIMAL':
            case 'NUMERIC':
                return 'numeric';

            case 'DOUBLE':
                return 'float';

            case 'VARCHAR':
                return 'string';

            case 'TEXT':
            case 'TINYTEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                return 'text';

            case 'VARBINARY':
                return 'binary';

            case 'BLOB':
            case 'TINYBLOB':
            case 'MEDIUMBLOB':
            case 'LONGBLOB':
                return 'blob';

            case 'TINYINT':
                return 'boolean';

            case 'DATE':
                return 'date';

            case 'DATETIME':
                return 'datetime';

            case 'TIME':
                return 'time';

            case 'TIMESTAMP':
                return 'timestamp';

            case 'JSON':
                return 'json';

            default:

                $mapping = [ // todo vergleichen und sortieren
                    'tinyint'       => 'boolean',
                    'smallint'      => 'smallint',
                    'mediumint'     => 'integer',
                    'int'           => 'integer',
                    'integer'       => 'integer',
                    'bigint'        => 'bigint',
                    'tinytext'      => 'text',
                    'mediumtext'    => 'text',
                    'longtext'      => 'text',
                    'text'          => 'text',
                    'varchar'       => 'string',
                    'string'        => 'string',
                    'char'          => 'string',
                    'date'          => 'date',
                    'datetime'      => 'datetime',
                    'timestamp'     => 'datetime',
                    'time'          => 'time',
                    'float'         => 'float',
                    'double'        => 'float',
                    'real'          => 'float',
                    'decimal'       => 'numeric',
                    'numeric'       => 'numeric',
                    'year'          => 'date',
                    'longblob'      => 'blob',
                    'blob'          => 'blob',
                    'mediumblob'    => 'blob',
                    'tinyblob'      => 'blob',
                    'binary'        => 'binary',
                    'varbinary'     => 'binary',
                    'set'           => 'simple_array',
                ];

                return isset($mapping[$dbType]) ? $mapping[$dbType] : 'string'; // Fallback Type
        }
    }
}