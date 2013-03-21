<?php
class SiteRequestHandler extends RequestHandler
{
	public static function handleRequest()
	{

		/* 
		 * This is to make sure any page that loads
		 * through Apache's ErrorDocument returns 200
		 * instead of 404.
		 */ 
		header('HTTP/1.0 200 OK');
		header('X-Powered-By: PHP/' . phpversion() . ' Emergence Framework (http://emr.ge)');
	    
        if(in_array($_SERVER['REMOTE_ADDR'],Site::$doNotTrack))
        {
	    	\php_error\reportErrors(array(
	    		'error_reporting_on'		=>	E_ALL & ~E_NOTICE & ~E_STRICT
	    		,'catch_supressed_errors'	=>	false
	    		,'catch_ajax_errors'		=>	false
	    		,'background_text'			=>	'Your Site'
	    	));
        }
        else
        {
            error_reporting(0);
        }

		switch($action = $action?$action:static::shiftPath())
		{			
			default:
				if(file_exists(templates_directory.$action.'.tpl'))
				{
					return static::respond(templates_directory.$action.'.tpl');
				}
				return ErrorRequestHandler::handleRequest();
		}
	}
	
}
