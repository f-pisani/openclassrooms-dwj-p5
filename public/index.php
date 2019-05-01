<?php
require_once '../autoload.php';
require_once '../bootstrap.php';

use App\{Router};

Router::match(['get', 'post'], '/login', 'LoginController@index');
Router::match(['get', 'post'], '/register', 'LoginController@register');

Router::dispatch();
