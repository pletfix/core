<?php

namespace Core\Services;

use Core\Services\Contracts\Delegate as DelegateContract;
use Core\Middleware\Contracts\Middleware;
use InvalidArgumentException;

class Delegate implements DelegateContract
{
    /**
     * List of Middleware classes.
     *
     * @var array
     */
    private $middleware = [];

    /**
     * The index of the next middleware to invoke.
     *
     * @var int
     */
    private $index = 0;

    /**
     * The target action which is at the end of the delegation list.
     *
     * @var callable
     */
    private $action;

    /**
     * The parameters of the target action.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Plugin's middleware.
     *
     * @var array|null
     */
    private $pluginMiddleware;

    /**
     * Manifest file of classes.
     *
     * @var string
     */
    private $pluginManifestOfMiddleware;

    /**
     * Create a new Delegate instance.
     * @param string|null $pluginManifestOfMiddleware
     */
    public function __construct($pluginManifestOfMiddleware = null)
    {
        $this->pluginManifestOfMiddleware = $pluginManifestOfMiddleware ?: manifest_path('plugins/middleware.php');
    }

    /**
     * @inheritdoc
     */
    public function setMiddleware($classes)
    {
        $this->middleware = $classes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAction($action, array $parameters)
    {
        $this->action     = $action;
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function process(Contracts\Request $request)
    {
        if (isset($this->middleware[$this->index])) {
            $class = $this->middleware[$this->index++];

            if (($pos = strpos($class, ':')) !== false) {
                $arguments = array_map(function($arg) {
                    return trim($arg);
                }, explode(',', substr($class, $pos + 1)));
                $class = substr($class, 0, $pos);
            }
            else {
                $arguments = [];
            }

            if ($class[0] != '\\') {
                if (file_exists(app_path('Middleware/' .  str_replace('\\', '/', $class) . '.php'))) {
                    $class = '\\App\\Middleware\\' . $class; // @codeCoverageIgnore
                } // @codeCoverageIgnore
                else if (($pluginMiddleware = $this->getPluginMiddleware($class)) !== null) {
                    $class = $pluginMiddleware;
                }
                else if (file_exists(__DIR__ . '/../Middleware/' .  str_replace('\\', '/', $class) . '.php')) {
                    $class = '\\Core\\Middleware\\' . $class;
                }
                else {
                    throw new InvalidArgumentException('Middleware "' . $class . '" not found.');
                }
            }

            /** @var Middleware $middleware */
            $middleware = new $class;

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            return $middleware->process($request, $this, ...$arguments);
        }

        $result = call_user_func_array($this->action, $this->parameters);

        if ($result instanceof Response) {
            return $result;
        }

        /** @var Response $response */
        $response = DI::getInstance()->get('response');
        $response->output($result);

        return $response;
    }

    /**
     * Get the full qualified class name of the plugin's middleware.
     *
     * @param string $class
     * @return null|string
     */
    private function getPluginMiddleware($class)
    {
        if ($this->pluginMiddleware === null) {
            /** @noinspection PhpIncludeInspection */
            $this->pluginMiddleware = file_exists($this->pluginManifestOfMiddleware) ? include $this->pluginManifestOfMiddleware : [];
        }

        return isset($this->pluginMiddleware[$class]) && count($this->pluginMiddleware[$class]) == 1 ? $this->pluginMiddleware[$class][0] : null;
    }
}
