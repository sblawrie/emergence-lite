<?php

Site::$config = array(
    'mysql'               => array(
        //'socket'    =>
        'host'      =>  'localhost'
        ,'database' =>  'database'
        ,'username' =>  'username'
        ,'password' =>  'password'
    )
    ,'recordKeys'	=>	array(
    	'VvFRxH4XvYdhaeaaL5LW5toYn2IgysPpZDZyUwW'
    )
);


Site::$doNotTrack = array(
	'127.0.0.1'
);

Site::$debug = ($_GET['debug']?true:false);
Site::$debug = true;
