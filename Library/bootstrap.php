<?php
if(php_sapi_name() == 'cli')
{
	$_SERVER['DOCUMENT_ROOT'] = $_SERVER['PWD'];
}
define('templates_directory',$_SERVER['DOCUMENT_ROOT'].'/Library/Views/');
define('classes_directory',$_SERVER['DOCUMENT_ROOT'].'/Library/Classes/');
define('controllers_directory',$_SERVER['DOCUMENT_ROOT'].'/Library/Controllers/');

ini_set('error_reporting',E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors',true);

session_start();

include(classes_directory . 'PrettyErrors.class.php');

function bootstrap_class_loader($class)
{
	/* Handle Namespaces */
	if(strpos($class, '\\') !== false)
	{
		// PSR-O compatibility
		$className = ltrim($class, '\\');
		$fileName  = '';
		$namespace = '';
		if($lastNsPos = strrpos($className, '\\'))
		{
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className);
	}
	else
	{
		$fileName = $class;
	}


	/* Controllers */
	if(stripos($fileName,'RequestHandler') !== false)
	{
	
		$file = controllers_directory.$fileName.'.class.php';
		if(is_readable($file))
		{
			include_once($file);
			return;
		}
		else
		{
			//echo $class; exit;
		}
	}

	/* All Classes */
	$file = classes_directory.$fileName.'.class.php';
	if(is_readable($file))
	{
		include_once($file);
	}
	else
	{
		//echo $file; exit;
	}
	
	// for ActiveRecord
	if(method_exists($class, '__classLoaded'))
	{
		call_user_func(array($class, '__classLoaded'));
	}
}

spl_autoload_register('bootstrap_class_loader');
include(classes_directory.'Dwoo/dwooAutoload.php');
include('Config.php');
