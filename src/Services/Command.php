<?php

namespace Core\Services;

use Core\Exceptions\StopException;
use Core\Services\Contracts\Command as CommandContract;
use InvalidArgumentException;
use LogicException;

/**
 * PHP Console application
 *
 * Function getTerminalDimensions(), getTerminalWidth() and getTerminalHeight(), getSttyColumns() and getConsoleMode()
 * are copied from Symfony's Console Application.
 *
 * @see https://github.com/symfony/console/blob/3.0/Application.php
 */
abstract class Command implements CommandContract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Possible arguments of the command.
     *
     * This is an associative array where the key is the argument name and the value is the argument attributes.
     * Each attribute is a array with following values:
     * - type:        (string) The data type (bool, float, int or string).
     * - default:     (mixed)  The default value for the argument. If not exist, the argument is required.
     * - description: (string) The description of the argument. Default: null
     *
     * @var array
     */
    protected $arguments = [
        // 'foo' => ['type' => 'string', 'default' => 'bar', 'description' => 'example'],
    ];

    /**
     * Possible options of the command.
     *
     * This is an associative array where the key is the option name and the value is the option attributes.
     * Each attribute is a array with following values:
     * - type:        (string) The data type (bool, float, int or string).
     * - short:       (string) Alternative option name.
     * - default:     (mixed)  The default value for the option. Will be ignored for bool type.
     * - description: (string) The description of the option. Default: null
     *
     * @var array
     */
    protected $options = [
        // 'foo' => ['type' => 'string', 'short' => 'f', 'default' => 'bar', 'description' => 'example'],
    ];

    /**
     * Input values.
     *
     * @var array
     */
    private $input = [];

    /**
     * Standard input/output stream.
     *
     * @var \Core\Services\Contracts\Stdio
     */
    private $stdio;

    /**
     * Terminal dimensions in which [width, height]
     *
     * @var int[]
     */
    private $terminalDimensions;

    /**
     * @inheritdoc
     */
    public function __construct($argv = null, $stdio = null)
    {
        if (!$this->name) {
            throw new LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_class($this)));
        }

        $this->stdio = $stdio !== null ? $stdio : stdio();

        $this->parseInput($argv); // parse the command line parameters
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->input('help')) {
            $this->printHelp();
            return static::EXIT_SUCCESS;
        }

        // execute
//        try {
        $exitCode = $this->handle();

        if ($exitCode === null || $exitCode === true) {
            $exitCode = static::EXIT_SUCCESS;
        }
        else  if ($exitCode === false) {
            $exitCode = static::EXIT_FAILURE;
        }

//        } catch (\Exception $e) {
//            if (!$this->catchExceptions) {
//                throw $e;
//            }
//
//            if ($output instanceof ConsoleOutputInterface) {
//                $this->renderException($e, $output->getErrorOutput());
//            } else {
//                $this->renderException($e, $output);
//            }
//
//            $exitCode = $e->getCode();
//            if (is_numeric($exitCode)) {
//                $exitCode = (int) $exitCode;
//                if (0 === $exitCode) {
//                    $exitCode = 1;
//                }
//            } else {
//                $exitCode = 1;
//            }
//        }

        return $exitCode;
    }

    /**
     * Execute the console command.
     *
     * @return int|void Exit Code
     */
    abstract protected function handle();

    ///////////////////////////////////////////////////////////////////////////
    // Parse the command line arguments and options

    /**
     * Gets the global command line options.
     *
     * @return array
     */
    private function globalOptions()
    {
        return [
            'help'    => ['type' => 'bool', 'short' => 'h', 'description' => 'Display this help message.'],
            'verbose' => ['type' => 'int',  'short' => 'v', 'description' => 'Change verbosity of messages (0=quiet, 1=normal, 2=verbose, 3=debug).'],
        ];
    }

    /**
     * Parse the command line parameters and bind it to the input property.
     *
     * @param array|null $argv
     */
    private function parseInput($argv)
    {
        if ($argv === null || ($match = $this->findOption('help', 'h', $argv)) !== false) {
            $this->input['help'] = true;
            return;
        }

        if (($match = $this->findOption('verbose', 'v', $argv)) !== false) {
            if (empty($match[1]) && $match[1] !== '0') {
                throw new InvalidArgumentException('Value for option "verbose" expected.');
            }
            $this->stdio->setVerbosity($match[1]);
            unset($argv[$match[0]]);
        }

        // parse the arguments
        foreach ($this->arguments as $key => $def) {
            if (isset($argv[0]) && $argv[0][0] != '-') {
                $value = array_shift($argv);
                $this->input[$key] = $this->cast($value, $def['type']);
            }
            else {
                if (!array_key_exists('default', $def)) {
                    throw new InvalidArgumentException('Argument "' . $key . '"" is required.');
                }
                $this->input[$key] = $def['default'];
            }
        }

        // parse the options
        foreach ($this->options as $key => $def) {
            if (($match = $this->findOption($key, isset($def['short']) ? $def['short'] : null, $argv)) !== false) {
                list($i, $value) = $match;
                if ($value !== null) {
                    // option with value
                    if ($def['type'] == 'bool') {
                        throw new InvalidArgumentException('Value for option "' . $key . '"" does not expected.');
                    }
                    if ($value !== '') {
                        $this->input[$key] = $this->cast($value, $def['type']);
                    }
                    else {
                        $this->input[$key] = isset($def['default']) ? $def['default'] : null;
                    }
                }
                else {
                    // option without a value
                    if ($def['type'] != 'bool') {
                        throw new InvalidArgumentException('Value for option "' . $key . '"" expected.');
                    }
                    $this->input[$key] = true;
                }
                unset($argv[$i]);
            }
            else {
                $this->input[$key] = $def['type'] == 'bool' ? false : (isset($def['default']) ? $def['default'] : null);
            }
        }

        if (!empty($argv)) {
            throw new InvalidArgumentException('Parameter "' . $argv[0] . '" not expected.');
        }
    }

    /**
     * Cast the value.
     *
     * Called by parseInput.
     *
     * @param string $value
     * @param string $type
     * @return bool|float|int|string
     */
    private function cast($value, $type)
    {
        switch ($type) {
            case 'bool':
                return (bool)$value;
            case 'float':
                return (float)$value;
            case 'int':
                return (int)$value;
            default:
                return $value;
        }
    }

    /**
     * Find the option.
     *
     * Called by parseInput.
     *
     * @param string $key
     * @param string $short
     * @param array $argv
     * @return array|false
     */
    private function findOption($key, $short, $argv)
    {
        foreach ($argv as $i => $arg) {
            if (preg_match('/^-(-)?([\w-]+)(?:=(.*))?$/s', $arg, $match)) {
                if ((!empty($match[1]) && $match[2] === $key) || (empty($match[1]) && $match[2] === $short)) {
                    $value = isset($match[3]) ? $match[3] : null;
                    return [$i, $value];
                }
            }
        }

        return false;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Generate Help

    /**
     * @inheritdoc
     */
    public function printHelp()
    {
        $this->stdio->notice('Help:');
        $this->stdio->line('  ' . $this->description);
        $this->stdio->line('');

        $this->stdio->notice('Usage:');
        $this->stdio->line('  php console ' . $this->name . ' [<arguments>] [<options>]');
        $this->stdio->line('');

        $length = 0;
        $block = [];

        foreach ($this->arguments as $name => $def) {
            $s = '  ' . $name;
            $block[$name] = $s;
            $n = strlen($s);
            if ($length < $n) {
                $length = $n;
            }
        }

        $namelen = 0;
        $options = array_merge($this->options, $this->globalOptions());
        foreach ($options as $name => $def) {
            $n = strlen($name) + ($def['type'] != 'bool' ? 2 : 0);
            if ($namelen < $n) {
                $namelen = $n;
            }
        }

        foreach ($options as $name => $def) {
            $value = $def['type'] != 'bool' ? '=?' : '';
            $s = '  --' . $name . $value;
            if (isset($def['short'])) {
                $s .=  ',' . str_repeat(' ', $namelen - strlen($name . $value)) . ' -' . $def['short'] . $value;
            }
            $block[$name] = $s;
            $n = strlen($s);
            if ($length < $n) {
                $length = $n;
            }
        }

        $this->stdio->notice('Arguments:');
        foreach ($this->arguments as $name => $def) {
            $this->printParameters($block[$name], $def, $length);
        }
        $this->stdio->line('');

        $this->stdio->notice('Options:');
        foreach ($options as $name => $def) {
            $this->printParameters($block[$name], $def, $length);
        }

        $this->stdio->line('');
    }

    /**
     * Print arguments and options.
     *
     * @param string $name Name and shortcut of the parameter
     * @param array $def Definitions (Description and default value)
     * @param int $length Maximal characters of the left site
     */
    private function printParameters($name, $def, $length)
    {
        $name .= str_repeat(' ', 2 + $length - strlen($name));
        $this->stdio->write($name, false, [Stdio::STYLE_GREEN]);

        $this->stdio->write(trim($def['description'] ?: ''));

        if (array_key_exists('default', $def)) {
            $default = $def['default'];
            if ($default === null) {
                $default = 'null';
            }
            else if ($def['type'] == 'string') {
                $default = '"' . $default . '"';
            }
            $this->stdio->write(' [default: ' . $default . ']', true);
        }
        else {
            $this->stdio->write('', true);
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // Error Handling

    /**
     * @inheritdoc
     */
    public function abort($message, $exitCode = self::EXIT_FAILURE)
    {
        throw new StopException($message, $exitCode);
    }

    ///////////////////////////////////////////////////////////////////////////
    // Terminal functions
    
    /**
     * @inheritdoc
     */
    public function clearTerminal()
    {
        if (DIRECTORY_SEPARATOR === '/') {
            passthru('clear');
        }
        else {
            passthru('cls'); // @codeCoverageIgnore
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function terminalWidth()
    {
        $dimensions = $this->getTerminalDimensions();

        return $dimensions[0];
    }

    /**
     * @inheritdoc
     */
    public function terminalHeight()
    {
        $dimensions = $this->getTerminalDimensions();

        return $dimensions[1];
    }

    /**
     * @inheritdoc
     */
    private function getTerminalDimensions()
    {
        if ($this->terminalDimensions !== null) {
            return $this->terminalDimensions;
        }

        $this->terminalDimensions = [null, null];

        // @codeCoverageIgnoreStart
        if (DIRECTORY_SEPARATOR === '\\') {
            // extract [w, H] from "wxh (WxH)"
            if (preg_match('/^(\d+)x\d+ \(\d+x(\d+)\)$/', trim(getenv('ANSICON')), $matches)) {
                $this->terminalDimensions = [(int)$matches[1], (int)$matches[2]];
            }
            // extract [w, h] from "wxh"
            else if (preg_match('/^(\d+)x(\d+)$/', $this->getConsoleMode(), $matches)) {
                $this->terminalDimensions = [(int)$matches[1], (int)$matches[2]];
            }
        }
        // @codeCoverageIgnoreEnd

        else if ($sttyString = $this->getSttyColumns()) {
            // extract [w, h] from "rows h; columns w;"
            if (preg_match('/rows.(\d+);.columns.(\d+);/i', $sttyString, $matches)) {
                $this->terminalDimensions = [(int)$matches[2], (int)$matches[1]]; // @codeCoverageIgnore
            } // @codeCoverageIgnore
            // extract [w, h] from "; h rows; w columns"
            else if (preg_match('/;.(\d+).rows;.(\d+).columns/i', $sttyString, $matches)) {
                $this->terminalDimensions = [(int)$matches[2], (int)$matches[1]]; // @codeCoverageIgnore
            } // @codeCoverageIgnore
        }

        return $this->terminalDimensions;
    }

    /**
     * Runs and parses stty -a if it's available, suppressing any error output.
     *
     * @return string
     */
    private function getSttyColumns()
    {
        if (!function_exists('proc_open')) {
            return null; // @codeCoverageIgnore
        }

        $descriptorspec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open('stty -a | grep columns', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (is_resource($process) === false) {
            return null; // @codeCoverageIgnore
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $info;
    }

    /**
     * Runs and parses mode CON if it's available, suppressing any error output.
     *
     * @return string|null <width>x<height> or null if it could not be parsed
     */
    private function getConsoleMode()
    {
        if (!function_exists('proc_open')) {
            return null; // @codeCoverageIgnore
        }

        $descriptorspec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open('mode CON', $descriptorspec, $pipes, null, null, ['suppress_errors' => true]);
        if (is_resource($process) === false) {
            return null; // @codeCoverageIgnore
        }

        $info = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if (!preg_match('/--------+\r?\n.+?(\d+)\r?\n.+?(\d+)\r?\n/', $info, $matches)) {
            return null;
        }

        return $matches[2] . 'x' . $matches[1]; // @codeCoverageIgnore
    }

    ///////////////////////////////////////////////////////////////////////////
    // Standard Input Stream

    /**
     * @inheritdoc
     */
    public function read($prompt, $options = null, $default = null)
    {
        return $this->stdio->read($prompt, $options, $default);
    }

    /**
     * @inheritdoc
     */
    public function ask($prompt, $default = null)
    {
        return $this->stdio->ask($prompt, $default);
    }

    /**
     * @inheritdoc
     */
    public function confirm($prompt, $default = false)
    {
        return $this->stdio->confirm($prompt, $default);
    }

    /**
     * @inheritdoc
     */
    public function choice($prompt, $options, $default = null)
    {
        return $this->stdio->choice($prompt, $options, $default);
    }

    /**
     * @inheritdoc
     */
    public function secret($prompt, $default = null)
    {
        return $this->stdio->secret($prompt, $default);
    }
    
    ///////////////////////////////////////////////////////////////////////////
    // Standard Output Stream

    /**
     * @inheritdoc
     */
    public function format($text, array $styles = [])
    {
        return $this->stdio->format($text, $styles);
    }

    /**
     * @inheritdoc
     */
    public function write($text, $newline = false, array $styles = [], $verbosity = Stdio::VERBOSITY_NORMAL)
    {
        $this->stdio->write($text, $newline, $styles, $verbosity);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function line($text)
    {
        $this->stdio->line($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function info($text)
    {
        $this->stdio->info($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function notice($text)
    {
        $this->stdio->notice($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function question($text)
    {
        $this->stdio->question($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function warn($text)
    {
        $this->stdio->warn($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function error($text)
    {
        $this->stdio->error($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function quiet($text, array $styles = [])
    {
        $this->stdio->quiet($text, $styles);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function verbose($text, array $styles = [])
    {
        $this->stdio->verbose($text, $styles);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function debug($text, array $styles = [])
    {
        $this->stdio->debug($text, $styles);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hr($width = 79)
    {
        $this->stdio->hr($width);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function table(array $headers, array $rows)
    {
        $this->stdio->table($headers, $rows);

        return $this;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Getter / Setter

    /**
     * @inheritdoc
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * @inheritdoc
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     */
    public function input($key)
    {
        return isset($this->input[$key]) ? $this->input[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function stdio()
    {
        return $this->stdio;
    }

    /**
     * @inheritdoc
     */
    public function isQuiet()
    {
        return $this->stdio->isQuiet();
    }

    /**
     * @inheritdoc
     */
    public function isVerbose()
    {
        return $this->stdio->isVerbose();
    }

    /**
     * @inheritdoc
     */
    public function isDebug()
    {
        return $this->stdio->isDebug();
    }
}
