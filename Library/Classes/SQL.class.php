<?php

class SQL
{

	static public function getCreateTable($recordClass, $historyVariant = false)
	{
		$queryFields = array();
		$indexes = $historyVariant ? array() : $recordClass::$indexes;
		$fulltextColumns = array();
		
		// history table revisionID field
		if($historyVariant)
		{
			$queryFields[] = '`RevisionID` int(10) unsigned NOT NULL auto_increment';
			$queryFields[] = 'PRIMARY KEY (`RevisionID`)';
		}
		
		// compile fields
		$rootClass = !empty($recordClass::$rootClass) ? $recordClass::$rootClass : $recordClass;
		foreach($recordClass::getClassFields() AS $fieldId => $field)
		{
			//Debug::dump($field, "Field: $field[columnName]");
			if($field['columnName'] == 'RevisionID')
			{
				continue;
			}
			
			// force notnull=false on non-rootclass fields
			if($rootClass && !$rootClass::_fieldExists($fieldId))
			{
				$field['notnull'] = false;
			}
			
			// auto-prepend class type
			if($field['columnName'] == 'Class' && $field['type'] == 'enum' && !in_array($rootClass, $field['values']) && empty($rootClass::$subClasses))
			{
				array_unshift($field['values'], $rootClass);
			}
			
			$fieldDef = '`'.$field['columnName'].'`';
			$fieldDef .= ' '.static::getSQLType($field);
			$fieldDef .= ' '. ($field['notnull'] ? 'NOT NULL' : 'NULL');
			
			if($field['autoincrement'] && !$historyVariant)
				$fieldDef .= ' auto_increment';
			elseif( ($field['type'] == 'timestamp') && ($field['default'] == 'CURRENT_TIMESTAMP') )
				$fieldDef .= ' default CURRENT_TIMESTAMP';
			elseif(empty($field['notnull']) && ($field['default'] == null))
				$fieldDef .= ' default NULL';
			elseif(isset($field['default']))
			{
				if($field['type'] == 'boolean')
				{
					$fieldDef .= ' default ' . ($field['default'] ? 1 : 0);
				}
				else
				{
					$fieldDef .= ' default "'.DB::escape($field['default']).'"';
				}
			}				
			$queryFields[] = $fieldDef;
			
			if($field['primary'])
			{
				if($historyVariant)
					$queryFields[] = 'KEY `'.$field['columnName'].'` (`'.$field['columnName'].'`)';
				else
					$queryFields[] = 'PRIMARY KEY (`'.$field['columnName'].'`)';
			}
			
			if($field['unique'] && !$historyVariant)
			{
				$queryFields[] = 'UNIQUE KEY `'.$field['columnName'].'` (`'.$field['columnName'].'`)';
			}
			
			if($field['index'] && !$historyVariant)
			{
				$queryFields[] = 'KEY `'.$field['columnName'].'` (`'.$field['columnName'].'`)';
			}
			
			if($field['fulltext'] && !$historyVariant)
			{
				$fulltextColumns[] = $field['columnName'];
			}
		}
		
		// context index
		if(!$historyVariant && $recordClass::_fieldExists('ContextClass') && $recordClass::_fieldExists('ContextID'))
		{
			$queryFields[] = 'KEY `CONTEXT` (`'.$recordClass::getColumnName('ContextClass').'`,`'.$recordClass::getColumnName('ContextID').'`)';
		}
		
		// compile indexes
		foreach($indexes AS $indexName => $index)
		{
			if(is_array($index['fields']))
			{
				$indexFields = $index['fields'];
			}
			elseif($index['fields'])
			{
				$indexFields = array($index['fields']);
			}
			else
			{
				continue;
			}
		
			// translate field names
			foreach($index['fields'] AS &$indexField)
			{
				$indexField = $recordClass::getColumnName($indexField);
			}

			if(!empty($index['fulltext']))
			{
				$fulltextColumns = array_unique(array_merge($fulltextColumns, $index['fields']));
				continue;
			}
		
			$queryFields[] = sprintf(
				'%s KEY `%s` (`%s`)'
				, !empty($index['unique']) ? 'UNIQUE' : ''
				, $indexName
				, join('`,`', $index['fields'])
			);
		}

		if(!empty($fulltextColumns))
		{
			$queryFields[] = 'FULLTEXT KEY `FULLTEXT` (`'.join('`,`', $fulltextColumns).'`)';
		}
		

		$createSQL = sprintf(
			"--\n-- %s for class %s\n--\n"
			."CREATE TABLE IF NOT EXISTS `%s` (\n\t%s\n) ENGINE=MyISAM DEFAULT CHARSET=%s;"
			, $historyVariant ? 'History table' : 'Table'
			, $recordClass
			, $historyVariant ? $recordClass::$historyTable : $recordClass::$tableName
			, join("\n\t,", $queryFields)
			, DB::$charset
		);
		
		// append history table SQL
		if(!$historyVariant && is_subclass_of($recordClass, 'VersionedRecord'))
		{
			$createSQL .= PHP_EOL.PHP_EOL.PHP_EOL . static::getCreateTable($recordClass, true);
		}
		
		return $createSQL;
	}
	
	static public function getCreateClass($tableName, $className)
	{
		$db_name = DB::oneValue('SELECT DATABASE()');
		$sql = "SELECT `COLUMN_NAME`, `DATA_TYPE`, `COLUMN_KEY`, `EXTRA`
				FROM `INFORMATION_SCHEMA`.`COLUMNS` 
				WHERE `TABLE_SCHEMA`='$db_name' 
				AND `TABLE_NAME`='$tableName';";
		$Columns = DB::allRecords($sql);
		$str = '<?php' . "\n\n //Create Class From Table \n \n" . 'class ' . $className . ' extends ActiveRecord
{

'. "\t" . '// ActiveRecord configuration
'. "\t" . 'static public $tableName = \'' . $tableName . '\';
'. "\t" . 'static public $singularNoun = \'' . $className . '\';
'. "\t" . 'static public $pluralNoun = \'' . $tableName . '\';

'. "\t" . 'static public $rootClass = __CLASS__;
'. "\t" . 'static public $defaultClass = __CLASS__;
'. "\t" . 'static public $subClasses = array(__CLASS__);';
		$fields = array();
		foreach($Columns as $Column)
		{
			$field_array = array();
			if($Column['COLUMN_KEY']=='PRI' && $Column['COLUMN_NAME']!='ID')
			{
				$str .= "\n \n\t" .  'static public $primaryKey' . " = '{$Column['COLUMN_NAME']}';";
			}
			
			$Map = array('varchar'=>'string', 'mediumtext'=>'string', 'text'=>'string', 'largetext'=>'text', 'int'=>'integer', 'date'=>'timestamp', 'datetime'=>'timestamp', 'float'=>'decimal', 'tinyint'=>'integer');
			$type = (isset($Map[$Column['DATA_TYPE']]))?$Map[$Column['DATA_TYPE']]:$Column['DATA_TYPE'];
			if($type=='string')
			{
				$fields[] = "'{$Column['COLUMN_NAME']}'";
			}
			else
			{

		        $field_array[] = "'type' => '$type'";
		  
		        if($Column['EXTRA']=='auto_increment')
		        {
			        $field_array[] = "'autoincrement' => true";
		        }
		        
		        $fields[] = "'{$Column['COLUMN_NAME']}' => array(\n\t\t\t" . implode("\n\t\t\t,", $field_array) . "\n\t\t)";
			}
			
		}
		
		$str .= "\n\n\t" . 'static public $fields = array(' . "\n\t\t" . implode("\n\t\t,", $fields) . "\n\t" . ");\n}";
		echo $str;
		
	}

	
	static public function getSQLType($field)
	{
		switch($field['type'])
		{
			case 'boolean':
				return 'boolean';
			case 'tinyint':
				return 'tinyint' . ($field['unsigned'] ? ' unsigned' : '') . ($field['zerofill'] ? ' zerofill' : '');
			case 'uint':
				$field['unsigned'] = true;
			case 'int':
			case 'integer':
				return 'int' . ($field['unsigned'] ? ' unsigned' : '') . ($field['zerofill'] ? ' zerofill' : '');;
			case 'decimal':
				return sprintf('decimal(%s)', $field['length']) . ($field['unsigned'] ? ' unsigned' : '') . ($field['zerofill'] ? ' zerofill' : '');;
			case 'float':
				return 'float';
			case 'double':
				return 'double';
				
			case 'password':
			case 'string':
			case 'list':
				return $field['length'] ? sprintf('char(%u)', $field['length']) : 'varchar(255)';
			case 'clob':
			case 'serialized':
				return 'text';
			case 'blob':
				return 'blob';
				
			case 'timestamp':
				return 'timestamp';
			case 'date':
				return 'date';
			case 'year':
				return 'year';
				
			case 'enum':
				return sprintf('enum("%s")', join('","', $field['values']));
				
			case 'set':
				return sprintf('set("%s")', join('","', $field['values']));
				
			default:
				die("getSQLType: unhandled type $field[type]");
		}
	}


}
