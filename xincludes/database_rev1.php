<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2015 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.
*/
$database=new database_class();
$database->temp=new database_temp_class();

class database_class {
    function __construct($query_limit=50) {
	    $this->query_limit=$query_limit;
	    $connection;
    }
    function scs_version() {
    	$results=array();
        $results['02/21/2015']="Add group option to function order";
        $results['09/25/2014']="query_where delimited by quotes";
        $results['12/08/2013']="Add reconnect function";
        $results['11/11/2013']="Add order function";
        $results['06/12/2013']="Add omit, and_group, or_group";
        $results['05/02/2013']=array("Add fetch_array","Obscure errors when log available");
        $results['02/08/2013']="No page break if 0 query_limit";
        $results['01/08/2013']="Add multiple level query_where";
        $results['12/03/2012']="query_limit set at initialize w/default 25";
        $results['10/11/2012']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function connect($host, $user, $password, $database) {
    	if (isset($this->connection->host_handle)) return;
		$this->connection=new database_connection_class($host, $user, $password, $database);
    	$this->connection->host_handle = @mysql_connect($this->connection->host, $this->connection->user, $this->connection->password);
		if (mysql_errno()) $this->error("Cannot connect to host: {$this->connection->host}");
		$this->connection->database_handle = @mysql_select_db($this->connection->database,$this->connection->host_handle);
		if (mysql_errno()) $this->error("Cannot connect to database: {$this->connection->database}");
	}
	function disconnect() {
	    if (!isset($this->connection->host_handle)) return;
        @mysql_close($this->connection->host_handle);
        unset($this->connection->host_handle);
	}
    function reconnect() {
		$this->disconnect();
        $this->connect($this->connection->host, $this->connection->user, $this->connection->password,$this->connection->database);
    }
    function server() {
		$results=array();
        $results['engine']="mysql";
        $results['host']=$this->connection->host;
        $results['user']=$this->connection->user;
        $results['database']=$this->connection->database;
        return $results;
    }
    function query_limit($query_limit=50) {
	    $this->query_limit=$query_limit;
    }
    function query($query,$found_rows=FALSE) {
		if ($this->meta->debug) print "<!--\n\n{$query}\n\n-->\n";
		$this->meta->query=$query;
		if ($found_rows) $this->found_rows($query);
	    $this->meta->handle = @mysql_query($query);
	    if (!$this->meta->handle) $this->error($query);
		$this->meta->rows=@mysql_num_rows($this->meta->handle);
        if (!$found_rows) $this->meta->found_rows=$this->meta->rows;
		$this->meta->errno=mysql_errno();
		$this->meta->error=mysql_error();
    }
	function insert_id() {
		$this->query("select last_insert_id();");
	    $fetch=$this->fetch_array($this->meta->handle);
	    return floatval($fetch[0]);
	}
	function free_result() {
        if ($this->meta->handle) {
        	$result=@mysql_free_result($this->meta->handle);
        	$this->meta->handle=FALSE;
        }
    }
    function fetch_array() {
		return @mysql_fetch_array($this->meta->handle);
    }
	function found_rows($query) {
		$found_rows_query=str_replace("select","select SQL_CALC_FOUND_ROWS ",str_replace("\n"," ",$query));
		if ($this->meta->debug) print "<!--\n\n{$found_rows_query}\n\n-->\n";
		$handle = @mysql_query($found_rows_query);
		if (!$handle) return;
		$handle = @mysql_query("select found_rows()");
		if (!$handle) return;
		$fetch=@mysql_fetch_array($handle);
		$this->meta->found_rows=intval($fetch[0]);
	}
    function affected_rows() {
		return mysql_affected_rows();
    }
    function error($text="") {
    global $database;
		switch (TRUE) {
		  case (mysql_errno() == 1062):
          case ((isset($this->meta->error_abort)) && (!$this->meta->error_abort)):
          	return;
		}
        $results=array();
		$results[]="MySQL Error #" . mysql_errno() . " has occured in the script " . $_SERVER['PHP_SELF'];
		$results[]=mysql_error();
        if ($text) $results[]=str_replace("\n","<br>",$text);
        switch (TRUE) {
		  case (!$database->connection->database_handle):
          case (!method_exists($database->log,"ipc")):
			print "<p>" . implode("<br>",$results) . "</p>\n";
            break;
          default:
            print "<p>Database error #" . mysql_errno() . "</b><br>" . mysql_error() . "</p>\n";
			$database->log->meta->error_abort=FALSE;
			$database->log->ipc("MySQL: Error",$results);
		}
        die;
    }
	function where($data,$option="") {
    	$having_omit="";
		switch ($option) {
		  case "1":
          case "having":
			$having_omit="having";
            break;
          case "omit":
			$having_omit="omit";
            break;
        }
    	$results="";
        if (!is_array($data)) return "";
        foreach ($data as $key_key => $item_list) {
			if (!is_array($item_list)) {
            	$items=array($item_list);
			  } else {
              	$items=$item_list;
			}
            foreach ($items as $item_key => $item) {
 	            switch (TRUE) {
	              case (($item->operator == 'in') && (!is_array($item->value))):
	              case (($item->operator == 'in') && (!sizeof($item->value))):
	                continue 2;
                  case ($item->and_or=="and_group"):
	                $results .= " and ( \n";
                  	break;
                  case ($item->and_or=="or_group"):
	                $results .= " or ( \n";
                  	break;
                  case ( (!$results) && ($having_omit=="omit") ):
                 	break;
	              case ( (!$results) && ($having_omit=="having") ):
	                $results = "having ";
	                break;
	              case (!$results):
	                $results = "where ";
	                break;
	              case ($item->and_or):
	                $results .= " or ";
	                break;
	              default:
	                $results .= " and ";
	            }
                if ((is_array($item_list)) && (!$item_key)) $results .= "(\n";
	            switch (TRUE) {
	              case (!$item->operator):
	                $results .= $item->field;
	                break;
	              case ($item->operator == 'in'):
	                $text=array();
	                foreach ($item->value as $value) {
	                    $text[]=fn_escape($value);
	                }
	                $results .= $item->field . " in (" . implode(",",$text) . ")";
	                break;
	              case ($item->type == ""):
	              case ($item->type == "constant"):
	                $results .= "$item->field $item->operator '" .  mysql_real_escape_string($item->value) . "'";
	                break;
	              case ($item->type=="field"):
	                $results .= $item->field . $item->operator . $item->value;
	                break;
	              default:
	                print "Invalid query type: " . $item->type . "<br>Field: " . $item->field;
	                die;
	            }
				switch (TRUE) {
                  case ($item->and_or=="and_group"):
                  case ($item->and_or=="or_group"):
	                $results .= ") \n";
                  	break;
				}
	            $results .= "\n";
			}
			if (is_array($item_list)) $results .= ")\n";
        }
        return $results;
    }
    function order($order=array(),$group=FALSE) {
    	if (!sizeof($order)) return;
        $results=array();
        foreach ($order as $field => $desc) {
        	if ( (!$group) && ($desc) ) {
	            $results[]="$field desc";
	          } else {
	            $results[]=$field;
	        }
		}
        if ($group) {
        	return "group by " . implode(", ",$results) . "\n";
		  } else {
        	return "order by " . implode(", ",$results) . "\n";
		}
    }
    function page_limit($page, $debug=FALSE) {
		switch (TRUE) {
          case (!$this->query_limit):
          	return;
          case (intval($page) < 1):
          	$page=1;
		}
        if ($debug) {
        	$query_limit=3;
		  } else {
        	$query_limit=$this->query_limit;
		}
		return "\nlimit " . (($page - 1) * $query_limit) . "," . $query_limit;
    }
    function item_limit($item) {
		$item=intval($item);
		if ($item) $item=($item - 1);
		return "\nlimit " . $item  . ",1";
    }
}
class database_temp_class extends database_class {
	var $fetch=array();
	function __construct() {
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch() {
		$this->fetch=$this->fetch_array($this->meta->handle);
	}
}
class database_meta_class {
    var $query;
    var $handle;
    var $rows;
    var $found_rows;
    var $errno=0;
    var $error;
    var $error_abort=TRUE;
    var $debug=FALSE;
}
class database_connection_class {
    var $host;
    var $user;
    var $password;
    var $database;
    var $host_handle;
	var $database_handle;
	function __construct($host, $user, $password, $database) {
		$this->host=$host;
		$this->user=$user;
		$this->password=$password;
		$this->database=$database;
    }
}
class query_where {
	var $field;
    var $operator;
    var $value;
    var $and_or;
    var $type;
    function __construct($field,$operator,$value,$and_or="",$type="") {
	    $this->field=$field;
	    $this->operator=strtolower($operator);
	    $this->value=$value;
	    $this->and_or=strtolower($and_or);
	    $this->type=strtolower($type);
    }
}
?>