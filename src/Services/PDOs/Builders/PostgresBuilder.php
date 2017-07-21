<?php

namespace Core\Services\PDOs\Builders;

/**
 * PostgreSQL Query Builder
 */
class PostgresBuilder extends AbstractBuilder
{
    /**
     * @inheritdoc
     */
    protected function doInsert(array $data = [])
    {
        if (empty($data)) {
            // todo leere Sätze im Bulk-Modus werden noch nicht abgefagen
            $table   = trim($this->getTable(), '"');
            $columns = $this->db->schema()->columns($table);
            $column  = key($columns); // first columns
            $attr    = $columns[$column];
            $qTable  = $this->db->quoteName($table);
            $qColumn = $this->db->quoteName($column);
            if (in_array($attr['type'], ['identity', 'bigidentity'])) {
                $value = "nextval(pg_get_serial_sequence('$table', '$column'))";
                /** @noinspection SqlDialectInspection */
                $this->db->exec("INSERT INTO $qTable ($qColumn) VALUES ($value)");
            }
            else {
                $value = $attr['default'];
                if ($value === null && !$attr['nullable']) {
                    $value = '';
                }
                /** @noinspection SqlDialectInspection */
                $this->db->exec("INSERT INTO $qTable ($qColumn) VALUES (?)", [$value]);
            }

            return $this->db->lastInsertId();
        }

        return parent::doInsert($data);
    }
}