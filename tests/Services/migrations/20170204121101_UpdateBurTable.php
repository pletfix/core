<?php

use Core\Services\Contracts\Database;
use Core\Services\Contracts\Migration as MigrationContract;

class UpdateBurTable implements MigrationContract
{
    public function up(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('INSERT INTO foo ("name") VALUES (?)', ['bur']);
    }

    public function down(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('DELETE FROM foo WHERE "name"=?', ['bur']);
    }
}