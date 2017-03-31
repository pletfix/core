<?php

namespace Core\Services;

use Core\Services\Contracts\Stdio as StdioContract;
use InvalidArgumentException;
use RuntimeException;

/**
 * Provides a wrapper for standard input/output streams.
 *
 * This class based on Aura.Cli's Stdio and Handle implementation (http://opensource.org/licenses/bsd-license.php BSD License).
 * Function read() based on CakePHP's ConsoleInput (http://www.opensource.org/licenses/mit-license.php MIT License).
 * VERBOSITY constants and the functions ask(), choice(), getInput() and hr() are from CakePHP's ConsoleIo.
 * The clear function is copied from CakePHP's Shell
 * The verbosity functions are from Symfony's Console Output Interface.
 * The function hasColorSupport() is copied from Symfony's StreamOutput.
 * The signature of table() comes from Symfony's SymfonyStyle.
 * Function secret() and hasSttyAvailable() based on Symfony's QuestionHelper.
 *
 * @see https://github.com/auraphp/Aura.Cli/blob/2.x/src/Stdio.php
 * @see https://github.com/auraphp/Aura.Cli/blob/2.x/src/Stdio/Handle.php
 * @see https://github.com/auraphp/Aura.Cli/blob/2.x/src/Stdio/Formatter.php
 * @see https://github.com/cakephp/cakephp/blob/3.next/src/Console/ConsoleIo.php
 * @see https://github.com/cakephp/cakephp/blob/3.next/src/Console/ConsoleInput.php
 * @see https://github.com/cakephp/cakephp/blob/3.next/src/Console/ConsoleOutput.php
 * @see https://github.com/cakephp/cakephp/blob/3.next/src/Console/Shell.php
 * @see https://github.com/symfony/console/blob/3.2/Output/OutputInterface.php
 * @see https://github.com/symfony/console/blob/3.2/Output/StreamOutput.php
 * @see https://github.com/symfony/console/blob/3.2/Style/SymfonyStyle.php
 * @see https://github.com/symfony/console/blob/3.2/Helper/QuestionHelper.php
 * @see http://php.net/manual/de/features.commandline.io-streams.php
 */
class Stdio implements StdioContract
{
    /**
     * The current verbose level.
     *
     * @var int
     */
    private $verbosity = self::VERBOSITY_NORMAL;

    /**
     * Standard input stream.
     *
     * @var resource
     */
    private $stdin;

    /**
     * Standard output stream.
     *
     * @var resource
     */
    private $stdout;

    /**
     * Standard error stream.
     *
     * @var resource
     */
    private $stderr;

    /**
     * Determine if console supports color.
     *
     * @var bool
     */
    private $canColor;

    /**
     * Determine if STTY is available.
     *
     * @var
     */
    private static $stty;

    /**
     * @inheritdoc
     */
    public function __construct ($stdin = null, $stdout = null, $stderr = null)
    {
        $this->stdin  = $stdin  !== null ? $stdin  : fopen('php://stdin',  'r'); // == STDIN
        $this->stdout = $stdout !== null ? $stdout : fopen('php://stdout', 'w'); // == STDOUT
        $this->stderr = $stderr !== null ? $stderr : fopen('php://stderr', 'w'); // == STDERR

        $this->canColor = $this->hasColorSupport();
    }

    /**
     * Returns true if the stream supports colorization.
     *
     * Colorization is disabled if not supported by the stream:
     *
     *  -  Windows before 10.0.10586 without Ansicon, ConEmu or Mintty
     *  -  non tty consoles
     *
     * @return bool true if the stream supports colorization, false otherwise
     */
    private function hasColorSupport()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return
                0 >= version_compare('10.0.10586', PHP_WINDOWS_VERSION_MAJOR.'.'.PHP_WINDOWS_VERSION_MINOR.'.'.PHP_WINDOWS_VERSION_BUILD)
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && @posix_isatty($this->stdout);
    }

    ///////////////////////////////////////////////////////////////////////////
    // Input

    //todo canRead() testen
    /**
     * @inheritdoc
     */
    public function canRead($timeout = 0)
    {
        $readFds = [$this->stdin];
        $readyFds = stream_select($readFds, $writeFds, $errorFds, $timeout);

        return $readyFds > 0;
    }

    /**
     * @inheritdoc
     */
    public function read($prompt, $options = null, $default = null)
    {
        // style the prompt
        $prompt = $this->format($prompt, [self::STYLE_GREEN]);

        // add default information to the prompt
        if ($default !== null) {
            if ($default === '') {
                $defaultText = 'empty';
            }
            else if (is_bool($default)) {
                $defaultText = $default ? 'true': 'false';
            }
            else {
                $defaultText = $default;
            }
            $prompt .= ' [' . $this->format($defaultText, [self::STYLE_YELLOW]) . ']';
        }
        $prompt .= ':' . PHP_EOL;

        // add the option list to the prompt
        if ($options !== null) {
            $optionHint = [];
            foreach ($options as $key => $option) {
                $optionHint[] = ' [' . $this->format($key, [self::STYLE_YELLOW]) . '] ' . $option;
            }
            $prompt .= implode(PHP_EOL, $optionHint) . PHP_EOL;
        }
        $prompt .= '> ';

        do {
            // write the prompt
            $this->write($prompt);

            // read a value from the stream
            $result = trim(fgets($this->stdin)); // todo !!! Testen, wenn übergroße Daten eingegeben werden. Muss gepuffert werden?

            // validate the input
            $isEmpty = $result === '' || $result === null;
            if ($isEmpty) {
                if ($default !== null) {
                    return $default;
                }
                $this->error('A value is required.');
            }
            else if ($options !== null) {
                if (isset($options[$result])) {
                    $result = $options[$result];
                }
                else if (($i = array_search(strtolower($result), array_map('strtolower', $options))) !== false) {
                    $result = $options[$i];
                }
                else {
                    $this->error('Value "' . $result .'" is invalid.');
                    $isEmpty = true;
                }
            }

        } while ($isEmpty);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function ask($prompt, $default = null)
    {
        return $this->read($prompt, null, $default);
    }

    /**
     * @inheritdoc
     */
    public function confirm($prompt, $default = false)
    {
        do {
            $result = strtolower($this->read($prompt . ' (yes/no)', null, $default ? 'yes' : 'no'));
            $valid = in_array($result, ['y', 'ye', 'yes', 'n', 'no']);
            if (!$valid) {
                $this->error('Value "' . $result .'" is invalid.');
            }
        } while (!$valid);

        return $result[0] == 'y';
    }

    /**
     * @inheritdoc
     */
    public function choice($prompt, $options, $default = null)
    {
        return $this->read($prompt, $options, $default);
    }

    /**
     * @inheritdoc
     */
    public function secret($prompt, $default = null)
    {
        $prompt = $this->format($prompt, [self::STYLE_GREEN]) . ':' . PHP_EOL . '> ';

        do {
            $this->write($prompt);

            if ('\\' === DIRECTORY_SEPARATOR) {
                $exe = __DIR__ . '/../../bin/hiddeninput.exe';
                //$b = file_exists($exe);
                if ('phar:' === substr(__FILE__, 0, 5)) { // handle code running from a phar
                    $tmpExe = sys_get_temp_dir() . '/hiddeninput.exe';
                    copy($exe, $tmpExe);
                    $exe = $tmpExe;
                }
                $result = rtrim(shell_exec($exe));
                $this->write('', true);
                if (isset($tmpExe)) {
                    unlink($tmpExe);
                }
            }
            else if ($this->hasSttyAvailable()) {
                $sttyMode = shell_exec('stty -g');
                try {
                    shell_exec('stty -echo'); // Disable echo
                    $result = trim(fgets($this->stdin));
                } finally {
                    shell_exec(sprintf('stty %s', $sttyMode)); // Reset stty so it behaves normally again
                }
                $this->write('', true);
            }
            else {
                fwrite($this->stdout, "\033[30;40m"); // fallback: set black as blackground and font color
                flush();
                $result = trim(fgets($this->stdin));
                fwrite($this->stdout, "\e[0m");
            }

            $isEmpty = $result === '' || $result === null;
            if ($isEmpty) {
                if ($default !== null) {
                    return $default;
                }
                $this->error('A value is required.');
            }

        } while ($isEmpty);

        return $result;
    }

    /**
     * Returns whether Stty is available or not.
     *
     * @return bool
     */
    private function hasSttyAvailable()
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Output

    /**
     * @inheritdoc
     */
    public function clear() // todo nicht schlüssig, ob clear() hier hin gehört (evtl eigene Terminal Klasse bauen)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            passthru('clear');
        }
        else {
            passthru('cls');
        }
    }

    /**
     * @inheritdoc
     */
    public function format($text, array $styles = [])
    {
        if (!$this->canColor) {
            return $text;
        }

        if (strpos($text, PHP_EOL) === false) {
            return "\033[" . implode(';', $styles) . "m" . $text . "\033[0m";
        }

        $text = $this->block(explode(PHP_EOL, $text));
        foreach ($text as $i => $line) {
            $text[$i] = "\033[" . implode(';', $styles) . "m" . $line . "\033[0m";
        }

        return implode(PHP_EOL, $text);
    }

    /**
     * @inheritdoc
     */
    public function write($text, $newline = false, array $styles = [], $verbosity = self::VERBOSITY_NORMAL)
    {
        if ($verbosity > $this->verbosity) {
            return;
        }

        if (!empty($styles)) {
            $text = $this->format($text, $styles);
        }

        if ($newline) {
            $text .= PHP_EOL;
        }

        if (fwrite($this->stdout, $text) === false) {
            throw new RuntimeException('Unable to write output.');
        }

        // fflush($this->stdout); // macht symfony\console\Output\StreamOutput.php
    }

    /**
     * @inheritdoc
     */
    public function line($text)
    {
        $this->write($text, true);
    }

    /**
     * @inheritdoc
     */
    public function info($text)
    {
        $this->write($text, true, [self::STYLE_GREEN]);
    }

    /**
     * @inheritdoc
     */
    public function notice($text)
    {
        $this->write($text, true, [self::STYLE_YELLOW]);
    }

    /**
     * @inheritdoc
     */
    public function question($text)
    {
        $this->write($text, true, [self::STYLE_BLACK, self::STYLE_CYAN_BG]);
    }

    /**
     * @inheritdoc
     */
    public function warn($text)
    {
        $this->write($text, true, [self::STYLE_BLACK, self::STYLE_YELLOW_BG]);
    }

    /**
     * @inheritdoc
     */
    public function error($text)
    {
        $this->write($text, true, [self::STYLE_WHITE, self::STYLE_RED_BG], self::VERBOSITY_QUIET);
    }

    /**
     * @inheritdoc
     */
    public function quiet($text, array $styles = [])
    {
        $this->write($text, true, $styles, self::VERBOSITY_QUIET);
    }

    /**
     * @inheritdoc
     */
    public function verbose($text, array $styles = [])
    {
        $this->write($text, true, $styles, self::VERBOSITY_VERBOSE);
    }

    /**
     * @inheritdoc
     */
    public function debug($text, array $styles = [])
    {
        $this->write($text, true, $styles, self::VERBOSITY_DEBUG);
    }

    /**
     * @inheritdoc
     */
    public function hr($width = 79)
    {
        $this->write(str_repeat('-', $width), true);
    }

    /**
     * @inheritdoc
     */
    public function table(array $headers, array $rows)
    {
        // todo table ()
//        $style = clone Table::getStyleDefinition('symfony-style-guide');
//        $style->setCellHeaderFormat('<info>%s</info>');
//
//        $table = new Table($this);
//        $table->setHeaders($headers);
//        $table->setRows($rows);
//        $table->setStyle($style);
//
//        $table->render();
//        $this->newLine();
    }

    /**
     * Fill each line with spaces, so that all are equal in length.
     *
     * @param string[] $textblock
     * @return string[]
     */
    private function block($textblock)
    {
        $length = 0;
        foreach ($textblock as $line) {
            $n = strlen($line);
            if ($length < $n) {
                $length = $n;
            }
        }

        foreach ($textblock as $i => $line) {
            $textblock[$i] .= str_repeat(' ', $length - strlen($line));
        }

        return $textblock;
    }

// @see https://github.com/symfony/console/blob/3.2/Style/SymfonyStyle.php
// @see https://github.com/symfony/console/blob/3.0/Helper/ProgressBar.php
//
//    /**
//     * Starts the progress output.
//     *
//     * @param int $max Maximum steps (0 if unknown)
//     */
//    public function progressStart($max = 0)
//    {
////        $this->progressBar = $this->createProgressBar($max);
////        $this->progressBar->start();
//    }
//
//    /**
//     * Advances the progress output X steps.
//     *
//     * @param int $step Number of steps to advance
//     */
//    public function progressAdvance($step = 1)
//    {
//        $this->getProgressBar()->advance($step);
//    }
//
//    /**
//     * Finishes the progress output.
//     */
//    public function progressFinish()
//    {
//        $this->getProgressBar()->finish();
//        $this->newLine(2);
//        $this->progressBar = null;
//    }
//
//    /**
//     * @param int $max
//     * @return ProgressBar
//     */
//    public function createProgressBar($max = 0)
//    {
//        $progressBar = parent::createProgressBar($max);
//
//        if ('\\' !== DIRECTORY_SEPARATOR) {
//            $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
//            $progressBar->setProgressCharacter('');
//            $progressBar->setBarCharacter('▓'); // dark shade character \u2593
//        }
//
//        return $progressBar;
//    }

    ///////////////////////////////////////////////////////////////////////////
    // Error Output

    //todo testen, wozu stderr gut ist
    /**
     * @inheritdoc
     */
    public function err($text = null)
    {
        fwrite($this->stderr, $text . PHP_EOL);
    }

    ///////////////////////////////////////////////////////////////////////////
    // Getter / Setter

    /**
     * @inheritdoc
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * @inheritdoc
     */
    public function setStdin($stdin)
    {
        if ($this->stdin !== null) {
            fclose($this->stdin);
        }

        $this->stdin = $stdin;
    }

    /**
     * @inheritdoc
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * @inheritdoc
     */
    public function setStdout($stdout)
    {
        if ($this->stdout !== null) {
            fclose($this->stdout);
        }

        $this->stdout = $stdout;
    }

    /**
     * @inheritdoc
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * @inheritdoc
     */
    public function setStderr($stderr)
    {
        if ($this->stderr !== null) {
            fclose($this->stderr);
        }

        $this->stderr = $stderr;
    }

    /**
     * @inheritdoc
     */
    public function setVerbosity($level)
    {
        $level = (int)$level;

        if ($level < self::VERBOSITY_QUIET || $level > self::VERBOSITY_DEBUG) {
            throw new InvalidArgumentException('Verbose level ' . $level . ' is not defined.');
        }

        $this->verbosity = $level;
    }

    /**
     * @inheritdoc
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @inheritdoc
     */
    public function isQuiet()
    {
        return $this->verbosity === self::VERBOSITY_QUIET;
    }

    /**
     * @inheritdoc
     */
    public function isVerbose()
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    /**
     * @inheritdoc
     */
    public function isDebug()
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }
}