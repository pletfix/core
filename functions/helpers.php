<?php

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
    function abort($status = HTTP_STATUS_INTERNAL_SERVER_ERROR, $message = '', array $headers = [])
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
        if (is_null($manifest)) {
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
    function command($name, array $argv = []) // todo command() testen
    {
        /** @var \Core\Services\Contracts\Command|false $command */
        $command = DI::getInstance()->get('command-factory')->command(array_unshift($argv, [$name]));

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
//        return DI::getInstance()->get('config')->get($key, $default);

        /** @var \Core\Services\Contracts\Config $service */
        static $service;
        if ($service === null) { // promote fast access...
            $service = DI::getInstance()->get('config');
        }

        return $service->get($key, $default);
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
            return var_export($value, $return);
        }

        $output = '<pre>' . var_export($value, true) . '</pre>';
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
        die(1);
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

if (!function_exists('is_win')) {
    /**
     * Determine if the os is windows.
     *
     * @return bool
     */
    function is_win()
    {
        return strtolower(substr(PHP_OS, 0, 3)) == 'win'; // todo testen (mit laravel vergl.)
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
    function list_files(&$result, $path, $filter = null, $recursive = true)
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
     */
    function list_classes(&$result, $path, $namespace)
    {
        foreach (scandir($path) as $file) {
            if ($file[0] == '.') {
                continue;
            }
            if (@is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                list_classes($result, $path . DIRECTORY_SEPARATOR . $file, $namespace . '\\' . $file);
            }
            else if (substr($file, -4) == '.php') {
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

if (!function_exists('remove_dir')) {
    /**
     * Delete a folder (or file).
     *
     * @param string $path
     * @return bool
     */
    //
    function remove_dir($path)
    {
        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }

        foreach (scandir($path) as $file) {
            if ($file[0] == '.') {
                continue;
            }
            if (@is_dir($file)) {
                remove_dir($path . DIRECTORY_SEPARATOR . $file);
            }
            else {
                unlink($path);
            }
        }

        return rmdir($path);
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
        return request()->baseUrl() . (!empty($path) ? '/' . $path : '') . (!empty($parameters) ? '?' . http_build_query($parameters) : '');
    }
}

/*
 * --------------------------------------------------------------------------------------------------------------
 * Path Helper
 * --------------------------------------------------------------------------------------------------------------
 */

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

if (!function_exists('asset_path')) { // todo ist noch nicht dokumentiert - evtl mit resource_path('assets/' . $file) ersetzen!
    /**
     * Get the asset path (the subfolder of resources).
     *
     * @param string $path
     * @return string
     */
    function asset_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'assets' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
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

if (!function_exists('environment_file')) { // todo ist noch nicht dokumentiert - evtl mit base_path('.env') ersetzen!
    /**
     * Get the environment file.
     */
    function environment_file()
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . '.env';
    }
}

if (!function_exists('migration_path')) { // todo ist noch nicht dokumentiert - evtl mit resource_path('migrations/' . $file) ersetzen
    /**
     * Get the migration path.
     *
     * @param string $path
     * @return string
     */
    function migration_path($path = '')
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'migrations' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
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

if (!function_exists('view_path')) {
    /**
     * Get the view path.
     *
     * @param string $path
     * @return string
     */
    function view_path($path = '') // todo noch nicht dokumentiert - evtl mit resource_path('views/' . $file) ersetzen
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('workbench_path')) {
    /**
     * Get the workbench path.
     *
     * @param string $path
     * @return string
     */
    function workbench_path($path = '') // todo noch nicht dokumentiert - evtl mit base_path('workbench/' . $file) ersetzen
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'workbench' . (!empty($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

////////////////////////////////////////////////////////////////////////////////
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