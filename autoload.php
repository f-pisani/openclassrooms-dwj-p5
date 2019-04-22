<?php
/***********************************************************************************************************************
 * spl_autoload_register is case sensitive : namespace and class name must match a valid path
 */
spl_autoload_register(function($class_path){
	$autoload_path = str_replace('\\', '/', getcwd().'/../'.$class_path.'.php');

	if(file_exists($autoload_path)) require_once($autoload_path);
});
