<?php


class RemoteEmergenceMedia extends EmergenceMedia
{

  static public $remoteStore = 'XXX';
	
	static public $remoteUpload = 'XXX';

	static public $remoteKey = 'XXX';
	
	static public $_record_cache;
	
	static public $_timeout = 0; // See: CURLOPT_CONNECTTIMEOUT @ http://php.net/manual/en/function.curl-setopt.php

	function __get($name)
	{
		switch($name)
		{
			case 'FilesystemPath':

				if($this->ID == false)
				{
					return false;
				}

				return 'http://cdeworld.com/media/' . $this->ID;
				

			default:
				return parent::__get($name);
		}
	}

		
	static public function createFromFile($file, $fieldValues = array())
	{
		
		$url = static::$remoteUpload . '?Key=' . static::$remoteKey;
		
		if(count($fieldValues)>0)
		{
			$url .= '&' . http_build_query($fieldValues);
		}
		
		$image = fopen($file, "rb");

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_PUT, 1);
		curl_setopt($curl, CURLOPT_INFILE, $image);
		curl_setopt($curl, CURLOPT_INFILESIZE, filesize($file));
		
		$result = curl_exec($curl);
		
		curl_close($curl); 
	
		$result = json_decode($result);
		
		if(is_array($result))
		{
			return static::create($result);
		}
		else
		{
			throw new Exception('Data from remote store is not an array');
		}
		
		
		
	}
		
   static public function getRecordByField($field, $value, $cacheIndex = false)
    {	
    	$filter = array(array(
    		'property'	=>	$field
    		,'value'	=>	$value
    	));
    	
    	$URL = static::$remoteStore . '?Key=' . static::$remoteKey . '&filter=' . json_encode($filter);
    	
    	if($cacheIndex)
    	{
	    	$key = sprintf('%s', md5($URL));
	    	return static::oneRecordCached($key,$URL,array());
    	}
    	else
    	{
	    	return static::oneRecord($URL, array());
    	}
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
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::$_timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		
		if(is_integer(strpos($data,'Fatal error: Allowed memory size')))
		{
			throw new Exception('Remote store ran out of memory. Try imposing a limit.');
		}
		
		if(empty($data))
		{
			throw new Exception('No data has been returned from the remote store. '.$URL);
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
