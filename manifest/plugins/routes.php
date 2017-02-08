<?php

$route = \Core\Application::route();

///////////////////////////////////////////////////////////////////////////////
// pletfix/hello

$route->get('hello', function() {
    return di('hello')->sayHello();
//  return view('test2', $data = collect(['name' => 'Anton']));
});
