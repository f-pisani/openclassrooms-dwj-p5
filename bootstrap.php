<?php
use App\{Session, Config, Request};

Session::start();

Config::set("DB_HOST", "localhost");
Config::set("DB_USER", "root");
Config::set("DB_PWD", "");
Config::set("DB_BASE", "projectflow");

Config::set("SITE_URL", Request::protocol()."://".Request::hostname());
Config::set("APP_PATH", dirname(str_replace('\\', '/', getcwd())));
Config::set("APP_PATH_PUBLIC", APP_PATH.'/public');
