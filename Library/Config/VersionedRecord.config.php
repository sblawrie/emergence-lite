<?php
/*
VersionedRecord::$beforeSave = function(ActiveRecord &$Model)
{
	$Class = get_class($Model);
	
	if($Class::_fieldExists('CreatorID') && (SiteRequestHandler::$Session || Admin\AdminRequestHandler::$User))
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
	
	if($Class::_fieldExists('Created'))
    {
        $Model->Created = time();
    }
};
*/
