<?php

namespace Core\Services;

use Core\Services\Contracts\Response as ResponseContract;

/**
 * The Response class represents an HTTP response.
 *
 * The class based on Flight, an extensible micro-framework by Mike Cao <mike@mikecao.com> (http://flightphp.com/license MIT License)
 *
 * @see https://github.com/mikecao/flight/blob/master/flight/net/Response.php
 */
class Response implements ResponseContract
{
    /**
     * HTTP response body
     *
     * @var string
     */
    private $content;

    /**
     * HTTP status code
     *
     * @var int
     */
    private $status = 200;

    /**
     * HTTP headers
     *
     * @var array
     */
    private $headers = [];

//    /**
//     * Create a new Response instance.
//     */
//    public function __construct()
//    {
//    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->status  = 200;
        $this->headers = [];
        $this->content = '';

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function output($content, $status = 200, $headers = [])
    {
        $this->content = $content;
        $this->status  = $status;
        if (!empty($headers)) {
            $this->header($headers);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function view($name, array $variables = [], $status = 200, $headers = [])
    {
        return $this->output(DI::getInstance()->get('view')->render($name, $variables), $status, $headers);
    }

    /**
     * @inheritdoc
     */
    public function redirect($url, $status = 302, $headers = [])
    {
        if (!empty($headers)) {
            $this->header($headers);
        }

        return $this->header('location', $url)->status($status);
    }

    /**
     * @inheritdoc
     */
    public function status($code = 200)
    {
//        if (!array_key_exists($code, static::$statusTexts)) {
//            throw new \Exception('Invalid status code.');
//        }
        $this->status = $code;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode() // todo umbennenen in statusCode()
    {
        return $this->status;
    }

    /**
     * @inheritdoc
     */
    public function getStatusText() // todo umbennenen in statusText()
    {
//        return isset(static::$statusTexts[$this->status]) ?  static::$statusTexts[$this->status] : '';
        return http_status_text($this->status);
    }

    /**
     * @inheritdoc
     */
    public function header($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        }
        else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders() // todo umbennenen in headers()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function write($str)
    {
        $this->content .= $str;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function cache($expires)
    {
        if ($expires === false) {
            $this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = array(
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            );
            $this->headers['Pragma'] = 'no-cache';
        }
        else {
            $expires = is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age='.($expires - time());
            if (isset($this->headers['Pragma']) && $this->headers['Pragma'] == 'no-cache'){
                unset($this->headers['Pragma']);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
//        if (ob_get_length() > 0) {
//            $echo = ob_get_clean();
//        }

        if (!headers_sent()) {
            $this->sendHeaders();
        }

//        if (isset($echo)) {
//            echo $echo;
//        }

        echo $this->content;
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    private function sendHeaders()
    {
        // Send status code header
        if (PHP_SAPI == 'cli') {
            header(
                sprintf(
                    'Status: %d %s',
                    $this->status,
                    $this->getStatusText()
                ),
                true
            );
        }
        else {
            header(
                sprintf(
                    '%s %d %s',
                    (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1'),
                    $this->status,
                    $this->getStatusText()
                ),
                true,
                $this->status
            );
        }

        // Send other headers
        foreach ($this->headers as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($field.': ' . $v, false);
                }
            }
            else {
                header($field.': ' . $value);
            }
        }

        // Send content length
        if (($length = strlen($this->content)) > 0) {
            header('Content-Length: ' . $length);
        }

        return $this;
    }

    /*
    # todo: setCookie()

    The `cookie` method on response instances allows you to easily attach cookies to the response. For example, you may use the `cookie` method to generate a cookie and fluently attach it to the response instance like so:

        return response($content)
                ->header('Content-Type', $type)
                ->cookie('name', 'value', $minutes);

    The `cookie` method also accepts a few more arguments which are used less frequently. Generally, these arguments have the same purpose and meaning as the arguments that would be given to PHP's native [setcookie](https://secure.php.net/manual/en/function.setcookie.php) method:

        ->cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly)

    By default, all cookies generated by Pletfix are encrypted and signed so that they can't be modified or read by the client. If you would like to disable encryption for a subset of cookies generated by your application, you may use the `$except` property of the `App\Http\Middleware\EncryptCookies` middleware, which is located in the `app/Http/Middleware` directory:

         * The names of the cookies that should not be encrypted.
         *
         * @var array
        protected $except = [
            'cookie_name',
        ];


    # todo Response JSON

    The `json` method will automatically set the `Content-Type` header to `application/json`, as well as convert the given array to JSON using the `json_encode` PHP function:

        return response()->json([
            'name' => 'Abigail',
            'state' => 'CA'
        ]);

    If you would like to create a JSONP response, you may use the `json` method in combination with the `withCallback` method:

        return response()
                ->json(['name' => 'Abigail', 'state' => 'CA'])
                ->withCallback($request->input('callback'));



    # todo File Downloads

    The `download` method may be used to generate a response that forces the user's browser to download the file at the given path. The `download` method accepts a file name as the second argument to the method, which will determine the file name that is seen by the user downloading the file. Finally, you may pass an array of HTTP headers as the third argument to the method:

        return response()->download($pathToFile);

        return response()->download($pathToFile, $name, $headers);

    > {note} Symfony HttpFoundation, which manages file downloads, requires the file being downloaded to have an ASCII file name.

    # todo File Responses

    The `file` method may be used to display a file, such as an image or PDF, directly in the user's browser instead of initiating a download. This method accepts the path to the file as its first argument and an array of headers as its second argument:

        return response()->file($pathToFile);

        return response()->file($pathToFile, $headers);


    # todo back()

    The `back()` function generates a redirect response to the user's previous location:

        return back();

    */

}