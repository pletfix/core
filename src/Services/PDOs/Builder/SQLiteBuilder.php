<?php

namespace Core\Services\PDOs\Builder;

/**
 * SQLite Query Builder
 */
class SQLiteBuilder extends AbstractBuilder
{
    /**
     * @inheritdoc
     */
    public function truncate()
    {
        $this->db->transaction(function() {
            $table = implode(', ', $this->from);
            $this->db->exec("DELETE FROM $table");
            /** @noinspection SqlDialectInspection */
            $this->db->exec('DELETE FROM sqlite_sequence WHERE name = ?', [trim($table, '"')]);
        });
    }
}