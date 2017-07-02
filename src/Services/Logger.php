<?php

namespace Core\Services;

use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface as LoggerContract;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as Monolog;
use RuntimeException;

/**
 * PSR-3 compatible Adapter for Monolog Logger
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data, the only assumption that can be made by implementors is that if an
 * Exception instance is given to produce a stack trace, it MUST be in a key named "exception".
 *
 * @see https://github.com/Seldaek/monolog Monolog Logger on GitHub
 * @see http://www.php-fig.org/psr/psr-3/ PSR-3 Specification
 */
class Logger implements LoggerContract
{
    /**
     * Instance of Monolog Logger
     *
     * @var Monolog
     */
    private $log;

    /**
     * Create a new Log instance.
     */
    public function __construct()
    {
        $this->log = new Monolog('');
        $this->log->pushProcessor(new PsrLogMessageProcessor);

        $config = array_merge([
            'type'       => 'daily',
            'level'      => 'debug',
            'max_files'  => 5,
            'app_file'   => 'app.log',
            'cli_file'   => 'cli.log',
            'permission' => 0664,
        ], config('logger'));

        $file   = storage_path('logs/' . $config[is_console() ? 'cli_file' : 'app_file']);
        $levels = $this->log->getLevels();
        $level  = $levels[strtoupper($config['level'])];
        $format = "[%datetime%] %level_name%: %message% %context%\n";

        switch ($config['type']) {
            case 'single':
                $this->log->pushHandler(
                    $handler = new StreamHandler($file, $level, true, $config['permission'], false)
                );

                $handler->setFormatter(new LineFormatter($format, null, true, true));
                break;
            case 'daily':
                $this->log->pushHandler(
                    $handler = new RotatingFileHandler($file, $config['max_files'], $level, true, $config['permission'], false)
                );
                $handler->setFormatter(new LineFormatter($format, null, true, true));
                break;
            case 'syslog':
                $this->log->pushHandler(
                    new SyslogHandler(config('app.name', 'Pletfix'), LOG_USER, $level)
                );
                break;
            case 'errorlog':
                $this->log->pushHandler(
                    $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level)
                );
                $handler->setFormatter(new LineFormatter($format, null, true, true));
                break;
            default:
                throw new RuntimeException('Invalid log type in configuration "app.log".');
        }
    }

    /**
     * @inheritdoc
     */
    public function emergency($message, array $context = [])
    {
        $this->log->addRecord(Monolog::EMERGENCY, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = [])
    {
        $this->log->addRecord(Monolog::ALERT, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = [])
    {
        $this->log->addRecord(Monolog::CRITICAL, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        $this->log->addRecord(Monolog::ERROR, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = [])
    {
        $this->log->addRecord(Monolog::WARNING, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = [])
    {
        $this->log->addRecord(Monolog::NOTICE, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        $this->log->addRecord(Monolog::INFO, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        $this->log->addRecord(Monolog::DEBUG, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        $map    = $this->log->getLevels();
        $ulevel = strtoupper($level);
        if (!isset($map[$ulevel])) {
            throw new InvalidArgumentException('Log Level ' . $level . 'is not defined.');
        }

        $this->log->addRecord($map[$ulevel], $message, $context);
    }
}