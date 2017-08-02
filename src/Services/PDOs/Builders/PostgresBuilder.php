<?php

namespace Core\Services\PDOs\Builders;
use Core\Services\Contracts\Database;

/**
 * PostgreSQL Query Builder
 */
class PostgresBuilder extends Builder
{
    /**
     * PostgresBuilder constructor.
     *
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        if (!in_array('ILIKE', self::$keywords)) {
            self::$keywords = array_merge(self::$keywords, ['ILIKE', 'CURRENT_DATE']);
        }

        parent::__construct($db);
    }

    /**
     * @inheritdoc
     */
    protected function insertEmptyRecord()
    {
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
    }
}