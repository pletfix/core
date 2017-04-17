<?php

namespace Core\Services\PDOs\Tables;

/**
 * SQLite Database Table
 */
class SQLiteTable extends AbstractTable
{
    /**
     * @inheritdoc
     */
    public function truncate()
    {
        $this->db->transaction(function() {
            $quotedTable = $this->db->quoteName($this->table);
            $this->db->exec("DELETE FROM {$quotedTable}");
            /** @noinspection SqlDialectInspection */
            $this->db->exec('DELETE FROM sqlite_sequence WHERE name = ?', [$this->table]);
        });
    }
}