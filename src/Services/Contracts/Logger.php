<?php

namespace Core\Services\Contracts;

/**
 * Class Logger
 *
 * The available log levels are "debug", "info", "notice", "warning", "error", "critical", "alert" and "emergency".
 */

interface Logger
{
    /**
     * Adds a log record at the DEBUG level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $context = []);

    /**
     * Adds a log record at the INFO level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $context = []);

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice($message, array $context = []);

    /**
     * Adds a log record at the WARNING level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning($message, array $context = []);

    /**
     * Adds a log record at the ERROR level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function error($message, array $context = []);

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical($message, array $context = []);

    /**
     * Adds a log record at the ALERT level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert($message, array $context = []);

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency($message, array $context = []);
}