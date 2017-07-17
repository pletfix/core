<?php

namespace Core\Services\PDOs\Schemas;

use InvalidArgumentException;

/**
 * SQLite Database Schema
 *
 * Functions tables() and columns() are based on Aura.SqlSchema
 * Function compileFieldType() based on Dontrine's Mapping Matrix.
 *
 * @see https://github.com/auraphp/Aura.SqlSchema/blob/2.x/src/SqliteSchema.php Aura.SqlSchema on GitHub
 * @see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#mapping-matrix Doctrine's Mapping Matrix
 */
class SQLiteSchema extends AbstractSchema
{
    /**
     * Case Insensitive Collation (default by database access layer)
     *
     * @var string
     */
    private $collateCI = 'NOCASE';

    /**
     * Case Sensitive Collation (default by sqlite)
     *
     * @var string
     */
    private $collateCS = 'BINARY';

    /**
     * @inheritdoc
     */
    public function tables()
    {
        $tables = [];

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $info = $this->db->query("
            SELECT name 
            FROM sqlite_master 
            WHERE type = 'table'
            AND name <> 'sqlite_sequence' 
            AND name <> '_comments' 
            ORDER BY name
        ");

        foreach ($info as $val) {
            $name = $val['name'];
            $tables[$name] = [
                'name'      => $name,
                'collation' => $this->collateCS, // == BINARY (default by sqlite)
                'comment'   => null,
            ];
        }

        // merge comments...

        /** @noinspection SqlDialectInspection */
        $info = $this->db->query('SELECT table_name, content FROM _comments WHERE column_name IS NULL');
        foreach ($info as $val) {
            $name = $val['table_name'];
            if (isset($tables[$name])) {
                $tables[$name]['comment'] = $val['content'];
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

        $comments = [];
        /** @noinspection SqlDialectInspection */
        $info = $this->db->query('SELECT column_name, content FROM _comments WHERE table_name = ? AND column_name IS NOT NULL', [$table]);
        foreach ($info as $val) {
            $comments[$val['column_name']] = $val['content'];
        }

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $createSql = $this->db->scalar("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);

        $info  = $this->db->query('PRAGMA TABLE_INFO(' . $this->db->quote($table) . ')');
        foreach ($info as $val) {
            $name     = $val['name'];
            $nullable = !(bool)($val['notnull']);
            $default  = !is_null($val['dflt_value']) ? trim($val['dflt_value'], "'") : null;

            list($dbType, $size, $scale, $unsigned) = $this->extractFieldType($val['type']);
            list($type, $comment) = $this->extractTypeHintFromComment(isset($comments[$name]) ? $comments[$name] : null);

            $quotedName = "\"$name\"|'$name'|`$name`|\\[$name\\]";

            if (is_null($type)) {
                // find autoincrement column in CREATE TABLE sql
                $pattern = '/(' . $quotedName . ')\s+INTEGER\s+(?:NULL\s+|NOT\s+NULL\s+)?PRIMARY\s+KEY\s+AUTOINCREMENT/Ui';
                $autoinc = (bool)preg_match($pattern, $createSql, $matches);
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

            // find collation of column in CREATE TABLE sql
            $collation = null;
            if (in_array($type, ['string', 'text'])) {
                $pattern = '/(?:' . $quotedName . ')[^,(]+(?:\([^()]+\)[^,]*)?(?:(?:DEFAULT|CHECK)\s*(?:\(.*?\))?[^,]*)*COLLATE\s+["\']?([^\s,"\')]+)/isx';
                //$pattern = '/(?:' . $quotedName . ').*COLLATE\s+(\w++)/U';
                if (preg_match($pattern, $createSql, $matches)) {
                    $collation = $matches[1];
                }
                else {
                    $collation = $this->collateCS; // sqlite's default
                }
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
     * Extract a given database field type into field type, size, scale and unsigned flag.
     *
     * This function could be used by columns().
     *
     * @param string $spec The database field specification; for example, "VARCHAR(255)" or "NUMERIC(10,2) UNSIGNED".
     * @return array
     */
    private function extractFieldType($spec)
    {
        if (!preg_match('/(\w+)(?:\s*\(\s*([0-9]+)(?:\,\s*([0-9]+))?\s*\))?(?:\s*(\w+))?/s', strtoupper($spec), $match)) {
            return [null, null, null, null];
        }

        $dbType   = isset($match[1]) ? $match[1] : null;
        $size     = isset($match[2]) && $match[2] != '' ? (int)$match[2] : null;
        $scale    = isset($match[3]) && $match[2] != '' ? (int)$match[3] : null;
        $unsigned = isset($match[4]) && $match[4] == 'UNSIGNED';

        return [$dbType, $size, $scale, $unsigned];
    }

    /**
     * @inheritdoc
     */
    public function indexes($table)
    {
        $indexes = [];

        // Find INTEGER PRIMARY KEY AUTOINCREMENT column.
        $info  = $this->db->query('PRAGMA TABLE_INFO(' . $this->db->quote($table) . ')');
        $pks = [];
        foreach ($info as $val) {
            if ($val['pk']) {
                $pks[] = $val['name'];
            }
        }

        $info = $this->db->query('PRAGMA INDEX_LIST(' . $this->db->quote($table) . ')');
        $foundPrimary = false;
        foreach ($info as $val) {
            $name    = $val['name'];
            $unique  = (bool)($val['unique']);
            $info2 = $this->db->query('PRAGMA INDEX_INFO(' . $this->db->quoteName($name) . ')');
            $columns = [];
            //$primary = $val['origin'] == 'pk'; // not available by sqlite 3.7.17
            $primary = true;
            foreach ($info2 as $val2) {
                $columns[] = $val2['name'];
                if ($primary && !in_array($val2['name'], $pks)) {
                    $primary = false;
                }
            }
            $indexes[$name] = [
                'name'    => $name,
                'columns' => $columns,
                'unique'  => $unique,
                'primary' => $primary,
            ];
            if ($primary) {
                $foundPrimary = $primary;
            }
        }

        if (!$foundPrimary && !empty($pks)) {
            $indexes['PRIMARY'] = [
                'name'    => 'PRIMARY',
                'columns' => array_values($pks),
                'unique'  => true,
                'primary' => true,
            ];
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

        // and this is Sqlite specific...

        $this->db->transaction(function() use($sql, $table, $columns, $options) {
            $this->db->exec($sql);

            // save comments
            /** @noinspection SqlDialectInspection */
            $this->db->exec('DELETE FROM _comments WHERE table_name = ?', [$table]);
            $comment = !empty($options['comment']) ? $options['comment'] : null;
            if (!is_null($comment)) {
                /** @noinspection SqlDialectInspection */
                $this->db->exec('INSERT INTO _comments (table_name, content) VALUES (?, ?)', [$table, $comment]);

            }
            foreach ($columns as $column => $attr) {
                $type = $attr['type'];
                $comment = isset($attr['comment']) ? $attr['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = (!is_null($comment) ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if (!is_null($comment)) {
                    /** @noinspection SqlDialectInspection */
                    $this->db->exec('INSERT INTO _comments (table_name, column_name, content) VALUES (?, ?, ?)', [$table, $column, $comment]);

                }
            }
        });

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropTable($table)
    {
        $this->db->transaction(function() use($table) {
            $quotedTable = $this->db->quoteName($table);

            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("DROP TABLE {$quotedTable}");

            /** @noinspection SqlDialectInspection */
            $this->db->exec('DELETE FROM _comments WHERE table_name = ?', [$table]);
        });

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameTable($from, $to)
    {
        $this->db->transaction(function() use($from, $to) {
            $quotedFrom = $this->db->quoteName($from);
            $quotedTo = $this->db->quoteName($to);

            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("ALTER TABLE {$quotedFrom} RENAME TO {$quotedTo}");

            /** @noinspection SqlDialectInspection */
            $this->db->exec('UPDATE _comments SET table_name = ? WHERE table_name = ?', [$to, $from]);
        });

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
            in_array($type, ['identity', 'bigidentity']) || // sqlite cannot add a PRIMARY KEY column
            (!$nullable && is_null($default)) || // sqlite cannot add a NOT NULL column without default value
            $default == 'CURRENT_TIMESTAMP'      // sqlite cannot add a column with non-constant default
        ) {
            // We have to recreate the table...
            $this->recreateTable($table, 'addColumn', ['column' => $column, 'options' => $options]);
        }
        else {
            $this->db->transaction(function () use ($table, $column, $options, $type) {
                // We can add the column on the regularly way.
                $quotedTable  = $this->db->quoteName($table);
                $quotedColumn = $this->db->quoteName($column);
                $definition   = $this->compileColumnDefinition($options);
                $this->db->exec("ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$definition}");
                // save comment
                $comment = isset($options['comment']) ? $options['comment'] : null;
                if ($this->needATypeHint($type)) {
                    $comment = (!is_null($comment) ? $comment . ' ' : '') . '(DC2Type:' . $type . ')';
                }
                if (!is_null($comment)) {
                    /** @noinspection SqlDialectInspection */
                    $this->db->exec('INSERT OR REPLACE INTO _comments (table_name, column_name, content) VALUES (?, ?, ?)', [$table, $column, $comment]);

                }
                else {
                    /** @noinspection SqlDialectInspection */
                    $this->db->exec('DELETE FROM _comments WHERE table_name = ? AND column_name = ?', [$table, $column]);
                }
            });
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropColumn($table, $column)
    {
        $this->recreateTable($table, 'dropColumn', ['column' => $column]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $from, $to)
    {
        $this->recreateTable($table, 'renameColumn', ['from' => $from, 'to' => $to]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addIndex($table, $name, array $options)
    {
        $columns = $options['columns'];
        if (empty($columns)) {
            throw new InvalidArgumentException("Cannot add index without columns.");
        }

        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) {
            // We have to recreate the table...
            $this->recreateTable($table, 'addPrimary', ['columns' => $columns]);
        }
        else {
            // We can add the index on the regularly way.
            $unique = isset($options['unique']) ? $options['unique'] : false;
            $index  = $unique ? 'UNIQUE INDEX' : 'INDEX';
            if (is_null($name)) {
                $name = $this->createIndexName($table, $columns, $unique);
            }
            $quotedTable   = $this->db->quoteName($table);
            $quotedColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', $columns))) . '"';
            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("CREATE {$index} {$name} ON {$quotedTable} ($quotedColumns)");
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($table, $name, array $options = [])
    {
        $primary = isset($options['primary']) ? $options['primary'] : false;
        if ($primary) { // index associated with PRIMARY KEY constraint cannot be dropped
            $this->recreateTable($table, 'dropIndex', ['primary' => true]);
        }
        else {
            $unique = isset($options['unique']) ? $options['unique'] : false;
            if (is_null($name)) {
                if (empty($options['columns'])) {
                    throw new InvalidArgumentException("Cannot find index without name and columns.");
                }
                $columns = $options['columns'];
                $name = $this->createIndexName($table, $columns, $unique);
                // todo der Name sollte besser aus der Indexliste gesucht werden.
                // Momentan wird einfach angenommen, dass der Index wie vom Access Layer vorgegeben heißt.
            }
            if ($unique) { // index associated with UNIQUE constraint cannot be dropped
                $this->recreateTable($table, 'dropIndex', ['name' => $name]);
            }
            else {
                /** @noinspection SqlNoDataSourceInspection */
                $this->db->exec("DROP INDEX {$name}");
            }
        }

        return $this;
    }

    /**
     * Re-Create the table with modified column.
     *
     * @param string $table
     * @param string $action
     * @param array $params
     */
    private function recreateTable($table, $action, $params)
    {
        $this->db->transaction(function() use($table, $action, $params) {
            $quotedTable = $this->db->quoteName($table);
            $columns = $this->columns($table);
            $indexes = $this->indexes($table);
            $oldColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($columns)))) . '"';

            // 1. rename the old table
            $oldTable = 't' . uniqid();
            $this->db->exec("ALTER TABLE {$quotedTable} RENAME TO {$oldTable}");

            if ($action == 'addColumn') {
                $column  = $params['column'];
                $options = $params['options'];

                // 2. create the new table with the new column
                $columns[$column] = $options;
                $this->createTable($table, $columns);
                $newColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($columns)))) . '"';

                // 3. copy the contents across from the original table
                /** @noinspection SqlNoDataSourceInspection */
                $quotedColumn = $this->db->quoteName($column);
                $zero = $this->zero($options['type']);
                $this->db->exec("INSERT INTO {$quotedTable} ({$newColumns}) SELECT {$oldColumns}, ? AS {$quotedColumn} FROM {$oldTable}", [$zero]);
            }
            else if ($action == 'dropColumn') {
                $column = $params['column'];

                // 2. create the new table without dropped column
                unset($columns[$column]);
                $this->createTable($table, $columns);
                $newColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($columns)))) . '"';

                /** @noinspection SqlDialectInspection */
                $this->db->exec('DELETE FROM _comments WHERE table_name = ? AND column_name = ?', [$table, $column]);

                // 3. copy the contents across from the original table
                /** @noinspection SqlNoDataSourceInspection */
                $this->db->exec("INSERT INTO {$quotedTable} ({$newColumns}) SELECT {$newColumns} FROM {$oldTable}");
            }
            else if ($action == 'renameColumn') {
                $from = $params['from'];
                $to   = $params['to'];

                // 2. create the new table with renamed column
                $columns[$from]['name'] = $to;
                $newColumns = [];
                foreach ($columns as $attr) {
                    $newColumns[$attr['name']] = $attr;
                }
                $this->createTable($table, $newColumns);
                $newColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', array_keys($newColumns)))) . '"';

                /** @noinspection SqlDialectInspection */
                $this->db->exec('DELETE FROM _comments WHERE table_name = ? AND column_name = ?', [$table, $from]);

                // 3. copy the contents across from the original table
                /** @noinspection SqlNoDataSourceInspection */
                $this->db->exec("INSERT INTO {$quotedTable} ({$newColumns}) SELECT {$oldColumns} FROM {$oldTable}");
            }
            else if ($action == 'addPrimary') {
                $idxColumns = $params['columns'];

                // 2. create the new table with primary key
                $definition = [];
                foreach ($columns as $column => $attr) {
                    $definition[$column] = $this->db->quoteName($column) . ' ' . $this->compileColumnDefinition($attr);
                }
                $definition = implode(', ', $definition);
                $idxColumns = '"' . str_replace(',', '","', str_replace('"', '""', implode(',', $idxColumns))) . '"';
                /** @noinspection SqlNoDataSourceInspection */
                $this->db->exec("CREATE TABLE {$quotedTable} ({$definition}, PRIMARY KEY ($idxColumns))");

                // 3. copy the contents across from the original table
                /** @noinspection SqlNoDataSourceInspection */
                $this->db->exec("INSERT INTO {$quotedTable} ({$oldColumns}) SELECT {$oldColumns} FROM {$oldTable}");
            }
            else if ($action == 'dropIndex') {
                $primary = isset($params['primary']) ? $params['primary'] : false ;
                if ($primary) {
                    $indexes = array_filter($indexes, function($index) {
                        return !$index['primary'];
                    });
                }
                else {
                    $name = $params['name'];
                    $indexes = array_filter($indexes, function($index) use ($name) {
                        return $index['name'] != $name;
                    });
                }
                $this->createTable($table, $columns);
                $this->db->exec("INSERT INTO {$quotedTable} ({$oldColumns}) SELECT {$oldColumns} FROM {$oldTable}");
            }

            // 4. drop the old table
            /** @noinspection SqlNoDataSourceInspection */
            $this->db->exec("DROP TABLE {$oldTable}");

            // 5. Create the indexes
            foreach ($indexes as $name => $attr) {
                $this->addIndex($table, $name, $attr);
            }
        });
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
            'autoinc'   => null,
            'collation' => null,
            'comment'   => null,
        ], $options);

        $type  = strtolower($options['type']);

        if ($type == 'identity' || $type == 'bigidentity') {     // Is the column the auto-incremented primary key?
            $sql = 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL'; // AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
        }
        else {
            $size  = $options['size'];
            $scale = $options['scale'];
            $sql   = $this->compileFieldType($type, $size, $scale);

            if (!$options['nullable']) {
                $sql .= ' NOT NULL';
            }

            if (!is_null($options['default'])) {
                $default = $options['default'];
                if (is_string($default) && $default != 'CURRENT_TIMESTAMP') {
                    $default = $this->db->quote($options['default']);
                }
                $sql .= ' DEFAULT ' . $default;
            }

            if (in_array($type, ['string', 'text'])) {
                $collation = !empty($options['collation']) ? $options['collation'] : $this->collateCI;
                $sql .= ' COLLATE ' . $collation;
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
    protected function needATypeHint($type)
    {
        return in_array($type, ['bigidentity', 'array', 'json', 'object']);
    }

    /**
     * Convert a database field type to a column type supported by Database Access Layer.
     *
     * @see https://www.sqlite.org/datatype3.html Datatypes In SQLite Version 3
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
            case 'INTEGER':
            case 'SERIAL':
                return 'integer';

            case 'BIGINT':
            case 'MEDIUMINT':
            case 'LONGINT':
            case 'BIGSERIAL':
                return 'bigint';

            case 'NUMERIC':
            case 'DECIMAL':
                return 'numeric';

            case 'DOUBLE':
            case 'DOUBLE PRECISION':
            case 'REAL':
                return 'float';

            case 'VARCHAR':
            case 'VARCHAR2':
            case 'NVARCHAR':
            case 'CHAR':
            case 'TINYTEXT':
            case 'STRING':
                return 'string';

            case 'TEXT':
            case 'NTEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
            case 'LONGVARCHAR':
            case 'CLOB':
                return 'text';

            case 'UUID':
            case 'GUID':
                return 'guid';

            case 'VARBINARY':
            case 'BINARY':
                return 'binary';

            case 'BLOB':
            case 'IMAGE':
                return 'blob';

            case 'BOOLEAN':
            case 'BOOL':
            case 'TINYINT':
                return 'boolean';

            case 'DATE':
                return 'date';

            case 'DATETIME':
                return 'datetime';

            case 'TIMESTAMP':
                return 'timestamp';

            case 'TIME':
                return 'time';

            default:
                // Determination Of Column Affinity, see https://www.sqlite.org/datatype3.html
                if (stripos($dbType, 'INT') !== false) {
                    return 'integer';
                }
                if (stripos($dbType, 'CHAR') !== false) {
                    return 'string';
                }
                if (stripos($dbType, 'TEXT') !== false || stripos($dbType, 'CLOB') !== false) {
                    return 'text';
                }
                if (stripos($dbType, 'BLOB') !== false) {
                    return 'blob';
                }
                if (stripos($dbType, 'REAL') !== false || stripos($dbType, 'FLOA') !== false || stripos($dbType, 'DOUB') !== false) {
                    return 'float';
                }

                return 'string';  // Fallback Type
        }
    }

}