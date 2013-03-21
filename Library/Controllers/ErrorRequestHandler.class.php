<?php
class ErrorRequestHandler extends RequestHandler
{
	public static function handleRequest($action='404')
	{
		switch($action)
		{
			
			case '404':
				return static::handlePageNotFound();
			
		}
		
	}
	
	public static function handlePageNotFound()
	{
		header("HTTP/1.0 404 Not Found");
		
		// disable tracking
		Site::$tracking = false;
		
		static::respond(templates_directory.'404.tpl',$data);	
	}
}