<?php

namespace Core\Services\Contracts;

use LogicException;
use RuntimeException;

interface Process
{
    /**
     * Runs a process.
     *
     * The STDOUT and STDERR are also available after the process is finished via the getOutput() and getErrorOutput() methods.
     *
     * @param int|float|null $timeout Seconds to wait until the process is finished. Set null to disable the timeout.
     * @return int|null The exit code of the process (or null at timeout)
     * @throws LogicException   When process is already running
     * @throws RuntimeException When process can't be launched
     */
    public function run($timeout = 60);

    /**
     * Starts a background process.
     *
     * @throws LogicException   When process is already running
     * @throws RuntimeException When process can't be launched
     */
    public function start();

    /**
     * Waits for the process to terminate.
     *
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @return int|null The exit code of the process (or null at timeout)
     */
    public function wait($timeout = 60);

    /**
     * Terminate the process.
     *
     * This optional parameter is only useful on POSIX operating systems; you may specify a signal to send to the
     * process using the kill(2) system call. The default is SIGTERM (15).
     *
     * SIGTERM (15) is the termination signal. The default behavior is to terminate the process, but it also can be
     * caught or ignored. The intention is to kill the process, but to first allow it a chance to cleanup.
     *
     * SIGKILL (9) is the kill signal. The only behavior is to kill the process, immediately. As the process cannot
     * catch the signal, it cannot cleanup, and thus this is a signal of last resort.
     *
     * @param int $signal
     * @return bool The termination status of the process that was run.
     */
    public function terminate($signal = 15);

    /**
     * Kill the process immediately.
     *
     * @return bool The termination status of the process that was run.
     */
    public function kill();

    /**
     * Read a line from STDOUT.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function read();

    /**
     * Write a line to STDIN.
     *
     * Returns the number of bytes written, or FALSE on error.
     *
     * @param string $line
     * @return int|false
     */
    public function write($line);

    /**
     * Get the output from STDOUT when the process is finished.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function getOutput();

    /**
     * Get the error output from STDERR when the process is finished.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function getErrorOutput();

    /**
     * Determine if the process is currently running.
     *
     * @return bool true if the process is currently running, false otherwise.
     */
    public function isRunning();

    /**
     * Get the exit code returned by the process.
     *
     * @return null|int The exit status code, null if the Process is not terminated.
     */
    public function getExitCode();

    /**
     * Determine if the process ended successfully.
     *
     * @return bool true if the process ended successfully, false otherwise.
     */
    public function isSuccessful();
}