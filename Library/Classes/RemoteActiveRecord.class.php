<?php
class RemoteActiveRecord extends ActiveRecord
{
	static public $remoteStore;

	static public $remoteKey;
	
    static public function getRecordByField($field, $value, $cacheIndex = false)
    {	
    	$filter = array(array(
    		'property'	=>	$field
    		,'value'	=>	$value
    	));
    	
    	$URL = static::$remoteStore . '?Key=' . static::$remoteKey . '&filter=' . json_encode($filter);
    	
    	$data = file_get_contents($URL);
    	
		$Data = json_decode($data,true);
    
		return $Data['data'][0];
    }
    
    static public function getRecordByWhere($conditions, $options = array())
    {
    	var_dump($conditions);
    	
    	var_dump($options);
    }
    
    static public function getAllRecordsByWhere($conditions = array(), $options = array())
    {
    	$QueryString = http_build_query($options);
    
		$URL = static::$remoteStore . '?Key=' . static::$remoteKey . '&' . $QueryString;
		
		$data = file_get_contents($URL);
		
		if(is_integer(strpos($data,'Fatal error: Allowed memory size')))
		{
			throw new Exception('Remote store ran out of memory. Try imposing a limit.');
		}
		
		$Data = json_decode($data,true);
    
		return $Data['data'];
    }
    
    public function save($deep = true)
    {
        // set creator
        if(static::_fieldExists('CreatorID') && !$this->CreatorID && $_SESSION['User'])
        {
            $this->CreatorID = $_SESSION['User']->ID;
        }
        
        // set created
        if(static::_fieldExists('Created') && (!$this->Created || ($this->Created == 'CURRENT_TIMESTAMP')))
        {
            $this->Created = time();
        }
        
        // validate
        if(!$this->validate($deep))
        {
            throw new RecordValidationException($this, 'Cannot save invalid record');
        }
        
        if($this->isDirty)
        {
            // prepare record values
            $recordValues = $this->_prepareRecordValues();
    
            // transform record to set array
            $set = static::_mapValuesToSet($recordValues);
            
            // create new or update existing
            if($this->_isPhantom)
            {
                //do create
                $URL = static::$remoteStore . '/create?Key=' . static::$remoteKey;
                
                $Context = stream_context_create(array(
                	'http'	=>	array(
                		'method'	=>	'POST'
                		,'content'	=>	http_build_query($recordValues)
                	)
                ));
                
                $data = file_get_contents($URL,null,$Context);
                
                $Data = json_decode($data,true);
                
                if($Data['success'])
                {
	                $this->_record[static::$primaryKey?static::$primaryKey:'ID'] = $Data['data'][0][static::$primaryKey?static::$primaryKey:'ID'];
	                $this->_isPhantom = false;
	                $this->_isNew = true;
                }
                else
                {
	                throw new Exception('Saving to remote storage failed.');
                }
            }
            elseif(count($set))
            {
                // do edit
                
                $PKValue = $this->getValue(static::$primaryKey?static::$primaryKey:'ID');
                
                $URL = static::$remoteStore . '/' . $PKValue . '/edit?Key=' . static::$remoteKey;
                
                $Context = stream_context_create(array(
                	'http'	=>	array(
                		'method'	=>	'POST'
                		,'content'	=>	http_build_query($recordValues)
                	)
                ));
                
                $data = file_get_contents($URL,null,$Context);
                
                $Data = json_decode($data,true);
                
                if($Data['success'])
                {
                	$this->_isUpdated = true;
                }
                else
                {
	                throw new Exception('Saving to remote storage failed.');
                }
            }
            
            // update state
            $this->_isDirty = false;
        }
    }
}