<?php

namespace Core\Services;

use Core\Services\Contracts\ViewCompiler as ViewCompilerContract;

/**
 * View Template Compiler
 *
 * License: MIT
 *
 * This code based on Laravel's Blade Compiler 5.3 (see copyright notice license-laravel.md)
 *
 * @see https://laravel.com/docs/5.3/blade Laravel's Documentation
 * @see https://github.com/illuminate/view/blob/5.3/Compilers/BladeCompiler.php Laravel's Blade Compiler on GitHub by Taylor Otwell
 */
class ViewCompiler implements ViewCompilerContract
{
    /**
     * Array of footer lines to be added to template.
     *
     * @var array
     */
    private $footer = [];

//    /**
//     * ViewCompiler constructor.
//     */
//    public function __construct()
//    {
//    }

    /**
     * Compile the given template content to the corresponding valid PHP.
     *
     * @param string $content
     * @return string
     */
    public function compile($content)
    {
        $phpCode = '';
        foreach (token_get_all($content) as $token) {
            $phpCode .= is_array($token) ? $this->parsePHPToken($token) : $token;
        }

        if (count($this->footer) > 0) {
            $phpCode = ltrim($phpCode, PHP_EOL) . PHP_EOL . implode(PHP_EOL, array_reverse($this->footer));
        }

        return $phpCode;
    }

    /**
     * Parse the PHP token.
     *
     * @param array $token
     * @return string
     */
    private function parsePHPToken($token)
    {
        list($type, $expr) = $token;

        if ($type == T_INLINE_HTML) {
            $expr = $this->compileStatements($expr);
            $expr = $this->compileComments($expr);
            $expr = $this->compileRawEchos($expr);
            $expr = $this->compileRegularEchos($expr);
        }

        return $expr;
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Comments
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Compile template comments into valid PHP.
     *
     * @param string $expr PHP Token expression
     * @return string
     */
    private function compileComments($expr)
    {
        $commentTags = ['{{--', '--}}'];
        $pattern = sprintf('/%s(.*?)%s/s', $commentTags[0], $commentTags[1]);

        return preg_replace($pattern, '', $expr);
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Echos
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Compile the "raw" echo statements.
     *
     * @param string $expr PHP Token expression
     * @return string
     */
    private function compileRawEchos($expr)
    {
        return $this->compileEchos($expr, false);
    }

    /**
     * Compile the "regular" echo statements.
     *
     * @param string $expr PHP Token expression
     * @return string
     */
    private function compileRegularEchos($expr)
    {
        return $this->compileEchos($expr, true);
    }

    /**
     * Compile the echo statements.
     *
     * @param string $expr PHP Token expression
     * @param bool $escaped
     * @return string
     */
    private function compileEchos($expr, $escaped)
    {
        $tags = $escaped ? ['{{', '}}'] : ['{!!', '!!}'];
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $tags[0], $tags[1]);

        return preg_replace_callback($pattern, function ($matches) use ($escaped) {
            $content = $this->compileEchoDefaults($matches[2]);
            if ($escaped) {
                $content = sprintf('e(%s)', $content);
            }

            if ($matches[1]) {
                // The expression begins with "@{{" or "@{!!". Don't compile this part!
                return substr($matches[0], 1);
            }

            // If the expression ends with a line feed, we need a additional line feed.
            $lineFeeds = !empty($matches[3]) ? $matches[3] . $matches[3] : '';

            return '<?php echo ' . $content . '; ?>' . $lineFeeds;
        }, $expr);
    }

    /**
     * Compile the default values for the echo statement.
     *
     * Example:
     * <pre>
     *      compileEchoDefaults("$name or 'Default'");
     *          => "isset($name) ? $name : 'Default'"
     * </pre>
     *
     * @param string $expr PHP Token expression
     * @return string
     */
    private function compileEchoDefaults($expr)
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $expr);
    }

    /*
     * --------------------------------------------------------------------------------------------------------------
     * Statements
     * --------------------------------------------------------------------------------------------------------------
     */

    /**
     * Compile template statements that start with "@".
     *
     * @param string $expr PHP Token expression
     * @return mixed
     */
    private function compileStatements($expr)
    {
        $pattern = '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x';

        return preg_replace_callback($pattern, function ($match) {
            if (strpos($match[1], '@') !== false) {
                return substr($match[0], 1); // The expression begins with "@@". Don't compile the this part!
            }

            $method = 'compile' . ucfirst($match[1]);
            if (!method_exists($this, $method)) {
                return $match[0]; // The statement is not defined. Skip it!
            }

            if (isset($match[3])) {
                // e.g. @if (true)
                $result = $this->$method($match[3]); // $match[3] is the argument with parentheses; $match[4] is the argument without parentheses
            }
            else {
                // e.g. @endif
                $result = $this->$method(null) . $match[2]; // match[2] is the space between the statement and parentheses
            }

            return $result;
        }, $expr);
    }

    /**
     * Strip the parentheses from the given expression.
     *
     * @param string $expr
     * @return string
     */
    private function stripParentheses($expr)
    {
        if ($expr[0] == '(') {
            $expr = substr($expr, 1, -1);
        }

        return $expr;
    }

    /*
     * Extending the Layout and including Partial Views
     * ------------------------------------------------
     */

    /**
     * Compile the extends statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileExtends($expr)
    {
        $expr = $this->stripParentheses($expr);
        $this->footer[] = "<?php echo \$this->make({$expr}, get_defined_vars()); ?>";

        return '';
    }

    /**
     * Compile the include statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileInclude($expr)
    {
        $expr = explode(',', $this->stripParentheses($expr), 2);
        $vars = isset($expr[1]) ? trim($expr[1]) : '';
        if (empty($vars)) {
            return "<?php echo \$this->make({$expr[0]}, get_defined_vars()); ?>";
        }
        else {
            $result = "<?php echo \$this->make({$expr[0]}, array_merge(get_defined_vars(), {$vars})); ?>";
            return $result;
        }
    }

    /**
     * Compile the yield statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileYield($expr)
    {
        return "<?php echo \$this->yieldContent{$expr}; ?>";
    }

    /**
     * Compile the section statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileSection($expr)
    {
        return "<?php \$this->startSection{$expr}; ?>";
    }

    /**
     * Compile the end-section statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndsection(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php $this->endSection(); ?>';
    }

    /*
     * If Statements
     * -------------
     */

    /**
     * Compile the if statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileIf($expr)
    {
        return "<?php if{$expr}: ?>";
    }

    /**
     * Compile the else-if statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileElseif($expr)
    {
        return "<?php elseif{$expr}: ?>";
    }

    /**
     * Compile the else statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileElse(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php else: ?>';
    }

    /**
     * Compile the end-if statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndif(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endif; ?>';
    }

    /*
     * Loop Statements
     * ---------------
     */

    /**
     * Compile the for statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileFor($expr)
    {
        return "<?php for{$expr}: ?>";
    }

    /**
     * Compile the end-for statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndfor(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endfor; ?>';
    }

    /**
     * Compile the foreach statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileForeach($expr)
    {
        preg_match('/\( *(.*) +as *([^\)]*)/is', $expr, $matches);
        $iteratee = trim($matches[1]);
        $iteration = trim($matches[2]);

        return "<?php foreach({$iteratee} as {$iteration}): ?>";
    }

    /**
     * Compile the end-for-each statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndforeach(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endforeach; ?>';
    }

    /**
     * Compile the while statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileWhile($expr)
    {
        return "<?php while{$expr}: ?>";
    }

    /**
     * Compile the end-while statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndwhile(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endwhile; ?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param string|null $expr
     * @return string
     */
    protected function compileBreak($expr)
    {
        return !is_null($expr) ? "<?php if{$expr} break; ?>" : '<?php break; ?>';
    }

    /**
     * Compile the continue statements into valid PHP.
     *
     * @param string|null $expr
     * @return string
     */
    protected function compileContinue($expr)
    {
        return !is_null($expr) ? "<?php if{$expr} continue; ?>" : '<?php continue; ?>';
    }

    /*
     * Check authorization and permissions
     * -----------------------------------
     */

    /**
     * Compile the can statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileCan($expr)
    {
        return "<?php if (auth()->can{$expr}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param string|null $expr
     * @return string
     */
    protected function compileElsecan($expr)
    {
        if (is_null($expr)) {
            return '<?php else: ?>';
        }

        return "<?php elseif (auth()->can{$expr}): ?>";
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndcan(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param string $expr
     * @return string
     */
    protected function compileCannot($expr)
    {
        return "<?php if (!auth()->can{$expr}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param string|null $expr
     * @return string
     */
    protected function compileElsecannot($expr)
    {
        if (is_null($expr)) {
            return '<?php else: ?>';
        }

        return "<?php elseif (!auth()->can{$expr}): ?>";
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndcannot(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return '<?php endif; ?>';
    }

    /*
     * Raw PHP Statement
     * -----------------
     */

    /**
     * Compile the raw PHP statements into valid PHP.
     *
     * @param string|null $expr
     * @return string
     */
    protected function compilePhp($expr)
    {
        return !is_null($expr) ? "<?php {$expr}; ?>" : '<?php ';
    }

    /**
     * Compile end-php statement into valid PHP.
     *
     * @param null $expr
     * @return string
     */
    protected function compileEndphp(/** @noinspection PhpUnusedParameterInspection */ $expr)
    {
        return ' ?>';
    }
}