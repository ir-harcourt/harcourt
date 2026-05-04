<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2024 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.
*/

/*
Mysql 5.7 has a password validator
uninstall plugin validate_password;
defined functions
    [0] => autocommit
    [3] => close
    [4] => commit
    [5] => connect
    [15] => multi_query
    [16] => mysqli
    [23] => query
    [24] => real_connect
    [25] => real_escape_string
    [26] => reap_async_query
    [27] => escape_string
    [28] => real_query
    [29] => rollback

undefined
    [1] => change_user
    [2] => character_set_name
    [6] => dump_debug_info
    [7] => debug
    [8] => get_charset
    [9] => get_client_info
    [10] => get_connection_stats
    [11] => get_server_info
    [12] => get_warnings
    [13] => init
    [14] => kill
    [17] => more_results
    [18] => next_result
    [19] => options
    [20] => ping
    [21] => poll
    [22] => prepare
    [30] => select_db
    [31] => set_charset
    [32] => set_opt
    [33] => ssl_set
    [34] => stat
    [35] => stmt_init
    [36] => store_result
    [37] => thread_safe
    [38] => use_result
    [39] => refresh
*/

$database=new database_class();
$database->temp=new database_temp_class();

class database_class {
    function scs_version() {
    	$results=array();
        $results['06/23/2025']="Add schema";
        $results['06/13/2024']="Add fetch_json";
        $results['03/10/2024']="Add database_hotfix_class";
        $results['08/22/2023']="Enable LEGACY";
        $results['03/19/2023']="Add limit (deprecate limit_page, limit_item)";
        $results['04/10/2022']="Add LEGACY";
        $results['02/21/2022']="Add timestamp";
        $results['01/26/2022']="Add transaction, commit, rollback";
        $results['10/05/2021']="Add sql_mode";
        $results['09/15/2021']="Add user_password";
        $results['07/22/2021']="Add unixtime";
        $results['04/20/2021']="Add hotfix";
        $results['12/28/2020']="Add tables";
        $results['08/02/2020']="Add multi_query";
        $results['12/05/2018']="Add not in operator";
        $results['03/05/2017']="MySQL 5.7 compatible";
        $results['01/25/2017']="Add query_large";
        $results['12/23/2015']="Add explain";
        $results['09/29/2015']="Allow query to be an array";
        $results['05/15/2015']="Add commit / rollback";
        $results['03/06/2015']=array("Initial Release","MySQLI Engine","Based on database_rev1 version 02/21/2015");
		$results['file']= __FILE__;
        return $results;
    }
    function __construct() {
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->meta=new stdCLass();
        $this->meta->revision= __FILE__ . " " . key($this->scs_version());
        $this->db_options();
    }
    function db_options($options=array()) {
		$this->query_limit=(isset($options['query_limit']) ? $options['query_limit'] : 50);
		$this->null_date=(isset($options['null_date']) ? $options['null_date'] : FALSE);
        $this->meta->sql_mode=( (isset($options['sql_mode'])) && (is_array($options['sql_mode'])) ) ? $options['sql_mode'] : array();
    }
    function connect($host, $user, $password, $database) {
        global $mysqli;
		if (isset($myssqli)) return;
        $this->meta->host=$host;
        $this->meta->user=$user;
        $this->meta->password=$password;
        $this->meta->database=$database;
		$this->meta->transaction=FALSE;
		try {
			$mysqli=new mysqli($host,$user,$password,$database);
		  } catch (mysqli_sql_exception $e) {
			die("Cannot connect to host: $host");
		}
        if ($mysqli->connect_error) die("Database connection error: " . $mysqli->connect_errno);
    }
	function disconnect() {
        global $mysqli;
	    if (!isset($mysqli)) return;
        if ($this->meta->transaction) $mysqli->commit();
		$mysqli->close();
        unset ($mysqli);
	}
    function reconnect() {
		$this->disconnect();
        $this->connect($this->meta->host, $this->meta->user, $this->meta->password,$this->meta->database);
    }
    function set_charset($charset) {
        global $mysqli;
		$mysqli->set_charset($charset);
    }
    function server() {
    global $mysqli;
		$charset=$mysqli->get_charset();
		$results=array();
        $results['engine']="mysqli";
        $results['host']=$this->meta->host;
        $results['user']=$this->meta->user;
        $results['database']=$this->meta->database;
        $results['client_info']=$mysqli->client_info;
        $results['client_version']=$mysqli->client_version;
        $results['host_info']=$mysqli->host_info;
        $results['info']=$mysqli->info;
        $results['server_info']=$mysqli->server_info;
        $results['server_version']=$mysqli->server_version;
        $results['stat']=$mysqli->stat;
        $results['sqlstate']=$mysqli->sqlstate;
        $results['protocol_version']=$mysqli->protocol_version;
        $results['charset']=$charset->charset;
        $results['collation']=$charset->collation;
        return $results;
    }
    function query($query,$found_rows=FALSE) {
        global $mysqli;
		if (is_array($query)) $query=implode("\n",$query);
		if ($this->meta->debug) $this->debug(__METHOD__,$query);
		if (substr($query,-1)==";") $query=substr($query,0,-1);
		$this->meta->query=$query;
		if ($found_rows) $this->found_rows($query);
		$this->meta->handle=$mysqli->query($query);
	    if (!$this->meta->handle) {
        	$this->error($query);
		  } else {
          	$this->meta->errors();
		}
	    if (is_object($this->meta->handle)) {
	        $this->meta->rows=$this->meta->handle->num_rows;
	      } else {
	        $this->meta->rows=0;
	    }
		if (!$found_rows) $this->meta->found_rows=$this->meta->rows;
	}
    function multi_query($query) {
        global $mysqli;
        $this->meta->query="";
        foreach ($query as $item) {
        	$this->meta->query .= "{$item};\n";
        }
		if ($this->meta->debug) $this->debug(__METHOD__,$this->meta->query);
		$this->meta->handle=$mysqli->query($this->meta->query);
	    if (!$this->meta->handle) {
        	$this->error($this->meta->query);
		  } else {
          	$this->meta->errors();
		}
	}
    function query_large($query) { // no other mysql commands can execute while running
        global $mysqli;
		if (is_array($query)) $query=implode("\n",$query);
		if ($this->meta->debug) $this->debug(__METHOD__,$query);
		$this->meta->handle=$mysqli->query($query,MYSQLI_USE_RESULT);
        $this->meta->rows=-1;
        $this->meta->found_rows=-1;
	    if (!$this->meta->handle) {
        	$this->error($query);
		  } else {
          	$this->meta->errors();
		}
    }
	function found_rows($query) {
        global $mysqli;
		$found_rows_query=preg_replace(array("/^select/i","/\n/"),array("select SQL_CALC_FOUND_ROWS "," "),$query);
		if ($this->meta->debug) $this->debug(__METHOD__,$found_rows_query);
		if (!$mysqli->query($found_rows_query)) return;
		$qh=$mysqli->query("select found_rows()");
		$results=$qh->fetch_assoc();
		$this->meta->found_rows=intval($results['found_rows()']);
	}
    function affected_rows() {
        global $mysqli;
		return $mysqli->affected_rows;
    }
    function fetch_array($both=FALSE) {
		switch (TRUE) {
		  case (!is_object($this->meta->handle)):
			return;
		  case ($both):
			$option=MYSQLI_BOTH;
            break;
		  default:
			$option=MYSQLI_ASSOC;
        }
		return $this->meta->handle->fetch_array($option);
    }
	function insert_id() {
        global $mysqli;
		return $mysqli->insert_id;
	}
	function free_result() {
        if (is_object($this->meta->handle)) {
			if ($this->meta->debug) $this->debug(__METHOD__);
			$this->meta->handle->free_result();
        	$this->meta->handle=FALSE;
        }
    }
    function explain($query) {
        global $mysqli;
		if (is_array($query)) {
			$qh=$mysqli->query("explain " . implode("\n",$query));
		  } else {
			$qh=$mysqli->query("explain $query");
		}
		$data=$qh->fetch_assoc();
        $qh->free_result();
        $results=new stdClass();
        foreach ($data as $key => $value) {
        	$results->{$key}=$value;
        }
		return $results;
    }
    function transaction($action) {
        global $mysqli;
    	switch (TRUE) {
          case ( ($action == "start") && (!$this->meta->transaction) ):
          	$mysqli->autocommit(0);
			$mysqli->begin_transaction();
            $this->meta->transaction=TRUE;
            break;
          case ( ($action == "commit") && ($this->meta->transaction) ):
			$mysqli->commit();
          	$mysqli->autocommit(1);
            $this->meta->transaction=FALSE;
            break;
          case ( ($action == "rollback") && ($this->meta->transaction) ):
			$mysqli->rollback();
          	$mysqli->autocommit(1);
            $this->meta->transaction=FALSE;
            break;
		}
    }
    function debug($method,$data="") {
		$results=array();
        $results[]="Method: $method";
        if (method_exists($this,"scs_table_version")) {
			$scs_table_version=$this->scs_table_version();
            if (array_key_exists("file",$scs_table_version)) {
            	$text=$scs_table_version['file'];
			  } else {
            	$text="Unknown";
			}
            $results[]="File: $text Revised: " . key($scs_table_version);
        }
        $results[]="Time: " . date("m/d/Y H:i:s");
        $results[]="Memory used: " . number_format(memory_get_usage(FALSE));
        if ($data) $results[]=$data;
		print "<!-- \n" . implode("\n",$results) . "\n--->\n";
    }
    function error($text="") {
        global $database, $mysqli;
    	if (!isset($mysqli)) die("Not connected to database");
		$this->meta->errors($mysqli->errno,$mysqli->error);
		switch (TRUE) {
		  case ($database->transaction->active):
			$database->transaction->error();
          	return;
		  case ($this->meta->errno == 1062):  //  Duplicate entry for seconday key
		  case ($this->meta->errno == 1819):  // Your password does not satisfy the current policy requirements
          case ((isset($this->meta->error_abort)) && (!$this->meta->error_abort)):
          	return;
		}
        $results=array();
		$results[]="MySQL Error #" . $this->meta->errno . " has occured in the script " . $_SERVER['PHP_SELF'];
		$results[]=$this->meta->error;
        if ($text) $results[]=str_replace("\n","<br>",$text);
        if (!method_exists($database->log,"ipc")) {
			print "<p>" . implode("<br>",$results) . "</p>\n";
		  } else {
            print "<p>Database error #" . $this->meta->errno . "</b><br>" . $this->meta->error . "</p>\n";
			$database->log->meta->error_abort=FALSE;
			$database->log->ipc("MySQL: Error",$results);
		}
        die;
    }
// 02/21/2022: Deprecated
    function unixtime($field) {
    	return "from_unixtime({$field},'%Y/%m/%d')";
    }
	function timestamp($text, $max) {
		$timestamp=strtotime($text);
        if ($max) $timestamp += (60 * 60 * 24) - 1;
		return $timestamp;
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
	              case ( (in_array($item->operator,array("in","not in"))) && (!is_array($item->value)) ):
	              case ( (in_array($item->operator,array("in","not in"))) && (!sizeof($item->value)) ):
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
	              case (in_array($item->operator,array("in","not in"))):
	                $text=array();
	                foreach ($item->value as $value) {
	                    $text[]=fn_escape($value);
	                }
	                $results .= "{$item->field} {$item->operator} (" . implode(", ",$text) . ")";
	                break;
	              case ($item->type == ""):
	              case ($item->type == "constant"):
	                $results .= "$item->field $item->operator " . fn_escape($item->value);
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
    function limit($type, $page, $item=0) {
    	if ( ($type == "page") && (!$this->query_limit) ) return;
        $results[]="limit";
        $results[]=($type == "page") ? ($this->query_limit * $page) : ($this->query_limit * $page) + $item;
        $results[]=",";
        $results[]=($type == "page") ? $this->query_limit : 1;
		return implode(" ",$results);
    }
// 03/19/2023: Deprecated
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
// 03/19/2023: Deprecated
    function item_limit($item) {
		$item=intval($item);
		if ($item) $item=($item - 1);
		return "\nlimit " . $item  . ",1";
    }
    function tables() {
		$results=array();
        foreach ($this as $table => $table_obj) {
        	if (!method_exists($table_obj,"scs_table_version")) continue;
            $scs_table_version=$table_obj->scs_table_version();
        	$results[$table]=$scs_table_version['file'];
        }
		ksort($results);
		return $results;
    }
    function table_comment($table) {
        global $mysqli;
		$query=array("SELECT table_comment");
        $query[]="FROM INFORMATION_SCHEMA.TABLES";
        $query[]="WHERE table_schema=" . fn_escape($this->meta->database);
        $query[]="AND table_name=" . fn_escape($table);
		$qh=$mysqli->query(implode("\n",$query));
		$fetch=( ($qh->num_rows) ? $qh->fetch_array(MYSQLI_NUM) : array());
        list($text, $revised)=preg_split("/[\(|\)]/",$fetch[0]);
        return $revised;
    }
    function hotfix_test($table, $date) {
        global $mysqli;
        return (strtotime($date) > strtotime($this->table_comment($table)));
    }
    function hotfix_update($query) {
        global $mysqli;
		$qh=$mysqli->query(implode("\n",$query));
    }
    function user_password($password) {
        global $database;
    	return password_encoding() . "=" . $this->user_password_field($password);
    }
    function user_password_field($password) {
        global $database;
        switch ($database->registry->password->encoding) {
          case "SHA1":
			return "sha1(" . fn_escape($password) . ")";
          case "SHA2:256":
			return "sha2(" . fn_escape($password) . ",256)";
          case "SHA2:512":
			return "sha2(" . fn_escape($password) . ",512)";
          case "Legacy":
			return "concat('*', upper(sha1(unhex(sha1(" . fn_escape($password) . ")))))";
		  default:
			return "password(" . fn_escape($password) . ")";
		}
    }
    function fetch_json($data, $array=0) {
        $results=json_decode($data, $array);
        switch (TRUE) {
          case ( (is_object($results)) && (!$array) ):
          case ( (is_array($results)) && ($array) ):
            return $results;
          case ($array):
            return array();
          default:
            return new stdClass();
        }
    }
    function schema($table, $field="") {
        $query=array("select * from INFORMATION_SCHEMA.TABLES");
        $query[]="where TABLE_SCHEMA = " . fn_escape($this->meta->database);
        $query[]="and table_name=" . fn_escape($table);
        $this->temp->query($query);
        $this->temp->fetch();
        return (strlen($field)) ? $this->temp->fetch[$field] : $this->temp->fetch;
    }
}
class database_temp_class extends database_class {
	var $fetch=array();
	function __construct() {
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch() {
		$this->fetch=$this->fetch_array();
	}
}
class database_meta_class {
    var $query;
    var $handle;
    var $rows;
    var $found_rows;
    var $errno;
    var $error;
    var $error_abort=TRUE;
    var $version;
    var $debug=FALSE;
    function errors($errno=0, $error="") {
		$this->errno=$errno;
        $this->error=$error;
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