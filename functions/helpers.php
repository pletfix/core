<?php

/*
 * Source hints
 *
 * The `plural`and `singular` functions are based on Laravel's Str class and Laravel' Pluralizer by Taylor Otwell,
 * which again used the Doctrine's Inflector (https://github.com/doctrine/inflector/blob/1.1.x/LICENSE MIT License).
 *
 * `pascal_case`, `limit_string`, `slug` and `utf8_to_ascii` are copied from Laravel's Str class.
 *
 * $charsArray of the `utf8_to_ascii` method is adapted from Stringy by Daniel St. Jules (https://github.com/danielstjules/Stringy/blob/2.3.1/LICENSE.txt MIT License)
 *
 * @see https://github.com/illuminate/support/blob/5.3/Str.php Laravel's Str Class on GitHub
 * @see https://github.com/illuminate/support/blob/5.3/Pluralizer.php Laravel's Pluralizer on GitHub
 * @see https://github.com/doctrine/inflector/tree/1.1.x Doctrine's Inflector on GitHub
 * @see https://github.com/danielstjules/Stringy/blob/master/src/Stringy.php Stringy on GitHub
 */

use Core\Services\Contracts\Response;
use Core\Services\DI;

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param int $status HTTP status code
     * @param string $message
     * @param array $headers
     * @throws Exception
     */
    function abort($status = Response::HTTP_INTERNAL_SERVER_ERROR, $message = '', array $headers = [])
    {
//        if ($code == 404) {
//            throw new NotFoundHttpException($message);
//        }
        throw new \Core\Exceptions\HttpException($status, $message, null, $headers);
    }
}

if (! function_exists('asset')) {
    /**
     * Generate the URL to an application asset.
     *
     * Returns the versioned asset if exist.
     *
     * @param string $file
     * @return string
     */
    function asset($file)
    {
        static $manifest = null;
        if ($manifest === null) {
            $manifestFile = manifest_path('assets/manifest.php');
            /** @noinspection PhpIncludeInspection */
            $manifest = @file_exists($manifestFile) ? require $manifestFile : [];
        }

        if (isset($manifest[$file])) {
            $file = $manifest[$file];
        }

        return request()->baseUrl() . '/' . $file;
    }
}

if (! function_exists('bcrypt')) {
    /**
     * Creates a password hash using the <b>CRYPT_BLOWFISH</b> algorithm.
     *
     * @param string $password The plain password.
     * @param int $cost The crypt cost factor. Examples of these values can be found on the {@link "http://www.php.net/manual/en/function.crypt.php crypt()"} page.
     * @return string Returns the hashed password.
     */
    function bcrypt($password, $cost = 10)
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.'); // @codeCoverageIgnore
        }

        return $hash;
    }
}

if (!function_exists('benchmark')) {
    /**
     * Print the elapsed time in milliseconds and the memory usage in bytes since the last call.
     *
     * @param callable $callback
     * @param int $loops Number of loops which execute the callback.
     * @param bool|null $return [optional] <p>
     * If used and set to true, benchmark will return the variable representation instead of outputting it.
     * @return null|array Delay and memory usage if the return parameter is true. Otherwise, this function will return null.
     */
    function benchmark(callable $callback, $loops = 1, $return = null)
    {
        $memoryBefore = memory_get_usage();
        $timestamp = microtime(true);
        for ($loop = 0; $loop < $loops; $loop++) {
            $callback($loop);
        }
        $delay = round((microtime(true) - $timestamp) * 1000, 3);
        $memoryAfter = memory_get_usage();

        if ($return) {
            return [$delay, $memoryBefore, $memoryAfter];
        }

        echo 'Delay: ' . round($delay, 6) . ' ms';
        if ($loops > 1) {
            echo ' (avg ' . round($delay / $loops, 6) . ' ms)';
        }
        echo ', Memory: ' . round($memoryAfter / 1024, 3) . ' KB' .
                    ' - ' . round($memoryBefore / 1024, 3) . ' KB' .
                    ' = ' . round(($memoryAfter - $memoryBefore) / 1024, 3) . 'KB<br/>' . PHP_EOL;

        return null;
    }
}

if (! function_exists('command')) {
    /**
     * Run a console command by name.
     *
     * @param string $name Command name
     * @param array $argv Command line arguments and options
     * @return int Exit code
     */
    function command($name, array $argv = [])
    {
        array_unshift($argv, $name);
        /** @var \Core\Services\Contracts\Command|false $command */
        $command = DI::getInstance()->get('command-factory')->command($argv);

        return $command !== false ? $command->run() : 0;
    }
}

if (!function_exists('config')) {
    /**
     * Get the Configuration
     *
     * @param string|null $key Key using "dot" notation.
     * @param mixed $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        /** @var \Core\Services\Contracts\Config $service */
        static $service;
        if ($service === null) {
            // is execute by the bootstrap, always before the test is started!
            $service = DI::getInstance()->get('config'); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        return $service->get($key, $default);
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Get a CSRF token value.
     *
     * @return string
     */
    function csrf_token()
    {
        $session = DI::getInstance()->get('session');

        $csrf = $session->get('_csrf_token');
        if ($csrf === null) {
            $csrf = random_string(40);
            $session->set('_csrf_token', $csrf);
        }

        return $csrf;
    }
}

if (!function_exists('dump')) {
    /**
     * Dump a value.
     *
     * @param mixed $value
     * @param bool|null $return [optional] <p>
     * If used and set to true, dump will return the variable representation instead of outputing it.
     * @return mixed The variable representation when the return parameter is used and evaluates to true. Otherwise,
     * this function will return null.
     * </p>
     */
    function dump($value, $return = null)
    {
        if (PHP_SAPI === 'cli') {
            $output = var_export($value, true);
        }
        else {
            $output = '<pre>' . var_export($value, true) . '</pre>'; // @codeCoverageIgnore
        }

        if ($return) {
            return $output;
        }

        echo $output;

        return null;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump a value and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd($value)
    {
        dump($value);
        if (!is_testing()) {
            die(1); // @codeCoverageIgnore
        }
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param string  $value
     * @return string
     */
    function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * IMPORTANT:
     * If the config was cached the environment file ".env" is not read. Therefore you should never use this
     * function directly, instead only in the configuration files.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'empty':
                return '';
            case 'null':
                return null;
        }

        $n = strlen($value);
        if ($n > 1 && $value[0] == '"' && $value[$n - 1] == '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (! function_exists('error')) {
    /**
     * Retrieve an error message from the flash.
     *
     * @param string $key
     * @param string $default
     * @return string|array
     */
    function error($key = null, $default = null)
    {
        if ($key === null) {
            return flash('errors', []);
        }

        return flash('errors.' . $key, $default);
    }
}

//if (!function_exists('format_datetime')) {
//    /**
//     * Returns the given datetime formatted by the apps settings.
//     *
//     * @param string $value DateTime
//     * @return string
//     */
//    function format_datetime($value)
//    {
//        return date_create($value)->format(config('app.date_formats.' . config('app.locale') . '.datetime'));
//    }
//}
//
//if (!function_exists('format_date')) {
//    /**
//     * Returns the given date formatted by the apps settings.
//     *
//     * @param string $value Date
//     * @return string
//     */
//    function format_date($value)
//    {
//        return date_create($value)->format(config('app.date_formats.' . config('app.locale') . '.date'));
//    }
//}
//
//if (!function_exists('format_time')) {
//    /**
//     * Returns the given time formatted by the apps settings.
//     *
//     * @param string $value Time
//     * @return string
//     */
//    function format_time($value)
//    {
//        return date_create($value)->format(config('app.date_formats.' . config('app.locale') . '.time'));
//    }
//}

if (!function_exists('is_console')) {
    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    function is_console()
    {
        return PHP_SAPI == 'cli';
    }
}

if (!function_exists('is_testing')) {
    /**
     * Determine if we are running unit tests.
     *
     * @return bool
     */
    function is_testing()
    {
        return config('app.env') == 'testing';
    }
}

if (!function_exists('is_windows')) {
    /**
     * Determine if the os is windows.
     *
     * @return bool
     */
    function is_windows()
    {
        return strtolower(substr(PHP_OS, 0, 3)) == 'win';
    }
}

if (!function_exists('list_files')) {
    /**
     * Read files recursive.
     *
     * @param array &$result Receives the files
     * @param string $path Path to scan
     * @param array|null $filter List of extensions which should be listed, e.g. ['css', 'less', 'scss']
     * @param bool $recursive
     */
    function list_files(array &$result, $path, array $filter = null, $recursive = true)
    {
        foreach (scandir($path) as $file) {
            if ($file[0] == '.') {
                continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (@is_dir($file)) {
                if ($recursive) {
                    list_files($result, $file, $filter);
                }
            }
            else {
                if (!is_null($filter)) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (!in_array($ext, $filter)) {
                        continue;
                    }
                }
                $result[] = $file;
            }
        }
    }
}

if (!function_exists('list_classes')) {
    /**
     * Read available PHP classes recursive from given path.
     *
     * @param array &$result Receives the classes
     * @param string $path
     * @param string $namespace
     * @param string|null $suffix
     */
    function list_classes(array &$result, $path, $namespace, $suffix = '')
    {
        $ext = $suffix . '.php';
        $len = strlen($ext);

        foreach (scandir($path) as $file) {
            if ($file[0] == '.') {
                continue;
            }
            if (@is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                list_classes($result, $path . DIRECTORY_SEPARATOR . $file, $namespace . '\\' . $file, $suffix);
            }
            else if (substr($file, -$len) == $ext) {
                $result[] = $namespace . '\\' . basename($file, '.php');
            }
        }
    }
}

if (!function_exists('locale')) {
    /**
     * Get and set the current locale.
     *
     * @param string $lang
     * @return string
     */
    function locale($lang = null)
    {
        if ($lang !== null) {
            DI::getInstance()->get('config')->set('app.locale', $lang);
            DI::getInstance()->get('translator')->setLocale($lang);
            $dt = DI::getInstance()->get('date-time');
            $dt::setLocale($lang);
            return $lang;
        }

        return config('app.locale');
    }
}

if (!function_exists('mail_address')) {
    /**
     * Get the email address without the name.
     *
     * @param string $address e.g. "User <user@example.com>"
     * @return string e.g. "user@example.com"
     */
    function mail_address($address)
    {
        if (($pos = strpos($address, '<')) === false || $address[strlen($address) - 1] != '>') {
            return $address;
        }

        return substr($address, $pos + 1, -1);
    }
}

if (!function_exists('make_dir')) {
    /**
     * Create a folder recursive.
     *
     * @param string $path
     * @param int $mode
     * @return bool
     */
    function make_dir($path, $mode = 0777)
    {
        $old = umask(0);
        try {
            $result = @mkdir($path, $mode, true);
        }
        finally {
            umask($old);
        }

        return $result;
    }
}

if (! function_exists('message')) {
    /**
     * Retrieve a message from the flash.
     *
     * @param string $default
     * @return string
     */
    function message($default = null)
    {
        return flash('message', $default);
    }
}

if (! function_exists('old')) {
    /**
     * Retrieve an input item from the flash.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old($key = null, $default = null)
    {
        if ($key === null) {
            return flash('input', []);
        }

        return flash('input.' . $key, $default);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get a redirect response to the given path.
     *
     * @param string $path
     * @param array $parameters
     * @param array $flash
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function redirect($path, $parameters = [], $flash = [], $status = 302, $headers = [])
    {
        if (!empty($flash)) {
            DI::getInstance()->get('flash')->merge(null, $flash);
        }

//        if (substr($path, 0, 8) !== 'https://' && substr($path, 0, 7) !== 'http://') {
//            $url = DI::getInstance()->get('request')->baseUrl() . (!empty($path) ? '/' . $path : '') . (!empty($parameters) ? '?' . http_build_query($parameters) : '');
//        }
//        else {
//            $url = $path . (!empty($parameters) ? '?' . http_build_query($parameters) : '');
//        }

        $url = DI::getInstance()->get('request')->baseUrl() . (!empty($path) ? '/' . $path : '') . (!empty($parameters) ? '?' . http_build_query($parameters) : '');

        return DI::getInstance()->get('response')->redirect($url, $status, $headers);
    }
}

if (!function_exists('remove_dir')) {
    /**
     * Delete a folder (or file).
     *
     * The folder does not have to be empty.
     *
     * @param string $path
     * @return bool
     */
    function remove_dir($path)
    {
        if (@is_file($path) || @is_link($path)) {
            return @unlink($path);
        }

        foreach (@scandir($path) as $filename) {
            if ($filename[0] == '.') {
                continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . $filename;
            if (@is_dir($file)) {
                remove_dir($file);
            }
            else {
                @unlink($file);
            }
        }

        return @rmdir($path);
    }
}

if (!function_exists('http_status_text')) {
    /**
     * Translate a HTTP Status code to plain text.
     *
     * The list of codes is complete according to the HTTP Status Code Registry:
     * http://www.iana.org/assignments/http-status-codes.
     *
     * Last updated: 2016-03-01
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     *
     * @param int $status
     * @return string
     */
    function http_status_text($status)
    {
        $statusTexts = [

            // 1xx Informational
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',                        // RFC2518

            // 2xx Success
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',                      // RFC4918
            208 => 'Already Reported',                  // RFC5842
            226 => 'IM Used',                           // RFC3229

            // 3xx Redirection
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',                // RFC7238

            // 4xx Client Error
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',                     // RFC2324
            421 => 'Misdirected Request',               // RFC7540
            422 => 'Unprocessable Entity',              // RFC4918
            423 => 'Locked',                            // RFC4918
            424 => 'Failed Dependency',                 // RFC4918
            426 => 'Upgrade Required',                  // RFC2817
            428 => 'Precondition Required',             // RFC6585
            429 => 'Too Many Requests',                 // RFC6585
            431 => 'Request Header Fields Too Large',   // RFC6585
            451 => 'Unavailable For Legal Reasons',     // RFC7725

            // 5xx Server Error
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',           // RFC2295
            507 => 'Insufficient Storage',              // RFC4918
            508 => 'Loop Detected',                     // RFC5842
            510 => 'Not Extended',                      // RFC2774
            511 => 'Network Authentication Required',   // RFC6585
        ];

        return isset($statusTexts[$status]) ? $statusTexts[$status] : '';
    }
}

if (!function_exists('t')) {
    /**
     * Get the translation for the given key.
     *
     * If the key does not exist, the key is returned.
     *
     * @param string $key Key using "dot" notation.
     * @param array $replace Values replacing the placeholders.
     * @return string|array
     */
    function t($key, $replace = [])
    {
        return DI::getInstance()->get('translator')->translate($key, $replace);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a absolute URL to the given path.
     *
     * @param string $path
     * @param array $parameters
     * @return string
     */
    function url($path = '', $parameters = [])
    {
        return DI::getInstance()->get('request')->baseUrl() . (!empty($path) ? '/' . $path : '') . (!empty($parameters) ? '?' . http_build_query($parameters) : '');
    }
}

// --------------------------------------------------------------------
// Path Helper
// --------------------------------------------------------------------

if (!function_exists('app_path')) {
    /**
     * Get the app path.
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return BASE_PATH .  DIRECTORY_SEPARATOR . 'app'  . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path for the application.
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return BASE_PATH . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'config' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('manifest_path')) {
    /**
     * Get the manifest path.
     *
     * @param string $path
     * @return string
     */
    function manifest_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . '.manifest' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the public path.
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'public' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the resource path.
     *
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'resources' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path.
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('vendor_path')) {
    /**
     * Get the vendor path.
     *
     * @param string $path
     * @return string
     */
    function vendor_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('workbench_path')) {
    /**
     * Get the workbench path.
     *
     * @param string $path
     * @return string
     */
    function workbench_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'workbench' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

// --------------------------------------------------------------------
// String Manipulation
// --------------------------------------------------------------------

if (!function_exists('plural')) {
    /**
     * Get the plural form of an english word.
     *
     * @param string $word
     * @return string
     */
    function plural($word)
    {
        static $uncountable;
        if (!isset($uncountable)) {
            $uncountable = [
                'audio',
                'bison',
                'chassis',
                'compensation',
                'coreopsis',
                'data',
                'deer',
                'education',
                'emoji',
                'equipment',
                'fish',
                'furniture',
                'gold',
                'information',
                'knowledge',
                'love',
                'rain',
                'money',
                'moose',
                'nutrition',
                'offspring',
                'plankton',
                'pokemon',
                'police',
                'rice',
                'series',
                'sheep',
                'species',
                'swine',
                'traffic',
                'wheat',
            ];
        }

        if (in_array(strtolower($word), $uncountable)) {
            return $word;
        }

        $plural = Doctrine\Common\Inflector\Inflector::pluralize($word);

        if (mb_strtolower($word) === $word) {
            return mb_strtolower($plural);
        }

        if (mb_strtoupper($word) === $word) {
            return mb_strtoupper($plural);
        }

        if (ucfirst($word) === $word) {
            return ucfirst($plural);
        }

        return $plural;
    }
}

if (!function_exists('singular')) {

    /**
     * Get the singular form of an english word.
     *
     * @param string $word
     * @return string
     */
    function singular($word)
    {
        $singular = Doctrine\Common\Inflector\Inflector::singularize($word);

        if (mb_strtolower($word) === $word) {
            return mb_strtolower($singular);
        }

        if (mb_strtoupper($word) === $word) {
            return mb_strtoupper($singular);
        }

        if (ucfirst($word) === $word) {
            return ucfirst($singular);
        }

        return $singular;
    }
}

if (!function_exists('camel_case')) {
    /**
     * Converts a word to camel case (camelCase).
     *
     * This is useful to convert a word into the format for a variable or method name.
     *
     * @param string $word
     * @return string
     */
    function camel_case($word)
    {
        return lcfirst(pascal_case($word));
    }
}

if (!function_exists('lower_case')) {
    /**
     * Convert the given word to lower case.
     *
     * @param string $word
     * @return string
     */
    function lower_case($word)
    {
        return mb_strtolower($word, 'UTF-8');
    }
}

if (!function_exists('pascal_case')) {
    /**
     * Converts the given word to pascal case, also known as studly caps case (PascalCase).
     *
     * This is useful to convert a word into the format for a class name.
     *
     * @param string $word
     * @return string
     */
    function pascal_case($word)
    {
        return str_replace(' ', '', ucwords(strtr($word, '_-', '  ')));
    }
}

if (!function_exists('random_string')) {
    /**
     * Generate cryptographically secure pseudo-random alpha-numeric string.
     *
     * @param int $length
     * @return string
     */
    function random_string($length = 32)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode(random_bytes($size))), 0, $size);
        }

        return $string;
    }
}

if (!function_exists('snake_case')) {
    /**
     * Convert the given word to snake case (snake_case).
     *
     * This is useful to converts a word into the format for a table or a global function name.
     *
     * @param string $word
     * @return string
     */
    function snake_case($word)
    {
        return mb_strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $word), 'UTF-8');

        // Laravels version:
        //if (!ctype_lower($word)) {
        //    return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', preg_replace('/\s+/u', '', $word)), 'UTF-8');
        //}
        //return $word;
    }
}

if (!function_exists('title_case')) {
    /**
     * Convert the given word to title case (Title Case).
     *
     * @param string $word
     * @return string
     */
    function title_case($word)
    {
        return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
    }
}

if (!function_exists('upper_case')) {
    /**
     * Convert the given word to upper case.
     *
     * @param string $word
     * @return string
     */
    function upper_case($word)
    {
        return mb_strtoupper($word, 'UTF-8');
    }
}

if (!function_exists('limit_string')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    function limit_string($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }
}

if (!function_exists('slug')) {
    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string $title
     * @param string $separator
     * @return string
     */
    function slug($title, $separator = '-')
    {
        $title = utf8_to_ascii($title);

        // Convert all dashes/underscores into separator.
        $flip = $separator == '-' ? '_' : '-';

        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator.
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

        return trim($title, $separator);
    }
}

if (!function_exists('utf8_to_ascii')) {
    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @param string $value UTF-8 string
     * @return string ASCII string
     */
    function utf8_to_ascii($value)
    {
        static $charsArray;
        if (!isset($charsArray)) {
            $charsArray = [
                // ASCII => UTF-8
                '0'    => ['°', '₀', '۰'],
                '1'    => ['¹', '₁', '۱'],
                '2'    => ['²', '₂', '۲'],
                '3'    => ['³', '₃', '۳'],
                '4'    => ['⁴', '₄', '۴', '٤'],
                '5'    => ['⁵', '₅', '۵', '٥'],
                '6'    => ['⁶', '₆', '۶', '٦'],
                '7'    => ['⁷', '₇', '۷'],
                '8'    => ['⁸', '₈', '۸'],
                '9'    => ['⁹', '₉', '۹'],
                'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا'],
                'b'    => ['б', 'β', 'Ъ', 'Ь', 'ب', 'ဗ', 'ბ'],
                'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ'],
                'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ'],
                'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ'],
                'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ'],
                'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ'],
                'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ'],
                'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ'],
                'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج'],
                'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک'],
                'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ'],
                'm'    => ['м', 'μ', 'م', 'မ', 'მ'],
                'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ'],
                'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ'],
                'p'    => ['п', 'π', 'ပ', 'პ', 'پ'],
                'q'    => ['ყ'],
                'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ'],
                's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს'],
                't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ'],
                'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ'],
                'v'    => ['в', 'ვ', 'ϐ'],
                'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ'],
                'x'    => ['χ', 'ξ'],
                'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ'],
                'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ'],
                'aa'   => ['ع', 'आ', 'آ'],
                'ae'   => ['ä', 'æ', 'ǽ'],
                'ai'   => ['ऐ'],
                'at'   => ['@'],
                'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
                'dj'   => ['ђ', 'đ'],
                'dz'   => ['џ', 'ძ'],
                'ei'   => ['ऍ'],
                'gh'   => ['غ', 'ღ'],
                'ii'   => ['ई'],
                'ij'   => ['ĳ'],
                'kh'   => ['х', 'خ', 'ხ'],
                'lj'   => ['љ'],
                'nj'   => ['њ'],
                'oe'   => ['ö', 'œ', 'ؤ'],
                'oi'   => ['ऑ'],
                'oii'  => ['ऒ'],
                'ps'   => ['ψ'],
                'sh'   => ['ш', 'შ', 'ش'],
                'shch' => ['щ'],
                'ss'   => ['ß'],
                'sx'   => ['ŝ'],
                'th'   => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
                'ts'   => ['ц', 'ც', 'წ'],
                'ue'   => ['ü'],
                'uu'   => ['ऊ'],
                'ya'   => ['я'],
                'yu'   => ['ю'],
                'zh'   => ['ж', 'ჟ', 'ژ'],
                '(c)'  => ['©'],
                'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ'],
                'B'    => ['Б', 'Β', 'ब'],
                'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ'],
                'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ'],
                'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə'],
                'F'    => ['Ф', 'Φ'],
                'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ'],
                'H'    => ['Η', 'Ή', 'Ħ'],
                'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ'],
                'K'    => ['К', 'Κ'],
                'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल'],
                'M'    => ['М', 'Μ'],
                'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν'],
                'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ'],
                'P'    => ['П', 'Π'],
                'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ'],
                'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ'],
                'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ'],
                'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ'],
                'V'    => ['В'],
                'W'    => ['Ω', 'Ώ', 'Ŵ'],
                'X'    => ['Χ', 'Ξ'],
                'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ'],
                'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ'],
                'AE'   => ['Ä', 'Æ', 'Ǽ'],
                'CH'   => ['Ч'],
                'DJ'   => ['Ђ'],
                'DZ'   => ['Џ'],
                'GX'   => ['Ĝ'],
                'HX'   => ['Ĥ'],
                'IJ'   => ['Ĳ'],
                'JX'   => ['Ĵ'],
                'KH'   => ['Х'],
                'LJ'   => ['Љ'],
                'NJ'   => ['Њ'],
                'OE'   => ['Ö', 'Œ'],
                'PS'   => ['Ψ'],
                'SH'   => ['Ш'],
                'SHCH' => ['Щ'],
                'SS'   => ['ẞ'],
                'TH'   => ['Þ'],
                'TS'   => ['Ц'],
                'UE'   => ['Ü'],
                'YA'   => ['Я'],
                'YU'   => ['Ю'],
                'ZH'   => ['Ж'],
                ' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80"],
            ];
        }

        foreach ($charsArray as $key => $val) {
            $value = str_replace($val, $key, $value);
        }

        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }
}

///////////////////////////////////////////////////////////////////////
// Database

//if (!function_exists('get_sql')) {
//    /**
//     * Bind parameters into the SQL query
//     *
//     * @param \Core\Services\Contracts\Database $db
//     * @return string
//     */
//    function get_sql($query)
//    {
//        $sql = $query->toSql();
//        foreach ($query->getBindings() as $binding) {
//            $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
//            $sql = preg_replace('/\?/', $value, $sql, 1);
//        }
//
//        return $sql;
//    }
//}

//if (!function_exists('log_sql')) {
//    /**
//     * Log all sql queries.
//     */
//    function log_sql()
//    {
//        $logfile = storage_path().'/logs/sql.log';
//        \Log::useFiles($logfile);
//        file_put_contents($logfile, '');
//        \DB::listen(
//            function ($query) {
//                \Log::info($query->sql, ['bindings' => $query->bindings, 'time' => $query->time]);
//            }
//        );
//    }
//}