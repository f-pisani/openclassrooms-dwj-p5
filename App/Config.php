<?php
namespace App;

/***********************************************************************************************************************
 * Class Config
 */
class Config
{
	public static function set($name, $value)
	{
		if(!defined($name)) define($name, $value);
	}

	public static function get($name, $default_value=null)
	{
		if(defined($name)) return constant($name);

		return $default_value;
	}
}
