<?php
namespace App;

/***********************************************************************************************************************
 * Class RouterException
 *
 * Exceptions throws by Router.
 */
class RouterException extends \Exception {};

/***********************************************************************************************************************
 * Class Router
 *     public static function get($uri, $controller)
 *     public static function post($uri, $controller)
 *     public static function put($uri, $controller)
 *     public static function patch($uri, $controller)
 *     public static function delete($uri, $controller)
 *     public static function options($uri, $controller)
 *     public static function any($uri, $controller)
 *     public static function match($methods, $uri, $controller)
 *     public static function load($file)
 *     public static function redirect($src, $dest, $code=302)
 *     public static function permanentRedirect($src, $dest)
 *     public static function name($uri=null)
 *     public static function url($name, $datas=[])
 *     public static function dispatch()
 *	   private static function register($methods, $uri, $controller)
 *     private static function executeController($controller)
 *
 * Router implementations.
 */
class Router
{
	private static $routes = [];
	private static $redirects = [];
	private static $route_404 = null;
	
	private function __construct() {} // DO NOT INSTANCIATE

	/*******************************************************************************************************************
	 * public static function METHOD($uri, $controller)
	 *	   $uri : URI to handle (ex: '/about' ; '/users/edit/{id}')
	 *	   $controller : Controller to call on dispatch (ex: 'UserController@edit')
	 *
	 * Returns registered route.
	 */
	public static function get($uri, $controller) { return self::register(['get', 'head'], $uri, $controller); }
	public static function post($uri, $controller) { return self::register(['post'], $uri, $controller); }
	public static function put($uri, $controller) { return self::register(['put'], $uri, $controller); }
	public static function patch($uri, $controller) { return self::register(['patch'], $uri, $controller); }
	public static function delete($uri, $controller) { return self::register(['delete'], $uri, $controller); }
	public static function options($uri, $controller) { return self::register(['options'], $uri, $controller); }
	public static function any($uri, $controller) { return self::register(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], $uri, $controller); }

	/*******************************************************************************************************************
	 * public static function match($methods, $uri, $controller)
	 *     $methods : Array with allowed methods for this uri (get, post, ...)
	 *	   $uri : URI to handle (ex: '/about' ; '/users/edit/{id}')
	 *	   $controller : Controller to call on dispatch (ex: 'UserController@edit')
	 *
	 * Returns registered route.
	 */
	public static function match($methods, $uri, $controller) { return self::register($methods, $uri, $controller); }

	/*******************************************************************************************************************
	 * public static function load($file)
	 *     $file : File to load. Must be located in /Routes/ folder.
	 *
	 * Loads $file.
	 */
	public static function load($file) { require_once APP_PATH.'/Routes/'.$file.'.php'; }

	/*******************************************************************************************************************
	 * public static function error404($controller)
	 *     $controller : Controller to call on 404 error (ex: 'ErrorControler@notFound')
	 */
	public static function error404($controller) { self::$route_404 = new Route(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], '', $controller); }

	/*******************************************************************************************************************
	 * public static function redirect($src, $dest, $code=302)
	 *     $src : Source URI to redirect
	 *	   $dest : Destination URI
	 *	   $code : HTTP status code ; 302 by default
	 *
	 * Register a redirection from uri $src to uri $dest ; $src require $dest uri to have less or equal numbers of parameters
	 * all $dest parameters must be present in $src.
	 */
	public static function redirect($src, $dest, $code=302)
	{
		$src_parameters = [];
		$src_params_count = preg_match_all("/\{([a-zA-Z0-9_]+)\}/", $src, $src_parameters);
		if($src_params_count) $src_parameters = $src_parameters[1];

		$dest_parameters = [];
		$dest_params_count = preg_match_all("/\{([a-zA-Z0-9_]+)\}/", $dest, $dest_parameters);
		if($dest_params_count) $dest_parameters = $dest_parameters[1];

		sort($src_parameters);
		sort($dest_parameters);

		if ($src_params_count <= $dest_params_count && $dest_parameters == array_intersect($dest_parameters, $src_parameters)) // Can't redirect to URI with more params
			self::$redirects[$src] = ['uri' => $dest, 'parameters' => $src_parameters, 'code' => $code];
	}

	/*******************************************************************************************************************
	 * public static function permanentRedirect($src, $dest)
	 *
	 * Register a redirection using 301 HTTP status code.
	 */
    public static function permanentRedirect($src, $dest) { self::redirect($src, $dest, 301); }

	/*******************************************************************************************************************
	 * public static function name($uri=null)
	 *     $uri : uri to check ; if null, check current uri
	 *
	 * Returns route name or uri if route exists ; otherwise returns null.
	 */
	public static function name($uri=null)
	{
		if (!$uri) $uri = Request::uri();
		$method = Request::method();

		foreach (self::$routes as $Route)
		{
			if ($Route->check($uri) && in_array($method, $Route->methods())) return $Route->name();
		}

		return null;
	}

	/*******************************************************************************************************************
	 * public static function url($name, $datas=[], $saveQueryString=false)
	 *     $name : Route name
	 *	   $datas : Assoc array with parameters for the route ; Default empty array
	 *     $saveQueryString : Appends current query string to generated URL ; By default false
	 *
	 * Returns a generated URL based on a route $name using $datas for parameters. Returns null on invalid route $name or
	 * mising route parameters.
	 */
	public static function url($name, $datas=[], $saveQueryString=false)
	{
		foreach (self::$routes as $Route)
		{
			if ($Route->name() == $name)
			{
				$uri = $Route->uri();
				$query = Request::query() ? '?'.Request::query() : '';
				$params = array_keys($Route->parameters());

				foreach($params as $param)
				{
					if (!array_key_exists($param, $datas)) return null;

					$uri = str_replace('{'.$param.'}', $datas[$param], $uri);
				}

				if ($saveQueryString) return SITE_URL.$uri.$query;

				return SITE_URL.$uri;
			}
		}

		return null;
	}

	/*******************************************************************************************************************
	 * public static function dispatch()
	 *
	 * Router dispatch, will check for a valid route to handle the request.
	 */
	public static function dispatch()
	{
		$uri = Request::uri();
		$method = Request::method();

		foreach(self::$routes as $Route)
		{
			if($Route->check($uri) && in_array($method, $Route->methods()))
			{
				// Redirection for this URI
				if (array_key_exists($Route->uri(), self::$redirects))
				{
					$redirect = self::$redirects[$Route->uri()]['uri'];
					$query = Request::query() ? '?'.Request::query() : '';
					$src_params = $Route->parameters();

					foreach(self::$redirects[$Route->uri()]['parameters'] as $param) $redirect = str_replace('{'.$param.'}', $src_params[$param], $redirect);

					header('Location: '.SITE_URL.$redirect.$query, true, self::$redirects[$Route->uri()]['code']);
					die();
				}

				$_REQUEST = array_merge($_REQUEST, $Route->parameters());
				return self::executeController($Route->controllerName().'@'.$Route->controllerMethodName());
			}
		}

		if (self::$route_404 !== null)
		{
			http_response_code(404);
			return self::executeController(self::$route_404->controllerName().'@'.self::$route_404->controllerMethodName());
		}

		throw new RouterException("Router::dispatch(): No route registered for uri '$uri' with request method '$method'.");
	}


	/*******************************************************************************************************************
	 * private static function register($methods, $uri, $controller)
	 *     $methods : Array with http methods to accept (get, post, ...)
	 *	   $uri : URI to handle (ex: '/about' ; '/users/edit/{id}')
	 *	   $controller : Controller to call on dispatch (ex: 'UserController@edit' will instanciate a UserController and call the edit method)
	 *
	 * Returns the generated route.
	 */
	private static function register($methods, $uri, $controller)
	{
		if (!is_array($methods)) $methods = explode(',', $methods);

		if (!array_key_exists($uri, self::$routes))
			self::$routes[$uri] = new Route($methods, $uri, $controller);
		else
			throw new RouterException("Router::register(): Route already exists for '".$uri."'.");

		return self::$routes[$uri];
	}


	/*******************************************************************************************************************
	 * private static function executeController($controller)
	 *	   $controller : Controller to execute (ex: 'UserController@edit' will returns $UserController->edit())
	 *
	 * Returns the execution of $controller->method().
	 */
	private static function executeController($controller)
	{
		$controller_split = array();
		// Callback format is 'Controller@MethodToCall'
		if(preg_match_all('/^([A-Za-z0-9_\\\]+)@([A-Za-z0-9_]+)$/', $controller, $controller_split) === 1)
		{
			$className = $controller_split[1][0]; // Controller Class
			$classMethodName = $controller_split[2][0]; // Controller Method

			$controller = new $className();
			return $controller->$classMethodName();
		}

		throw new RouterException("Router::executeController(): callback '$controller' is not well formated or doesn't exists.");
	}
}
