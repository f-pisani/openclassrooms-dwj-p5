<?php
namespace App;

/***********************************************************************************************************************
 * Class Request
 *     public static function all()
 *     public static function assoc()
 *     public static function set($key, $data)
 *     public static function get($key)
 *     public static function exists($key)
 *     public static function unset($key)
 *     public static function route()
 *     public static function uri()
 *     public static function protocol()
 *     public static function secure()
 *     public static function method()
 *     public static function code()
 *     public static function query()
 *     public static function time()
 *     public static function floattime()
 *     public static function script()
 *     public static function hostname()
 *     public static function port()
 *     public static function header()
 *     public static function ip()
 *     public static function accepts($mimes=null)
 *     public static function acceptsCharsets($charsets=null)
 *     public static function acceptsEncodings($encodings=null)
 *     public static function acceptsLanguages($languages=null)
 *     private static function checkAccepts($http_accept_type, $toCheck=null)
 *
 * Helper for handling $_REQUEST, $_POST, $_GET and $_FILES vars and various request datas
 */
class Request
{
	/*******************************************************************************************************************
	 * public static function all()
	 *
	 * Return an array_merge of $_REQUEST, $_POST, $_GET and $_FILES
	 * KEYS PRESENTS IN MULTIPLE ARRAYS ARE ERASED FROM THIS ARRAY ; Use assoc() instead or specific path using get()
	 */
	public static function all()
	{
		return array_merge($_REQUEST, $_POST, $_GET, $_FILES);
	}

	/*******************************************************************************************************************
	 * public static function assoc()
	 *
	 * Returns an associative array with $_REQUEST, $_POST, $_GET and $_FILES
	 */
	public static function assoc()
	{
		return ['request' => $_REQUEST, 'post' => $_POST, 'get' => $_GET, 'files' => $_FILES];
	}

	/*******************************************************************************************************************
	 * public static function set($key, $data)
	 *     $key : By default store $data into $_REQUEST[$key], $key can also be a dotted path for multidimensional arrays
	 *			  request__key will store $data into $_REQUEST['key'] // 'request__subarray.key' will store $data into $_REQUEST['subarray']['key']
	 *			  post__key will store $data into $_POST['key'] // 'post__subarray.key' will store $data into $_POST['subarray']['key']
	 *			  get__key will store $data into $_GET['key'] // 'get__subarray.key' will store $data into $_GET['subarray']['key']
	 *			  files__key will store $data into $_FILES['key'] // 'files__subarray.key' will store $data into $_FILES['subarray']['key']
	 *     $data : Data
	 */
	public static function set($key, $data)
	{
		$targetArray = &$_REQUEST;
		$matches = [];
		if (preg_match("/^(request|post|get|files)__(.+)$/i", $key, $matches))
		{
			$key = $matches[2];
			switch (strtoupper($matches[1]))
			{
				case "REQUEST": $targetArray = &$_REQUEST; break;
				case "POST": $targetArray = &$_POST; break;
				case "GET": $targetArray = &$_GET; break;
				case "FILES": $targetArray = &$_FILES; break;
			}
		}

		$keys = explode('.', $key);
		foreach ($keys as $key) $targetArray = &$targetArray[$key];
		$targetArray = $data;
	}

	/*******************************************************************************************************************
	 * public static function get($key)
	 *     $key : Retrieves datas from Request::all()[$key], $key can also be a dotted path for multidimensional arrays
	 *			  'request__key' will retrieves datas from $_REQUEST['key'] // 'request__subarray.key' will retrieves datas from $_REQUEST['subarray']['key']
	 *			  'post__key' will retrieves datas from $_POST['key'] // 'post__subarray.key' will retrieves datas from $_POST['subarray']['key']
	 *			  'get__key' will retrieves datas from $_GET['key'] // 'get__subarray.key' will retrieves datas from $_GET['subarray']['key']
	 *			  'files__key' will retrieves datas from $_FILES['key'] // 'files__subarray.key' will retrieves datas from $_FILES['subarray']['key']
	 *
	 * Returns value for $key ; null if $key is not found
	 */
	public static function get($key)
	{
		$targetArray = self::all();
		$matches = [];
		if (preg_match("/^(request|post|get|files)__(.+)$/i", $key, $matches))
		{
			$key = $matches[2];
			switch (strtoupper($matches[1]))
			{
				case "REQUEST": $targetArray = $_REQUEST; break;
				case "POST": $targetArray = $_POST; break;
				case "GET": $targetArray = $_GET; break;
				case "FILES": $targetArray = $_FILES; break;
			}
		}

		$keys = explode('.', $key);
		foreach ($keys as $key) $targetArray = &$targetArray[$key];

		return $targetArray ?? null;
	}

	/*******************************************************************************************************************
	 * public static function exists($key)
	 *     $key : Check if $key exists into Request::all(), $key can also be a dotted path for multidimensional arrays
	 *			  'request__key' will check if $_REQUEST['key'] exists // 'request__subarray.key' will check if $_REQUEST['subarray']['key'] exists
	 *			  'post__key' will check if $_POST['key'] exists // 'post__subarray.key' will check if $_POST['subarray']['key'] exists
	 *			  'get__key' will check if $_GET['key'] exists // 'get__subarray.key' will check if $_GET['subarray']['key'] exists
	 *			  'files__key' will check if $_FILES['key'] exists // 'files__subarray.key' will check if $_FILES['subarray']['key'] exists
	 *
	 * Returns true if $key exists ; False otherwise.
	 */
	public static function exists($key)
	{
		$targetArray = self::all();
		$matches = [];
		if (preg_match("/^(request|post|get|files)__(.+)$/i", $key, $matches))
		{
			$key = $matches[2];
			switch (strtoupper($matches[1]))
			{
				case "REQUEST": $targetArray = $_REQUEST; break;
				case "POST": $targetArray = $_POST; break;
				case "GET": $targetArray = $_GET; break;
				case "FILES": $targetArray = $_FILES; break;
			}
		}

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
	 *     $key : Unset $_REQUEST[$key], $key can also be a dotted path for multidimensional arrays
	 *			  'request__key' will unset $_REQUEST['key'] // 'request__subarray.key' will unset $_REQUEST['subarray']['key']
	 *			  'post__key' will unset $_POST['key'] // 'post__subarray.key' will unset $_POST['subarray']['key']
	 *			  'get__key' will unset $_GET['key'] // 'get__subarray.key' will unset $_GET['subarray']['key']
	 *			  'files__key' will unset $_FILES['key'] // 'files__subarray.key' will unset $_FILES['subarray']['key']
	 */
	public static function unset($key)
	{
		$targetArray = &$_REQUEST;
		$matches = [];
		if (preg_match("/^(request|post|get|files)__(.+)$/i", $key, $matches))
		{
			$key = $matches[2];
			switch (strtoupper($matches[1]))
			{
				case "REQUEST": $targetArray = &$_REQUEST; break;
				case "POST": $targetArray = &$_POST; break;
				case "GET": $targetArray = &$_GET; break;
				case "FILES": $targetArray = &$_FILES; break;
			}
		}

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

	public static function route() { return Router::name(); }
	public static function uri() { return current(explode('?', $_SERVER['REQUEST_URI'])); }
	public static function protocol() { return $_SERVER['REQUEST_SCHEME']; }
	public static function secure() { return (self::protocol() == 'https')?true:false; }
	public static function method() { return $_SERVER['REQUEST_METHOD']; }
	public static function code() { return http_response_code(); }
	public static function query() { return $_SERVER['QUERY_STRING']; }
	public static function time() { return $_SERVER['REQUEST_TIME']; }
	public static function floattime() { return $_SERVER['REQUEST_TIME_FLOAT']; }
	public static function script() { return $_SERVER['SCRIPT_FILENAME']; }
	public static function hostname() { return $_SERVER['HTTP_HOST']; }
	public static function port() { return $_SERVER['SERVER_PORT']; }
	public static function header() { return apache_request_headers(); }
	public static function ip() { return $_SERVER['REMOTE_ADDR']; }

	public static function accepts($mimes=null) { return self::checkAccepts('HTTP_ACCEPT', $mimes); }
	public static function acceptsCharsets($charsets=null) { return self::checkAccepts('HTTP_ACCEPT_CHARSET', $charsets); }
	public static function acceptsEncodings($encodings=null) { return self::checkAccepts('HTTP_ACCEPT_ENCODING', $encodings); }
	public static function acceptsLanguages($languages=null) { return self::checkAccepts('HTTP_ACCEPT_LANGUAGE', $languages); }

	/*******************************************************************************************************************
	 * private static function checkAccepts($http_accept_type, $toCheck=null)
	 *     $http_accept_type : HTTP_ACCEPT type
	 *     $toCheck : Types to check (could be a string, a comma separated string or and array)
	 *
	 * WARNING : DO NOT SUPPORT joker types
	 * Returns the best match or full list of supported types if $toCheck is null
	 */
	private static function checkAccepts($http_accept_type, $toCheck=null)
	{
		$accepts = [];

	    $http_accept_types = strtolower(str_replace(' ', '', (array_key_exists($http_accept_type, $_SERVER)) ? $_SERVER[$http_accept_type] : '*'));
	    $http_accept_types = explode(',', $http_accept_types);
	    foreach ($http_accept_types as $type)
		{
	        if (strpos($type, ';q=')) list($type, $q) = explode(';q=', $type);
			else $q = 1;

	        $accepts[$type] = $q;
	    }
	    arsort($accepts);

	    // if no parameter was passed, just return parsed data
	    if (!$toCheck) return $accepts;
		if (!is_array($toCheck)) $toCheck = explode(',', $toCheck);

	    $toCheck = array_map('strtolower', (array)$toCheck);

	    foreach ($accepts as $type => $q)
		{
			// q=0 -> unsupported mime
	       	if ($q && (in_array($type, $toCheck) || $type == '*')) return $type;
	    }

	    return false;
	}
}
