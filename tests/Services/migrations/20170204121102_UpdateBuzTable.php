<?php

use Core\Services\Contracts\Database;
use Core\Services\Contracts\Migration as MigrationContract;

class UpdateBuzTable implements MigrationContract
{
    public function up(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('INSERT INTO foo ("name") VALUES (?)', ['buz']);
    }

    public function down(Database $db)
    {
        /** @noinspection SqlDialectInspection */
        $db->exec('DELETE FROM foo WHERE "name"=?', ['buz']);
    }
}