<?php

namespace Core\Bootstraps;

use Core\Exceptions\FatalErrorException;
use Core\Services\DI;
use Core\Bootstraps\Contracts\Bootable;
use ErrorException;
use Exception;
use Throwable;

/**
 * Class HandleExceptions
 *
 * This code based on Laravel (see Illuminate\Foundation\Bootstrap).
 */
class HandleExceptions implements Bootable
{
    /**
     * Bootstrap
     *
     * @codeCoverageIgnore
     */
    public function boot()
    {
        error_reporting(-1); // E_ALL
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
        if (config('app.env') != 'testing') {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Handle an uncaught exception.
     *
     * Note: Since PHP 7, the handler also catches Errors which are caused by the most internal PHP functions.
     *
     * @see http://php.net/manual/en/function.set-exception-handler.php)
     *
     * @param Throwable $e
     */
    public function handleException($e)
    {
        if (!$e instanceof Exception) { // Since PHP 7, in addition to an Exception, an internal Error may occur.
            // Convert the Error to ErrorException
            $e = new ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine()); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        $this->getExceptionHandler()->handle($e);
    }

    /**
     * Convert an "error reporting" (caused by some internal PHP functions) to an ErrorException.
     *
     * Example to throw an error reporting:
     * <code>
     *      fopen('foo', 'r');
     *
     *      //Note: This will not fire an exception:
     *      @ fopen('foo', 'r');
     * </code>
     *
     * @see http://php.net/manual/en/function.set-error-handler.php
     * @see http://php.net/manual/en/class.errorexception.php
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @throws ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, /** @noinspection PhpUnusedParameterInspection */ $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    } // @codeCoverageIgnore

    /**
     * Convert an fatal Error to an FatalErrorException.
     *
     * A fatal Error can not be caught because it is not throwable. The application will be shut down immediately.
     * It will be occur e.g. if you try to call a function which does not exists.
     *
     * @see http://php.net/manual/en/function.set-error-handler.php
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        if ($error !== null && $this->isFatal($error['type'])) {
            $this->handleException(new FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line'])); // @codeCoverageIgnore
        } // @codeCoverageIgnore
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     * @return bool
     */
    private function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return \Core\Handler\Contracts\ExceptionHandler
     */
    private function getExceptionHandler()
    {
        return DI::getInstance()->get('exception-handler');
    }
}
