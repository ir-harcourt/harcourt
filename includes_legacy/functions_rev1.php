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
$LOCAL_URL=explode("/",$_SERVER['HTTP_HOST']);
function fn_development_server() {
	switch (TRUE) {
	  case ($_SERVER['SERVER_ADMIN']=="host@suburbancomputer.com"):
        return TRUE;
	  default:
		return FALSE;
    }
}
function scs_version() {
    $results=array();
    $results['06/11/2015']="Add role_access";
    $results['05/30/2015']="Add mod10 support";
    $results['03/31/2015']="Expand fn_url";
    $results['03/06/2015']=array("mysqli support","Add date:ymd8");
    $results['02/02/2015']=array("Add percent to fn_number","Add fn_next_poll");
    $results['12/16/2014']=array("Drop linefeed from fn_href link","Add debug feature to jquery_tab_class");
    $results['08/21/2014']=array("Remember viewed jquery_tab_class tabs","Change menu_group_class output");
    $results['07/30/2014']=array("Revise jquery_tab_class");
    $results['06/05/2014']=array("Add import_match","Add fn_text");
    $results['04/07/2014']=array("Add jquery_tab_class","Drop jump_item","address_verify prints/returns results");
    $results['03/17/2014']=array("Add preg_replace_class");
    $results['02/21/2014']=array("fn_url parse mailto query");
    $results['01/13/2014']=array("Add bookmark to fn_href");
    $results['10/29/2013']=array("Add email type","Add menu_page & menu_group_class");
    $results['08/15/2013']="Add decimal to fn_number";
    $results['05/06/2013']="Add user_login function";
    $results['04/22/2013']="Bug: fn_number ignored sign";
    $results['04/06/2013']="Add functions scs_verify_folder, scs_filename";
    $results['02/20/2013']=array("Add forced domain to email_class","Add target to fn_url_redirect","Allow for 0 $database->query_limit","Add fn_name","Add registry->login->ssl");
    $results['01/04/2013']="Add date:unix_timestamp, date:timestamp_unix";
    $results['12/20/2012']="Add null href (#) fn_url";
    $results['11/30/2012']="Add fn_sentence_trim";
    $results['10/08/2012']="Initial release";
	$results['file']= __FILE__;
    return $results;
}
function fn_url_query($url_query,$raw=FALSE) {
	if (!is_array($url_query)) return;
	$results=array();
    foreach ($url_query as $key => $value) {
		switch (TRUE) {
          case (!strlen($value)):
			break;
		  case ($raw):
            $results[]="{$key}=" . rawurlencode($value);
            break;
          default:
            $results[]="{$key}=" . urlencode($value);
        }
    }
    if (sizeof($results)) return "?" . implode("&",$results);
}
function fn_url($page,$url_query=array(),$secure=FALSE,$raw=FALSE) {
global $database, $LOCAL_URL;
    switch (TRUE) {
      case (is_object($page)):
		return $page->url . fn_url_query($url_query);
      case (preg_match("/^http:/i", $page)):
      case (preg_match("/^https:/i", $page)):
      case (preg_match("/^javascript:/i", $page)):
		return $page . fn_url_query($url_query);
      case (preg_match("/^mailto:/i", $page)):
		return $page . fn_url_query($url_query,TRUE);
	  case ($page=="#"):
		return $page;
	}
    $results=array();
    switch (TRUE) {
      case ( ($secure) && (isset($database->registry->login->ssl)) ):
        $results[]="https";
		break;
      case ($secure):
	    $results[]="HTTP";
	    break;
	  default:
        $results[]="http";
    }
    $results[]="://";
    $results[]=implode("/",$LOCAL_URL);
	$url=parse_url($page);
    switch (TRUE) {
	  case (!strlen($url['path'])):
		return;
	  case ( (isset($LOCAL_URL[1])) && (preg_match("/^(\\/|\s*)?" . $LOCAL_URL[1] . "\//",$url['path'])) ):
        $results[]=preg_replace("/^(\\/|\s*)?" . $LOCAL_URL[1] . "/","",$url['path']);
		break;
	  default:
        $results[]=preg_replace("/^(\\/|\s*)?/","/",$url['path']);
    }
    if (isset($url['query'])) {
		parse_str($url['query'],$page_query);
		$url_query=array_merge($page_query,$url_query);
    }
    $results[]=trim(fn_url_query($url_query,$raw));
    return trim(implode("",$results));
}
function fn_url_redirect($page,$url_query=array(),$secure=FALSE,$target="") {
	switch (TRUE) {
 	  case (is_object($page)):
    	$url=$page->url;
		$url_target=$page->target;
        break;
	  case (preg_match("/^http/i", $page)):
    	$url=$page . fn_url_query($url_query);
        break;
	  default:
    	$url=fn_url($page,$url_query,$secure=FALSE);
        break;
	}
    if ($target) $url_target=$target;
	if ($url_target) {
		print "<script language=\"javascript\">\n";
	    print "window.open('" . $url . "','" . $url_target . "')\n";
	    print "</script>";
        exit();
	  } else {
    	header("Location: " . $url);
        die;
    }
}
function fn_href($name,$url,$url_query=array(),$forms_options=array(),$secure=FALSE) {
global $forms;
	$results=array();
	switch (TRUE) {
      case (is_null($name)):
      	break;
	  case (!$url):
	  	$results[]="<b>$name</b>";
        break;
	  default:
		if ($name == "&nbsp;") $name="";
		$data=array();
        $data[]=trim(fn_url($url,$url_query,$secure));
		if (isset($forms_options['bookmark'])) {
        	$data[]="#" . $forms_options['bookmark'];
			unset($forms_options['bookmark']);
		}
        $results[]="<a href=\"" . implode("",$data) . "\" " . trim($forms->options("",$forms_options)) . ">$name</a>";
	}
    return implode("\n",$results);
}
function fn_mailto($email,$subject="") {
	$results=array();
    $results[]="mailto:";
    $results[]=$email;
    if ($subject) $results[]="?subject=" . $subject;
	return fn_href($email,implode("",$results));
}
function fn_name($name,$text="") {
	return "<a name=" . fn_escape($name) . ">$text</a>\n";
}
function fn_escape($text,$type="") {
global $mysqli;
	switch (TRUE) {
      case (is_array($text)):
		return;
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
    if (isset($mysqli)) {
        return "'" . $mysqli->real_escape_string($text) . "'";
      } else {
		return "'" . mysql_real_escape_string($text) . "'";
    }
}
function fn_text($text,$uppercase=FALSE) {
global $database;
	$text=preg_replace("/(<script)(.*)(>)/i","",$text,-1,$blacklist);
    switch (TRUE) {
	  case (!$blacklist):
      case (!isset($database->registry->www->blacklist_script)):
      case (!isset($database->blacklist)):
      case (!method_exists($database->blacklist,"blacklist_script_injection")):
		break;
      default:
		$database->blacklist->blacklist_script_injection();
	}
    if ($uppercase) {
    	return strtoupper(trim($text));
	  } else {
		return trim($text);
	}
}
function fn_number($text,$decimal=0) {
    if (preg_match("/\%/",$text)) {
    	$multiplier=.01;
	  } else {
		$multiplier=1;
	}
    $results=floatval(preg_replace("/[^0-9.-]*/ ","",$text));
    if 	(func_num_args() > 1) $results=number_format($results,$decimal,".","");
    return floatval($results * $multiplier);
}
function fn_pre($data,$die_info=FALSE) {
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
	if ($die_info) die();
}
function fn_date($text,$action) {
global $forms, $date_ok;
    $data=preg_split("/[ \/\.-]/",$text);
    $results = "";
    $date_ok=TRUE;
    switch (TRUE) {
      case ($text == ""):
      case ($text == "0"):
      case ($text == "0000-00-00"):
      case ($text == "0000/00/00"):
        break;
      case ($action == "mysql"):
        $results = $data[0] . "/" . $data[1] . "/" . $data[2];
        break;
      case ($action == "ymd"):
        $results = $data[1] . "/" . $data[2] . "/" . $data[0];
        break;
      case ($action == "yymm"):
        $results=$data[1] . "/" . $data[0];
        break;
      case ($action == "unix_timestamp"):
      	if (date("H:i:s",$text) == "00:00:00") {
			$results=date("m/d/Y",$text);
		  } else {
			$results=date("m/d/Y H:i:s",$text);
		}
        break;
      case ($action == "timestamp_unix"):
		$results=strtotime($text);
        break;
      case ($action == "unix_ymd"):
		$results=date("m/d/Y",$text);
        break;
      case ($action == "ymd8"):
		$text=substr("00000000" . trim($text),-8);
		$data=array();
	    $data['yyyy']=substr($text,0,4);
	    $data['mm']=substr($text,4,2);
	    $data['dd']=substr($text,6,2);
        $date_ok=checkdate($data['mm'],$data['dd'],$data['yyyy']);
		if (($data['yyyy'] < 1800) | ($data['yyyy'] > 2200)) $date_ok=FALSE;
        if ($date_ok) $results = $data['yyyy'] . "/" . $data['mm'] . "/" . $data['dd'];
      	break;
      case ($action == "mdy"):
      case ($action == "mmyy"):
        $data[0] += 0;
        $data[1] += 0;
        $data[2] += 0;
        if ($action == "mmyy") {
            $data[2]=$data[1];
            $data[1]=1;
        }
        if ($data[0] < 10) $data[0] = "0" . $data[0];
        if ($data[1] < 10) $data[1] = "0" . $data[1];
        switch(TRUE) {
          case ($data[2] < 50):
            $data[2] += 2000;
            break;
          case ($data[2] < 100):
            $data[2] += 1900;
            break;
        }
        $date_ok=checkdate($data[0],$data[1],$data[2]);
		if (($data[2] < 1800) | ($data[2] > 2200)) $date_ok=FALSE;
        if ($date_ok) $results = $data[2] . "/" . $data[0] . "/" . $data[1];
        break;
      case ($action == "unix_mdy"):
		if (strtotime($text) < 0) {
			$date_ok=FALSE;
		  } else {
			$results=strtotime($text);
		}
        break;
	  default:
		print "Invalid date action: $action text: $text";
		scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
        die;
    }
    $forms->date_ok=$date_ok;
    return $results;
}
function verify_file($file,$type,$required=FALSE) {
/* Always store files in this format
$_SERVER['DOCUMENT_ROOT'] . "/" . $file;
*/
	switch (TRUE) {
	  case ((!$file) && (!$required)):
	    return;
	  case (($type) && ($type != end(preg_split("/\./",strtolower($file))))):
		return "{$file} not type .{$type}";
	  case (!$file):
	    return "Cannot be blank";
	  case (substr($file,0,1) != "/"):
	    return "{$file} must begin with /";
	  case (!file_exists($_SERVER['DOCUMENT_ROOT'] . $file)):
    	return "{$file} not found";
	}
}
function scs_verify_folder($folder,$required=FALSE) {
	switch (TRUE) {
	  case ((!$folder) && (!$required)):
	    return;
	  case (!$folder):
	    return "Folder name cannot be blank";
	  case (substr($folder,0,1) != "/"):
      case (substr($folder,-1,1) != "/"):
	  case (substr($folder,0,2) == "//"):
	  case (substr($folder,-2,2) == "//"):
		return "Folder names must begin and end with a single /";
	  case (!is_dir(scs_filename($folder))):
    	return "Folder not found";
	}
}
function scs_filename($folder,$filename="") {
	return $_SERVER['DOCUMENT_ROOT'] . $folder . $filename;
}
function fn_sentence_trim($text,$count,$words=FALSE) {
	$results="";
    $text=trim($text);
	$offset=strlen($text);
	$data=str_word_count($text,2);
    switch (TRUE) {
      case (!$count):
      case (($words) && ($count >= sizeof($data))):
		return $text;
      case ($words):
		reset($data);
		for ($i=0; $i < $count; $i++) {
			next($data);
        }
		$offset=key($data);
	  	break;
	  default:
        foreach ($data as $key => $value) {
			if ($key > $count) break;
            $offset=$key;
        }
	}
    $results=trim(substr($text,0,$offset));
    switch (TRUE) {
      case ($results==$text):
        break;
      case (substr($results,-1,1) == "."):
        $results .= "..";
        break;
      default:
        $results .= " ...";
        break;
    }
	return $results;
}
function scs_function_debug($file,$method,$count,$options=array()) {
//usage: scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
    print "\n<!-- \n";
    print "File: $file\n";
    print "Method: $method\n";
    print "Arguments: $count\n";
    fn_pre($options);
    print "-->\n";
}
function url_sort($a,$b) {
    switch (TRUE) {
      case ((substr_count($a,"/") == substr_count($b,"/")) && ($a > $b)):
      case (substr_count($a,"/") > substr_count($b,"/")):
        return TRUE;
      default:
        return FALSE;

    }
}
function encrypt_data($action,$data) {
global $database;
	switch (TRUE) {
      case (!$database->registry->login->encrypt):
      case (!$data):
      	return;
	}
    $code=$database->registry->login->encrypt;
    $td=mcrypt_module_open('des', '', 'ecb', '');
    $key=substr($code, 0, mcrypt_enc_get_key_size($td));
    $iv_size=mcrypt_enc_get_iv_size($td);
    $iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
    mcrypt_generic_init($td, $key, $iv);
    switch ($action) {
      case "encrypt":
        $results=mcrypt_generic($td, $data);
        break;
      case "decrypt":
        $results=trim(mdecrypt_generic($td, $data));
        break;
      default:
        $results="";
    }
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return $results;
}
function import_match($text,$data) {
	$key=array_search(strtolower($text),array_map('strtolower',$data));
	if (strlen($key)) {
	    return $data[$key];
	  } else {
	    return $text;
	}
}
function fn_next_poll($increment="") {
	return date("m/d/Y g:i:s A",strtotime("now $increment"));
}
function mod10_verify($data) {
	if ($data == mod10_calculate(substr($data,0,-1)) ) return TRUE;
}
function mod10_calculate($data) {
	$items=str_split($data);
    krsort($items);
    $multiplier=3;
    $bcc=0;
    foreach ($items as $value) {
    	$bcc += ($value * $multiplier);
        if ($multiplier == 1) {
        	$multiplier=3;
		  } else {
        	$multiplier=1;
        }
	}
	$bcc=substr(10 - ($bcc % 10), -1);
	return $data . $bcc;
}
function role_access($role,$role_minimum) {
global $database;
	$roles=array();
	switch (TRUE) {
	  case (!isset($database->user)):
		break;
	  case (isset($database->user->constant->role)):
		$roles=$database->user->constant->role;
        break;
	  case (isset($database->user->constant->type)):
		$roles=$database->user->constant->type;
        break;
    }
	switch (TRUE) {
	  case (!sizeof($roles)):
	  case (!$role):
		return FALSE;
	  case (!$role_minimum):
		return TRUE;
	  case (!is_array($roles)):
	  case (!in_array($role,$roles)):
	  case (!in_array($role_minimum,$roles)):
	  case (array_search($role,$roles) < array_search($role_minimum,$roles)):
		return FALSE;
	  default:
		return TRUE;
    }
}
class email_class {
	var $address;
    var $account;
    var $domain;
    var $error;
    function __construct($email,$forced=FALSE,$domain="") {
		$domains=array();
		switch (TRUE) {
		  case (func_num_args() < 3):
			break;
		  case (is_array($domain)):
			$domains=$domain;
            break;
		  default:
			$domains[]=$domain;
		}
		$this->address=strtolower(trim($email));
        $address_parse=explode("@",$this->address);
        if (is_array($address_parse)) $domain_parse=explode(".",$address_parse[1]);
        switch (TRUE) {
          case ((!$this->address) && (!$forced)):
          	break;
          case (!$this->address):
          	$this->error="Email address cannot be blank";
        	break;
          case (preg_match("/[ \/\\\\,?!:;]/",$this->address)):
	        $this->error="Address contains invalid characters";
	        break;
          case (!is_array($address_parse)):
          case (sizeof($address_parse) != 2):
	        $this->error="Invalid email format";
			break;
          case (!$address_parse[0]):
	        $this->error="Account missing";
			break;
          case (!$address_parse[1]):
          case (!is_array($domain_parse)):
		  case (!$domain_parse[0]):
          case (strlen($domain_parse[1]) < 2):
			$this->error="Invalid domain";
            break;
          case ( (sizeof($domains)) && (!in_array($address_parse[1],$domains)) ):
	        $this->error="Address must be in domain " . implode(", ",$domains);
			break;
	    }
    }
}
class page_item_break {
    var $found_rows;
    var $url;
    var $page=0;
    var $item=0;
    var $query_limit;
	var $comment=array();
    var $items=array();
	function __construct($found_rows,$url="",$options=array()) {
	global $database;
        $this->found_rows=$found_rows;
		$this->url=$url;
        if (array_key_exists("debug",$options)) {
        	$this->query_limit=3;
		  } else {
        	$this->query_limit=$database->query_limit;
		}
	    $this->items["First"]=array("text"=>"<< First");
	    $this->items["Previous"]=array("text"=>"< Previous");
	    $this->items["Return"]=array("text"=>"");
	    $this->items["Next"]=array("text"=>"Next >");
	    $this->items["Last"]=array("text"=>"Last >>");
    }
    function return_url($text,$url,$item=0) {
    	switch (TRUE) {
          case (!$this->query_limit):
          	break;
          case (func_num_args() == 3):
          	str_replace(array("%page%"),ceil(($item + 1) / $this->query_limit),$url);
		}
	    $this->items["Return"]=array("text"=>$text,"url"=>$url);
    }
    function page($page,$comment="") {
    	switch (TRUE) {
          case (!$this->query_limit):
			print "<p class='standard center'>" . number_format($this->found_rows) . " Records Found" . "</p>\n";
          	return;
          case (!$this->url):
          	$this->url="fn_page_item('list',%page%,0);";
		}
		$this->page=$page;
	    if (!$this->page) $this->page=1;
        $this->comment[1]=number_format($this->found_rows) . " Records Found";
		if ($comment) $this->comment[3]=$comment;
        $pages=ceil($this->found_rows / $this->query_limit);
    	$this->results("Page",$this->page,$pages);
	}
    function item($item,$comment="") {
		if (!$this->url) $this->url="fn_page_item('list',0,%item%);";
		switch (TRUE) {
          case ($item < 1):
          	$this->item=1;
            break;
          case ($item > $this->found_rows):
          	$this->item=$this->found_rows;
            break;
          default:
			$this->item=$item;
		}
		$this->page=(floor($this->item / $this->query_limit) + 1);
		$this->items['Return']['url']=str_replace("%page%",$this->page,$this->items['Return']['url']);
        if ($comment) $this->comment[0]=$comment;
        $this->results("Record",$this->item,$this->found_rows);
    }
    private function results($title,$current,$last) {
		global $forms;
	    if ($current > 1) {
	        $this->items['First']['item']=1;
	        $this->items['Previous']['item']=$current - 1;
	    }
	    if ($last > $current) {
	        $this->items['Next']['item']=$current + 1;
	        $this->items['Last']['item']=$last;
	    }
		$button_text="";
		$button=FALSE;
	    foreach ($this->items as $field => $item) {
	        switch (TRUE) {
	          case ($item['url']):
	            $url=$item['url'];
	            break;
              case (!isset($item['item'])):
	          case ($item['item'] < $first):
	          case ($item['item'] > $last):
	          case ($item['item'] == $current):
	            $url="";
	            break;
	          default:
				$url=preg_replace(array("/%item%/i","/%25item%25/i"),$item['item'],$this->url);
	        }
            $options=array();
	        if ($url) {
            	$button=TRUE;
            	$options["onclick"]=preg_replace(array("/%item%/i","/%25item%25/i","/%page%/i","/%25page%25/i"),$item['item'],$url);
			  } else {
				$options["class"]="nav_button_disabled";
            }
			if ($item['text']) $button_text .= $forms->button($item['text'],$options);
	    }
		$output="<p class='standard center'>";
		if ($button) {
        	$output .= $button_text;
			$this->comment[2]="$title " . number_format($current) . " of " . number_format($last);
		}
        ksort($this->comment);
        foreach ($this->comment as $comment) {
			$output .= "<br>$comment\n";
        }
		$output .= "</p>\n";
		print $output;
	}
}
function address_verify($table,$data,$update=FALSE,$mandatory=array(),$output=TRUE) {
global $database,$forms;
    if (!sizeof($mandatory)) {
        foreach ($database->registry->address->{$table} as $field => $value) {
            if ($value=="required") $mandatory[]=$field;
        }
    }
    if (array_key_exists("name_first",$data) && array_key_exists("name_last",$data)) {
		$first_last=TRUE;
	  } else {
      	$first_last=FALSE;
	}
    if ($update) {
        $data->country_code=fn_text($_POST['country_code']);
        switch ($data->country_code) {
          case "US":
            $state_field="state_us";
            break;
          case "CA":
            $state_field="state_ca";
            break;
          default:
            $state_field="state_other";
        }
        switch (TRUE) {
          case (!$database->registry->address->{$table}['name']):
			break;
          case ($first_last):
            $data->name_first=fn_text($_POST['name_first']);
            $data->name_last=fn_text($_POST['name_last']);
            $data->name=trim($data->name_first . " " . $data->name_last);
            if (in_array("name",$mandatory)) {
				if (!$data->name_first) $forms->error("name_first");
				if (!$data->name_last) $forms->error("name_last");
            }
			break;
          default:
            $data->name=fn_text($_POST['name']);
            if ((in_array("name",$mandatory)) && (!$data->name)) $forms->error("name");
        }
        if ($database->registry->address->{$table}['title']) {
            $data->title=fn_text($_POST['title']);
            if ((in_array("title",$mandatory)) && (!$data->title)) $forms->error("title");
        }
        if ($database->registry->address->{$table}['company_name']) {
            $data->company_name=fn_text($_POST['company_name']);
            if ((in_array("company_name",$mandatory)) && (!$data->company_name)) $forms->error("company_name");
        }
        if ($database->registry->address->{$table}['address']) {
            $data->address=fn_text($_POST['address']);
            if ((in_array("address",$mandatory)) && (!$data->address)) $forms->error("address");
        }
        if ($database->registry->address->{$table}['city']) {
            $data->city=fn_text($_POST['city']);
            if ((in_array("city",$mandatory)) && (!$data->city)) $forms->error("city");
        }
        if ($database->registry->address->{$table}['state']) {
            $data->state=fn_text($_POST[$state_field]);
            if ((in_array("state",$mandatory)) && (!$data->state)) $forms->error($state_field);
        }
        if ($database->registry->address->{$table}['zip']) {
            $data->zip=fn_text($_POST['zip']);
            if ((in_array("zip",$mandatory)) && (!$data->zip)) $forms->error("zip");
        }
      } else {
		$results=array();
        if ($database->registry->address->{$table}['name']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("name",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            if ($first_last) {
            	$results[]="First/Last Name";
			  } else {
            	$results[]="Name";
			}
            $results[]="</td>";
            $results[]="<td>";
            if ($first_last) {
				$results[]=$forms->text("name_first",$data->name_first,30,40);
				$results[]=$forms->text("name_last",$data->name_last,30,40);
			  } else {
				$results[]=$forms->text("name",$data->name,40,40);
			}
            $results[]="</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['title']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("title",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Title</td>";
            $results[]="<td>" . $forms->text("title",$data->title,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['company_name']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("company_name",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Company Name</td>";
            $results[]="<td>" . $forms->text("company_name",$data->company_name,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['address']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("address",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Address</td>";
            $results[]="<td>" . $forms->textarea("address",$data->address,3,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['city']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("city",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="City</td>";
            $results[]="<td>" . $forms->text("city",$data->city,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['state']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("state",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="<span id=state_text></span></td>";
            $results[]="<td>";
            $results[]=$database->country->select("US",$data->state,"state_us");
            $results[]=$database->country->select("CA",$data->state,"state_ca");
            $results[]=$forms->text("state_other",$data->state,40,40,"",array("id"=>"state_other"));
            $results[]="</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['zip']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("zip",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="<span id=zip_text></span></td>";
            $results[]="<td>" . $forms->text("zip",$data->zip,25,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['country_code']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("country_code",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Country</td>";
            $results[]="<td>" . $database->country->select("",$data->country_code,"country_code",array("onchange"=>"fn_country_address();"));
            $results[]="</td>";
            $results[]="</tr>";
         } else {
            $results[]=$forms->hidden("country_code","US");
        }
        $results[]="<script type='text/javascript'>";
        $results[]="function fn_country_address() {";
        $results[]=" country_obj=document.getElementById('country_code');";
        $results[]=" switch (true) {";
        $results[]="   case (!country_obj):";
        $results[]="   return;";
        $results[]="  case (country_obj.value == 'US'):";
		$results[]="   state='State';";
		$results[]="   zip='ZIP Code';";
		$results[]="   div_show('state_us',1);";
		$results[]="   div_show('state_ca',0);";
		$results[]="   div_show('state_other',0);";
		$results[]="   break;";
		$results[]="  case (country_obj.value == 'CA'):";
		$results[]="   state='Province';";
		$results[]="   zip='Postal Code';";
		$results[]="    div_show('state_us',0);";
		$results[]="   div_show('state_ca',1);";
		$results[]="   div_show('state_other',0);";
		$results[]="   break;";
		$results[]="  default:";
		$results[]="   state='Province/State';";
		$results[]="   zip='Postal Code';";
		$results[]="   div_show('state_us',0);";
		$results[]="   div_show('state_ca',0);";
		$results[]="   div_show('state_other',1);";
		$results[]=" }";
		$results[]=" state_obj=document.getElementById('state_text');";
		$results[]=" if (state_obj) state_obj.innerHTML=state;";
		$results[]=" zip_obj=document.getElementById('zip_text');";
		$results[]=" if (zip_obj) zip_obj.innerHTML=zip;";
		$results[]="}";
		$results[]="fn_country_address();";
		$results[]="</script>";
	    if ($output) {
	        print implode("\n",$results);
	      } else {
	        return implode("\n",$results);
	    }
    }
}
function address_table($table,$data) {
global $database;
    $results=array();
    if ($data->name) {
	    $results[]="<tr>";
	    $results[]="<td>Name</td>";
	    $results[]="<td>" . $data->name . "</td>";
	    $results[]="</tr>";
    }
    if ($data->title) {
	    $results[]="<tr>";
	    $results[]="<td>Title</td>";
	    $results[]="<td>" . $data->title . "</td>";
	    $results[]="</tr>";
    }
    if ($data->company_name) {
	    $results[]="<tr>";
	    $results[]="<td>Company Name</td>";
	    $results[]="<td>" . $data->company_name . "</td>";
	    $results[]="</tr>";
    }
    if ($data->address) {
	    $results[]="<tr>";
	    $results[]="<td>Address</td>";
	    $results[]="<td>" . str_replace("","<br>",$data->address) . "</td>";
	    $results[]="</tr>";
    }
    if ($data->city) {
        $results[]="<tr>";
        $results[]="<td>City</td>";
        $results[]="<td>" . $data->city . "</td>";
        $results[]="</tr>";
    }
    switch (TRUE) {
      case (!$data->state):
      	break;
      case ($data->country_code=="US"):
        $results[]="<tr><td>State</td><td>" . $data->state . "</td></tr>";
        break;
      default:
        $results[]="<tr><td>Province</td><td>" . $data->state . "</td></tr>";
    }
    switch (TRUE) {
      case (!$data->zip):
      	break;
      case ($data->country_code=="US"):
        $results[]="<tr><td>Zip Code</td><td>" . $data->zip . "</td></tr>";
        break;
      default:
        $results[]="<tr><td>Postal Code</td><td>" . $data->zip . "</td></tr>";
    }
    if ($data->country_code) {
        $database->country->read($data->country_code);
        $results[]="<tr>";
        $results[]="<td>Country</td>";
        $results[]="<td>" . $database->country->data->name . "</td>";
        $results[]="</tr>";
    }
    return implode("\n",$results);
}
function address_html($table,$data) {
global $database;
    $results="";
    if ($data->name) $results .= "<br>Name: " . $data->name;
    if ($data->title) $results .= "<br>Title: " . $data->title;
    if ($data->company_name) $results .= "<br>Company Name: " . $data->company_name;
    if ($data->address) $results .= "<br>Address: " . str_replace("\n","<br>",$data->address);
    if ($data->city) $results .= "<br>City: " . $data->city;
	switch (TRUE) {
      case (!$data->state):
    	break;
      case ($data->country_code=="US"):
	    $results .= "<br>State: " . $data->state;
		break;
      default:
	    $results .= "<br>Province: " . $data->state;
    }
	switch (TRUE) {
      case (!$data->zip):
    	break;
      case ($data->country_code=="US"):
	    $results .= "<br>Zip Code: " . $data->zip;
		break;
      default:
	    $results .= "<br>Postal Code: " . $data->zip;
    }
    if (!in_array($data->country_code,array("","US"))) {
        $database->country->read($data->country_code);
        $results .= "<br>Country: " .$database->country->data->name;
    }
    return $results;
}
function user_login($update=FALSE,$administrator=FALSE) {
global $database,$forms;
    if ($update) {
		if (array_key_exists("username",$database->user->data)) {
	        $database->user->data->username=strtolower(trim($_POST['username']));
	        switch (TRUE) {
	          case (strlen($database->user->data->username) < 6):
	            $forms->error("username","Must contain at least 6 characters");
	            break;
	          case (preg_match("/[ !@#$%^&*()\/\\\]/",$database->user->data->username)):
	            $forms->error("username","Cannot contain special characters");
	            break;
	        }
		}
        $email=new email_class($_POST['email'],TRUE);
        $database->user->data->email=$email->address;
        if ($email->error) $forms->error('email',$email->error);

        $password=trim($_POST['password']);
        switch (TRUE) {
          case(($password == $database->user->constant->password) && (!$database->user->fetch['password'])):
          case ($password != $database->user->constant->password):
            $database->user->new_password=$password;
            if ($_SESSION['user']->id) $database->user->data->password_date=strtotime("now");
        }
        switch (TRUE) {
          case ((!$database->user->new_password) && ($database->user->fetch['password'])):
            break;
          case (($database->user->new_password==$database->user->constant->password) && (!$administrator)):
            $forms->error("password","Default password must be changed");
            break;
          case (strlen($database->user->new_password) < $database->registry->password->minimum):
            $forms->error("password","Password length must be at least " . $database->registry->password->minimum . " characters");
            break;
          case (strlen($database->user->new_password) > $database->registry->password->maximum):
            $forms->error("password","Password cannot excede " . $database->registry->password->maximum . " characters");
            break;
        }
		if ( (isset($_SESSION['myaccount'])) && (array_key_exists('password',$_SESSION['myaccount'])) ) {
	        $query=
	            "select password(" . fn_escape($database->user->new_password) . ") as new_password, " .
				"password as password " .
				"from user where id=" . fn_escape($_SESSION['user']->id);
			$database->temp->query($query);
            $database->temp->fetch(TRUE);
            $database->temp->free_result();
			switch (TRUE) {
			  case (!$database->user->new_password):
              case ($database->temp->fetch['new_password'] == $database->temp->fetch['password']):
				$forms->error("password","Password must be changed");
            }
		}
	    $database->user->data->auto_login=$_POST['auto_login'];
      } else {
		$results=array();
	    $results[]="<script type='text/javascript'>";
        if (isset($database->registry->password->js)) $results[]=$database->registry->password->js;
	    $results[]="function password_blank(obj) {";
	    $results[]="  if (obj.className) {";
	    $results[]="    obj.className='';";
	    $results[]="    obj.value='';";
	    $results[]="  }";
	    $results[]="}";
	    $results[]="</script>";
		if (array_key_exists("username",$database->user->data)) {
	        $results[]="<tr>";
	        $results[]="<td width>" . $forms->font("red","<b>*</b>"," User name") . "</td>";
	        $results[]="<td width>" . $forms->text("username",$database->user->data->username,20,20);
            if ($forms->error['username']) $results[]="<br><b>" . $forms->error_text['username'] . "</b>";
            $results[]="</td>";
	        $results[]="</tr>";
        }
	    $results[]="<tr>";
	    $results[]="<td width='30%'>" . $forms->font("red","<b>*</b>"," Email") . "</td>";
	    $results[]="<td width='70%'>" . $forms->text("email",$database->user->data->email,40,40,"email");
	    if ($forms->error_text['email']) $results[]="<br><b>" . $forms->error_text['email'] . "</b>";
	    $results[]="</td>";
	    $results[]="</tr>";
        if ($database->user->fetch['password']) {
            $password_text="Change password? " . $forms->checkbox("change_password",1,0,array("onclick"=>"document.scs_form.password.focus();"));
          } else {
            $password_text="Password";
        }
        $options=array();
        switch (TRUE) {
          case ($database->user->new_password):
            $password=$database->user->new_password;
            break;
          case ($database->user->fetch['password']):
            $password=$database->user->constant->password;
            $options["class"]="password_green";
            $options["onfocus"]="password_blank(this);";
            break;
          default:
            $password=$database->user->constant->password;
            $options["class"]="password_red";
            $options["onfocus"]="password_blank(this);";
        }
        if ($forms->error['password']) unset($options['class']);
        $results[]="<tr>";
        $results[]="<td>" . $forms->font("red","<b>*</b>"," $password_text") . "</td>";
        $results[]="<td>" . $forms->text("password",$password,20,$database->registry->password->maximum,"",$options);
        $results[]=" (" . $database->registry->password->minimum;
        if ($database->registry->password->minimum != $database->registry->password->maximum) $results[]="-" . $database->registry->password->maximum;
        $results[]=" characters, case sensitive)";
        if ($database->user->data->password_date) $results[]=" (Changed " . date("m/d/Y h:i A",$database->user->data->password_date) . ")";
        if ($forms->error_text['password']) $results[]="<br><b>" . $forms->error_text['password'] . "</b>";
        if ($database->registry->password->charset) $results[]="<br>" . fn_href("Generate random password","javascript:random_password();");
        $results[]="</td>";
        $results[]="</tr>";
		if ($database->registry->login->auto) {
	        $results[]="<tr>";
	        $results[]="<td>Auto login? " . $forms->checkbox("auto_login",1,$database->user->data->auto_login) . "</td>";
	        $results[]="<td>(Automatic access if last successful login was from the same browser on the same desktop)</td>";
	        $results[]="</tr>";
		}
		return implode("\n",$results);
	}
}
class menu_page {
    var $name;
    var $title;
	var $page;
    var $url_query=array();
    var $href_options=array();
    var $secure=FALSE;
    var $url;
    var $href;
    var $icon_small;
    var $icon_small_href;
    var $icon_large;
    var $icon_large_href;
    function __construct($name,$page,$url_query=array(),$href_options=array(),$secure=FALSE) {
        $this->name=$name;
		$this->page=$page;
		$this->url_query=$url_query;
        $this->href_options=$href_options;
        $this->secure=$secure;
		if (!array_key_exists("class",$this->href_options)) $this->href_options['class']="nobr";
        $this->build();
    }
    function name($name) {
		$this->name=$name;
        $this->build();
    }
    function title($title) {
		$this->title=$title;
    }
    function page($page,$url_query=array(),$href_options=array()) {
		$this->page=$page;
		$this->url_query=$url_query;
        $this->href_options=$href_options;
        $this->build();
    }
    function icon($icon,$img_options=array(),$large=TRUE) {
    global $forms;
		if (!isset($img_options['title'])) $img_options['title']=$this->name;
		if ($large) {
			$this->icon_large=$forms->img($icon,$img_options);
		  } else {
			$this->icon_small=$forms->img($icon,$img_options);
		}
	    $this->build();
    }
    private function build() {
    global $menu;
        switch (TRUE) {
          case preg_match("/^javascript:/", $this->page):
        	$this->url=$this->page;
			break;
          case preg_match("/^http:/", $this->page):
          case preg_match("/^https:/", $this->page):
        	$this->url=$this->page;
            $this->secure=FALSE;
			break;
          default:
			$this->url=fn_url($this->page,$this->url_query,$this->secure);
		}
        $this->href=fn_href($this->name,$this,array(),$this->href_options);
        if ($this->icon_small) {
			$this->icon_small_href=fn_href($this->icon_small . " " . $this->name,$this,array(),$this->href_options);
		  } else {
          	$this->icon_small_href="";
        }
        if ($this->icon_large) {
			$options['title']=$this->name;
			$this->icon_large_href=fn_href($this->icon_large,$this,array(),$this->href_options);
		  } else {
          	$this->icon_large_href="";
        }
    }
}
class menu_group_class {
	var $items=array();
    var $group;
    var $group_class;
    var $href_class;
    function __construct($group_class="standard left",$href_class="nobr") {
    	$this->group_class=$group_class;
    	$this->href_class=$href_class;
    }
	function group($group) {
    	$this->group=$group;
    }
	function item($access,$name,$page,$url_query=array(),$href_options=array(),$secure=FALSE) {
	global $database;
		if ( ($this->href_class) && (!array_key_exists("class",$href_options)) ) $href_options['class']=$this->href_class;
		if ($database->user->access($access,FALSE)) $this->items[$this->group][]=new menu_page($name,$page,$url_query,$href_options,$secure);
	}
    function output() {
		$results=array();
	    foreach ($this->items as $group => $item_list) {
            if (sizeof($item_list)) {
	    		$items=array();
	            foreach ($item_list as $item) {
	                $items[]=$item->href;
	            }
				$results[]="<p class='" . $this->group_class . "'>";
                $results[]="<span style='font-size: 140%;'>$group</span><br>";
                $results[]=implode("\n | ",$items);
                $results[]="</p>";
			}
	    }
		return implode("\n",$results);
    }
}
class preg_replace_class {
	var $filter=array();
	var $post_filter=array();
	var $pattern=array();
    var $replacement=array();
    function __construct($post_filter=FALSE,$additional_filters=array() ) {
		$this->filter['crlf']="Hard return (CR/LF) to Line Break (&lt;BR&gt;)";
		$this->filter['msword']="Microsoft Word";
		$this->filter['wordpress']="Wordpress";
		if ( ($_SERVER['REQUEST_METHOD']=="POST") && (sizeof($_POST)) ) {
	        foreach ($_POST as $key=>$value) {
	            if (substr($key,0,15)=="replace_filter_") $this->post_filter[]=substr($key,15);
	        }
        }
        switch (TRUE) {
          case (is_array($additional_filters)):
	        foreach ($additional_filters as $filter) {
	        	$this->post_filter[]=$filter;
	        }
            break;
          case (strlen($additional_filters)):
	    	$this->post_filter[]=$additional_filters;
            break;
		}
        foreach (array_unique($this->post_filter) as $filter) {
			if (method_exists($this,$filter)) $this->$filter();
        }
    }
	function crlf() {
		$this->load("/<br>\n|<br>\r\n|\r\n|\r|\n/","<br>\n");
    }
	function msword() {
		$this->load("/\xe2\x80\x93/","-");
		$this->load("/\xe2\x80\x98/","'");
		$this->load("/\xe2\x80\x99/","'");
		$this->load("/\xe2\x80\x9c/",'"');
		$this->load("/\xe2\x80\x9d/",'"');
		$this->load("/\xe2\x80\xa2/","&bull;");
		$this->load("/\xe2\x84\xa2/","&trade;");
		$this->load("/\xc2\x96/","-");
		$this->load("/\xc2\xa9/","&copy;");
		$this->load("/\xc2\xa2/","&cent;");
		$this->load("/\xc2\xae/","&reg;");
		$this->load("/\xc2\xb0/","&deg;");
		$this->load("/\xc2\xbc/","1/4");
		$this->load("/\xc2\xbd/","1/2");
		$this->load("/\xc2\xbe/","3/4");

		$this->load("/\t/"," ");
		$this->load("/\x91/","'");
		$this->load("/\x92/","'");
		$this->load("/\x93/",'"');
		$this->load("/\x94/",'"');
		$this->load("/\x96/","-");
		$this->load("/\x97/","-");
    }
	function wordpress() {
		$this->load("/[\x0a|\xa0]/","<br>");
		$this->load("/[\x80-\xff]/"," ");
    }
    function load($pattern,$replacement,$delimit_pattern=FALSE,$ignore_case=FALSE) {
		switch (TRUE) {
          case (!$pattern):
          	return;
          case ($delimit_pattern):
			$pattern="/" . preg_quote($pattern,"/") . "/";
            if ($ignore_case) $pattern .= "i";
			break;
		}
		if (!in_array($pattern,$this->pattern) ) {
	        $this->pattern[]=$pattern;
	        $this->replacement[]=$replacement;
		}
    }
    function filter($text) {
		if ( (sizeof($this->pattern)) && (strlen($text)) ) {
        	$results=preg_replace($this->pattern,$this->replacement,trim($text));
		  } else {
          	$results=trim($text);
		}
        return $results;
    }
    function filter_html() {
    global $forms;
		$results=array();
	    foreach ($this->filter as $key => $value) {
	        $results[]=$forms->checkbox("replace_filter_" . $key,1,in_array($key,$this->post_filter)) . " $value";
	    }
        return $results;
    }
}
class jquery_tab_class {
	var $id;
    var $options;
    var $class_tabs;
    var $class_content;
    var $item=0;
    var $landing_tab=0;
    var $landing_name;
    var $tabs=array();
    var $contents=array();
	function __construct($id,$class_tabs='scs_jquery_tabs',$class_content='scs_jquery_tab') {
    global $forms;
		$this->id=urlencode($id);
        $this->class_tabs=$class_tabs;
        $this->class_content=$class_content;
		$this->landing_tab($_REQUEST);
        $this->options=new stdClass();
		$this->options->lineheight='content';
    }
    private function tab_name($name) {
		return "jquerytab_" . base64_encode(trim($name));
    }
    function options($options=array()) {
		foreach ($this->options as $option_code => $option_value) {
			if (array_key_exists($option_code,$options)) $this->options->{$option_code}=$options[$option_code];
        }
    }
    function landing_tab($request=array(),$override=FALSE) {
    global $forms;
		switch (TRUE) {
		  case (!func_num_args()):
			$this->landing_tab=sizeof($this->tabs);
			return;
		  case ( ($override) && (!is_array($request)) && (strlen($request)) ):
			if (!$this->landing_name) $this->landing_name=$this->tab_name($request);
			return;
		  case (!sizeof($forms->error)):
          case (!is_array($request)):
			return;
		}
		foreach ($request as $field => $field_value) {
			switch (TRUE) {
			  case (substr($field,0,10)=="jquerytab_"):
				$landing_name=$field;
				break;
        	  case (!isset($landing_name)):
				break;
			  case (array_key_exists($field,$forms->error)):
				$this->landing_name=$landing_name;
				return;
        	}
    	}
    }
    function item($name,$contents,$iframe_url="",$iframe_options=array()) {
    global $forms;
	if ($forms->debug) scs_function_debug(__FILE__,__METHOD__,func_num_args(),func_get_args());
		$this->item++;
        $tab=new stdClass();
        $tab->name=$name;
        $tab->tab_name=$this->tab_name($name);
		$tab->id=$this->id . "_" . $this->item;
        $options=array();
        $options[]="a href=" . fn_escape("#" . $tab->id);
        $options[]="class=" . fn_escape($this->class_content);
		switch (TRUE) {
		  case ($iframe_url):
			$tab->type="javascript";
            $iframe_options['src']="";
			if (!array_key_exists('resize',$iframe_options)) $iframe_options['resize']=TRUE;
			$options[]="onclick=\"fn_jquery_url('" . $tab->id . "'," . fn_escape($iframe_url) . ");\"";
            if (!array_key_exists('style',$iframe_options)) $iframe_options['style']="padding:0px;";
			$this->contents[$this->item]=$forms->iframe($tab->id,"",$iframe_options,FALSE);
          	break;
          case ( (is_array($contents)) && (!sizeof($contents)) ):
          case ( (!is_array($contents)) && (!strlen($contents)) ):
			return;
		  default:
			$tab->type="hyperlink";
	        $this->contents[$this->item]=
				$forms->hidden($this->tab_name($name), $name) .
            	"<div id=" . fn_escape($tab->id) . ">\n";
			if (is_array($contents)) {
				$this->contents[$this->item] .= implode("\n",$contents);
			  } else {
				$this->contents[$this->item] .= trim($contents);
			}
			$this->contents[$this->item] .= "\n</div>\n";
		}
        $tab->url="<" . implode(" ",$options) . ">$name</a>";
		$this->tabs[]=$tab;
        if ( (!$this->landing_tab) && ($tab->tab_name==$this->landing_name) ) $this->landing_tab=(sizeof($this->tabs) - 1);
	}
    function output($debug=array()) {
    global $forms;
		if ($forms->debug) {
			$results=array();
            if (!is_array($debug)) $debug[]=$debug;
			if (sizeof($debug)) $results[]="<p class='standard'><b>Local Content:</b><br>\n" . implode("<br>\n",$debug) . "</p>";
            $tabs_content=array();
            $tabs_content[]="ID: " . $this->id;
            $tabs_content[]="Class: " . $this->class_tabs;
            $tabs_content[]="# Items: " . $this->item;
            if ($this->landing_name) $tabs_content[]="Landing tab name: " . $this->landing_name;
			$results[]="<p class='standard'><b>Tabs Content:</b><br>\n" . implode("<br>\n",$tabs_content) . "</p>";
            foreach ($this->tabs as $tab_item => $tab) {
				$tabs_content=array();
                foreach ($tab as $key => $value) {
                	$tabs_content[]="$key: $value";
                }
				$results[]="<p class='standard'><b>Tab #{$tab_item}:</b><br>\n" . implode("<br>\n",$tabs_content) . "</p>";
            }
            $this->item("SCS Debug",$results);
        }
		if (!sizeof($this->tabs)) return;
		if (!array_key_exists("tabs",$forms->jquery)) $forms->jquery['tabs']=array();
		$results=array();
        if (!in_array($this->class_tabs,$forms->jquery['tabs'])) {
        	$forms->jquery['tabs'][]=$this->class_tabs;
            if ($this->landing_tab) {
            	$landing_tab=" { active: " . $this->landing_tab . " } ";
			  } else {
				$landing_tab="";
			}
	        $results[]="";
            $results[]="<script type='text/javascript'>";
            $results[]="$(document).ready(function() {";
			$results[]="var tabs=$('." . $this->class_tabs . "').tabs({heightStyle: '" . $this->options->lineheight . "'});";
			$results[]="$('." . $this->class_tabs . "').tabs(" . $landing_tab . ") });";
            $results[]="var jquery_active_tabs = new Array();";
            $results[]="function fn_jquery_url(id,url) {";
            $results[]="if (jquery_active_tabs.indexOf(id) == -1) {";
            $results[]="jquery_active_tabs.push(id);";
            $results[]="document.getElementById(id).src=url;";
            $results[]="}";
            $results[]="}\n";
            $results[]="</script>\n";
		}
        $results[]="<div id=" . $this->id . " class=" . fn_escape($this->class_tabs) . ">";
        $results[]="<ul>";
        foreach ($this->tabs as $item => $tab) {
        	$results[]="<li id='" . $this->id . "_tab_{$item}'>" . $tab->url . "</li>";
		}
        $results[]="</ul>";
		foreach ($this->contents as $contents) {
			if (is_array($contents)) {
            	$results[]=implode("\n",$contents);
			  } else {
        		$results[]=$contents;
			}
		}
        $results[]="</div>";
        print implode("\n",$results);
	}
}
?>