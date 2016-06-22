<?php

class DB
{
    private $link = null;
    
    static $inst = null;
    
    
    private function __construct($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME)
    {
        mb_internal_encoding( 'UTF-8' );
        mb_regex_encoding( 'UTF-8' );
        mysqli_report( MYSQLI_REPORT_STRICT );
        try {
            $this->link = new mysqli( $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME );
            $this->link->set_charset( "utf8" );
        } catch ( Exception $e ) {
            die( 'Unable to connect to database' );
        }
    }
    public function __destruct()
    {
        if( $this->link)
        {
            $this->disconnect();
        }
    }

    static function getInstance($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME)
    {
        if( self::$inst == null )
        {
            self::$inst = new DB($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        }
        return self::$inst;
    }

    public function create_table($name, $fields = array())
    {
        if ( empty($name) || empty($fields) )
            return;
        $sql = "CREATE TABLE `" . $name . "` ";
        $sql.= "( `id` INT(11) NOT NULL AUTO_INCREMENT";
        foreach($fields as $f=>$v)
        {
            $sql.= ", `" . $v . "` VARCHAR(40) ";
        }

        $sql.= " ,PRIMARY KEY (`id`)) ENGINE = InnoDB;";
        if (!$this->query($sql))
        {
            throw new Exception($this->link->error, 1);
        }
    }
    public function query( $query )
    {
        $full_query = $this->link->query( $query );
        if( $this->link->error )
        {
            return false; 
        }
        else
        {
            return true;
        }
    }
    
     public function escape( $data )
     {
         if( !is_array( $data ) )
         {
             $data = $this->link->real_escape_string( $data );
         }
         else
         {
             $data = array_map( array( $this, 'escape' ), $data );
         }
         return $data;
     }      

     public function table_exists( $name )
     {
         $check = $this->link->query( "SELECT 1 FROM `$name`" );

         if( $check !== false )
         {
             return true;
         }
         else
         {
             return false;
         }
     }
    public function get_random_record( $name )
    {
        $rows_count = $this->num_rows("SELECT 1 FROM `$name` ");
        $random_int = rand(1, $rows_count);
        $sql = "SELECT * FROM $name LIMIT " . $random_int . ", " . $rows_count;
        $res = $this->get_results($sql);
        return $res[0];
    }
    
    public function num_rows( $query )
    {
        $num_rows = $this->link->query( $query );
        if( $this->link->error )
        {
            return $this->link->error;
        }
        else
        {
            return $num_rows->num_rows;
        }
    }
      
    public function get_results( $query, $object = false )
    {
        $row = null;
        
        $results = $this->link->query( $query );
        if( $this->link->error )
        {
            return false;
        }
        else
        {
            $row = array();
            while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            {
                $row[] = $r;
            }
            return $row;   
        }
    }
    
    public function insert( $table, $variables = array() )
    {
        if( empty( $variables ) )
        {
            return false;
        }
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $field;
            $values[] = "'".$value."'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';
        
        $sql .= $fields .' VALUES '. $values;
        $query = $this->link->query( $sql );
        
        if( $this->link->error )
        {
            return false;
        }
        else
        {
            return true;
        }
    }    
    
    public function insert_multi( $table, $columns = array(), $records = array() )
    {
        
        if( empty( $columns ) || empty( $records ) )
        {
            return false;
        }
        $number_columns = count( $columns );     
        $added = 0;
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        foreach( $columns as $field )
        {
            $fields[] = '`'.$field.'`';
        }
        $fields = ' (' . implode(', ', $fields) . ')';

        $values = array();
        foreach( $records as $record )
        {
            if( count( $record[0] ) == $number_columns )
            {
                $values[] = '(\''. implode( '\', \'', array_values( $record[0] ) ) .'\')';
                $added++;
            }
        }
        $values = implode( ', ', $values );
        $sql .= $fields .' VALUES '. $values;

        $query = $this->link->query( $sql );
        if( $this->link->error )
        {
            return false;
        }
        else
        {
            return $added;
        }
    }
    
    public function update( $table, $variables = array(), $where = array(), $limit = '' )
    {
        if( empty( $variables ) )
        {
            return false;
        }
        $sql = "UPDATE ". $table ." SET ";
        foreach( $variables as $field => $value )
        {
            
            $updates[] = "`$field` = '$value'";
        }
        $sql .= implode(', ', $updates);
        
        if( !empty( $where ) )
        {
            foreach( $where as $field => $value )
            {
                $value = $value;
                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE '. implode(' AND ', $clause);   
        }
        
        if( !empty( $limit ) )
        {
            $sql .= ' LIMIT '. $limit;
        }
        $query = $this->link->query( $sql );
        if( $this->link->error )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function select_row($table_name, $where = array())
    {
        $sql = "SELECT * FROM $table_name ";
        if ( !empty($where) )
        {
            foreach( $where as $field => $value )
            {
                $value = $value;
                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE '. implode(' AND ', $clause);   
        }
        $sql.= " LIMIT 1";
        $res = $this->get_results($sql);
        return $res[0];
    }
       
    

    public function num_fields( $query )
    {
        $query = $this->link->query( $query );
        $fields = $query->field_count;
        return $fields;
    }

    
    public function disconnect()
    {
        $this->link->close();
    }
}