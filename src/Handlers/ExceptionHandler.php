<?php

namespace Core\Handlers;

use Core\Exceptions\Contracts\HttpException;
use Core\Exceptions\QueryException;
use Core\Exceptions\StopException;
use Core\Handlers\Contracts\ExceptionHandler as ExceptionHandlerContract;
use Core\Services\Contracts\Command;
use Core\Services\Contracts\Response;
use Exception;
use SqlFormatter;
use Throwable;

/**
 * Class ExceptionHandler
 *
 * This code based on Laravel's Exception Handlers.
 * Function renderPHP() was inspirited by selfphp.de ("Programmcode farbig hervorheben inkl. Zeilennummern").
 *
 * @see https://github.com/laravel/laravel/blob/5.3/app/Exceptions/Handler.php
 * @see http://www.selfphp.de/kochbuch/kochbuch.php?code=39
 */
class ExceptionHandler implements ExceptionHandlerContract
{
    /**
     * @inheritdoc
     */
    public function handle(Exception $e)
    {
        try {
            $this->log($e);
        }
        catch (Exception $e2) { // don't overwrite the original exception
            if (!is_writable(storage_path('logs')) || !is_writable(storage_path('cache'))) { // cannot generate view without writable cache!
                return response()->output('
                    <div style="font-family: Arial,serif; color: #a94442; background-color: #f2dede; border-color: #ebccd1; padding: 1px 1px 1px 10px; border-radius: 4px;">' .
                    (config('app.debug') ? '<p><b>Fatal Error!</b> ' . $e->getMessage() . '</p>' : '').
                    '<p>The directories within the storage folder must be writable for your web server! Check the permissions!</p>' .
                    '</div>'
                )->send();
            }
        }

        if (is_console()) {
            $this->handleForConsole($e);
        }
        else {
            $this->handleForBrowser($e);
        }
    }

    /**
     * Log the exception.
     *
     * @param Exception $e
     * @throws Exception
     */
    private function log(Exception $e)
    {
        $dontLog = [
//            AuthenticationException::class,
//            AuthorizationException::class,
            HttpException::class,
            StopException::class,
//            TokenMismatchException::class,
//            ValidationException::class,
        ];

        foreach ($dontLog as $type) {
            if ($e instanceof $type) {
                return;
            }
        }

        $data = $this->convertException($e);
        $class = get_class($e);
        $file  = $this->stripFile($data['file']);
        $line  = $data['line'];
        $code  = $data['code'];

        // set the error message
        if ($e instanceof QueryException) {
            $message = $data['message'] . '; sql: ' . $data['sql'];
        }
        else {
            $message = $data['message'];
        }

        logger()->error($class . ' in "' . $file . '", line ' . $line . ': ' . $message . ' (code ' . $code . ')');
    }

    /**
     * Write a error message to the console and exit the script.
     *
     * @param Exception $e
     */
    private function handleForConsole(Exception $e)
    {
        if ($e instanceof StopException) {
            $output = $e->getMessage();
        }
        else {
            $data = $this->convertException($e);
            $class = get_class($e);
            $file  = $this->stripFile($data['file']);
            $line  = $data['line'];
            $code  = $data['code'];

            // set the error message
            if ($e instanceof HttpException) {
                $message = http_status_text($data['status']) . ' (' . $data['status'] . ')';
                if (!empty($data['message'])) {
                    $message .=  '. ' . $data['message'];
                }
            }
            else if ($e instanceof QueryException) {
                $message = $data['message'] . '; sql: ' . $data['sql'];
            }
            else {
                $message = $data['message'];
            }

            $output = $class . ' in "' . $file . '", line ' . $line . ':' . PHP_EOL . $message . ' (code ' . $code . ')';
        }

        stdio()->error($output);

        $exitCode = $e->getCode();
        if ($exitCode === null) {
            $exitCode = Command::EXIT_FAILURE;
        }

        exit ($exitCode);
    }

    /**
     * Send a error message as a HTTP response.
     *
     * @param Exception $e
     */
    private function handleForBrowser(Exception $e)
    {
//        $request = request();
//        if ($e instanceof ModelNotFoundException) {
//            $e = new NotFoundHttpException($e->getMessage(), $e);
//        }
//        elseif ($e instanceof AuthorizationException) {
//            $e = new HttpException(403, $e->getMessage());
//        }
//
//        if ($e instanceof HttpResponseException) {
//            return $e->getResponse();
//        }
//        if ($e instanceof AuthenticationException) {
//            header('Location: ' . url('auth/login'));
//            exit;
//        }
//        else if ($e instanceof ValidationException) {
//            return $this->convertValidationExceptionToResponse($e, $request);
//        }
//        else if ($request->isAjax() || $request->wantsJson()) {
//            /** @var HttpException $e */
//            return response()->json($e->getMessage(), $this->isHttpException($e) ? $e->getStatusCode() : 400);
//        }

        $data = $this->convertException($e);

        // set error title and subtitle

        if ($e instanceof HttpException) {
            // set the HTTP Response Status Text as error title
            $data['title'] = http_status_text($data['status']);

            // set the Exception class and the HTTP Response Status Code as subtitle
            $data['subtitle'] = get_class($e) . ', Status Code ' . $data['status'];
        }
        else if ($e instanceof QueryException) {
            // set title
            $data['title'] = 'SQL Error';

            // set the Exception class and error codes as subtitle
            $data['subtitle'] = get_class($e) . ' (Exception code: ' . $data['code'] . ')';
            if ($data['ansiCode'] !== null) {
                $data['subtitle'] .= ', ANSI SQLSTATE Error Code: ' . $data['ansiCode'];
            }
            if ($data['driverCode'] !== null) {
                $data['subtitle'] .= ', Driver-specific Error Code: ' . $data['driverCode'];
            }
        }
        else {
            // set error title
            $data['title'] = 'Error';

            // // set the Exception class and the Exception code as subtitle
            $data['subtitle'] = get_class($e) . ' (Exception code: ' . $data['code'] . ')';
        }

        // render the source code

        if ($e instanceof QueryException) {
            // parse the sql line from the message if exists
            if (preg_match('/at line (\d)$/', $data['message'], $match)) {
                $sqlLine = $match[1];
            }
            else {
                $sqlLine = 0;
            }
            // set the SQL Statement as source
            $data['source'] = $this->renderSQL($data['sql'], $sqlLine);
        }
        else {
            // set the PHP Code as source
            $data['source'] = $this->renderPHP($data['file'], $data['line']);
        }

        // render the trace

        $data['trace'] = $this->renderTrace($data['trace']);

        // strip the filename

        $data['file'] = $this->stripFile($data['file']);

        // choose the template

        $view = config('app.debug') ? 'errors.debug' : 'errors.default';
        if (!config('app.debug') && view()->exists('errors.' . $data['status'])) {
            $view = 'errors.' . $data['status'];
        }

        // send the response

        $headers = ($e instanceof HttpException) ? $e->getHeaders() : [];

        return response()->view($view, $data, $data['status'], $headers)->send();
    }

    ///////////////////////////////////////////////////////////////////////////
    // Helpers

    /**
     * Convert a Exception to a simple array.
     *
     * @param Exception $e
     * @return array
     */
    private function convertException(Exception $e)
    {
        $data = [];
        $data['message']  = $e->getMessage();
        $data['code']     = $e->getCode();
        $data['trace']    = $this->filterTrace($e->getTrace());
        $data['status']   = Response::HTTP_INTERNAL_SERVER_ERROR;

        // determine the relevant file
        $firstTraceEntry = !empty($data['trace']) ? reset($data['trace']) : null;
        if ($firstTraceEntry !== null && isset($firstTraceEntry['file']) && isset($firstTraceEntry['line'])) {
            $data['file'] = $firstTraceEntry['file'];
            $data['line'] = $firstTraceEntry['line'];
        }
        else {
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }

        if ($e instanceof HttpException) {
            $data['status'] = $e->getStatusCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        else if ($e instanceof QueryException) {
            $data['ansiCode']   = isset($e->errorInfo[0]) ? $e->errorInfo[0] : null;
            $data['driverCode'] = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
            if (!empty($e->errorInfo[2])) {
                $data['message'] = $e->errorInfo[2];
            }
            $data['sql'] = $e->getDump();
        }

        return $data;
    }

    /**
     * Get the filename relative to base path.
     *
     * @param $file
     * @return string
     */
    private function stripFile($file)
    {
        return trim(str_replace(base_path(), '', $file), '\/ ');
    }

    /**
     * Render PHP
     *
     * @param string $file
     * @param int $line
     * @return null|string
     */
    private function renderPHP($file, $line)
    {
        try {
            $count = count(file($file));
            $code  = highlight_file($file, true);
            return $this->wrapLineNumbers($count, $code, $line);
        }
        catch(Throwable $e) { // Error or Exception (executed only in PHP 7, will not match in PHP 5)
            return null;
        }
        catch(Exception $e) { // Once PHP 5 support is no longer needed, this block can be removed.
            return null;
        }
    }

    /**
     * Render SQL.
     *
     * @param string $sql
     * @param int $line
     * @return string
     */
    private function renderSQL($sql, $line)
    {
        try {
            $sql = trim($sql);
            $count = count(explode(PHP_EOL, $sql));
            $code  = '<code>' . SqlFormatter::highlight($sql) . '</code>';
            return $this->wrapLineNumbers($count, $code, $line);
        }
        catch(Throwable $e) { // Error or Exception (executed only in PHP 7, will not match in PHP 5)
            return null;
        }
        catch(Exception $e) { // Once PHP 5 support is no longer needed, this block can be removed.
            return null;
        }
    }

    /**
     * Render code with line numbers
     *
     * @param int $count
     * @param string $code
     * @param int $line
     * @return null|string
     */
    private function wrapLineNumbers($count, $code, $line)
    {
        $length  = max(strlen($count), 2);
        $numbers = [];
        for ($number = 1; $number <= $count; $number++) {
            $numbers[] = str_pad($number, $length, '0', STR_PAD_LEFT);
        }
        if ($line > 0) {
            $numbers[$line - 1] = '<span class="errorline">' . $numbers[$line - 1] . '</span>';
        }
        $numbers = implode('<br/>', $numbers) . '<br/>';

        return
            '<table class="code"><tr>' .
            '<td><code>' . $numbers . '</code></td>' .
            '<td>' . $code . '</td>' .
            '</tr></table>';
    }

    /**
     * Filter Trace to relevant entries
     *
     * @param array $trace
     * @return array
     */
    private function filterTrace($trace)
    {
        $ignore = 'app/Services/Database.php';
        $j = strlen($ignore);
        foreach ($trace as $i => $entry) {
            if (isset($entry['file'])) {
                $s = $this->stripFile($entry['file']);
                if (substr($s, 0, $j) == $ignore) {
                    unset($trace[$i]);
                }
            }
        }

        return $trace;
    }

    /**
     * Render Trace
     *
     * @param array $trace
     * @return string
     */
    private function renderTrace($trace)
    {
        foreach ($trace as $i => $entry) {
            $s = '';
            if (!empty($entry['file'])) {
                $s .= $this->stripFile($entry['file']);
                if (!empty($entry['line'])) {
                    $s .= ' (line ' . $entry['line'] . ')';
                }
            }
            if (!empty($entry['function'])) {
                $class = !empty($entry['class']) ? $entry['class'] . $entry['type'] : '';
                $args  = !empty($entry['args']) ? $this->dumpArgs($entry['args']) : '' ;
                $s .= ':<br/>&nbsp;&nbsp;&nbsp;' . $class . $entry['function'] . '(' . $args . ')';
            }
            $trace[$i] = '<li>' . $s . '</li>';
        }

        return '<code><ul>' . implode('<br/>', $trace) . '</ul></code>';
    }

    /**
     * Render the arguments of a function.
     *
     * @param $args
     * @return string
     */
    private function dumpArgs($args)
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg)) {
                $v = '{' . get_class($arg) . '}';
            }
            else if (is_array($arg)) {
                $v = '[' . $this->dumpArgs($arg) . ']';
            }
            else if (is_string($arg)) {
                $v = '"' . (strlen($arg) <= 16 ? $arg : trim(substr($arg, 0, 15)) . '...') . '"';
            }
            else if (is_bool($arg)) {
                $v = $arg ? 'true' : 'false';
            }
            else if ($arg === null) {
                $v = 'null';
            }
            else {
                $v = $arg;
            }

            $args[$key] = is_string($key) ? '"' . $key . '" => ' . $v : $v;
        }

        return implode(', ', $args);
    }
}
