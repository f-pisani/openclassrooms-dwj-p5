<?php
require_once '../autoload.php';
require_once '../bootstrap.php';

use App\{Router};

Router::get('/', 'HomeController@index')->setName('ROOT');
Router::error404('HomeController@index');

Router::dispatch();
