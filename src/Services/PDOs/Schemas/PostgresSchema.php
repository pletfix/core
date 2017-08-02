<?php

namespace Core\Services\PDOs\Schemas;

use InvalidArgumentException;

/**
 * PostgreSQL Database Schema
 *
 * Functions tables() and columns() are based on Aura.SqlSchema
 * Function compileFieldType() based on Dontrine's Mapping Matrix.
 *
 * @see https://github.com/auraphp/Aura.SqlSchema/blob/2.x/src/PgsqlSchema.php Aura.SqlSchema on GitHub
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix Doctrine's Mapping Matrix
 */
class PostgresSchema extends Schema
{
    /**
     * @inheritdoc
     */
    public function tables()
    {
        $schema = $this->db->config('schema');

        $tables = [];

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $info = $this->db->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name != 'geometry_columns'
            AND table_name != 'spatial_ref_sys'
            AND table_type != 'VIEW'
            ORDER BY table_name
        ", [$schema]);

        foreach ($info as $val) {
            $name = $val['table_name'];
            $tables[$name] = [
                'name'      => $name,
                'collation' => null,
                'comment'   => null,
            ];
        }

        // merge comments...

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT c.relname, d.description
            FROM pg_class AS c
            INNER JOIN pg_description AS d ON (d.objoid = c.oid AND d.objsubid = 0)
            INNER JOIN pg_namespace AS n ON c.relnamespace = n.oid
            WHERE c.relkind = 'r'
            AND n.nspname = ?
            AND d.description > ''
        ", [$schema]);
        foreach ($info as $val) {
            $name = $val['relname'];
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
        $schema = $this->db->config('schema');

        $columns = [];

        // get comments
        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT c.column_name, pgd.description
            FROM pg_statio_all_tables AS st
            INNER JOIN pg_description pgd ON pgd.objoid = st.relid
            INNER JOIN information_schema.columns c ON (pgd.objsubid = c.ordinal_position AND c.table_schema = st.schemaname and c.table_name = st.relname)
            WHERE c.table_schema = ? 
            AND st.relname = ?
            AND pgd.description > ''
        ", [$schema, $table]);
        $comments = [];
        foreach ($info as $val) {
            $comments[$val['column_name']] = $val['description'];
        }

        // modified from Zend_Db_Connection_Pdo_Pgsql
        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT column_name, 
              data_type,
              column_default,
              is_nullable,
              is_identity,
              character_maximum_length AS length,
              numeric_precision AS precision,
              numeric_precision_radix AS radix,
              numeric_scale AS scale,
              collation_name
            FROM information_schema.columns
            WHERE table_schema = ?
            AND table_name = ?
            ORDER by ordinal_position
        ", [$schema, $table]);

        foreach ($info as $val) {
            $name      = $val['column_name'];
            $dbType    = strtoupper($val['data_type']);
            $size      = $val['radix'] == 10 ? $val['precision'] : $val['length'];
            $scale     = $val['radix'] == 10 ? $val['scale'] : null;
            $nullable  = $val['is_nullable'] == 'YES';
            $default   = $val['column_default'];
            $collation = $val['collation_name'];

            list($type, $comment) = $this->extractTypeHintFromComment(isset($comments[$name]) ? $comments[$name] : null);

            if ($type === null) {
                // $identity = $val['is_identity'] == 'YES'; // always NO :-(
                $autoinc = $default !== null && substr($default, 0, 8) == 'nextval(';
                if ($autoinc) {
                    $type = $dbType == 'BIGINT' ? 'bigidentity' : 'identity';
                    $default = null;
                }
                else {
                    $type = $this->convertFieldType($dbType);
                }
            }

            if ($default !== null) {
                if ($default == 'now()') {
                    $default = 'CURRENT_TIMESTAMP';
                }
                else if (($pos = strrpos($default, '::')) !== false) {
                    $default = substr($default, 0, $pos);
                    if (!is_numeric($default)) {
                        $k = substr($default, 0, 1);
                        if ($k == '"' || $k == "'") {
                            $default = substr($default, 1, -1); // remove the leading and trailing quotes...
                        }
                    }
                }
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
                'comment'   => $comment,
            ];
        }

        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function indexes($table)
    {
        $schema = $this->db->config('schema');

        $indexes = [];

        // merge unique-flag...

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query("
            SELECT
              i.relname as index_name,
              a.attname as column_name,
              ix.indisunique,
              ix.indisprimary
            FROM pg_index AS ix
            INNER JOIN pg_class AS t ON t.oid = ix.indrelid
            INNER JOIN pg_class AS i ON i.oid = ix.indexrelid
            INNER JOIN pg_namespace AS n ON t.relnamespace = n.oid
            INNER JOIN pg_attribute AS a ON (a.attrelid = t.oid AND a.attnum = ANY(ix.indkey))
            WHERE t.relkind = 'r'
            AND n.nspname = ?
            AND t.relname = ?
            ORDER BY i.relname
        ", [$schema, $table]);

        foreach ($info as $val) {
            $name   = $val['index_name'];
            $column = $val['column_name'];
            $unique = $val['indisunique'];
            $primary = $val['indisprimary'];
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

        $definition = [];
        foreach ($columns as $column => $attr) {
            $definition[$column] = $this->db->quoteName($column) . ' ' . $this->compileColumnDefinition($attr);
        }
        $definition = implode(', ', $definition);

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

        // and this is PostgreSQL specific...

        $this->db->transaction(function() use($sql, $table, $columns, $options) {
            $this->db->exec($sql);

            $quotedTable = $this->db->quoteName($table);

            // save comments
            $comment = isset($options['comment']) ? $options['comment'] : null;
            if ($comment !== null) {
                $comment = $this->db->quote($comment);
                /** @noinspection SqlDialectInspection */
                $this->db->exec("COMMENT ON TABLE {$quotedTable} IS {$comment}");

            }
            foreach ($columns as $column => $attr) {
                $type = $attr['type'];
                $comment = isset($attr['comment']) ? $attr['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = ($comment !== null ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if ($comment !== null) {
                    $quotedColumn = $this->db->quoteName($column);
                    $comment = $this->db->quote($comment);
                    /** @noinspection SqlDialectInspection */
                    $this->db->exec("COMMENT ON COLUMN {$quotedTable}.{$quotedColumn} IS {$comment}");
                }
            }
        });

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, array $options)
    {
        $default  = isset($options['default'])  ? $options['default']  : null;
        $nullable = isset($options['nullable']) ? $options['nullable'] : false;

        if (!$nullable && $default === null && !$this->isEmpty($table)) { // cannot add a NOT NULL column without default value to a non-empty table
            // We have to recreate the table...
            $this->recreateTable($table, ['column' => $column, 'options' => $options]);
        }
        else {
            $this->db->transaction(function () use ($table, $column, $options) {
                // We can add the column on the regularly way.
                $quotedTable  = $this->db->quoteName($table);
                $quotedColumn = $this->db->quoteName($column);
                $definition   = $this->compileColumnDefinition($options);
                $this->db->exec("ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$definition}");

                // save comment
                $type = $options['type'];
                $comment = isset($options['comment']) ? $options['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = ($comment !== null ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if ($comment !== null) {
                    $comment = $this->db->quote($comment);
                    /** @noinspection SqlDialectInspection */
                    $this->db->exec("COMMENT ON COLUMN {$quotedTable}.{$quotedColumn} IS {$comment}");
                }
            });
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
        $quotedColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', $columns))) . '"';

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
        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            $quotedTable = $this->db->quoteName($table);
            $name = $table . '_pkey';
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
            $this->db->exec("DROP INDEX {$name}");
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
        // Bug by the PDO driver?
        // If the INSERT statement is incorrect and the transaction is running, the app is constantly called again !!
        // At some point, the browser says "Server does not respond".

        //$this->transaction(function() use($table, $params) {
            $quotedTable = $this->db->quoteName($table);
            $columns = $this->columns($table);
            $indexes = $this->indexes($table);
            $column  = $params['column'];
            $options = $params['options'];
            $oldColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($columns)))) . '"';

            // 1. rename the old table
            $oldTable = 't' . uniqid();
            $this->renameTable($table, $oldTable);

            // 2. create the new table with the new column
            $columns[$column] = $options;
            $this->createTable($table, $columns);
            $newColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($columns)))) . '"';

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
        //});
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

        $type  = strtolower($options['type']);

        if ($type == 'identity') { // Is the column the auto-incremented primary key?
            $sql = 'SERIAL PRIMARY KEY NOT NULL';
        }
        else if ($type == 'bigidentity') {
            $sql = 'BIGSERIAL PRIMARY KEY NOT NULL';
        }
        else {
            $size  = $options['size'];
            $scale = $options['scale'];
            $sql   = $this->compileFieldType($type, $size, $scale);

            if (!$options['nullable']) {
                $sql .= ' NOT NULL';
            }

            if ($options['default'] !== null) {
                $default = $options['default'];
                if (is_string($default) && $default != 'CURRENT_TIMESTAMP') {
                    $default = $this->db->quote($options['default']);
                }
                else if (is_bool($default) ) {
                    $default = $default ? 'TRUE' : 'FALSE';
                }
                $sql .= ' DEFAULT ' . $default;
            }

            if (in_array($type, ['string', 'text'])) {
                $collation = !empty($options['collation']) ? $options['collation'] : $this->db->config('collation');
                if (!empty($collation)) {
                    $sql .= ' COLLATE ' . $this->db->quoteName($collation);
                }
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
            case 'integer':     // 4 Byte
            case 'unsigned':     // 4 Byte
                return 'INTEGER';

            case 'bigint':      // 8 Byte
                return 'BIGINT';

            case 'float':
                return 'DOUBLE PRECISION';

            case 'json':
                $version = $this->db->version();
                return $version >= '9.4' ? 'JSONB' : ($version >= '9.2' ? 'JSON' : 'TEXT');

            case 'binary':
            case 'blob':
                return 'BYTEA';

            case 'datetime':
                return 'TIMESTAMP(0) WITHOUT TIME ZONE';

            case 'timestamp':  // datetime with time zone
                return 'TIMESTAMP(0) WITH TIME ZONE';

            case 'time':
                return 'TIME(0) WITHOUT TIME ZONE';

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
        if ($version >= '9.2') {
            return in_array($type, ['unsigned', 'array', 'object', 'blob']);
        }

        return in_array($type, ['unsigned', 'array', 'json', 'object', 'blob']); // @codeCoverageIgnore
    }

    /**
     * Convert a database field type to a column type supported by Database Access Layer.
     *
     * https://www.postgresql.org/docs/9.1/static/datatype.html
     * https://www.postgresql.org/docs/9.1/static/datatype-numeric.html#DATATYPE-INT
     *
     * @param string $dbType Native database field type
     * @return string
     */
    private function convertFieldType($dbType)
    {
        switch (strtoupper($dbType)) {
            case 'SMALLINT':
            case 'INT2':
                return 'smallint';

            case 'INTEGER':
            case 'INT':
            case 'INT4':
            case 'SERIAL':
            case 'SERIAL4':
                return 'integer';

            case 'BIGINT':
            case 'BIGSERIAL':
            case 'SERIAL8':
            case 'INT8':
                return 'bigint';

            case 'NUMERIC':
            case 'DECIMAL':
            case 'MONEY':
                return 'numeric';

            case 'DOUBLE PRECISION':
            case 'FLOAT':
            case 'FLOAT4':
            case 'FLOAT8':
            case 'REAL':
                return 'float';

            case 'VARCHAR':
            case 'CHARACTER VARYING':
            case 'INTERVAL':
            case 'INET':
            case 'CHAR':
            case 'BPCHAR':
                return 'string';

            case 'TEXT':
            case 'CITEXT':
                return 'text';

            case 'UUID':
                return 'guid';

            case 'BYTEA':
                return 'binary';

            case 'BOOLEAN':
            case 'BOOL':
                return 'boolean';

            case 'DATE':
            case 'YEAR':
                return 'date';

            case 'TIMESTAMP WITHOUT TIME ZONE':
            case 'DATETIME':
                return 'datetime';

            case 'TIMESTAMP WITH TIME ZONE':
            case 'TIMESTAMPTZ':
            case 'TIMESTAMP':
                return 'timestamp';

            case 'TIME WITHOUT TIME ZONE':
            case 'TIME':
                return 'time';

            case 'JSON':
            case 'JSONB':
                return 'json';

            default:
                return 'string'; // fallback Type
        }
    }
}