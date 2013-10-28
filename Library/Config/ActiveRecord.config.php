<?php
/*
ActiveRecord::$beforeSave = function(ActiveRecord &$Model)
{
	$Class = get_class($Model);
	
	if($Class::_fieldExists('CreatorID') && !$Model->CreatorID && (SiteRequestHandler::$Session || Admin\AdminRequestHandler::$User))
    {
    	if(Admin\AdminRequestHandler::$User['CDEWorldUID'])
    	{
        	$Model->CreatorID = Admin\AdminRequestHandler::$User['CDEWorldUID'];
        }
        else
        {
	        $Model->CreatorID = SiteRequestHandler::$Session->CreatorID;
        }
    }
    
    if($Class::_fieldExists('Created') && (!$Model->Created || ($Model->Created == 'CURRENT_TIMESTAMP')))
    {
        $Model->Created = time();
    }
};
*/
