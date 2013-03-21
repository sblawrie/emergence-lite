<?php
class EmergenceModel extends ActiveRecord
{
	static public $fields = array(
        'ID' => array(
            'type' => 'integer'
            ,'autoincrement' => true
            ,'unsigned' => true
        )
        ,'Class' => array(
            'type' => 'enum'
            ,'notnull' => true
            ,'values' => array()
        )
        ,'Created' => array(
            'type' => 'timestamp'
            ,'default' => 'CURRENT_TIMESTAMP'
        )
        ,'CreatorID' => array(
            'type' => 'integer'
            ,'notnull' => false
        )
    );
    
    static public $relationships = array(
        'Creator' => array(
            'type' => 'one-one'
            ,'class' => 'AdminUser'
            ,'local' => 'CreatorID'
            ,'foreign' => 'user_id'
        )
    );
}