<?php

namespace Core\Services\Contracts;

use Core\Services\PDOs\Schemas\Contracts\Schema;

interface Migration
{
    /**
     * Migrate Up
     *
     * @param Schema $schema
     */
    public function up(Schema $schema);

    /**
     * Migrate Down
     *
     * @param Schema $schema
     */
    public function down(Schema $schema);
}