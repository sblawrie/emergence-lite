<?php
abstract class RequestHandler
{
	
	public static $responseMode = 'html';
	public static $injectableData = array();
	public static $routes;
	
	// abstract methods
	//abstract public static function handleRequest();
	
	// static properties
	protected static $_path;
	protected static $_parameters;
	protected static $_options = array();
	

	// protected static methods

	protected static function setPath($path = null)
	{
		if(!Site::$pathStack)
		{
			$requestURI = parse_url($_SERVER['REQUEST_URI']);
			Site::$pathStack = Site::$requestPath = explode('/', ltrim($requestURI['path'], '/'));	
		}
	
		static::$_path = isset($path) ? $path : Site::$pathStack;
	}
	
	protected static function setOptions($options)
	{
		static::$_options = isset(self::$_options) ? array_merge(static::$_options, $options) : $options;
	}
	
	
	protected static function peekPath()
	{
		if(!isset(static::$_path)) static::setPath();
		return count(static::$_path) ? static::$_path[0] : false;
	}

	protected static function shiftPath()
	{
		if(!isset(static::$_path)) static::setPath();
		return array_shift(static::$_path);
	}

	protected static function getPath()
	{
		if(!isset(static::$_path)) static::setPath();
		return static::$_path;
	}
	
	protected static function unshiftPath($string)
	{
		if(!isset(static::$_path)) static::setPath();
		return array_unshift(static::$_path, $string);
	}
	
	static public function respond($responseID, $responseData = array(), $responseMode = false)
	{
		if(!headers_sent())
		{
			header('X-Response-ID: '.$responseID);
			header('Content-Type: text/html; charset=utf-8');
		}
	
		switch($responseMode ? $responseMode : static::$responseMode)
		{
			case 'json':
				JSON::translateAndRespond($responseData);

			case 'text':
				header('Content-Type: text/plain');
			
			case 'html':
				$responseData['responseID'] = $responseID;
				
				$dwoo = new DwooWrapper();
				
				
				if(!empty(static::$injectableData))
				{
					$responseData = array_merge(static::$injectableData, $responseData);
				}
				
				$data = array(
					'responseID' => $responseID
					,'data' => 	$responseData
				);
				
								
				echo $dwoo->get($responseID,$data);
				
				exit;
				
			case 'return':
				return array(
					'responseID' => $responseID
					,'data' => $responseData
				);

			default:
				die('Invalid response mode');
		}
	}
	
	static public function autoRouteRequest($action)
	{
		if($action=='' || !$action)
		{
			static::callMethod('handleHomeRequest');
		}
		else if(ctype_digit($action))
		{
			static::callMethod('handleObjectRequest', $action);
		}
		else
		{
			//Check for dashes
			$exp = explode('-', $action);
			if(count($exp)>1)
			{
				foreach($exp as &$part)
				{
					$part = ucfirst($part);
				}
				$action = implode('', $exp);
			}
			
			$methodName = 'handle' . ucfirst($action) . 'Request';
		
			static::callMethod($methodName);
		}
	}
	
	static public function callMethod($methodName, $params = null)
	{
		if(method_exists(get_called_class(), $methodName))
		{
			if($methodName=='handleObjectRequest')
			{
				return static::$methodName($params);
			}
			return static::$methodName();
		}
		else
		{
			ErrorRequestHandler::handleRequest();
		}
	}
	
	public static function handleRequest()
	{ 
		$action = static::shiftPath();
		if($action && isset(static::$routes[$action]))
		{
			$Controller = ucfirst(static::$routes[$action]) . 'RequestHandler';
			return $Controller::handleRequest();
		}
		else
		{
			static::autoRouteRequest($action);
		}		

	}
}
