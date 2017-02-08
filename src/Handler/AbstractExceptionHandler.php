<?php

namespace Core\Handler;

use Core\Exceptions\Contracts\HttpException;
use Core\Handler\Contracts\ExceptionHandler as ExceptionHandlerContract;
use Exception;

/**
 * Class ExceptionHandler
 *
 * This code based on Laravel (see Illuminate\Foundation\Exceptions).
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerContract
{
    /**
     * A list of the exception types that should not be logged.
     *
     * @var array
     */
    private $dontLog = [
//        AuthenticationException::class,
//        AuthorizationException::class,
        HttpException::class,
//        ModelNotFoundException::class,
//        TokenMismatchException::class,
//        ValidationException::class,
    ];

//    private $levels = [
//        E_DEPRECATED => 'Deprecated',
//        E_USER_DEPRECATED => 'User Deprecated',
//        E_NOTICE => 'Notice',
//        E_USER_NOTICE => 'User Notice',
//        E_STRICT => 'Runtime Notice',
//        E_WARNING => 'Warning',
//        E_USER_WARNING => 'User Warning',
//        E_COMPILE_WARNING => 'Compile Warning',
//        E_CORE_WARNING => 'Core Warning',
//        E_USER_ERROR => 'User Error',
//        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
//        E_COMPILE_ERROR => 'Compile Error',
//        E_PARSE => 'Parse Error',
//        E_ERROR => 'Error',
//        E_CORE_ERROR => 'Core Error',
//    ];

    /**
     * @inheritdoc
     */
    public function handle(Exception $e)
    {
        $this->log($e);
        $this->dump($e);
    }

    /**
     * Log the exception.
     *
     * @param Exception $e
     * @throws Exception
     */
    public function log(Exception $e)
    {
        if ($this->shouldntLog($e)) {
            return;
        }

        $exception = get_class($e);
        $message   = $e->getMessage();
        $relevantTraceEntry = null; //$this->findRelevantTraceEntry($e);
        $file = trim(str_replace(base_path(), '', isset($relevantTraceEntry) ? $relevantTraceEntry['file'] : $e->getFile()), '\/ ');
        $line = isset($relevantTraceEntry) ? $relevantTraceEntry['line'] : $e->getLine();
        $code = $e->getCode();

        try {
            $logger = logger();
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

//        if ($e instanceof QueryException) {
//            if (!empty($e->errorInfo[2])) {
//                $message  = $e->errorInfo[2];
//            }
//            $sql      = $e->getSql();
//            $bindings = implode(', ', $e->getBindings());
//            $ansiSqlStateErrorCode   = isset($e->errorInfo[0]) ? $e->errorInfo[0] : null;
//            $driverSpecificErrorCode = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
//            $logger->error($exception . ' in "' . $file . '", line ' . $line . ': ' . $message . '; sql: ' . $sql . '; bindings: [' . $bindings . '] (ansi sql state ' . $ansiSqlStateErrorCode . ', driver-specific error code ' . $driverSpecificErrorCode . ')');
//        }
//        else { // ErrorException, BadMethodCallException, ...
            $logger->error($exception . ' in "' . $file . '", line ' . $line . ': ' . $message . ' (code ' . $code . ')');
//        }
    }

    /**
     * Determine if the exception is in the "do not log" list.
     *
     * @param  \Exception  $e
     * @return bool
     */
    private function shouldntLog(Exception $e)
    {
        foreach ($this->dontLog as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dump the exception.
     *
     * @param \Exception $e
     */
    abstract public function dump(Exception $e);
}
