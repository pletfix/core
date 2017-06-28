<?php

namespace Core\Services;

use Core\Services\Contracts\DI as DIContract;

/**
 * Dependency Injector.
 *
 * All global services have to be registered in this IoC Container (Inversion of Control).
 *
 * @see http://poe-php.de/oop/objektorientierung-oop-verwalten-der-dienste-dependency-injection Dependency Injection
 * @see http://www.phptherightway.com/pages/Design-Patterns.html Singleton Pattern
 */
class DI implements DIContract
{
    /**
     * The current globally available container (if any).
     *
     * @var DI
     */
    private static $instance;

    /**
     * The container's services.
     *
     * @var array
     */
    private $services = [];

    /**
     * Don't allow object instantiation.
     */
    private function __construct() {}

    /**
     * Don't allow object cloning.
     */
    private function __clone() {}

    /**
     * @inheritdoc
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self(); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        return self::$instance;
    }

//    public static function saveToCache()
//    {
//        file_put_contents(storage_path('cache/services.php'), serialize(self::$instance)); // Serialization of 'Closure' is not allowed :-(
//    }
//
//    public static function loadFromCache()
//    {
//        return self::$instance = unserialize(file_get_contents(storage_path('cache/services.php')));
//    }

    /**
     * @inheritdoc
     */
    public function get($name, array $arguments = [])
    {
        if (!isset($this->services[$name])) {
            throw new \RuntimeException('Requested service "' . $name . '" not defined.');
        }

        $service = $this->services[$name];

        if (!$service->shared) {
            // Multiple instance!
            return $this->createInstance($service->definition, $arguments, false);
        }

        if ($service->instance === null) {
            $service->instance = $this->createInstance($service->definition, [], true);
        }

        return $service->instance;
    }

    /**
     * @inheritdoc
     */
    public function set($name, $definition, $shared = false)
    {
        $this->services[$name] = (object)[
            'definition' => $definition,
            'shared'     => $shared,
            'instance'   => null
        ];
    }

    /**
     * Create an instance of the given service.
     *
     * @param string|object|\Closure $definition
     * @param array $arguments
     * @param bool $shared
     * @return object
     */
    private function createInstance($definition, $arguments, $shared)
    {
        if (is_string($definition)) {
            return new $definition(...$arguments);
        }

        if (is_callable($definition)) {
            return $definition(...$arguments);
        }

        if (is_object($definition)) {
            if ($shared) {
                return $definition;
            }
            else {
                return new $definition(...$arguments);
            }
        }

        throw new \RuntimeException('Invalid service definition!');
    }
}