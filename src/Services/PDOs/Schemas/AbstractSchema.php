<?php

namespace Core\Services\PDOs\Schemas;

use Core\Services\Contracts\Database;
use Core\Services\PDOs\Schemas\Contracts\Schema as SchemaContract;
use InvalidArgumentException;

/**
 * Abstract Database Schema Management
 *
 * The column attribute definition (e.g. for columns()) based on Aura.Sql Column Factory ([BSD 2-clause "Simplified" License](https://github.com/auraphp/Aura.SqlSchema/blob/2.x/LICENSE)).
 * Function compileFieldType() based on Dontrine's Mapping Matrix ([MIT License](https://github.com/doctrine/dbal/blob/2.5/LICENSE)).
 * Function extractTypeHintFromComment() based on Doctrine's Schema Manager ([MIT License](https://github.com/doctrine/dbal/blob/2.5/LICENSE)).
 *
 * @see https://github.com/auraphp/Aura.SqlSchema/blob/2.x/src/ColumnFactory.php Aura.Sql Column Factory on GitHub
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix Doctrine's Mapping Matrix
 * @see https://github.com/doctrine/dbal/blob/2.5/lib/Doctrine/DBAL/Schema/AbstractSchemaManager.php Doctrine's Schema Manager
 */
abstract class AbstractSchema implements SchemaContract
{
    /**
     * Database Access Layer.
     *
     * @var Database
     */
    protected $db;

    /**
     * Create a new Database Schema instance.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritdoc
     */
    abstract public function tables();

    /**
     * @inheritdoc
     */
    abstract public function columns($table);

    /**
     * @inheritdoc
     */
    abstract public function indexes($table);

    /**
     * Split a type-hint for Database Access Layer from comment.
     *
     * The type-hint is compatible with Doctrine 2.
     *
     * @param string $comment
     * @return array
     */
    protected function extractTypeHintFromComment($comment)
    {
        if (empty($comment)) {
            return [null, null];
        }

        if (!preg_match('(\(DC2Type:(\w+)\))', $comment, $match)) {
            return [null, $comment];
        }

        $type    = $match[1];
        $comment = trim(str_replace('(DC2Type:' . $type . ')', '', $comment));

        if ($comment == '') {
            $comment = null;
        }

        return [$type, $comment];
    }

    /**
     * @inheritdoc
     */
    abstract public function createTable($table, array $columns, array $options = []);

    // todo createTempTable($table, $selectStatement)
    // “create table temp_table as select * from sync;”

    /**
     * @inheritdoc
     */
    public function dropTable($table)
    {
        $table = $this->db->quoteName($table);

        $this->db->exec("DROP TABLE {$table}"); // todo geschweifte Klammern müssen nicht sein

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameTable($from, $to)
    {
        $from = $this->db->quoteName($from);
        $to   = $this->db->quoteName($to);

        $this->db->exec("ALTER TABLE {$from} RENAME TO {$to}");

        return $this;
    }

//    /**
//     * @inheritdoc
//     */
//    public function truncateTable($table)
//    {
//        $table = $this->db->quoteName($table);
//
//        $this->db->exec("TRUNCATE TABLE $table");
//
//        return $this;
//    }

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, array $options)
    {
        $definition = $this->compileColumnDefinition($options);
        $table  = $this->db->quoteName($table);
        $column = $this->db->quoteName($column);

        $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropColumn($table, $column)
    {
        $table  = $this->db->quoteName($table);
        $column = $this->db->quoteName($column);

        $this->db->exec("ALTER TABLE {$table} DROP COLUMN {$column}");

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $from, $to)
    {
        $table = $this->db->quoteName($table);
        $from  = $this->db->quoteName($from);
        $to    = $this->db->quoteName($to);

        $this->db->exec("ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}");

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addIndex($table, $name, array $options) // todo name zu den optins packen, da nicht zwingend erforderlich
    {
        if (empty($options['columns'])) {
            throw new InvalidArgumentException("Cannot add index without columns.");
        }

        $quotedTable = $this->db->quoteName($table);

        $columns = $options['columns'];
        $quotedColumns = [];
        foreach ($columns as $column) {
            $quotedColumns[] = $this->db->quoteName($column);
        }
        $quotedColumns = implode(', ', $quotedColumns);

        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            $this->db->exec("ALTER TABLE {$quotedTable} ADD PRIMARY KEY ($quotedColumns)");
        }
        else {
            $unique = isset($options['unique']) ? $options['unique'] : false;
            $index  = $unique ? 'UNIQUE' : 'INDEX';
            if ($name === null) {
                $name = $this->createIndexName($table, $columns, $unique);
            }
            $name = $this->db->quoteName($name);
            $this->db->exec("ALTER TABLE {$quotedTable} ADD {$index} {$name} ($quotedColumns)");
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($table, $name, array $options = []) // todo name zu den optins packen, da nicht zwingend erforderlich
    {
        $quotedTable = $this->db->quoteName($table);

        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            $this->db->exec("ALTER TABLE {$quotedTable} DROP PRIMARY KEY");
        }
        else {
            if ($name === null) {
                if (empty($options['columns'])) {
                    throw new InvalidArgumentException("Cannot find index without name and columns.");
                }
                $columns = $options['columns'];
                $unique  = isset($options['unique']) ? $options['unique'] : false;
                $name = $this->createIndexName($table, $columns, $unique);
                // todo der Name sollte besser aus der Indexliste gesucht werden.
                // Momentan wird einfach angenommen, dass der Index wie vom Access Layer vorgegeben heißt.
            }
            $name = $this->db->quoteName($name);
            $this->db->exec("ALTER TABLE {$quotedTable} DROP INDEX {$name}");
        }

        return $this;
    }

    /**
     * Compile the column definitions.
     *
     * This function is used by createTable() and addColumn().
     *
     * Options have following properties:
     * - type:      (string) The column data type. Data types are as reported by the database.
     * - size:      (int)    The column size (the maximum number of digits).
     * - scale:     (int)    The number of digits to the right of the numeric point. It must be no larger than size.
     * - nullable:  (bool)   The column is not marked as NOT NULL.
     * - default:   (mixed)  The default value for the column.
     * - collation: (string) The collation of the column.
     * - comment:   (string) A hidden comment.
     *
     * @param array $options
     * @return string
     */
    protected function compileColumnDefinition(array $options)
    {
        $options = array_merge([
            'type'      => null,
            'size'      => null,
            'scale'     => null,
            'nullable'  => false,
            'default'   => null,
            'collation' => null,
            'comment'   => null,
        ], $options);

        $type   = strtolower($options['type']);

        if ($type == 'identity') { // Is the column the auto-incremented primary key?
            $sql = 'INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT';
        }
        else if ($type == 'bigidentity') {
            $sql = 'BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT';
        }
        else {
            $size   = $options['size'];
            $scale  = $options['scale'];
            $sql = $this->compileFieldType($type, $size, $scale);

            if (!$options['nullable']) {
                $sql .= ' NOT NULL';
            }

            if ($options['default'] !== null) {
                $default = $options['default'];
                if (is_string($default) && $default != 'CURRENT_TIMESTAMP') {
                    $default = $this->db->quote($options['default']);
                } else if (is_bool($default)) {
                    $default = $default ? '1' : '0';
                }
                $sql .= ' DEFAULT ' . $default;
            }

            if (!empty($options['collation'])) {
                $sql .= ' COLLATE ' . $options['collation'];
            }
        }

        $comment = $options['comment'];
        if ($this->needATypeHint($type)) {
            $comment = ($comment !== null ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
        }

        if (!is_null($comment)) {
            $sql .= ' COMMENT ' . $this->db->quote($comment);
        }

        return $sql;
    }

    /**
     * Create a default index name for the table.
     *
     * This function ist used by addIndex() and dropIndex();
     *
     * @param string $table
     * @param array $columns
     * @param boolean $unique
     * @return string
     */
    protected function createIndexName($table, array $columns, $unique = false)
    {
        $index = strtolower($table . '_' . implode('_', $columns) . '_' . ($unique ? 'unique' : 'index'));

        return str_replace(['-', '.'], '_', $index);
    }

    /*
     * ----------------------------------------------------------------------------------------------------------------
     * Type Mapping
     * ----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @inheritdoc
     */
    public function zero($type)
    {
        if (in_array($type, ['smallint', 'integer', 'unsigned', 'bigint', 'numeric', 'float'])) {
            return 0;
        }
        if (in_array($type, ['string', 'text', 'binary', 'blob'])) {
            return '';
        }
        if (in_array($type, ['array'])) {
            return '[]';
        }
        if (in_array($type, ['json'])) {
            return '{}'; // todo wie sieht der leer Inhalt eines JSONS aus?
        }
        if (in_array($type, ['object'])) {
            return '{}'; // todo wie sieht der leer Inhalt eines Objects aus?
        }
        if (in_array($type, ['guid'])) {
            return '00000000-0000-0000-0000-000000000000';
        }
        if (in_array($type, ['boolean'])) {
            return false; // oder 0?
        }
        if (in_array($type, ['date'])) {
            return '0000-00-00';
        }
        if (in_array($type, ['time'])) {
            return '00:00:00';
        }
        if (in_array($type, ['datetime', 'timestamp'])) {
            return '0000-00-00 00:00:00';
        }

        throw new InvalidArgumentException("Type '{$type}' not supported by Database Access Layer.");
    }

    /**
     * Compile the column type.
     *
     * This function is used by compileColumnDefinition().
     *
     * @param string $type by Column type supported by Database Access Layer
     * @param int|null $size
     * @param int|null $scale
     * @return string
     */
    protected function compileFieldType($type, $size, $scale)
    {
        switch (strtolower($type)) {
            case 'smallint':    // 2 Byte
                return 'SMALLINT';

            case 'integer':     // 4 Byte
                return 'INT';

            case 'unsigned':    // 4 Byte
                return 'INT UNSIGNED';

            case 'bigint':      // 8 Byte
                return 'BIGINT';

            case 'numeric':
                return 'NUMERIC(' . ($size ?: 10) . ', ' . ($scale ?: 0) . ')'; // size in digits

            case 'float':
                return 'DOUBLE';

            case 'string':
                return 'VARCHAR(' . ($size ?: 255) . ')'; // size in characters

            case 'text':
            case 'array':  // needs type-hint!
            case 'json':   // needs type-hint!
            case 'object': // needs type-hint!
                return 'TEXT';

            case 'guid':
                return 'UUID'; // Postgres converts to lowercase letters, SQLServer to uppercase!

            case 'binary':
                // size in bytes:
                // 1 Byte  = 2^8-1  = 255 characters
                // 2 Bytes = 2^16-1 = 65535 characters
                // 3 Bytes = 2^24-1 = 16777215 characters
                // 4 Bytes = 2^32-1 = 4294967295 characters
                return 'VARBINARY(' . ($size ?: 2) . ')';

            case 'blob':
                return 'BLOB';

            case 'boolean':
                return 'BOOLEAN';

            case 'date':
                return 'DATE';

            case 'datetime':
                return 'DATETIME';

            case 'timestamp':  // datetime with time zone
                return 'TIMESTAMP';

            case 'time':
                return 'TIME';

            default:
                throw new InvalidArgumentException("Type '{$type}' not supported by Database Access Layer.");
        }
    }

    /**
     * Determine if the type needs a hint.
     *
     * This function is used by compileColumnDefinition().
     *
     * @param string $type Column type supported by Database Access Layer
     * @return boolean
     */
    protected function needATypeHint($type)
    {
        return in_array($type, ['array', 'json', 'object']);
    }
}