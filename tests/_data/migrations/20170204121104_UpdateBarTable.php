<?php

use Core\Services\Contracts\Database;
use Core\Services\Contracts\Migration as MigrationContract;

class UpdateBarTable implements MigrationContract
{
    public function up(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('INSERT INTO foo ("name") VALUES (?)', ['bar']);
    }

    public function down(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('DELETE FROM foo WHERE "name"=?', ['bar']);
    }
}