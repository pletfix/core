<?php

namespace Core\Services\Contracts;

interface Migrator
{
    /**
     * Migrate Up
     *
     * @return $this;
     */
    public function run();

    /**
     * Migrate Down
     *
     * @return $this;
     */
    public function rollback();

    /**
     * Rollback all database migrations
     *
     * @return $this;
     */
    public function reset();
}