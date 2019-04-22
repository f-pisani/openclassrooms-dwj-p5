<?php
namespace App;

/***********************************************************************************************************************
 * Class Session
 *     public static function all()
 *     public static function assoc()
 *     public static function set($key, $data)
 *     public static function get($key)
 *     public static function exists($key)
 *     public static function unset($key)
 *     public static function clear()
 *     public static function start()
 *     public static function status()
 *     public static function id()
 *     public static function reset()
 *     public static function abort()
 *     public static function destroy()
 *
 * Helper for handling $_SESSION
 */
class Session
{
	/*******************************************************************************************************************
	 * public static function all()
	 *
	 * Returns $_SESSION
	 */
	public static function all()
	{
		return $_SESSION;
	}

	/*******************************************************************************************************************
	 * public static function set($key, $data)
	 *     $key : Store $data into $_SESSION[$key], $key can also be a dotted path for multidimensional arrays
	 *			  'key' will store $data into $_SESSION['key'] // 'subarray.key' will store $data into $_SESSION['subarray']['key']
	 *     $data : Data
	 */
	public static function set($key, $data)
	{
		$targetArray = &$_SESSION;

		$keys = explode('.', $key);
		foreach ($keys as $key) $targetArray = &$targetArray[$key];
		$targetArray = $data;
	}

	/*******************************************************************************************************************
	 * public static function get($key)
	 *     $key : Retrieves datas from $_SESSION[$key], $key can also be a dotted path for multidimensional arrays
	 *			  'key' will retrieves datas from $_SESSION['key'] // 'subarray.key' will retrieves datas from $_SESSION['subarray']['key']
	 *
	 * Returns $key value ; null if not found.
	 */
	public static function get($key)
	{
		$targetArray = $_SESSION;

		$keys = explode('.', $key);
		foreach ($keys as $key) $targetArray = &$targetArray[$key];

		return $targetArray ?? null;
	}

	/*******************************************************************************************************************
	 * public static function exists($key)
	 *     $key : Check if $_SESSION[$key] exists, $key can also be a dotted path for multidimensional arrays
	 *			  'key' will check if $_SESSION['key'] exists // 'subarray.key' will check if $_SESSION['subarray']['key'] exists
	 *
	 * Returns true if $key exists ; False otherwise.
	 */
	public static function exists($key)
	{
		$targetArray = $_SESSION;

		$keys = explode('.', $key);
		$last_array_value = $keys[count($keys)-1];
		foreach ($keys as $key)
		{
			if ($key == $last_array_value) break;
			if (array_key_exists($key, $targetArray)) $targetArray = &$targetArray[$key];
			else return false;
		}

		return array_key_exists($last_array_value, $targetArray);
	}

	/*******************************************************************************************************************
	 * public static function unset($key)
	 *     $key : Unset $_SESSION[$key], $key can also be a dotted path for multidimensional arrays
	 *			  'key' will unset $_SESSION['key'] // 'subarray.key' will unset $_SESSION['subarray']['key']
	 */
	public static function unset($key)
	{
		$targetArray = &$_SESSION;

		$keys = explode('.', $key);
		$last_array_value = $keys[count($keys)-1];
		foreach ($keys as $key)
		{
			if ($key == $last_array_value) break;
			if (array_key_exists($key, $targetArray)) $targetArray = &$targetArray[$key];
			else return false;
		}

		if (array_key_exists($last_array_value, $targetArray)) unset($targetArray[$last_array_value]);
	}

	public static function clear() { $_SESSION = []; }
	public static function start() { return session_start(); }
	public static function status() { return session_status(); }
	public static function id() { return session_id(); }
	public static function reset() { return session_reset(); }
	public static function abort() { return session_abort(); }
	public static function destroy() { return session_destroy(); }
}
