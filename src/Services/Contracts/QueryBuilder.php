<?php

namespace Core\Services\Contracts;

interface QueryBuilder
{
    /**
     * Get a new Aura.SqlQuery object to build a SQL SELECT statement.
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function select();

    /**
     * Get a new Aura.SqlQuery object to build a SQL INSERT statement.
     *
     * @return \Aura\SqlQuery\Common\InsertInterface
     */
    public function insert();

    /**
     * Get a new Aura.SqlQuery object to build a SQL UPDATE statement.
     *
     * @return \Aura\SqlQuery\Common\UpdateInterface
     */
    public function update();

    /**
     * Get a new Aura.SqlQuery object to build a SQL DELETE statement.
     *
     * @return \Aura\SqlQuery\Common\DeleteInterface
     */
    public function delete();
}
