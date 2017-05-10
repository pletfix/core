<?php

namespace Core\Services\PDOs\Builder;

/**
 * MySql Query Builder
 */
class MySqlBuilder extends AbstractBuilder
{
    /**
     * @inheritdoc
     */
    public function doDelete()
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