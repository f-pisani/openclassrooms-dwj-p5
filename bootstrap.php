<?php
use App\{Session, Config, Request, DataValidator};

Session::start();

Config::set("DB_HOST", "localhost");
Config::set("DB_USER", "root");
Config::set("DB_PWD", "");
Config::set("DB_BASE", "projectflow");

Config::set("SITE_URL", Request::protocol()."://".Request::hostname());
Config::set("APP_PATH", dirname(str_replace('\\', '/', getcwd())));
Config::set("APP_PATH_PUBLIC", APP_PATH.'/public');

DataValidator::addRuleset('email', [['exec'=>function($data){ return filter_var($data, FILTER_VALIDATE_EMAIL); }, 'msg'=>"L'adresse email n'est pas valide."],
									['length'=>'|256', 'msg'=>"L'adresse email est trop longue."]]);

DataValidator::addRuleset('nickname', [['empty'=>false, 'msg'=>"Le champ est obligatoire."],
									   ['regex'=>'/^([A-Za-z0-9]){1}([A-Za-z0-9_\- ]{1,30})([A-Za-z0-9]{1})$/',
									    'msg'=>"Le nom d'utilisateur ne peut contenir que des caractères alphanumériques, des underscores, des tirets et des espaces.
										        Il doit commencer et finir par une lettre ou un nombre"],
									   ['length'=>'3|32', 'msg'=>"Le nom d'utilisateur doit être compris entre 3 et 32 caractères."]]);
