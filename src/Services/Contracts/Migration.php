<?php

namespace Core\Services\Contracts;

interface Migration
{
    /**
     * Migrate Up
     *
     * @param Database $db
     */
    public function up(Database $db);

    /**
     * Migrate Down
     *
     * @param Database $db
     */
    public function down(Database $db);
}