<?php
$ajax=new ajax_class();
class ajax_class {
	var $table;
    var $action="search";
    var $method;
    var $debug=FALSE;
    var $search_terms=array();
    var $multi_items=array();
    var $keys=array();
    var $options=array();
    var $limit=10;
	function scs_table_version() {
		$results=array();
        $results['12/10/2023']="Add multi";
        $results['07/20/2023']="Add limit option";
        $results['07/21/2022']="Die on empty keys";
        $results['04/11/2020']="Improve response & security";
        $results['08/27/2015']="Expand external access tracking";
        $results['06/05/2015']="Add method";
        $results['01/01/2015']="Improve security";
        $results['06/09/2014']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct() {
        global $database, $forms;
		foreach ($_REQUEST as $key => $value) {
			$key=trim($key);
			if (!is_array($value)) $value=trim($value);
			switch (TRUE) {
              case ($key == "multi_items"):
                if (is_array($value)) $this->multi_items=$value;
                break;
			  case (!strlen($value)):
				break;
			  case ( ($key =="debug") && (fn_development_server()) ):
            	$this->debug=TRUE;
                break;
			  case ($key =="table"):
              	$this->table=strtolower($value);
                break;
			  case ($key =="action"):
              	$this->action=strtolower($value);
                break;
			  case ($key =="method"):
              	$this->method=strtolower($value);
                break;
			  case ($key =="term"):
				$this->search_terms=explode(" ",preg_replace('/\s+/',' ',$value));
                break;
			  case ($key =="limit"):
              	$limit=intval($value);
                if ($limit) $this->limit=$limit;
                break;
			  case (preg_match("/key_/i",$key)):
              	$this->keys[]=$value;
				break;
			  default:
				$this->options[$key]=$value;
			}
        }
        $methods=array("search"=>"ajax","fill"=>"ajax_fill","multi_fill"=>"ajax_multi_fill");
        switch (TRUE) {
		  case ($this->method):
			$this->action="method";
			break;
          case (array_key_exists($this->action,$methods)):
          	$this->method=$methods[$this->action];
		}
	    switch (TRUE) {
	      case (!$this->table):
	      case (!isset($this->table,$database)):
			$this->abort("Table",$this->table . " not found");
		  case (!preg_match("/^(http|https)" . preg_quote("://" . $_SERVER['HTTP_HOST'],"/") . "/i",$_SERVER['HTTP_REFERER'])):
			$this->abort("External access",array("HTTP_HOST: " . $_SERVER['HTTP_HOST'],"HTTP_REFERER: " . $_SERVER['HTTP_REFERER']));
	      case ( (method_exists($database->{$this->table},"ajax_access")) && (!$database->{$this->table}->ajax_access()) ):
			$this->abort("Access denied");
		  case (!$this->method):
          case ( ($this->action=="method") && (!preg_match("/ajax_/",$this->method)) ):
	      case (!method_exists($database->{$this->table},$this->method)):
			$this->abort("Method",$this->table . " action: " . $this->action . " method: " . $this->method . " not found");
          case ($this->debug):
			break;
	      case ( ($this->action == "search") && (!sizeof($this->search_terms)) ):
			$this->abort("Empty search");
	      case ( ($this->action == "fill") && (!sizeof($this->keys)) ):
			die();
	      case (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
	      case (!preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])):
			$this->abort("Direct request");
		}
        switch (TRUE) {
          case ($this->action == "method"):
			$results=$database->{$this->table}->{$this->method}($this->options);
            break;
          case ($this->action == "search"):
			$results=$database->{$this->table}->ajax($this->search_terms,$this->limit,$this->keys,$this->options);
            break;
            case ($this->action == "multi_fill"):
            $results=$database->{$this->table}->ajax_multi_fill($this->multi_items);
            break;
	      case (method_exists($database->{$this->table},"ajax_fill")):
			$results=$database->{$this->table}->ajax_fill($this->keys);
			break;
          default:
          	die();
		}
		if ($this->debug) {
			$content=array();
	        foreach ($_SERVER as $key => $value) {
	            $content[$key]=$value;
	        }
            $results[]=$content;
		}
    	die(json_encode($results, ($forms->version > 5.3) ? JSON_UNESCAPED_UNICODE : 0) );
	}
    function abort($error,$comment="") {
    global $database;
		$results=array();
        switch (TRUE) {
          case (is_array($comment)):
          	$results[]=implode("\n",$comment);
            break;
          case (strlen($comment)):
          	$results[]=$comment;
		}
        $results[]="Request method: " . $_SERVER['REQUEST_METHOD'];
	    foreach ($_REQUEST as $key => $value) {
	        $results[]="key: $key; value: " . (is_string($value)) ? htmlentities($value) : "";
	    }
		if ( (isset($database->log)) && (method_exists($database->log,"ipc")) ) $database->log->ipc("Ajax: $error",$results);
		header("HTTP/1.1 401 Unauthorized");
        die;
    }
}
?>