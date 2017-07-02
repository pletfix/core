<?php

namespace Core\Services;

/**
 * @codeCoverageIgnore
 */
class Facade
{
    /*
     * @var string Name of the service which is used in the Service Container.
     */
    //protected static $serviceName;

    /**
     * Handles calls to static methods.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed Callback results
     */
    public static function __callStatic($method, $arguments)
    {
        if (property_exists(static::class, 'serviceName')) {
            /** @noinspection PhpUndefinedFieldInspection */
            $serviceName = static::$serviceName;
        }
        else {
            $serviceName = strtolower(static::class);
        }

        return DI::getInstance()->get($serviceName)->$method(...$arguments);
    }
}