<?php

namespace Core\Services\Contracts;

/**
 * Provides a wrapper for standard input/output streams.
 */
interface Stdio
{
    /*
     * Styles.
     *
     * Based on the `ANSI/VT100 Terminal Control reference` at <http://www.termsys.demon.co.uk/vtansi.htm>.
     * @see https://github.com/auraphp/Aura.Cli/blob/2.x/src/Stdio/Formatter.php
     * @see https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
     * @see https://github.com/cakephp/cakephp/blob/3.next/src/Console/ConsoleOutput.php
     */
    //const STYLE_RESET    = 0;
    const STYLE_BOLD       = 1;
    const STYLE_DIM        = 2;
    const STYLE_UL         = 4;
    const STYLE_BLINK      = 5;
    const STYLE_REVERSE    = 7;
    const STYLE_BLACK      = 30;
    const STYLE_RED        = 31;
    const STYLE_GREEN      = 32;
    const STYLE_YELLOW     = 33;
    const STYLE_BLUE       = 34;
    const STYLE_MAGENTA    = 35;
    const STYLE_CYAN       = 36;
    const STYLE_WHITE      = 37;
    const STYLE_BLACK_BG   = 40;
    const STYLE_RED_BG     = 41;
    const STYLE_GREEN_BG   = 42;
    const STYLE_YELLOW_BG  = 43;
    const STYLE_BLUE_BG    = 44;
    const STYLE_MAGENTA_BG = 45;
    const STYLE_CYAN_BG    = 46;
    const STYLE_WHITE_BG   = 47;

    /*
     * Verbose level.
     */
    const VERBOSITY_QUIET   = 0;
    const VERBOSITY_NORMAL  = 1;
    const VERBOSITY_VERBOSE = 2;
    const VERBOSITY_DEBUG   = 3;

    /**
     * Create a new Stdio instance.
     *
     * @param resource $stdin Standard input stream
     * @param resource $stdout Standard output stream.
     * @param resource $stderr Standard error stream.
     */
    public function __construct($stdin = null, $stdout = null, $stderr = null);

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
     * @param bool $stty Try to use stty.
     * @return mixed Either the default value, or the user-provided input.
     */
    public function secret($prompt, $default = null, $stty = true);

    /**
     * Clear the console
     *
     * @return $this;
     */
    public function clear();

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
     * @return $this
     */
    public function write($text, $newline = false, array $styles = [], $verbosity = self::VERBOSITY_NORMAL);

    /**
     * Write standard text.
     *
     * @param string $text
     * @return $this
     */
    public function line($text);

    /**
     * Write a information.
     *
     * @param string $text
     * @return $this
     */
    public function info($text);

    /**
     * Write a notice.
     *
     * @param string $text
     * @return $this
     */
    public function notice($text);

    /**
     * Write a question.
     *
     * @param string $text
     * @return $this
     */
    public function question($text);

    /**
     * Write a warning.
     *
     * @param string $text
     * @return $this
     */
    public function warn($text);

    /**
     * Write an error text.
     *
     * @param string $text
     * @return $this
     */
    public function error($text);

    /**
     * Output at the quiet level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this
     */
    public function quiet($text, array $styles = []);

    /**
     * Output at the verbose level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this
     */
    public function verbose($text, array $styles = []);

    /**
     * Output at the debug level.
     *
     * @param string $text
     * @param array $styles Combination of Stdio::STYLE constants
     * @return $this
     */
    public function debug($text, array $styles = []);

    /**
     * Outputs a series of minus characters to the standard output, acts as a visual separator.
     *
     * @param int $width Width of the line, defaults to 79
     * @return $this
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
     * @return $this
     */
    public function table(array $headers, array $rows);

    /**
     * Prints text to Standard Error.
     *
     * @param string $text
     * @return $this
     */
    public function err($text = null);

    /**
     * Returns the Standard Input handle.
     *
     * @return resource
     */
    public function getStdin();

    /**
     * Set a Standard Input Handle.
     *
     * @param resource $stdin
     * @return $this
     */
    public function setStdin($stdin);

    /**
     * Returns the Standard Output handle.
     *
     * @return resource
     */
    public function getStdout();

    /**
     * Set a Standard Output handle.
     *
     * @param resource $stdout
     * @return $this
     */
    public function setStdout($stdout);

    /**
     * Returns the Standard Error handle.
     *
     * @return resource
     */
    public function getStderr();

    /**
     * Set a Standard Error handle.
     *
     * @param resource $stderr
     * @return $this
     */
    public function setStderr($stderr);

    /**
     * Gets the current verbosity of the output.
     *
     * @return int The current level of verbosity (one of the Stdio::VERBOSITY constants)
     */
    public function getVerbosity();

    /**
     * Sets the verbosity of the output.
     *
     * @param int $level The level of verbosity (one of the Stdio::VERBOSITY constants)
     * @return $this
     */
    public function setVerbosity($level);

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