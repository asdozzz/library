<?php

namespace Asdozzz\Library;

class TableConfig
{
	static function get($db_driver, $db_connection,$table)
	{
		if (empty($db_driver))
		{
			throw new \App\Exceptions\PageError(__CLASS__.":драйвер db_driver не указан");
		}

		$result = self::$db_driver($db_connection,$table);
		
		return $result;
	}

    static function mysql($conn = false, $table)
    {
        $st = $conn->query('SHOW FULL COLUMNS FROM '.$table);
        $result = $st->fetchAll(\PDO::FETCH_OBJ);

        $config = [];
        $config = ['columns' => []];

        if (empty($result)) return $config;

        foreach ($result as $key => $col) 
        {
            $field = [];
            $field['data'] = $field['name'] = $col->Field;

            if (!empty($col->Comment))
            {
                $field['name'] = $col->Comment;
            }

            switch ($col->Default) 
            {
                case 'CURRENT_TIMESTAMP':
                    $field['default_value'] = array(
                        'type' => 'function',
                        'value' => 'current_date'
                    );
                break;
                
                default:
                    $field['default_value'] = array(
                        'type' => 'scalar',
                        'value' => $col->Default
                    );
                break;
            }

            $field['required'] = $col->Null == 'NO';
            if (!empty($col->Key) && $col->Key == 'PRI')
            {
                $config['primary_key'] = $field['data'];
            }

            $type = $col->Type;

            $field['type'] = NULL;

            //TODO@ обязательно переписать на классы

            //Числовые типы
            $numeric_types = [
                'tinyint' => ['type' => 'integer'],
                'smallint' => ['type' => 'integer'],
                'mediumint' => ['type' => 'integer'],
                'bigint' => ['type' => 'integer'],
                'int' => ['type' => 'integer'],
                'bit' => ['type' => 'integer'],

                'float' => ['type' => 'float'],
                'double' => ['type' => 'float'],
                'decimal' => ['type' => 'float'],
            ];

            if (empty($field['type']))
            {
                foreach ($numeric_types as $key => $value) 
                {
                    if (preg_match('/'.$key.'/isu', $type))
                    {
                        $field['type'] = $value['type'];
                        $pattern = '/'.$key.'\(([0-9]+)\).*/isu';
                        $length = preg_replace( $pattern, "$1", $type);
                        if (!empty($length)) $field['max_length'] = $length;
                        break;
                    }
                }
            }

            //Строковые типы
            $string_types = [
                'varchar' => ['type' => 'string'],
                'char' => ['type' => 'string'],
                'tinytext' => ['type' => 'text'],
                'mediumtext' => ['type' => 'text'],
                'longtext' => ['type' => 'text'],
                'text' => ['type' => 'text'],

            ];
            
            if (empty($field['type']))
            {
                foreach ($string_types as $key => $value) 
                {
                    if (preg_match('/'.$key.'/isu', $type))
                    {
                        $field['type'] = $value['type'];
                        $pattern = '/'.$key.'\(([0-9]+)\).*/isu';
                        $length = preg_replace( $pattern, "$1", $type);
                        if (!empty($length)) $field['max_length'] = $length;
                        break;
                    }
                }
            }

            $datetime_types = [
                'datetime' => ['type' => 'datetime','format' => 'Y-m-d H:i:s'],
                'timestamp' => ['type' => 'datetime','format' => 'Y-m-d H:i:s'],
                'date' => ['type' => 'date','format' => 'Y-m-d'],
                'time' => ['type' => 'time','format' => 'H:i:s'],
                'year' => ['type' => 'year','format' => 'Y'],
            ];
            
            if (empty($field['type']))
            {
                foreach ($datetime_types as $key => $value) 
                {
                    if (preg_match('/'.$key.'/isu', $type))
                    {
                        $field['type'] = $value['type'];
                        break;
                    }
                }
            }

            if ($field['type'] == NULL)
            {
                $field['type'] = 'string';
                dd('Не удалось определить тип поля');
            }
            
            $config['columns'][$field['data']] = $field; 
        }

        $fk_query = "SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY' 
                    AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME = '".$table."';";

        $fk = "SELECT k.REFERENCED_TABLE_NAME as ref_table, k.REFERENCED_COLUMN_NAME as ref_col,k.COLUMN_NAME as col 
                    FROM information_schema.TABLE_CONSTRAINTS i 
                    LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
                    WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' 
                    AND i.TABLE_SCHEMA = DATABASE()
                    AND k.TABLE_NAME  = '".$table."';";

        $st2 = $conn->query($fk);
        $result2 = $st2->fetchAll(\PDO::FETCH_OBJ);
        
        if (!empty($result2))
        {
            foreach ($result2 as $key => $value) 
            {
                $config['columns'][$value->col]['type'] = 'select';
                $config['columns'][$value->col]['source'] = ['ref_table' => $value->ref_table,'ref_col' => $value->ref_col]; 
            }
        }
       
        return $config;
    }

	static function pgsql($conn = false, $table)
	{
		$st = $conn->query("
            SELECT 
            cols.*, (
                SELECT
                    pg_catalog.col_description(c.oid, cols.ordinal_position::int)
                FROM
                    pg_catalog.pg_class c
                WHERE
                    c.oid     = (SELECT '$table'::regclass::oid) AND
                    c.relname = cols.table_name
            ) as label
            FROM information_schema.columns cols WHERE cols.table_name = '".$table."';");
		$result = $st->fetchAll(\PDO::FETCH_OBJ);
       
		$config[$table] = ['name' => $table];

		if (empty($result)) return $config;

    	foreach ($result as $key => $col) 
    	{
    		$field = [];
    		$field['data'] = $field['name'] = $col->column_name;

            if (!empty($col->label))
            {
                $field['name'] = $col->label;
            }

            $default_value = $col->column_default;

            if (preg_match('/nextval/i', $default_value))
            {
                $default_value = NULL;
            }

            if (preg_match('/NULL/i', $default_value))
            {
                $default_value = NULL;
            }

            $field['default_value'] = $default_value;
            $field['required'] = $col->is_nullable == 'NO';

    		$type = $col->data_type;

            $field['type'] = NULL;

    		//TODO@ обязательно переписать на классы

    		//Числовые типы
    		$numeric_types = [
    			'smallint' => ['type' => 'integer'],
    			'integer' => ['type' => 'integer'],
    			'bigint' => ['type' => 'integer'],
    			'decimal' => ['type' => 'float'],
    			'numeric' => ['type' => 'float'],
    			'real' => ['type' => 'float'],
    			'double precision' => ['type' => 'float'],
    			'serial' => ['type' => 'integer'],
    			'bigserial' => ['type' => 'integer'],
    		];

    		if (!empty($numeric_types[$type]))
    		{
    			$field['type'] = $numeric_types[$type]['type'];
                $field['max_length'] = $col->character_maximum_length;
    		}

    		//Числовые типы
    		$string_types = [
    			'character varying' => ['type' => 'string'],
    			'varchar' => ['type' => 'string'],
    			'character' => ['type' => 'string'],
    			'char' => ['type' => 'string'],
    			'text' => ['type' => 'text'],
    		];
    		
    		if (!empty($string_types[$type]))
    		{
    			$field['type'] = $string_types[$type]['type'];
                $field['max_length'] = $col->character_maximum_length;
    		}

    		$datetime_types = [
    			'timestamp without time zone' => ['type' => 'datetime','format' => 'Y-m-d H:i:s'],
    			'timestamp with time zone' => ['type' => 'datetime','format' => 'Y-m-d H:i:s'],
    			'date' => ['type' => 'date','format' => 'Y-m-d'],
    			'time without time zone' => ['type' => 'time','format' => 'H:i:s'],
    			'time with time zone' => ['type' => 'time','format' => 'H:i:s'],
    			'interval' => ['type' => NULL,'format' => NULL]
    		];
    		
    		if (!empty($datetime_types[$type]))
    		{
    			$field['type'] = $datetime_types[$type]['type'];
                $field['format'] = $datetime_types[$type]['format'];
    		}

    		if ($type == 'boolean')
    		{
    			$field['type'] = 'boolean';
    		}

    		if ($field['type'] == NULL)
    		{
                $field['type'] = 'string';
    			//dd('Не удалось определить тип поля');
    		}

            

    		$config[$table]['columns'][$field['data']] = $field;

    	}

		return $config;
	}
}