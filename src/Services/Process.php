<?php

namespace Core\Services;

use Core\Services\Contracts\Process as ProcessContract;
use LogicException;
use RuntimeException;

class Process implements ProcessContract
{
    /**
     * The command line to run.
     *
     * @var string
     */
    private $cmd;

    /**
     * Environment variables.
     *
     * @var array|null
     */
    private $env;

    /**
     * The pipes created by proc_open.
     * @var
     */
    private $pipes;

    /**
     * The process.
     *
     * @var resource
     */
    private $process;

    /**
     * The PID of the process.
     *
     * @var int
     */
    private $pid;

    /**
     * The exitcode of the process.
     *
     * @var int
     */
    private $exitcode;

    /**
     * Process constructor.
     *
     * @param string $cmd The command line to run.
     * @param array|null $env Environment variables, e.g. ['XDEBUG_CONFIG' => 'idekey=PHPSTORM']
     */
    function __construct($cmd, $env = null)
    {
        $this->cmd = $cmd;
        $this->env = $env;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->reset();
    }

    /**
     * Reset the data.
     */
    private function reset()
    {
        // it is important that you close any pipes before calling proc_close in order to avoid a deadlock
        if ($this->pipes !== null) {
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
            }
            $this->pipes = null;
        }

        if ($this->process !== null) {
            proc_close($this->process);
            $this->process = null;
        }

        $this->pid      = null;
        $this->exitcode = null;
    }

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
    public function run($timeout = 60)
    {
        $this->start();

        return $this->wait($timeout);
    }

    /**
     * Starts a background process.
     *
     * @throws LogicException   When process is already running
     * @throws RuntimeException When process can't be launched
     */
    public function start()
    {
        if ($this->isRunning()) {
            throw new LogicException('Process is already running');
        }

        $this->reset();

        $cmd = $this->cmd;
        if (substr($cmd, 0, 4) == 'php ') {
            $php = PHP_BINARY ?: PHP_BINDIR . '/php';
            $cmd = $php . substr($cmd, 3);
        }

        $descriptorspec = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w']  // STDERR
        ];

        $options = [
            'suppress_errors' => true,
            'binary_pipes'    => true,
        ];

        $this->process = proc_open($cmd, $descriptorspec, $this->pipes, base_path(), $this->env, $options);

        if (!is_resource($this->process)) {
            throw new RuntimeException('Unable to launch a new process.');
        }
    }

    /**
     * Waits for the process to terminate.
     *
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @return int|null The exit code of the process (or null at timeout)
     */
    public function wait($timeout = 60)
    {
        $timeoutMicro = $timeout !== null ? microtime(true) + (float)$timeout : null;
        while ($this->isRunning()) {
            if ($timeout !== null && microtime(true) >= $timeoutMicro) {
                return null;
            }
            usleep(1000);
        }

        return $this->getExitCode();
    }

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
    public function terminate($signal = 15)
    {
        if ($this->process === null) {
            return false;
        }

        return proc_terminate($this->process, $signal);
    }

    /**
     * Kill the process immediately.
     *
     * @return bool The termination status of the process that was run.
     */
    public function kill()
    {
        return $this->terminate(9);
    }

    /**
     * Read a line from STDOUT.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function read()
    {
        return $this->pipes !== null ? trim(fgets($this->pipes[1])) : false;
    }

    /**
     * Write a line to STDIN.
     *
     * Returns the number of bytes written, or FALSE on error.
     *
     * @param string $line
     * @return int|false
     */
    public function write($line)
    {
        return $this->pipes !== null ? fwrite($this->pipes[0], $line . PHP_EOL) : false;
    }

    /**
     * Get the output from STDOUT when the process is finished.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function getOutput()
    {
        return $this->pipes !== null ? stream_get_contents($this->pipes[1]) : false;
    }

    /**
     * Get the error output from STDERR when the process is finished.
     *
     * If an error occurs, returns false.
     *
     * @return string|false
     */
    public function getErrorOutput()
    {
        return $this->pipes !== null ? stream_get_contents($this->pipes[2]) : false;
    }

    /**
     * Determine if the process is currently running.
     *
     * @return bool true if the process is currently running, false otherwise.
     */
    public function isRunning()
    {
        if ($this->process === null) {
            return false;
        }

        $status = proc_get_status($this->process);
        if ($status && $status['running'] === false && $this->exitcode === null) {
            $this->exitcode = $status['exitcode'];
            // Note that you can not use proc_get_status to determine the PID because proc_open first launches a shell (sh)
            // whose PID is returned!
        }

        return $status && $status['running'];
    }

    /**
     * Get the exit code returned by the process.
     *
     * @return null|int The exit status code, null if the Process is not terminated.
     */
    public function getExitCode()
    {
        if ($this->exitcode === null && $this->process !== null) {
            $status = proc_get_status($this->process);
            if ($status && $status['running'] === false) {
                $this->exitcode = $status['exitcode'];
            }
        }

        return $this->exitcode;
    }

    /**
     * Determine if the process ended successfully.
     *
     * @return bool true if the process ended successfully, false otherwise.
     */
    public function isSuccessful()
    {
        return $this->getExitCode() === 0;
    }
}
