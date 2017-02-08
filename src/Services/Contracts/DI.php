<?php

namespace Core\Services\Contracts;

/**
 * Dependency Injector.
 */
interface DI
{
    /**
     * Create the container if not exists.
     *
     * @return \Core\Services\Contracts\DI
     */
    public static function getInstance();

    /**
     * Get the given service.
     *
     * The second parameter will be ignored by shared services, because singletons can never have arguments in the
     * constructor!
     *
     * @param string $name Name of the service
     * @param array $arguments Arguments of the constructor
     * @return object
     */
    public function get($name, array $arguments = []);

    /**
     * Bind a service to the container.
     *
     * @param string $name Name of the service.
     * @param string|object|\Closure $definition Could by a class name, instance or a function.
     * @param bool $shared if true, the service will be created as a singleton object
     */
    public function set($name, $definition, $shared = false);

    /**
     * Bind a shared service to the container.
     *
     * @param string $name Name of the service.
     * @param string|object|\Closure $definition Could by a class name, instance or a function.
     */
    public function singleton($name, $definition);
}
