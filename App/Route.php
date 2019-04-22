<?php
namespace App;

/***********************************************************************************************************************
 * Class RouteException
 *
 * Exceptions throws by Route.
 */
class RouteException extends \Exception {};

/***********************************************************************************************************************
 * Class Route
 *     public function __construct($methods, $uri, $controller)
 *     public function check($uri)
 *     public function uri()
 *     public function name()
 *     public function setName($name)
 *     public function methods()
 *     public function regex()
 *     public function parameters()
 *     public function controllerName()
 *     public function controllerMethodName()
 *     public function where($params_regex)
 *     private function generateRegex()
 *     private function registerController($controller)
 *
 * Route implementations.
 */
class Route
{
	private $uri;
	private $name;
	private $methods;

	private $regex;
	private $parameters;
	private $where;
	private $controller;
	private $controllerMethod;

	/*******************************************************************************************************************
	 * public function __construct($methods, $uri, $controller)
	 *     $methods : Array with allowed methods for this uri (get, post, ...)
	 *	   $uri : URI to handle (ex: '/about' ; '/users/edit/{id}')
	 *	   $controller : Controller to call on dispatch (ex: 'UserController@edit')
	 */
	public function __construct($methods, $uri, $controller)
	{
		if (!is_array($methods)) $methods = explode(',', $methods);
		$methods = array_map('strtoupper', $methods);

		$this->methods = $methods;
		$this->uri = $uri;
		$this->name = $uri;

		$this->where = array();
		$this->generateRegex();
		$this->registerController($controller);
	}

	/*******************************************************************************************************************
	 * public function check($uri)
	 *	   $uri : URI to handle (ex: '/about' ; '/users/edit/{id}')
	 *
	 * Returns true if $uri is valid for this route ; Otherwise returns false.
	 */
	public function check($uri)
	{
		$this->generateRegex();

		if(preg_match($this->regex, $uri))
		{
			$data = array();
			preg_match_all($this->regex, $uri, $data);

			$key_parameters = array_keys($this->parameters);
			for($i=1; $i<count($data); $i++)
				$this->parameters[$key_parameters[$i-1]] = $data[$i][0];

			return true;
		}

		return false;
	}

	public function uri(){ return $this->uri; }
	public function name(){ return $this->name; }
	public function setName($name){ $this->name = $name; }
	public function methods(){ return $this->methods; }
	public function regex(){ return $this->regex; }
	public function parameters(){ return $this->parameters; }
	public function controllerName(){ return $this->controller; }
	public function controllerMethodName(){ return $this->controllerMethod; }

	/*******************************************************************************************************************
	 * public function where($params_regex)
	 *	   $params_regex : Assoc array with parameter name as key and regex to validate as value
	 *
	 * Custom regex for specifics route parameters.
	 */
	public function where($params_regex)
	{
		if(is_array($params_regex))
		{
			foreach($params_regex as $param_name => $param_regex)
				$this->where[$param_name] = $param_regex;
		}
	}

	/*******************************************************************************************************************
	 * private function generateRegex()
	 *	   $params_regex : Assoc array with parameter name as key and regex to validate as value
	 *
	 * Custom regex for specifics route parameters.
	 */
	private function generateRegex()
	{
		$this->parameters = array();
		$this->regex = '/^' . str_replace('/', '\\/', $this->uri) . '$/';
		$uri_params = array();
		$uri_params_count = preg_match_all("/\{([a-zA-Z0-9_]+)\}/", $this->uri, $uri_params);

		// $uri contains at least 1 parameter ({var name})
		if($uri_params_count)
		{
			// Replace each parameter by a regex
			foreach($uri_params[1] as $var_name)
			{
				if(!array_key_exists($var_name, $this->parameters))
					$this->parameters[$var_name] = null;

				if(array_key_exists($var_name, $this->where))
					$this->regex = str_replace('{' . $var_name . '}', '('.$this->where[$var_name].')', $this->regex);
				else
					$this->regex = str_replace('{' . $var_name . '}', '([A-Za-z0-9_\-]+)', $this->regex);
			}
		}
	}

	/*******************************************************************************************************************
	 * private function registerController($controller)
	 *	   $controller : Controller to call on dispatch (ex: 'UserController@edit')
	 *
	 * Returns true if $controller is valid ; Otherwise false and throws RouteException.
	 */
	private function registerController($controller)
	{
		$controller_split = array();

		// Callback format is 'Controller@MethodToCall'
		if(preg_match_all('/^([A-Za-z0-9_]+)@([A-Za-z0-9_]+)$/', $controller, $controller_split) === 1)
		{
			$this->controller = "\\Controllers\\".$controller_split[1][0]; // Controller Class
			$this->controllerMethod = $controller_split[2][0]; // Controller Method

			// ReflectionClass will throw a ReflectionException if className doesn't exists
			try
			{
				$controllerReflection = new \ReflectionClass($this->controller);
			}
			catch(\ReflectionException $e)
			{
				throw new RouteException("Route::registerController(): class '".$this->controller."' does not exists.");

				return false;
			}

			if($controllerReflection->isSubclassOf('App\Controller') && $controllerReflection->hasMethod($this->controllerMethod))
				return true;
			else
				throw new RouteException("Route::registerController(): class '".$this->controller."' does not
				                             implements method '".$this->controllerMethod."' or does not inherit
											 from 'App\Controller'.");
		}

		return false;
	}
}
