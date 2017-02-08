<?php

namespace Core\Services\Contracts;

interface Migrator
{
    /**
     * Migrate Up
     */
    public function run();

    /**
     * Migrate Down
     */
    public function rollback();

    /**
     * Rollback all database migrations
     */
    public function reset();
}