<?php

namespace Core\Services\Contracts;

use Core\Exceptions\StopException;

interface Command
{
    /**
     * Exit Codes
     */
    const EXIT_SUCCESS = 0;
    const EXIT_FAILURE = 1;

    /**
     * Create a new Console.
     *
     * @param array|null $argv Command line parameters (without application and command name, just only arguments and options)
     * @param Stdio|null $stdio
     */
    public function __construct($argv = null, $stdio = null);

    /**
     * Run the Console
     *
     * @return int Exit Status Code
     */
    public function run();

    ///////////////////////////////////////////////////////////////////////////
    // Generate Help

    /**
     * Print the Help
     */
    public function printHelp();

    ///////////////////////////////////////////////////////////////////////////
    // Error Handling

    /**
     * Throw an StopException with the given message.
     *
     * @param string $message
     * @param int $exitCode
     * @throws StopException
     */
    public function abort($message, $exitCode = self::EXIT_FAILURE);

    ///////////////////////////////////////////////////////////////////////////
    // Terminal functions

    /**
     * Clear the console
     */
    public function clear();

    /**
     * Tries to figure out the terminal width in which this application runs.
     *
     * @return int|null
     */
    public function terminalWidth();

    /**
     * Tries to figure out the terminal height in which this application runs.
     *
     * @return int|null
     */
    public function terminalHeight();

    ///////////////////////////////////////////////////////////////////////////
    // Standard Input Stream

    /**
     * Check if data is available on standard input.
     *
     * @param int $timeout An optional time to wait for data
     * @return bool True for data available, false otherwise
     */
    public function canRead($timeout = 0);

    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|array|null $options String of options, e.g. ['y' => 'yes', 'n' => 'no'].  Pass null to omit.
     * @param string|null $default Default input value. Pass null to omit.
     * @return string Either the default value, or the user-provided input.
     */
    public function read($prompt, $options = null, $default = null);

    /**
     * Prompts the user for input, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param string|null $default Default input value.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function ask($prompt, $default = null);

    /**
     * Asking for Confirmation
     *
     * If you need to ask the user for a simple confirmation, you may use the confirm method. By default, this method
     * will return false. However, if the user enters y or yes in response to the prompt, the method will return true.
     *
     * @param string $prompt Prompt text.
     * @param boolean $default Default input value, true or false.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function confirm($prompt, $default = false);

    /**
     * Prompts the user for input based on a list of options, and returns it.
     *
     * @param string $prompt Prompt text.
     * @param array $options Array of options.
     * @param string|null $default Default input value.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function choice($prompt, $options, $default = null);

    /**
     * Asks a question with the user input hidden.
     *
     * The secret method is similar to ask, but the user's input will not be visible to them as they type in the console.
     * This method is useful when asking for sensitive information such as a password.
     *
     * The function will run only on a unix like system (linux or mac).
     *
     * @param string $prompt Prompt text.
     * @param string|null $default Default input value.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function secret($prompt, $default = null);

    ///////////////////////////////////////////////////////////////////////////
    // Standard Output Stream

    /**
     * Format text.
     *
     * @param string $text The message
     * @param array $styles Combination of Stdio::STYLE constants
     * @return string
     */
    public function format($text, array $styles = []);

    /**
     * Write a formatted text o standard output.
     *
     * @param string $text The message
     * @param bool $newline Whether to add a newline
     * @param array $styles Combination of Stdio::STYLE constants
     * @param int $verbosity Determine if the output should be only at the verbose level
     * @return $this;
     */
    public function write($text, $newline = false, array $styles = [], $verbosity = Stdio::VERBOSITY_NORMAL);

    /**
     * Write standard text.
     *
     * @param string $text
     * @return $this;
     */
    public function line($text);

    /**
     * Write a information.
     *
     * @param string $text
     * @return $this;
     */
    public function info($text);

    /**
     * Write a notice.
     *
     * @param string $text
     * @return $this;
     */
    public function notice($text);

    /**
     * Write a question.
     *
     * @param string $text
     * @return $this;
     */
    public function question($text);

    /**
     * Write a warning.
     *
     * @param string $text
     * @return $this;
     */
    public function warn($text);

    /**
     * Write an error text.
     *
     * @param string $text
     * @return $this;
     */
    public function error($text);

    /**
     * Output at the quiet level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this;
     */
    public function quiet($text, array $styles = []);

    /**
     * Output at the verbose level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this;
     */
    public function verbose($text, array $styles = []);

    /**
     * Output at the debug level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this;
     */
    public function debug($text, array $styles = []);

    /**
     * Outputs a series of minus characters to the standard output, acts as a visual separator.
     *
     * @param int $width Width of the line, defaults to 79
     * @return $this;
     */
    public function hr($width = 79);

    /**
     * Formats a table.
     *
     * Example:
     * +---------------+-----------------------+------------------+
     * | ISBN          | Title                 | Author           |
     * +---------------+-----------------------+------------------+
     * | 99921-58-10-7 | Divine Comedy         | Dante Alighieri  |
     * | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     * | 960-425-059-0 | The Lord of the Rings | J. R. R. Tolkien |
     * +---------------+-----------------------+------------------+
     *
     * @param array $headers
     * @param array $rows
     * @return $this;
     */
    public function table(array $headers, array $rows);

    ///////////////////////////////////////////////////////////////////////////
    // Getter / Setter

    /**
     * The console command name.
     *
     * @return string
     */
    public function name();

    /**
     * The console command description.
     *
     * @return string
     */
    public function description();

    /**
     * Possible arguments of the command.
     *
     * This is an associative array where the key is the argument name and the value is the argument attributes.
     * Each attribute is a array with following values:
     * - type:        (string) The data type (bool, float, int or string).
     * - default:     (mixed)  The default value for the argument. If not set, the argument is required.
     * - description: (string) The description of the argument. Default: null
     *
     * @return array
     */
    public function arguments();

    /**
     * Possible options of the command.
     *
     * This is an associative array where the key is the option name and the value is the option attributes.
     * Each attribute is a array with following values:
     * - type:        (string) The data type (bool, float, int or string).
     * - short:       (string) Alternative option name.
     * - default:     (mixed)  The default value for the option. If the default value is not set and the type is not bool, the option is required.
     * - description: (string) The description of the option. Default: null
     *
     * @return array
     */
    public function options();

    /**
     * Get the input value.
     *
     * @param string $key
     * @return array
     */
    public function input($key);

    /**
     * Standard input/output stream.
     *
     * @return Stdio
     */
    public function stdio();

    /**
     * Returns whether verbosity is quiet (-q).
     *
     * @return bool true if verbosity is set to VERBOSITY_QUIET, false otherwise
     */
    public function isQuiet();

    /**
     * Returns whether verbosity is verbose (-v) or debug (-vv).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERBOSE or VERBOSITY_DEBUG, false otherwise
     */
    public function isVerbose();

    /**
     * Returns whether verbosity is debug (-vv).
     *
     * @return bool true if verbosity is set to VERBOSITY_DEBUG, false otherwise
     */
    public function isDebug();
}
