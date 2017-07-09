<?php

use Core\Services\Contracts\Database;
use Core\Services\Contracts\Migration as MigrationContract;

class CreateFooTable implements MigrationContract
{
    public function up(Database $db)
    {
        $db->schema()->createTable('foo', [
            'id'   => ['type' => 'identity'],
            'name' => ['type' => 'string'],
        ]);
    }

    public function down(Database $db)
    {
        $db->schema()->dropTable('foo');
    }
}