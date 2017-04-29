<?php

namespace Core\Services\PDOs\Schemas\Contracts;

/**
 * Database Schema Management
 */
interface Schema
{
    /**
     * Returns an array of tables in the database.
     *
     * Each return item is an array with the following values:
     * - name:      (string) The table name
     * - collation: (string) The default collation of the table.
     * - comment:   (string) A hidden comment.
     *
     * @return array An associative array where the key is the table name and the value is the table attributes.
     */
    public function tables();

    /**
     * Returns an array of columns in a table.
     *
     * Each return item is an array with the following values:
     * - name:      (string) The column name
     * - type:      (string) The column data type. Data types are as reported by the database.
     * - size:      (int)    The column size (the maximum number of digits).
     * - scale:     (int)    The number of digits to the right of the numeric point. It must be no larger than size.
     * - nullable:  (bool)   Is the column not marked as NOT NULL?
     * - default:   (mixed)  The default value for the column.
     * - collation: (string) The collation of the column.
     * - comment:   (string) A hidden comment.
     *
     * @param string $table Name of the table.
     * @return array An associative array where the key is the column name and the value is the column attributes.
     */
    public function columns($table);

    /**
     * Returns an array of indexes in a table.
     *
     * Each return item is an array with the following values:
     * - name:      (string) The index name.
     * - columns:   (array)  The list of column names.
     * - unique:    (bool)   Is the index a unique index?
     * - primary:   (bool)   Is the index the primary key?
     *
     * @param string $table Name of the table.
     * @return array An associative array where the key is the index name and the value is the index attributes.
     */
    public function indexes($table);

    /**
     * Create a new table on the schema.
     *
     * Parameter $columns is an associative array where the key is the column name and the value is the column attributes.
     * A column attribute is an array with the following values:
     * - type:      (string) The column data type. Data types are as reported by the database.
     * - size:      (int)    The column size (the maximum number of digits).
     * - scale:     (int)    The number of digits to the right of the numeric point. It must be no larger than size.
     * - nullable:  (bool)   Is the column is not marked as NOT NULL.
     * - default:   (mixed)  The default value for the column.
     * - collation: (string) The collation of the column.
     * - comment:   (string) A hidden comment.
     *
     * Options have following values:
     * - temporary: (bool)   The table is temporary.  todo kann raus!
     * - collation: (string) The default collation of the table.
     * - comment:   (string) A hidden comment.
     *
     * @param  string $table
     * @param array $columns
     * @param array $options
     */
    public function createTable($table, array $columns, array $options = []);

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     */
    public function dropTable($table);

    /**
     * Rename a table on the schema.
     *
     * @param string $from old table name
     * @param string $to new table name
     */
    public function renameTable($from, $to);

    /**
     * Add a new column to the table.
     *
     * Options have following values:
     * - type:      (string) The column data type. Data types are as reported by the database.
     * - size:      (int)    The column size (the maximum number of digits).
     * - scale:     (int)    The number of digits to the right of the numeric point. It must be no larger than size.
     * - nullable:  (bool)   The column is not marked as NOT NULL.
     * - default:   (mixed)  The default value for the column.
     * - collation: (string) The collation of the column.
     * - comment:   (string) A hidden comment.
     *
     * @param string $table
     * @param string $column
     * @param array $options
     */
    public function addColumn($table, $column, array $options);

    /**
     * Drop a column from the table
     *
     * @param string $table
     * @param string $column
     */
    public function dropColumn($table, $column);

    /**
     * Rename a column for the table.
     *
     * @param string $table
     * @param string $from
     * @param string $to
     */
    public function renameColumn($table, $from, $to);

    /**
     * Create an index for a given table.
     *
     * Options have following values:
     * - columns    (string[])  List of column names.
     * - unique:    (bool)      The index is a unique index.
     * - primary:   (bool)      The index is the primary key.
     *
     * @param string $table Name of the table which the index is for.
     * @param string|null $name The name of the index. It will be generated automatically if not set and will be ignored by a primary key.
     * @param array $options
     */
    public function addIndex($table, $name, array $options);

    /**
     * Drop a index from the table.
     *
     * Options have following values:
     * - columns    (string[])  List of column names. Could be null if the index name is given or it's the primary index.
     * - unique:    (bool)      The index is a unique index. It's needed to generate the name.
     * - primary:   (bool)      The index is the primary key.
     *
     * @param string $table
     * @param string|null $name The name of the index. It will be generated automatically if not set.
     * @param array $options
     */
    public function dropIndex($table, $name, array $options = []);

    /**
     * Get a Zero-Value by given column type.
     * @param string $type Column Type supported by Database Access Layer
     * @return string
     */
    public function zero($type);
}