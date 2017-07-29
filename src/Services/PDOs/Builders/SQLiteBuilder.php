<?php

namespace Core\Services\PDOs\Builders;

/**
 * SQLite Query Builder
 */
class SQLiteBuilder extends Builder
{
    // @codeCoverageIgnoreStart
    // todo gehört zum Schema!

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

    // @codeCoverageIgnoreEnd
}