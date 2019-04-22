<?php
use App\{Router};

Router::any('/{id}', 'HomeController@index');
