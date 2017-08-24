<?php

namespace Core\Services\PDOs\Builders;

/**
 * Microsoft SQL Server Query Builder
 */
class MSSQLBuilder extends Builder
{
    /**
     * @inheritdoc
     */
    public function toSql()
    {
        if (empty($this->limit)) {
            return parent::toSql();
        }

        if (empty($this->offset)) {
            return 'SELECT TOP ' . $this->limit
                . $this->buildFlags()
                . $this->buildColumns()
                . $this->buildFrom()
                . $this->buildJoin()
                . $this->buildWhere()
                . $this->buildGroupBy()
                . $this->buildHaving()
                . $this->buildOrderBy();
        }

        $version = (int)$this->db->version();

        if ($version >= 2012) {
            return 'SELECT'
                . $this->buildFlags()
                . $this->buildColumns()
                . $this->buildFrom()
                . $this->buildJoin()
                . $this->buildWhere()
                . $this->buildGroupBy()
                . $this->buildHaving()
                . ' ORDER BY ' . (empty($this->orderBy) ? '(SELECT 0)' : implode(', ', $this->orderBy))
                . ' OFFSET ' . $this->offset . ' ROWS FETCH NEXT ' . $this->limit . ' ROWS ONLY';
        }

        if ($version >= 2005) {
            /** @noinspection SqlDialectInspection */
            return 'SELECT * FROM ('
                . 'SELECT'
                . $this->buildFlags()
                . $this->buildColumns()
                . ', ROW_NUMBER() OVER (ORDER BY ' . (empty($this->orderBy) ? '(SELECT 0)' :  implode(', ', $this->orderBy)) . ') AS _row'
                . $this->buildFrom()
                . $this->buildJoin()
                . $this->buildWhere()
                . $this->buildGroupBy()
                . $this->buildHaving()
                . ') AS _t1 WHERE _row BETWEEN ' . ($this->offset + 1) . ' AND ' . ($this->offset + $this->limit);
        }

        // Microsoft SQL Server 2000:

        $sql = $this->buildFlags()
            . $this->buildColumns()
            . $this->buildFrom()
            . $this->buildJoin()
            . $this->buildWhere()
            . $this->buildGroupBy()
            . $this->buildHaving()
            . $this->buildOrderBy();

        if (empty($this->orderBy)) {
            // by MSSQL, if a TOP clause is used in a sub select, an ORDER BY clause is required!
            $asc  = [];
            $desc = [];
            $table   = trim($this->getTable(), '[]');
            $columns = $this->db->schema()->columns($table);
            foreach ($columns as $column) {
                if ($column['type'] == 'identity' || $column['type'] == 'bigidentity') {
                    $asc  = ['[' . $column['name'] . ']'];
                    $desc = ['[' . $column['name'] . '] DESC'];
                    break;
                }
                $asc[]  = '[' . $column['name'] . ']';
                $desc[] = '[' . $column['name'] . '] DESC';
            }
            $asc  = implode(', ', $asc);
            $desc = implode(', ', $desc);
            $sql .= ' ORDER BY ' . $asc;
        }
        else {
            $desc = [];
            foreach ($this->orderBy as $column) {
                if (($pos = strpos($column, ' DESC')) !== false) {
                    $desc[] = substr($column, 0, $pos);
                }
                else if (($pos = strpos($column, ' ASC')) !== false) {
                    $desc[] = substr($column, 0, $pos) . ' DESC';
                }
                else {
                    $desc[] = $column . ' DESC';
                }
            }
            $asc  = implode(', ', $this->orderBy);
            $desc = implode(', ', $desc);
        }

        $n = $this->count();
        if ($this->limit > $n - $this->offset) {
            $this->limit = $n > $this->offset ? $n - $this->offset : 0;
        }

        /** @noinspection SqlDialectInspection */
        return 'SELECT * FROM ('
            .    'SELECT TOP ' . $this->limit . ' * FROM ('
            .       'SELECT TOP ' . ($this->offset + $this->limit) . $sql
            .    ') AS _t1 ORDER BY ' . $desc
            . ') AS _t2 ORDER BY ' . $asc;
    }

    /**
     * @inheritdoc
     */
    public function bindings()
    {
        if (!empty($this->bindings['order']) && !empty($this->offset) && (int)$this->db->version() < 2005) {
            return array_merge(parent::bindings(), $this->bindings['order'], $this->bindings['order']);
        }

        return parent::bindings();
    }
}