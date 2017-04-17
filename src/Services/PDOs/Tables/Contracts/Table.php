<?php

namespace Core\Services\PDOs\Tables\Contracts;
use Core\Services\PDOs\Builder\Contracts\Builder;

/**
 * Database Table
 */
interface Table
{
    /**
     * Find a single record by the primary key of given table.
     *
     * @param int $id Value of the primary Key
     * @param string $key Name of the primary Key
     * @param string|null $class Name of the class where the data are mapped to
     * @return mixed
     */
    public function find($id, $key = 'id', $class = null);

    /**
     * Selects records using a QueryBuilder.
     *
     * Multiple calls to select() will append to the list of columns, not overwrite the previous columns.
     *
     * @param string|array|Builder|\Closure|null $columns The columns or sub-select
     * @param string|null $alias The alias name for the sub-select.
     * @return Builder
     */
    public function select($columns = null, $alias = null);

    /**
     * Insert rows to the table and returns the inserted autoincrement sequence value.
     *
     * If you insert multiple rows, the method returns dependency of the driver the first or last inserted row.
     *
     * @param array $data Values to be updated
     * @return int
     */
    public function insert(array $data);

    /**
     * Truncate a table.
     */
    public function truncate();
}