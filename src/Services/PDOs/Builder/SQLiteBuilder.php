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
    public function doTruncate()
    {
        return $this->db->transaction(function() {
            $table = implode(', ', $this->from);
            $result = $this->db->exec("DELETE FROM $table");
            /** @noinspection SqlDialectInspection */
            $this->db->exec('DELETE FROM sqlite_sequence WHERE name = ?', [trim($table, '"')]);

            return $result;
        });
    }
}