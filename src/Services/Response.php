<?php

namespace Core\Services;

use Core\Services\Contracts\Response as ResponseContract;

/**
 * The Response class represents an HTTP response.
 *
 * The class based on Flight: An extensible micro-framework.
 * Copyright (c) 2011, Mike Cao <mike@mikecao.com>, MIT, http://flightphp.com/license
 * https://github.com/mikecao/flight/blob/master/flight/net/Response.php
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
        $this->headers = $headers;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function view($name, $variables = [], $status = 200, $headers = [])
    {
        return $this->output(DI::getInstance()->get('view')->render($name, $variables), $status, $headers);
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


    # todo Redirects

    Redirect responses are instances of the `Illuminate\Http\RedirectResponse` class, and contain the proper headers needed to redirect the user to another URL. There are several ways to generate a `RedirectResponse` instance. The simplest method is to use the global `redirect` helper:

        Route::get('dashboard', function () {
            return redirect('home/dashboard');
        });

    Sometimes you may wish to redirect the user to their previous location, such as when a submitted form is invalid. You may do so by using the global `back` helper function. Since this feature utilizes the [session](/docs/{{version}}/session), make sure the route calling the `back` function is using the `web` middleware group or has all of the session middleware applied:

        Route::post('user/profile', function () {
            // Validate the request...

            return back()->withInput();
        });

    When you call the `redirect` helper with no parameters, an instance of `Illuminate\Routing\Redirector` is returned, allowing you to call any method on the `Redirector` instance. For example, to generate a `RedirectResponse` to a named route, you may use the `route` method:

        return redirect()->route('login');

    If your route has parameters, you may pass them as the second argument to the `route` method:

        // For a route with the following URI: profile/{id}

        return redirect()->route('profile', ['id' => 1]);

    You may also generate redirects to [controller actions](/docs/{{version}}/controllers). To do so, pass the controller and action name to the `action` method. Remember, you do not need to specify the full namespace to the controller since Laravel's `RouteServiceProvider` will automatically set the base controller namespace:

        return redirect()->action('HomeController@index');

    If your controller route requires parameters, you may pass them as the second argument to the `action` method:

        return redirect()->action(
            'UserController@profile', ['id' => 1]
        );

    <a name="redirecting-with-flashed-session-data"></a>
    ### Redirecting With Flashed Session Data

    Redirecting to a new URL and [flashing data to the session](/docs/{{version}}/session#flash-data) are usually done at the same time. Typically, this is done after successfully performing an action when you flash a success message to the session. For convenience, you may create a `RedirectResponse` instance and flash data to the session in a single, fluent method chain:

        Route::post('user/profile', function () {
            // Update the user's profile...

            return redirect('dashboard')->with('status', 'Profile updated!');
        });

    After the user is redirected, you may display the flashed message from the [session](/docs/{{version}}/session). For example, using [Blade syntax](/docs/{{version}}/blade):

        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

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


    # todo redirect()

    The `redirect` function returns a redirect HTTP response, or returns the redirector instance if called with no arguments:

        return redirect('/home');

        return redirect()->route('route.name');

    */

}