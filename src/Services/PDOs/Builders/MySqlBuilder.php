<?php

namespace Core\Services\PDOs\Builders;

/**
 * MySql Query Builder
 */
class MySqlBuilder extends Builder
{
    /**
     * @inheritdoc
     */
    protected function insertEmptyRecord()
    {
        $table = implode(', ', $this->from);

        $this->db->exec("INSERT INTO $table () VALUES ()");
    }

    /**
     * @inheritdoc
     */
    protected function doDelete()
    {
        $bindings = array_merge($this->bindings['join'], $this->bindings['where'], $this->bindings['order']);

        $query = 'DELETE' . (!empty($this->join) ? ' ' . implode(', ', $this->from) : '')
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildOrderBy()
            . $this->buildLimit();

        return $this->db->exec($query, $bindings);
    }
}