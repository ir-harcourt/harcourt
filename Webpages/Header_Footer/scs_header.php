<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2012 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.
*/
//error_reporting(E_ALL);
//get_magic_quotes_gpc() (should be off)
$database=new database();
include_once "scs_menu.php";
include_once "classes/user.php";
include_once "classes/document.php";
include_once "classes/log.php";
session_start();
$menu=new menu_class();
$database->connect();
if (!isset($_SESSION['initial'])) {
	$_SESSION['uemail']=$_COOKIE['email'];
	setcookie("email",$_SESSION['uemail'],strtotime("+1 year"));
	$database->user->read($_SERVER['REMOTE_ADDR'],"ip");
    if ($database->user->meta->rows) {
    	$_SESSION['user']=$database->user->data;
        $database->user->data->last_login=strtotime("now");
        $database->user->update(TRUE);
        $database->log->create("Initial","","","",$database->user->data->last_login);
	}
    $database->document->session();
	$_SESSION['initial']=TRUE;
	session_write_close();
	if (!$override_landing_page) $menu->landing_page();
}

class database {
	var $query_limit=5;
    function connect() {
		switch (TRUE) {
          case (isset($this->connection)):
          	return;
		  case (fn_development_server()):
			$this->connection = new connection_class("localhost","demo","suburban","harcourt");
	        break;
		  default:
			$this->connection = new connection_class("localhost", "p142rp4m_thayes", "DNLM359mkd57", "p142rp4m_harcourt");
		}
    	$this->connection->host_handle = @mysql_connect($this->connection->host, $this->connection->user, $this->connection->password);
        if (!$this->connection->host_handle) $this->error("mysql_connect","Cannot connect to host: $host");
    	$this->connection->database_handle = @mysql_select_db($this->connection->database,$this->connection->host_handle);
        if (!$this->connection->database_handle) $this->error("mysql_select_db","Cannot connect to database: $database");
	}
	function disconnect() {
	    if (!isset($this->connection)) return;
        @mysql_close($this->connection->host_handle);
        unset($this->connection);
	}
    function mysql_query($query) {
	    $result = @mysql_query($query);
	    if (!$result) $this->error("query",$query);
	    return $result;
    }
	function mysql_insert_id() {
	    $handle=$this->mysql_query("select last_insert_id();");
	    $mysql_fetch = mysql_fetch_array($handle);
	    return floatval($mysql_fetch[0]);
	}
	function found_rows($query) {
		$found_rows_query=str_replace("select","select SQL_CALC_FOUND_ROWS ",str_replace("\n"," ",$query));
		$handle = @mysql_query($found_rows_query);
		if (!$handle) return;
		$handle = @mysql_query("select found_rows()");
		if (!$handle) return;
		$fetch = mysql_fetch_array($handle);
		$this->meta->found_rows=intval($fetch[0]);
	}
    function free_result() {
        if ($this->meta->handle) $this->meta->handle=@mysql_free_result($this->meta->handle);
    }
    function query($query,$found_rows=FALSE,$debug=FALSE) {
		if ($debug) $this->query_view($query,TRUE);
		$this->meta->query=$query;
		if ($found_rows) $this->found_rows($query);
	    $this->meta->handle = @mysql_query($query);
	    if (!$this->meta->handle) $this->error("query",$query);
		$this->meta->rows=@mysql_num_rows($this->meta->handle);
        if (!$found_rows) $this->meta->found_rows=$this->meta->rows;
		$this->meta->error=mysql_errno();
    }
	function query_view($query,$debug=FALSE) {
	    if (($_SESSION['user']->type == "Administrator") || ($debug)) print "<!--\n\n$query\n\n-->\n";
	}
    function error($command,$text="") {
    	if (mysql_errno() != 1062) {
	        print "<b>Database error #" . mysql_errno() . "</b><br>\n";
            print mysql_error() . "<br><br>";
	        if ($text) print str_replace("\n","<br>",$text);
	        die;
        }
    }
    function where($data,$parenthesis=FALSE) {
    	$where="";
        if (!is_array($data)) return "";
        foreach ($data as $key_key => $item) {
	        switch (TRUE) {
	          case (($item->operator == 'in') && (!is_array($item->value))):
	          case (($item->operator == 'in') && (!sizeof($item->value))):
	          case (($item->operator == 'match') && (!is_array($item->value))):
	          case (($item->operator == 'match') && (!sizeof($item->value))):
	            continue;
	          case (!$where):
	            $where = "where ";
                if ($parenthesis) $where .= "(";
	            break;
	          case ($item->and_or):
	            $where .= " or ";
	            break;
	          default:
	            $where .= " and ";
	        }
	        switch (TRUE) {
	          case ($item->operator == 'in'):
	            $text="";
	            foreach ($item->value as $value) {
	                if ($text) $text .= ",";
	                $text .= fn_escape($value);
	            }
	            $where .= $item->field . " in ($text)";
	            break;
	          case ($item->type == ""):
	          case ($item->type == "constant"):
	            $where .= $item->field . " " . $item->operator . " \"" . mysql_escape_string($item->value) . "\"";
	            break;
	          case ($item->type == "field"):
	            $where .= $item->field . $item->operator . $item->value;
	            break;
              case ($item->operator == "match"):
	            $text="";
	            foreach ($item->value as $value) {
	                if ($text) $text .= " ";
	                $text .= $value;
	            }
              	$where .= "match (" . $item->field . ") against ('$text' " . $item->type . ") \n";
            	break;
	          default:
	            print "Invalid query type: " . $item->type . "<br>Field: " . $item->field;
	            die;
	        }
            $where .= "\n";
        }
		if ($parenthesis) $where .= ")\n";
        return $where;
    }
    function page_limit($limit) {
    	if (intval($limit) < 1) $limit=1;
		return "\nlimit " . (($limit - 1) * $this->query_limit) . "," . $this->query_limit;
    }
    function item_limit($limit) {
    	if (intval($limit) < 1) $limit=1;
		return "\nlimit " . ($limit - 1) . ",1";
    }
}
class meta_class {
    var $query;
    var $handle;
    var $rows;
    var $found_rows;
}
class connection_class {
    var $host;
    var $user;
    var $password;
    var $database;
    var $host_handle;
	var $database_handle;
	public function __construct($host, $user, $password, $database) {
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
    public function __construct($field,$operator,$value,$and_or="",$type="") {
	    $this->field=$field;
	    $this->operator=strtolower($operator);
	    $this->value=$value;
	    $this->and_or=strtolower($and_or);
	    $this->type=strtolower($type);
    }
}
class database_template_class extends database {
	var $fetch=array();
	public function database_template_class() {
    	$this->meta=new meta_class();
    }
    public function fetch() {
		$this->fetch=@mysql_fetch_array($this->meta->handle);
	}
}
function fn_escape($text,$type="") {
	switch (TRUE) {
	  case (func_num_args() == 1):
      case (strlen($text)):
      	break;
	  case ($type==TRUE):
      case ($type=="date"):
		$text="0000/00/00";
        break;
      case ($type==FALSE):
		$text="0";
	}
	return "'" . mysql_escape_string($text) . "'";
}
function fn_escape_date($text) {
	if (!$text) $text="0000/00/00";
    return fn_escape($text);
}
function fn_html_value($html_text, $html_action="", $html_option="", $html_error="") {
	$html_return="";
    if ($html_error) $html_return = " " . $html_error;
    switch (TRUE) {
      case ($html_action == "date"):
    	$html_value=fn_date($html_option,$html_text);
        break;
      case (($html_action == "numeric") && (floatval($html_text))):
    	$html_value=number_format($html_text,$html_option);
        break;
      case ($html_text != ""):
    	$html_value=htmlspecialchars($html_text, ENT_QUOTES);
        break;
	}
    if ($html_value != "") $html_return .= " value='$html_value' ";
    return $html_return;
}
function fn_url($page,$secure=FALSE,$data=array()) {
	if (!is_array($data)) $data=array();
    if ($secure) {
    	$http="https://";
	  } else {
    	$http="http://";
    }
    $text="";
    foreach ($data as $key => $value) {
    	if ($value) {
        	if (!$text) {
	            $text .= "?";
	          } else {
	            $text .= "&";
            }
			$text .= $key . "=" . urlencode($value);
        }
    }
    return $http . $_SERVER['HTTP_HOST'] . $page . $text;
}
function fn_url_redirect($page) {
	if (is_object($page)) {
		header("Location: ". $page->url);
	  } else {
		header("Location: ". $page);
	}
}
function fn_number($input) {
    return floatval(ereg_replace("[^0-9.-]", "",$input));
}
function fn_time($action,$time) {
	$time_return=date(strtotime("00:00:00"));
	switch (TRUE) {
	  case (!$time):
		break;
      case ($action=="mysql"):
		$time_return=strtotime("01/01/2007 " . $time);
		break;
      case ($action=="ampm"):
		$time_return=date("h:i A",$time);
		break;
      case ($action=="hhmm"):
		$time_return=date("H:i",$time);
		break;
    }
    return $time_return;
}
function fn_date($date_action,$date_in) {
    global $date_ok;
    $date_data = split('[ /.-]', $date_in);
    $date_out = "";
    $date_ok=1;
    switch (TRUE) {
      case ($date_in == ""):
      case ($date_in == "0"):
      case ($date_in == "0000-00-00"):
      case ($date_in == "0000/00/00"):
      case ($date_in == "0000-00-00 00:00:00"):
      case (($date_in == "00000000000000") && ($date_action == "timestamp")):
        break;
      case ($date_action == "mysql"):
        $date_out = $date_data[0] . "/" . $date_data[1] . "/" . $date_data[2];
        if ($date_data[3]) $date_out .=  " " . $date_data[3];
        break;
      case ($date_action == "datetime"):
        $date_out = $date_data[1] . "/" . $date_data[2] . "/" . $date_data[0] . " " . $date_data[3];
        break;
      case ($date_action == "ymd"):
        $date_out = $date_data[1] . "/" . $date_data[2] . "/" . $date_data[0];
        break;
      case ($date_action == "yymm"):
        $date_out = $date_data[1] . "/" . $date_data[0];
        break;
      case ($date_action == "ymd_timestamp"):
        $date_out = $date_data[0] . $date_data[1] . $date_data[2] . "000000";
        break;
      case ($date_action == "mdy"):
      case ($date_action == "mmyy"):
        $date_data[0] += 0;
        $date_data[1] += 0;
        $date_data[2] += 0;
        if ($date_action == "mmyy") {
            $date_data[2]=$date_data[1];
            $date_data[1]=1;
        }
        if ($date_data[0] < 10) $date_data[0] = "0" . $date_data[0];
        if ($date_data[1] < 10) $date_data[1] = "0" . $date_data[1];
        switch(TRUE) {
          case ($date_data[2] < 50):
            $date_data[2] += 2000;
            break;
          case ($date_data[2] < 100):
            $date_data[2] += 1900;
            break;
        }
        $date_ok=checkdate($date_data[0],$date_data[1],$date_data[2]);
        if (($date_data[2] < 1800) | ($date_data[2] > 2200)) $date_ok=0;
        if ($date_ok) $date_out = $date_data[2] . "/" . $date_data[0] . "/" . $date_data[1];
        break;
      case ($date_action == "timestamp"):
        $date_out  = $date_out  = substr($date_in,4,2) . "/" . substr($date_in,6,2) . "/" . substr($date_in,0,4);
        if (substr($date_in,8,6)) $date_out .= " " . substr($date_in,8,2) . ":" . substr($date_in,10,2) . ":" . substr($date_in,12,2);
        break;
      case (($date_action == "unix") && ($date_in)):
      	$date_out=date("m/d/Y g:i A", $date_in);
        break;
      case ($date_action == "update_code"):
      	$date_out=substr($date_in,4,2) . "/" . substr($date_in,6,2) . "/" . substr($date_in,0,4) . " " . substr($date_in,8,2) . ":" . substr($date_in,10,2) . ":" . substr($date_in,12,2);
    	break;
    }
    return $date_out;
}
function fn_pre($data) {
	switch (TRUE) {
      case (is_null($data)):
      	print "<p>Null Object</p>\n";
        break;
      case (is_object($data)):
	    print "<pre>\n";
	    print_r($data);
        print_r(get_class_methods(get_class($data)));
	    print "</pre>\n";
		break;
	  case (is_array($data)):
	    print "<pre>\n";
	    print_r($data);
	    print "</pre>\n";
		break;
	  default:
      	print "<p>" . str_replace("\n","<br>",$data) . "</p>\n";
	}
}
function fn_stripslashes($value) {
	switch (TRUE) {
	  case (!get_magic_quotes_gpc()):
      case (!value):
      case ((is_array($value)) && (!sizeof($value))):
		return $value;
      case (is_array($value)):
		$array_return=array();
        foreach ($value as $array_key => $array_value) {
        	$array_return[$array_key]=stripslashes($array_value);
        }
        return $array_return;
	  default:
		return stripslashes($value);
	}
}
function encrypt_data($action,$data) {
	if (!php_module_installed("mcrypt")) return $data;
	$code="The quick brown fox jumped over the lazy dog";
    $td=mcrypt_module_open('des', '', 'ecb', '');
    $key=substr($code, 0, mcrypt_enc_get_key_size($td));
    $iv_size=mcrypt_enc_get_iv_size($td);
    $iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
	mcrypt_generic_init($td, $key, $iv);
    switch ($action) {
      case "encrypt":
        $results = mcrypt_generic($td, $data);
        break;
      case "decrypt":
        $results = mdecrypt_generic($td, $data);
        break;
      default:
      	$results="";
    }
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return $results;
}
function php_module_installed($module) {
	ob_start();
	phpinfo(INFO_MODULES);
	$data = explode("\n",strip_tags(ob_get_contents(),"<h2>"));
	ob_end_clean();
	if (in_array("<h2>" . strtolower($module) . "</h2>",$data)) {
		return TRUE;
      } else {
      	return FALSE;
    }
}
function fn_development_server() {
	switch (TRUE) {
	  case ($_SERVER['SERVER_NAME']=="localhost"):
	  case ($_SERVER['SERVER_NAME']=="192.168.1.100"):
	  case ($_SERVER['SERVER_NAME']=="173.162.39.54"):
        return TRUE;
	  default:
		return FALSE;
    }
}
class email_class {
	var $address;
    var $error;
    function __construct($email,$forced=FALSE) {
		$this->address=strtolower($email);
		$this->error="";
        switch (TRUE) {
          case ((!$this->address) && (!$forced)):
          	return;
          case (!$this->address):
          	$this->error="Email address cannot be blank";
        	return;
        }
	    $email_chars=array(" ",",","?","!",":",";");
	    foreach ($email_chars as $value) {
	        if (strpos($this->address,$value)) {
	            $this->error="Invalid character(s) found: $value";
	            return;
            }
	    }
	    if (substr_count($this->address,"@") <> 1) {
	        $this->error= "Invalid email format";
	        return;
	    }
	    $email_pos=strpos($this->address, "@");
	    $email_mailbox=substr($this->address, 0, $email_pos);
	    $email_domain=substr($this->address, $email_pos + 1);
	    $email_pos=strrpos($email_domain, ".");
	    $email_domain_prefix=substr($email_domain, 0, $email_pos);
	    $email_domain_suffix=substr($email_domain, $email_pos + 1);
	    if ((!$email_mailbox) || (!$email_domain_prefix) || (!$email_domain_suffix)) $this->error="Invalid mailbox/domain";
    }
}
class menu_page {
	var $page;
    var $secure;
    var $name;
    var $data;
    var $url;
    function menu_page($page,$secure=FALSE,$name="",$data=array()) {
    	$this->page=$page;
        $this->secure=$secure;
        $this->name=$name;
        $this->data=$data;
        $this->url=fn_url($page,$secure,$data);
    }
}
?>