<?php 

class TSUE_Database
{
    public $querycount = 0;
    public $query = NULL;
    public $query_cache = array(  );
    public $shutdown_queries = array(  );
    public $error = NULL;
    public $connection = NULL;
    public $locked = false;
    public $totalQueryRunTime = 0;
    public $inAnnounce = false;
//public function TSUE_Database()
    function __construct()
    {
        if( defined("SCRIPTNAME") && SCRIPTNAME == "announce.php" ) 
        {
            $this->inAnnounce = true;
        }

        if( !is_file(REALPATH . "library/config/database_config.php") ) 
        {
            $this->error = "Fatal Error: The database configuration file does not exists.";
            $this->halt(true);
        }

        require(REALPATH . "library/config/database_config.php");
        if( !($this->connection = mysqli_init()) ) 
        {
            $this->error = "mysqli_init() failed!";
            $this->halt(true);
        }

        if( !defined("MYSQL_HOST") || !defined("MYSQL_USER") || !defined("MYSQL_PASS") || !defined("MYSQL_DB") || !defined("MYSQL_PORT") || !defined("MYSQL_SOCKET") ) 
        {
            $this->error = "Fatal Error: Invalid variables in the database configuration file.";
            $this->halt(true);
        }

        if( !mysqli_real_connect($this->connection, MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT, MYSQL_SOCKET) ) 
        {
            $this->error = "MySQLi connection error!";
            $this->halt(true);
        }

        if( MYSQL_CHARSET != "" ) 
        {
            if( function_exists("mysqli_set_charset") ) 
            {
                mysqli_set_charset($this->connection, MYSQL_CHARSET);
                return NULL;
            }

            $this->execute_query("SET NAMES " . MYSQL_CHARSET);
        }

    }

    public function execute_query($query, $buffered = true)
    {
        $this->querycount++;
        $this->query = $query;
        $queryStartTime = microtime(true);
        if( $queryresult = mysqli_query($this->connection, $this->query, ($buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)) ) 
        {
            $queryEndTime = microtime(true) - $queryStartTime;
            $this->query_cache[] = array( $queryEndTime, $query );
            $this->totalQueryRunTime += $queryEndTime;
            return $queryresult;
        }

        $this->error = mysqli_error($this->connection);
        $this->halt();
    }

    public function query($query, $buffered = true)
    {
        return $this->execute_query($query, $buffered);
    }

    public function query_result($query, $buffered = true)
    {
        $returnarray = false;
        $queryresult = $this->execute_query($query, $buffered);
        if( $this->num_rows($queryresult) ) 
        {
            $returnarray = $this->fetch_assoc($queryresult);
            $this->free($queryresult);
        }

        return $returnarray;
    }

    public function replace($table, $buildQuery, $isShutDownQuery = false)
    {
        $Query = array(  );
        foreach( $buildQuery as $field => $value ) 
        {
            $Query[] = "`" . $field . "` = " . $this->escape($value);
        }
        $SET = implode(",", $Query);
        if( $isShutDownQuery ) 
        {
            return $this->shutdown_query("REPLACE INTO `" . $table . "` SET " . $SET);
        }

        return $this->query("REPLACE INTO `" . $table . "` SET " . $SET);
    }

    public function insert($table, $buildQuery, $isShutDownQuery = false, $EXTRA = "", $IGNORE = "")
    {
        $Query = array(  );
        foreach( $buildQuery as $field => $value ) 
        {
            $Query[] = "`" . $field . "` = " . $this->escape($value);
        }
        $SET = implode(",", $Query);
        if( $isShutDownQuery ) 
        {
            return $this->shutdown_query("INSERT" . (($IGNORE ? " IGNORE" : "")) . " INTO `" . $table . "` SET " . $SET . $EXTRA);
        }

        return $this->query("INSERT" . (($IGNORE ? " IGNORE" : "")) . " INTO `" . $table . "` SET " . $SET . $EXTRA);
    }

    public function update($table, $buildQuery, $where = "", $isShutDownQuery = false)
    {
        $Query = array(  );
        foreach( $buildQuery as $field => $value ) 
        {
            if( is_array($value) ) 
            {
                if( $value["escape"] == 0 ) 
                {
                    $Query[] = "`" . $field . "` = " . $value["value"];
                }
                else
                {
                    $Query[] = "`" . $field . "` = " . $this->escape($value["value"]);
                }

            }
            else
            {
                $Query[] = "`" . $field . "` = " . $this->escape($value);
            }

        }
        $SET = implode(",", $Query);
        if( $isShutDownQuery ) 
        {
            return $this->shutdown_query("UPDATE `" . $table . "` SET " . $SET . (($where ? " WHERE " . $where : "")));
        }

        return $this->query("UPDATE `" . $table . "` SET " . $SET . (($where ? " WHERE " . $where : "")));
    }

    public function delete($table, $WHERE = "")
    {
        $this->execute_query("DELETE FROM " . $table . (($WHERE ? " WHERE " . $WHERE : "")));
        return $this->affected_rows();
    }

    public function truncate($table)
    {
        return $this->execute_query("TRUNCATE TABLE `" . $table . "`");
    }

    public function row_count($query, $buffered = true)
    {
        return $this->num_rows($this->execute_query($query, $buffered));
    }

    public function exec_shutdown_queries()
    {
        if( !empty($this->shutdown_queries) ) 
        {
            foreach( $this->shutdown_queries as $query ) 
            {
                $this->execute_query($query);
            }
            $this->shutdown_queries = array(  );
        }

    }

    public function shutdown_query($query)
    {
        $this->shutdown_queries[] = $query;
    }

    public function lock_tables($tablelist = "")
    {
        if( !empty($tablelist) && is_array($tablelist) ) 
        {
            $sql = "";
            foreach( $tablelist as $name => $type ) 
            {
                $sql .= ((!empty($sql) ? ", " : "")) . $name . " " . $type;
            }
            $this->query("LOCK TABLES " . $sql);
            $this->locked = true;
        }

    }

    public function unlock_tables()
    {
        if( $this->locked ) 
        {
            $this->query("UNLOCK TABLES");
            $this->locked = false;
        }

    }

    public function affected_rows()
    {
        return mysqli_affected_rows($this->connection);
    }

    public function insert_id()
    {
        return mysqli_insert_id($this->connection);
    }

    public function fetch_assoc($query)
    {
        return mysqli_fetch_array($query, MYSQLI_ASSOC);
    }

    public function fetch_row($query)
    {
        return mysqli_fetch_row($query);
    }

    public function fetch_object($query)
    {
        return mysqli_fetch_object($query);
    }

    public function num_rows($query)
    {
        return mysqli_num_rows($query);
    }

    public function free($query)
    {
        mysqli_free_result($query);
    }

    public function escape($string)
    {
        return "'" . mysqli_real_escape_string($this->connection, $string) . "'";
    }

    public function escape_no_quotes($string)
    {
		if (is_null($string)) {
			return "";
		} else {
			return mysqli_real_escape_string($this->connection, $string);
		}
        
    }

    public function field_exists($table, $column)
    {
        return $this->row_count("SHOW columns FROM  `" . $table . "` WHERE Field = " . $this->escape($column));
    }

    public function halt($connectionError = false)
    {
        if( $connectionError ) 
        {
            if( $this->inAnnounce ) 
            {
                _printError(strip_tags($this->error));
            }
            else
            {
                exit( "<h2>" . $this->error . "</h2>" );
            }

        }

        $Message = "<h2>Database Error!</h2> Please try again later or contact an <b>Administrator</b>.<br />";
        $fullError = "\r\n\t\t(<b>" . mysqli_errno($this->connection) . "</b>) " . $this->error . "\r\n\t\t<hr />\r\n\t\t" . $this->query;
        if( function_exists("logAction") ) 
        {
            logAction(strip_tags($fullError));
        }

        if( $this->inAnnounce ) 
        {
            _printError(strip_tags($Message));
        }

        if( has_permission("canview_debug") ) 
        {
            $Message .= $fullError;
        }

        exit( $Message );
    }

    public function close()
    {
        mysqli_close($this->connection);
    }

    public function resetQueryCounts()
    {
        $this->querycount = 0;
        $this->query = NULL;
        $this->query_cache = array(  );
        $this->shutdown_queries = array(  );
    }

}


