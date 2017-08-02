<?php

namespace Core\Services\PDOs\Schemas;

use InvalidArgumentException;

/**
 * Microsoft SQL Server Database Schema
 *
 * Functions tables() and columns() are based on Aura.SqlSchema
 * Function compileFieldType() based on Dontrine's Mapping Matrix.
 *
 * @see https://github.com/auraphp/Aura.SqlSchema/blob/2.x/src/SqlsrvSchema.php Aura.SqlSchema on GitHub
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix Doctrine's Mapping Matrix
 */
class MSSQLSchema extends Schema
{
    /**
     * @inheritdoc
     */
    public function tables()
    {
        $tables = [];

        $defaultCollation = $this->db->scalar("SELECT CAST(SERVERPROPERTY('Collation') AS VARCHAR(255))");

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT name 
            FROM sysobjects 
            WHERE type = 'U' 
            AND name != 'sysdiagrams' 
            ORDER BY name
        ");

        foreach ($info as $val) {
            $name = $val['name'];
            $tables[$name] = [
                'name'      => $name,
                'collation' => $defaultCollation,
                'comment'   => null,
            ];
        }

        // merge comments (http://stackoverflow.com/questions/887370/sql-server-extract-table-meta-data-description-fields-and-their-data-types)

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT
              t.name AS table_name,
              CAST(td.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN sys.extended_properties AS td ON (td.major_id = t.id AND td.minor_id = 0)
            WHERE td.name = 'MS_Description'
            AND t.type = 'u'
        ");
        foreach ($info as $val) {
            $name = $val['table_name'];
            if (isset($tables[$name])) {
                $tables[$name]['comment'] = $val['description'];
            }
        }

        return $tables;
    }

    /**
     * @inheritdoc
     */
    public function columns($table)
    {
        $columns = [];

        // get comments

        // (http://stackoverflow.com/questions/887370/sql-server-extract-table-meta-data-description-fields-and-their-data-types)
        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT c.name AS column_name, CAST(cd.value AS VARCHAR(255)) AS description
            FROM sysobjects AS t
            INNER JOIN syscolumns AS c ON c.id = t.id
            INNER JOIN sys.extended_properties AS cd ON (cd.major_id = c.id AND cd.minor_id = c.colid)
            WHERE cd.name = 'MS_Description'
            AND t.type = 'u'
            AND t.name = ?
        ", [$table]);
        $comments = [];
        foreach ($info as $val) {
            $comments[$val['column_name']] = $val['description'];
        }

        // get collation

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT c.name, c.collation_name 
            FROM sys.columns AS c
            JOIN sys.objects AS t ON c.object_id = t.object_id
            WHERE t.type = 'U' 
            AND t.name = ?
            AND c.collation_name IS NOT NULL
        ", [$table]);
        $collations = [];
        foreach ($info as $val) {
            $collations[$val['name']] = $val['collation_name'];
        }

        // get column info

        $qTable = $this->db->quote($table);
        $info   = $this->db->query("EXEC sp_columns {$qTable}");

        foreach ($info as $val) {
            $name     = $val['COLUMN_NAME'];
            $dbType   = strtoupper($val['TYPE_NAME']);
            $size     = (int)$val['PRECISION'];
            $scale    = (int)$val['SCALE'];
            $nullable = $val['IS_NULLABLE'] == 'YES';
            $default  = $val['COLUMN_DEF'];

            list($type, $comment) = $this->extractTypeHintFromComment(isset($comments[$name]) ? $comments[$name] : null);

            if ($type === null) {
                if ($dbType == 'BIGINT IDENTITY') {
                    $type = 'bigidentity';
                    $default = null;
                }
                else if ($dbType == 'INT IDENTITY') {
                    $type = 'identity';
                    $default = null;
                }
                else {
                    $type = $this->convertFieldType($dbType);
                }
            }

            if ($default !== null) {
                if ($default == '(getdate())') {
                    $default = 'CURRENT_TIMESTAMP';
                }
                else {
                    $default = trim($default, '()');
                    if (!is_numeric($default)) {
                        if (!empty($default) && $default[0] == "'") {
                            $default = substr($default, 1, -1); // remove the leading and trailing quotes...
                        }
                    }
                    else if (in_array($type, ['smallint', 'integer', 'unsigned', 'bigint'])) {
                        $default = (int)$default;
                    }
                    else if ($type == 'float') {
                        $default = (float)$default;
                    }
                    else if ($type == 'boolean') {
                        $default = (boolean)$default;
                    }
                }
            }

            $collation = isset($collations[$name]) ? $collations[$name] : null;
            if (!in_array($type, ['string', 'text'])) {
                $collation = null;
            }

            if (!in_array($type, ['numeric', 'string', 'binary'])) {
                $size = null; // 0 -> null
            }

            if (!in_array($type, ['numeric'])) {
                $scale = null; // 0 -> null
            }

            $columns[$name] = array(
                'name'      => $name,
                'type'      => $type,
                'size'      => $size,
                'scale'     => $scale,
                'nullable'  => $nullable,
                'default'   => $default,
                'collation' => $collation,
                'comment'   => $comment,
            );
        }

        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function indexes($table)
    {
        $indexes = [];

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT 
                i.name, 
                co.[name] AS column_name,
                i.is_unique, 
                i.is_primary_key
            FROM sys.indexes i 
            INNER JOIN sys.index_columns ic ON ic.object_id = i.object_id  AND ic.index_id = i.index_id
            INNER JOIN sys.columns co ON co.object_id = i.object_id AND co.column_id = ic.column_id
            INNER JOIN sys.tables t ON t.object_id = i.object_id
            WHERE t.is_ms_shipped = 0 AND t.[name] = ?
            ORDER BY i.[name], ic.is_included_column, ic.key_ordinal
        ", [$table]);

        foreach ($info as $val) {
            $name    = $val['name'];
            $column  = $val['column_name'];
            $unique  = boolval($val['is_unique']);
            $primary = boolval($val['is_primary_key']);
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

        $definition = [];
        foreach ($columns as $column => $attr) {
            $definition[$column] = $this->db->quoteName($column) . ' ' . $this->compileColumnDefinition($attr);
        }
        $definition = implode(', ', $definition);

        $temporary   = isset($options['temporary']) ? $options['temporary'] : false;
        $quotedTable = $this->db->quoteName($temporary ? '#' . $table : $table);

        /** @noinspection SqlNoDataSourceInspection */
        $sql = "CREATE TABLE {$quotedTable} ({$definition})";

        // and this is MSSQL specific...

        $this->db->transaction(function() use($sql, $table, $columns, $options) {

            $this->db->exec($sql);

            // save comments
            $comment = isset($options['comment']) ? $options['comment'] : null;
            if (!is_null($comment)) {
                $comment = $this->db->quote($comment);
                $qtable  = $this->db->quote($table);
                $this->db->exec("
                    EXEC sp_addextendedproperty 'MS_Description', 
                        {$comment}, 'SCHEMA', 'dbo', 'TABLE', {$qtable}
                ");
            }

            foreach ($columns as $column => $attr) {
                $type = $attr['type'];
                $comment = isset($attr['comment']) ? $attr['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = (!is_null($comment) ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if (!is_null($comment)) {
                    $comment = $this->db->quote($comment);
                    $qtable  = $this->db->quote($table);
                    $column  = $this->db->quote($column);
                    $this->db->exec("
                        EXEC sp_addextendedproperty 'MS_Description', 
                            {$comment}, 'SCHEMA', 'dbo', 'TABLE', {$qtable}, 'COLUMN', {$column}
                    ");
                }
            }
        });

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameTable($from, $to)
    {
        $from = $this->db->quote($from);
        $to   = $this->db->quote($to);

        $this->db->exec("EXEC sp_rename {$from}, {$to}");

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, array $options)
    {
        $type     = $options['type'];
        $default  = isset($options['default'])  ? $options['default']  : null;
        $nullable = isset($options['nullable']) ? $options['nullable'] : false;

        if (
            (in_array($type, ['identity', 'bigidentity', 'timestamp']) || // cannot add a identity or timestamp column to a non-empty table
            (!$nullable && is_null($default)) || // cannot add a NOT NULL column without default value to a non-empty table
            $default == 'CURRENT_TIMESTAMP') &&  // cannot add a column with non-constant default to a non-empty table
            !$this->isEmpty($table)
        ) {
            // We have to recreate the table...
            $this->recreateTable($table, ['column' => $column, 'options' => $options]);
        }
        else {
            $this->db->transaction(function () use ($table, $column, $options, $type) {
                // We can add the column on the regularly way.
                $quotedTable  = $this->db->quoteName($table);
                $quotedColumn = $this->db->quoteName($column);
                $definition   = $this->compileColumnDefinition($options);
                $this->db->exec("ALTER TABLE {$quotedTable} ADD {$quotedColumn} {$definition}");

                // save comment
                $comment = isset($options['comment']) ? $options['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = (!is_null($comment) ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if (!is_null($comment)) {
                    $comment = $this->db->quote($comment);
                    $qtable  = $this->db->quote($table);
                    $column  = $this->db->quote($column);
                    $this->db->exec("
                    EXEC sp_addextendedproperty 'MS_Description', 
                        {$comment}, 'SCHEMA', 'dbo', 'TABLE', {$qtable}, 'COLUMN', {$column}
                    ");
                }
            });
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $from, $to)
    {
        $from  = $this->db->quote($table . '.' . $from);
        $to    = $this->db->quote($to);

        if ($from  != $to) {
            $this->db->exec("EXEC sp_rename {$from}, {$to}, 'COLUMN'");
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addIndex($table, $columns, array $options = [])
    {
        $columns       = (array)$columns;
        $quotedTable   = $this->db->quoteName($table);
        $quotedColumns = '[' . str_replace(',', '],[', str_replace(']', ']]', implode(',', $columns))) . ']';

        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            $this->db->exec("ALTER TABLE {$quotedTable} ADD PRIMARY KEY ($quotedColumns)");
        }
        else {
            // We can add the index on the regularly way.
            $unique = isset($options['unique']) ? $options['unique'] : false;
            $index  = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $name   = empty($options['name']) ? $this->createIndexName($table, $columns, $unique) : $options['name'];

            $quotedName = $this->db->quoteName($name);
            $this->db->exec("CREATE {$index} {$quotedName} ON {$quotedTable} ($quotedColumns)");
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($table, $columns, array $options = [])
    {
        $quotedTable = $this->db->quoteName($table);

        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            /** @noinspection SqlDialectInspection */
            $name = $this->db->scalar("
                SELECT name  
                FROM sys.key_constraints  
                WHERE type = 'PK' 
                AND OBJECT_NAME(parent_object_id) = ?;
            ", [$table]);
            $this->db->exec("ALTER TABLE {$quotedTable} DROP CONSTRAINT {$name}");
        }
        else {
            if (empty($options['name'])) {
                if (empty($columns)) {
                    throw new InvalidArgumentException("Cannot find index without name and columns.");
                }
                $unique  = isset($options['unique']) ? $options['unique'] : false;
                $name = $this->createIndexName($table, (array)$columns, $unique);
            }
            else {
                $name = $options['name'];
            }

            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("DROP INDEX {$name} ON {$quotedTable}");
        }

        return $this;
    }

    /**
     * Re-Create the table with modified column.
     *
     * @param string $table
     * @param array $params
     */
    private function recreateTable($table, $params)
    {
        // Bei einem fehlerhaften INSERT-Statement und mit aktiven Transaction wird ständig die App neu aufgerufen!!
        // (Browser meldet "Server antowrtet nicht"). Muss fehler vom Trieber sein.
        $this->db->transaction(function() use($table, $params) {
            $quotedTable = $this->db->quoteName($table);
            $columns = $this->columns($table);
            $indexes = $this->indexes($table);
            $oldColumns = '[' . str_replace(',', '],[', str_replace(']', ']]', implode(',', array_keys($columns)))) . ']';
            $column  = $params['column'];
            $options = $params['options'];

            // 1. rename the old table

            $oldTable = 't' . uniqid();
            $this->renameTable($table, $oldTable);

            // 2. create the new table with the new column
            $columns[$column] = $options;
            $this->createTable($table, $columns);
            $newColumns = '[' . str_replace(',', '],[', str_replace(']', ']]', implode(',', array_keys($columns)))) . ']';

            // 3. copy the contents across from the original table
            /** @noinspection SqlNoDataSourceInspection */
            $quotedColumn = $this->db->quoteName($column);
            $zero = $this->zero($options['type']);
            $this->db->exec("INSERT INTO {$quotedTable} ({$newColumns}) SELECT {$oldColumns}, ? AS {$quotedColumn} FROM {$oldTable}", [$zero]);

            // 4. drop the old table
            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("DROP TABLE {$oldTable}");

            // 5. Create the indexes
            foreach ($indexes as $attr) {
                $this->addIndex($table, $attr['columns'], $attr);
            }
        });
    }

    /**
     * Determine if the table is empty.
     *
     * @param string $table
     * @return bool
     */
    private function isEmpty($table)
    {
        $table = $this->db->quoteName($table);

        /** @noinspection SqlDialectInspection */
        return $this->db->scalar("SELECT COUNT(*) FROM {$table}") == 0;
    }

    /**
     * @inheritdoc
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

        $type = strtolower($options['type']);

        if ($type == 'identity') { // Is the column the auto-incremented primary key?
            $sql = 'INT NOT NULL IDENTITY(1,1) PRIMARY KEY';
        }
        else if ($type == 'bigidentity') {
            $sql = 'BIGINT NOT NULL IDENTITY(1,1) PRIMARY KEY';
        }
        else {
            $size = $options['size'];
            $scale = $options['scale'];
            $sql = $this->compileFieldType($type, $size, $scale);

            if (in_array($type, ['string', 'text'])) {
                $collation = !empty($options['collation']) ? $options['collation'] : $this->db->config('collation');
                if (!is_null($collation)) {
                    $sql .= ' COLLATE ' . $collation;
                }
            }

            if (!is_null($options['default'])) {
                $default = $options['default'];
                if (is_string($default) && $default != 'CURRENT_TIMESTAMP') {
                    $default = $this->db->quote($options['default']);
                }
                else if (is_bool($default)) {
                    $default = $default ? '1' : '0';
                }
                $sql .= ' DEFAULT ' . $default;
            }

            if ($options['nullable']) {
                $sql .= ' NULL';
            }
            else {
                $sql .= ' NOT NULL';
            }
        }

        return $sql;
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
            case 'unsigned':
                return 'INT';

            case 'float':
                return 'FLOAT';

            case 'string':
                return 'NVARCHAR(' . ($size ?: 255) . ')';

            case 'guid':
                return 'UNIQUEIDENTIFIER';

            case 'blob':
                return 'IMAGE';

            case 'boolean':
                return 'BIT';

            case 'date':
                $version = $this->db->version() ?: '2008';
                return $version >= '2008' ? 'DATE' : 'DATETIME';

            case 'timestamp':  // datetime with time zone
                $version = $this->db->version() ?: '2008';
                return $version >= '2008' ? 'DATETIMEOFFSET' : 'DATETIME';

            case 'time':
                $version = $this->db->version() ?: '2008';
                return $version >= '2008' ? 'TIME' : 'DATETIME';

            default:
                return parent::compileFieldType($type, $size, $scale);
        }
    }

    /**
     * @inheritdoc
     */
    protected function needATypeHint($type)
    {
        $version = $this->db->version() ?: '2008';
        if ($version >= '2008') {
            return in_array($type, ['unsigned', 'array', 'json', 'object']);
        }

        return in_array($type, ['unsigned', 'array', 'json', 'object', 'date', 'time', 'timestamp']); // @codeCoverageIgnore
    }

    /**
     * Convert a database field type to a column type supported by Database Access Layer.
     *
     * @param string $dbType Native database field type
     * @return string
     */
    private function convertFieldType($dbType)
    {
        switch (strtoupper($dbType)) {
            case 'SMALLINT':
            case 'TINYINT':
                return 'smallint';

            case 'INT':
            case 'MONEY':
            case 'SMALLMONEY':
                return 'integer';

            case 'BIGINT':
                return 'bigint';

            case 'DECIMAL':
            case 'NUMERIC':
                return 'numeric';

            case 'FLOAT':
            case 'REAL':
            case 'DOUBLE':
            case 'DOUBLE PRECISION':
                return 'float';

            case 'NVARCHAR':
            case 'VARCHAR':
            case 'NCHAR':
            case 'CHAR':
                return 'string';

            case 'TEXT':
            case 'NTEXT':
                return 'text';

            case 'UNIQUEIDENTIFIER':
                return 'guid';

            case 'VARBINARY':
            case 'BINARY':
                return 'binary';

            case 'IMAGE':
                return 'blob';

            case 'BIT':
                return 'boolean';

            case 'DATE':
                return 'date';

            case 'DATETIME':
            case 'SMALLDATETIME':
                return 'datetime';

            case 'TIME':
                return 'time';

            case 'DATETIMEOFFSET':
                return 'timestamp';

            default:
                return 'string';  // fallback Type
        }
    }

    /**
     * @inheritdoc
     */
    public function zero($type)
    {
        if (in_array($type, ['date'])) {
            return '0001-01-01';
        }

        if (in_array($type, ['datetime', 'timestamp'])) {
            return '0001-01-01 00:00:00';
        }

        return parent::zero($type);
    }
}