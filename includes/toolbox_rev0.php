<?php
$forms->title("SCS Toolbox (09/24/2025)");
$forms->report['action']=$_POST['action'];

$forms->constant->results=array();

$colortable=array();
foreach ($forms->background as $color => $background) {
	$code=preg_replace("/^style='color: #(.*);'/","$1",$forms->foreground->$color);
    $colortable[$code]=$color;
}
$toolbox_options=array();
$toolbox_options['PHP']=array();
$toolbox_options['PHP']['base64']="Base64 encode/decode (08/14/2014)";
$toolbox_options['PHP']['timestamp']="Unix Timestamp (04/06/2014)";
$toolbox_options['PHP']['unlock']="Unlock code (02/01/2021)";
$toolbox_options['PHP']['unserialize']="Unserialize PHP content (12/26/2018)";

$toolbox_options['Utility']=array();
$toolbox_options['Utility']['cookie']="Cookie Test (07/26/2020)";
$toolbox_options['Utility']['curl']="CURL Version (09/30/2019)";
$toolbox_options['Utility']['exec']="Execute Command (08/04/2023)";
$toolbox_options['Utility']['os']="Operating System (09/24/2025)";
$toolbox_options['Utility']['ftp']="FTP (09/10/2020)";
$toolbox_options['Utility']['geocode']="Geocode distance (03/13/2020)";
$toolbox_options['Utility']['session']="Session Test (07/26/2020)";
$toolbox_options['Utility']['password']="Password Generator (01/20/2016)";
$toolbox_options['Utility']['json']="JSON Decode (03/14/2024)";
$toolbox_options['Utility']['email']="Email (04/28/2025)";

$toolbox_options['File Utility']=array();
$toolbox_options['File Utility']['file']="File finder (06/03/2014)";
$toolbox_options['File Utility']['hex']="Hex/Decimal conversion (12/04/2014)";
$toolbox_options['File Utility']['filedump']="Hex Dump: File (02/26/2014)";
$toolbox_options['File Utility']['hexdump']="Hex Dump: Text (03/16/2014)";
$toolbox_options['File Utility']['hex2file']="Hex to File: (01/30/2021)";

$toolbox_options['HTML']=array();
$toolbox_options['HTML']['input']="HTML Input (04/03/2021)";
$toolbox_options['HTML']['color']="Color Combinations (11/27/2013)";
$toolbox_options['HTML']['colorwheel']="Color Wheel (11/27/2013)";
$toolbox_options['HTML']['colorview']="Color Viewer (09/19/2015)";
$toolbox_options['HTML']['image']="Embedded Image (05/14/2024)";

$toolbox_options['Browser/URL']=array();
$toolbox_options['Browser/URL']['browscap']="Browser Capabilities (08/14/2014)";
$toolbox_options['Browser/URL']['dns_get_record']="DNS: dns_get_record (03/09/2020)";
$toolbox_options['Browser/URL']['mx_record']="MX: getmxrr (08/23/2023)";
$toolbox_options['Browser/URL']['http_headers']="HTTP Headers (04/04/2015)";
if ($database->registry->recaptcha->version) $toolbox_options['Browser/URL']['recaptcha']="reCAPTCHA Log (08/08/2022)";
$toolbox_options['Browser/URL']['ie']="IE Agents (11/27/2013)";
$toolbox_options['Browser/URL']['ip']="IP Address (05/27/2014)";
$toolbox_options['Browser/URL']['url_build']="URL Build (08/20/2019)";
$toolbox_options['Browser/URL']['url_parse']="URL Parse (08/20/2019)";
$toolbox_options['Browser/URL']['urlencode']="URL Encode/Decode (08/07/2024)";
$toolbox_options['Browser/URL']['script']="Script Injection (05/28/2014)";

$toolbox_options['jQuery']=array();
$toolbox_options['jQuery']['jquery']="JQuery functions (01/20/2016)";
$toolbox_options['jQuery']['jsession']="JavaScript Sessions (06/16/2020)";

$toolbox_options['PHP/Registry']=array();
$toolbox_options['PHP/Registry']['extensions']="PHP Modules/Extensions (08/06/2018)";
$toolbox_options['PHP/Registry']['phperror']="PHP Errors (02/22/2014)";
$toolbox_options['PHP/Registry']['phplog']="PHP Log File (02/03/2020)";
$toolbox_options['PHP/Registry']['ini']="php.ini Reader (06/08/2015)";
$toolbox_options['PHP/Registry']['hotfix']="Hotfix history (11/04/2019)";
$toolbox_options['PHP/Registry']['registry']="Registry Dump (03/02/2014)";

$forms->report['action']=$_POST['action'];
switch ($forms->report['action']) {
  case "email":
    $forms->report['email_action']=fn_text($_POST['email_action']);
    $forms->report['email_sender']=fn_text($_POST['email_sender']);
    $forms->report['email_subject']=fn_text($_POST['email_subject']);
    $forms->report['email_message']=fn_text($_POST['email_message']);
    if (!$forms->report['email_action']) break;
    if (!strlen($forms->report['email_subject'])) $forms->error('email_subject');
    if (!strlen($forms->report['email_message'])) $forms->error('email_message');
    if (sizeof($forms->error)) break;
    if ($forms->report['email_sender']) {
        $forms->report['email_results']=mail($_SESSION['user']->email, $forms->report['email_subject'], $forms->report['email_message'], "", "-f {$forms->report['email_sender']}");
      } else {
        $forms->report['email_results']=mail($_SESSION['user']->email, $forms->report['email_subject'], $forms->report['email_message']);
    }
    break;
  case "urlencode":
    $forms->report['urlencode_action']=$_POST['urlencode_action'];
    switch ($forms->report['urlencode_action']) {
      case "":
        $forms->report['urlencode_action']="decode";
        break;
      case "decode":
        $forms->report['urlencode_encoded']=trim($_POST['urlencode_encoded']);
        $forms->report['urlencode_decoded']=urldecode($_POST['urlencode_encoded']);
        break;
      case "encode":
        $forms->report['urlencode_decoded']=trim($_POST['urlencode_decoded']);
        $forms->report['urlencode_encoded']=urlencode($_POST['urlencode_decoded']);
        break;
    }
    break;
  case "image":
    if (!array_key_exists("image_file", $_FILES)) break;
    switch (TRUE) {
      case (!strlen($_FILES['image_file']['name'])):
        $forms->error('image_file', "Cannot be blank");
        break;
      case ($_FILES['image_file']['error']):
        $forms->error('image_file', "Error: " . $_FILES['image_file']['error']);
        break;
      case (!$_FILES['image_file']['size']):
        $forms->error('image_file', "Empty file");
        break;
    }
    if (sizeof($forms->error)) break;
    $forms->constant->image_type=$_FILES['image_file']['type'];
    $forms->constant->image_base64=base64_encode(file_get_contents($_FILES['image_file']['tmp_name']));
    break;
  case "json":
    $forms->report['json_content']=trim($_POST['json_content']);
    break;
  case "mx_record":
	$forms->report['mx_record']=strtolower(trim($_POST['mx_record']));
    if (!strlen($forms->report['mx_record'])) break;
	getmxrr($forms->report['mx_record'], $hosts, $weights);
	if ( (!is_array($hosts)) || (!sizeof($hosts)) ) {
    	$forms->error("mx_record");
        break;
	}
    $items=array();
    foreach ($weights as $i => $weight) {
    	if (!array_key_exists($weight, $items)) $items[$weight]=array();
        $items[$weight][]=$hosts[$i];
    }
    ksort($items);
    $results=array();
    $results[]="<table class='border'>";
    $results[]="<tr>";
    $results[]="<th width='5%'>Weight</th>";
    $results[]="<th width='95%'>Host</th>";
    $results[]="</tr>";
    foreach ($items as $weight => $item) {
		$row=0;
        foreach ($item as $host) {
	        $results[]="<tr>";
	        if (!$row) $results[]="<td class=center rowspan=" . sizeof($item) . ">{$weight}</td>";
	        $results[]="<td>{$host}</td>";
	        $results[]="</tr>";
        	$row++;
        }
    }
    $results[]="</table>";
    $forms->constant->results=$results;
  	break;
  case "exec":
	$forms->report['exec_command']=trim($_POST['exec_command']);
    switch (TRUE) {
      case (!strlen($forms->report['exec_command'])):
      case (sizeof($forms->error)):
      	break;
	  default:
	    exec(str_replace("\n"," ",$forms->report['exec_command']), $response, $return_code);
	    $results=array();
	    $results[]="Return Code: {$return_code}";
	    if (is_array($response)) {
	        $results[]="Response:";
	        foreach ($response as $value) {
	            $results[]=trim(strip_tags($value));
	        }
	    }
    	$forms->report["exec_results"]="<div>" . implode("<br>",$results) . "</div>";
	}
  	break;
  case "recaptcha":
    $forms->constant->recaptcha=$_SERVER['DOCUMENT_ROOT'] . "/recaptcha.log";
    if ($_POST['recaptcha_purge']) {
    	unlink($forms->constant->recaptcha);
    	$forms->message[]="reCAPTCHA log purged";
	}
  	break;
  case "input":
	$forms->report['input_color']=trim($_POST['input_color']);
	$forms->report['input_date']=trim($_POST['input_date']);
	$forms->report['input_datetime_local']=trim($_POST['input_datetime_local']);
	$forms->report['input_month']=trim($_POST['input_month']);
	$forms->report['input_week']=trim($_POST['input_week']);
	$forms->report['input_time']=trim($_POST['input_time']);
	$forms->report['input_range']=trim($_POST['input_range']);
	$forms->report['input_phone']=trim($_POST['input_phone']);
	$forms->report['input_email']=trim($_POST['input_email']);
	break;
  case "hex2file":
	$forms->report['hex2file_file']=trim($_POST['hex2file_file']);
    if (!strlen($forms->report['hex2file_file'])) $forms->error('hex2file_file',"Cannot be blank");
	$forms->report['hex2file_content']=trim($_POST['hex2file_content']);
    switch (TRUE) {
      case (!strlen($forms->report['hex2file_content'])):
      	$forms->error('hex2file_content',"Cannot be blank");
        break;
      case (!preg_match("/^[a-fA-F0-9]+$/",$forms->report['hex2file_content'])):
      	$forms->error('hex2file_content',"Contains non-hexadecimal charaters (0-9, a-f)");
      	break;
      case (strlen($forms->report['hex2file_content']) % 2):
      	$forms->error('hex2file_content',"Must be an even number of characters");
        break;
    }
    if (sizeof($forms->error)) break;

	header("Content-type: text/plain");
	header("Content-Disposition: attachment; filename=" . $_POST['hex2file_file']);
	header("Pragma: no-cache");
	header("Expires: 0");
	$fh=fopen("php://output", "w");
	fwrite($fh,hex2bin($forms->report['hex2file_content']));
    fclose($fh);
    die();
  case "unlock":
	$forms->report['unlock_action']=$_POST['unlock_action'];
    switch ($forms->report['unlock_action']) {
	  case "":
		$forms->report['unlock_action']="date";
	    $forms->report['unlock_timestamp']=strtotime("now");
	    $forms->report['unlock_date']=date("m/d/Y g:i:s A",$forms->report['unlock_timestamp']);
	    $forms->report['unlock_code']=strtoupper(dechex($forms->report['unlock_timestamp']));
        break;
	  case "code":
	    $forms->report['unlock_code']=fn_text($_POST['unlock_code'],TRUE);
		if (!preg_match("/^[A-F0-9]{1,8}$/",$forms->report['unlock_code'])) {
			$forms->error('unlock_code',"Must be hexadecimal");
		  } else {
	        $forms->report['unlock_timestamp']=hexdec($forms->report['unlock_code']);
	        $forms->report['unlock_date']=date("m/d/Y g:i:s A",$forms->report['unlock_timestamp']);
		}
		break;
      default:
	    $forms->report['unlock_timestamp']=( ($forms->report['action'] == "timestamp") ? intval($_POST['unlock_timestamp']) : strtotime($_POST['unlock_date']) );
	    $forms->report['unlock_date']=date("m/d/Y g:i:s A",$forms->report['unlock_timestamp']);
	    $forms->report['unlock_code']=( ($forms->report['unlock_timestamp']) ? strtoupper(dechex($forms->report['unlock_timestamp'])) : "");
		break;
    }
	break;
  case "ftp":
	if (!@include "classes/ftp.php") {
    	$forms>error("action");
        break;
	}
	$menu->ftp=new ftp_class();
	if (!$_POST['ftp_action']) break;
	$menu->ftp->command=$_POST['ftp_command'];
	$menu->ftp->host=trim($_POST['ftp_host']);
	$menu->ftp->port=trim($_POST['ftp_port']);
	$menu->ftp->user=trim($_POST['ftp_user']);
	$menu->ftp->password=trim($_POST['ftp_password']);
	$menu->ftp->pasv=intval($_POST['ftp_pasv']);
	$menu->ftp->binary=intval($_POST['ftp_binary']);
	$menu->ftp->remote_folder=trim($_POST['ftp_remote_folder']);
	$menu->ftp->local_folder=trim($_POST['ftp_local_folder']);
    $files=explode("\n",$_POST['ftp_files']);
    foreach ($files as $file) {
    	$file=trim($file);
        if ( (strlen($file)) && (!in_array($file,$menu->ftp->files)) ) $menu->ftp->files[]=$file;
    }
    if (!strlen($menu->ftp->host)) $forms->error('ftp_host');
    if (!intval($menu->ftp->port)) $menu->ftp->port=21;
    if (!strlen($menu->ftp->user)) $forms->error('ftp_user');
    if (!strlen($menu->ftp->password)) $forms->error('ftp_password');
    if (sizeof($forms->error)) break;
    if (!$menu->ftp->execute()) {
		foreach ($menu->ftp->error as $key => $value) {
			$forms->error("ftp_{$key}",$value);
        }
    }
	break;
  case "cookie":
  	if (!$_POST['cookie_action']) {
    	$forms->report['cookie_name']="scs_toolbox";
    	$forms->report['cookie_text']=$_COOKIE['scs_toolbox'];
    	$forms->report['cookie_expires']="+1 hour";
    	$forms->report['cookie_path']="/";
	  } else {
    	$forms->report['cookie_name']=fn_text($_POST["cookie_name"]);
        if (!strlen($forms->report['cookie_name'])) $forms->error("cookie_name");
    	$forms->report['cookie_text']=fn_text($_POST["cookie_text"]);
    	$forms->report['cookie_expires']=fn_text($_POST["cookie_expires"]);
        if (!strlen($forms->report['cookie_expires'])) $forms->error("cookie_expires");
    	$forms->report['cookie_path']=trim($_POST['cookie_path']);
    	$forms->report['cookie_domain']=trim($_POST['cookie_domain']);
    	$forms->report['cookie_secure']=intval($_POST['cookie_secure']);
    	$forms->report['cookie_httponly']=intval($_POST['cookie_httponly']);
    	$forms->report['cookie_samesite']=$_POST['cookie_samesite'];
	}
    if (sizeof($forms->error)) break;
    $options=array();
	$options['expires']=strtotime($forms->report['cookie_expires']);
	if (strlen($forms->report['cookie_path'])) $options['path']=$forms->report['cookie_path'];
	if (strlen($forms->report['cookie_domain'])) $options['domain']=$forms->report['cookie_domain'];
	$options['secure']=$forms->report['cookie_secure'];
	$options['httponly']=$forms->report['cookie_httponly'];
	if (strlen($forms->report['cookie_samesite'])) $options['samesite']=$forms->report['cookie_samesite'];
	$forms->report['cookie_response']=fn_cookie($forms->report['cookie_name'], urldecode($forms->report['cookie_text']), $options);
    break;
  case "session":
  	if (!$_POST['session_action']) break;
  	$text=trim($_POST['session_value']);
    if (strlen($text)) {
    	$_SESSION["scs_toolbox"]=$text;
	  } else {
      	unset($_SESSION["scs_toolbox"]);
	}
	break;
  case "geocode":
	if (!@include "classes/geocode.php") {
    	$forms>error("action");
        break;
	}
	if (!$_POST['geocode']) {
    	$forms->report['geocode_miles']=TRUE;
    	break;
	}
	$forms->report['geocode_location1_address']=trim($_POST['geocode_location1_address']);
	$forms->report['geocode_location1_zip']=trim($_POST['geocode_location1_zip']);
	$forms->report['geocode_location2_address']=trim($_POST['geocode_location2_address']);
	$forms->report['geocode_location2_zip']=trim($_POST['geocode_location2_zip']);
    $forms->report['geocode_miles']=$_POST['geocode_miles'];
    if (!strlen($forms->report["geocode_location1_address"])) $forms->error("geocode_location1_address","Cannot be blank");
    if (!strlen($forms->report["geocode_location1_zip"])) $forms->error("geocode_location1_zip","Cannot be blank");
    if (!strlen($forms->report["geocode_location2_address"])) $forms->error("geocode_location2_address","Cannot be blank");
    if (!strlen($forms->report["geocode_location2_zip"])) $forms->error("geocode_location2_zip","Cannot be blank");
    if (sizeof($forms->error)) break;
    $menu->geocode->coordinates($forms->report['geocode_location1_address'],$forms->report["geocode_location1_zip"]);
    if ($menu->geocode->results->error) {
    	$forms->error("geocode_location1_address",$menu->geocode->results->error);
        break;
    }
    $forms->report["geocode_location1_results"]=$menu->geocode->results;
    $menu->geocode->coordinates($forms->report['geocode_location2_address'],$forms->report["geocode_location2_zip"]);
    if ($menu->geocode->results->error) {
    	$forms->error("geocode_location2_address",$menu->geocode->results->error);
        break;
    }
    $forms->report["geocode_location2_results"]=$menu->geocode->results;
	break;
  case "phplog":
  	$logfile=ini_get("error_log");
	switch (TRUE) {
      case (!$_POST['phplog']):
      case (!file_exists($logfile)):
      	break;
	  case ($_POST['phplog'] == "Clear"):
		$forms->message[]="{$logfile} cleared";
		unlink($logfile);
		break;
	  case (filesize($logfile) > 50000000):
		$forms->error("phplog","{$logfile} excededs 50MB limit");
		break;
	  case ($_POST['phplog'] == "View"):
        $forms->constant->phplog=file($logfile);
		break;
	  case ($_POST['phplog'] == "Upload"):
	    header("Content-type: text/plain");
		header("Content-Disposition: attachment; filename=log.txt");
	    header("Pragma: no-cache");
	    header("Expires: 0");
		readfile($logfile);
		die();
	}
	break;
  case "hotfix":
	foreach ($_POST as $key => $value) {
		list($field,$id)=(fn_base64($key,FALSE));
        if ($field == "hotfix") $database->temp->query("delete from registry where id=" . fn_escape($id));
    }
	break;
  case "url_build":
  	$forms->report['url_scheme']=trim($_POST['url_scheme']);
  	$forms->report['url_host']=trim($_POST['url_host']);
    if (preg_match("/\//",$forms->report['url_host'])) $forms->error("url_host","Host name cannot contain /");
  	$forms->report['url_path']=trim($_POST['url_path']);
    if ( (strlen($forms->report['url_path'])) && (substr($forms->report['url_path'],0,1) != "/") ) $forms->error("url_path","Must begin with /");
    $forms->constant->url_query=array();
    $url_query=array();
    for ($i=0; $i < 10; $i++) {
		$item=new stdClass();
        $item->key=trim($_POST["url_key{$i}"]);
    	$item->value=trim($_POST["url_value{$i}"]);
        $forms->constant->url_query[$i]=$item;
        switch (TRUE) {
          case ( (strlen($item->value)) && (!strlen($item->key)) ):
          	$forms->error("url_key{$i}","Cannot be blank");
            break;
          case ( (strlen($item->key)) && (array_key_exists($item->key,$url_query)) ):
          	$forms->error("url_key{$i}","Duplicate entry");
            break;
		}
        $url_query[$item->key]=$item->value;
    }
	$forms->report['url_fragment']=trim($_POST['url_fragment']);
    if ( (!strlen($forms->report['url_host'])) && (!strlen($forms->report['url_path']))) $forms->error("null","","Either host or path must be selected");
    if (sizeof($forms->error)) break;
    $url=array();
    if (strlen($forms->report['url_host'])) {
    	$url[]=$forms->report['url_scheme'];
		$url[]=$forms->report['url_host'];
	}
    if (strlen($forms->report['url_path'])) $url[]=$forms->report['url_path'];
	$forms->constant->url=fn_url(implode("",$url),$url_query);;
	break;
  case "url_parse":
  	$forms->report['url']=trim($_POST['url']);
    $forms->constant->url=parse_url($forms->report['url']);
	break;
  case "url_build":
	break;
  case "ini":
	$forms->report['ini_action']=$_POST['ini_action'];
    if (!$forms->report['ini_action']) break;
    switch (TRUE) {
	  case (!$_FILES['ini_file']['name']):
	  case (!intval($_FILES['ini_file']['size'])):
		$forms->error("ini_file","Upload file missing or empty");
        break;
	  case (intval($_FILES['ini_file']['error'])):
		$forms->error('ini_file',"Upload error: " . $_FILES['ini_file']['error']);
        break;
	  case ($_FILES['ini_file']['type'] != "application/octet-stream"):
		$forms->error('ini_file',"Invalid file type: " . $_FILES['ini_file']['type']);
        break;
    }
	break;
  case "http_headers":
	$forms->report['http_headers']=array();
	$forms->report['http_headers'][]=headers_list();
  	break;
  case "dns_get_record":
	$forms->report['dns_domain']=strtolower(trim($_POST['dns_domain']));
    if (!strlen($forms->report['dns_domain'])) break;
	$dns_results=dns_get_record($forms->report['dns_domain'],DNS_ANY);
	if (!sizeof($dns_results)) $forms->error('dns_domain');
	break;
  case "password":
	$forms->report['password_action']=$_POST['password_action'];
	$forms->report['password_size']=array();
    if ($forms->report['password_action']) {
		$forms->report['password_data']=array();
		$forms->report['password_size']['upper']=intval($_POST['password_upper_size']);
		if ($forms->report['password_size']['upper']) $forms->report['password_data']['upper']="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$forms->report['password_size']['lower']=intval($_POST['password_lower_size']);
		if ($forms->report['password_size']['lower']) $forms->report['password_data']['lower']="abcdefghijklmnopqrstuvwxyz";
	    $forms->report['password_size']['numeric']=intval($_POST['password_numeric_size']);
		if ($forms->report['password_size']['numeric']) $forms->report['password_data']['numeric']="0123456789";
	    $forms->report['password_size']['symbol']=intval($_POST['password_symbol_size']);
		if ($forms->report['password_size']['symbol']) $forms->report['password_data']['symbol']="@#$%^&*(){}_+=";
	    $forms->report['password_size']['punctuation']=intval($_POST['password_punctuation_size']);
	    if ($forms->report['password_size']['punctuation']) $forms->report['password_data']['punctuation']="!?,.;:-";
        if (!array_sum($forms->report['password_size'])) $forms->error("null","","Password length zero");
	    $forms->report['password_similar']=$_POST['password_similar'];
        if (!$forms->report['password_similar']) $forms->report['password_data']=preg_replace("/[" . preg_quote(".,-_({)}1Ili!|UVuv5S;:0O","/") . "]/","",$forms->report['password_data']);
	    $forms->report['password_duplicate']=$_POST['password_duplicate'];
        if (!$forms->report['password_duplicate']) {
			foreach ($forms->report['password_size'] as $code => $size) {
				if ( ($size) && (sizeof($forms->report['password_data'][$code]) < $size) ) $forms->error("password_{$code}_size");
            }
        }
        $forms->constant->password_dataset=implode($forms->report['password_data']);
        if (!strlen($forms->constant->password_dataset)) $forms->error('password_upper');
        if (sizeof($forms->error)) break;
        $max=strlen($forms->constant->password_dataset) - 1;
	    $forms->report['password_count']=array();
        foreach ($forms->report['password_data'] as $key => $value) {
			$forms->report['password_count'][$key]=0;
        }
        $ok=FALSE;
        while(!$ok) {
	        $ok=TRUE;
			$count=$forms->report['password_count'];
	        $forms->report['password_output']=array();
	        for ($i=0; $i < array_sum($forms->report['password_size']); $i++) {
	            $char=substr($forms->constant->password_dataset,mt_rand(0,$max),1);
				if ( (!$forms->report['password_duplicate']) && (in_array($char,$forms->report['password_output'])) ) {
                	$ok=FALSE;
                    break;
				}
	            $forms->report['password_output'][]=$char;
	            foreach ($forms->report['password_data'] as $key => $value) {
	                if  (preg_match("/"  . preg_quote($char,"/") . "/",$value)) {
	                    $count[$key]++;
	                    break;
	                }
	            }
	        }
	        foreach ($forms->report['password_size'] as $key => $value) {
				if ($count[$key] < $value) {
					$ok=FALSE;
                    break;
                }
	        }
        }
	  } else {
		$forms->report['password_size']['upper']=2;
		$forms->report['password_size']['lower']=2;
		$forms->report['password_size']['numeric']=2;
	    $forms->report['password_similar']=1;
	    $forms->report['password_duplicate']=1;
	}
	break;
  case "hex":
	$forms->report['hex_action']=$_POST['hex_action'];
    switch ($forms->report['hex_action']) {
	  case "":
		$forms->report['hex_action']="hex";
		break;
	  case "hex":
		$forms->report['hex_integer']=floatval(trim($_POST['hex_integer']));
        switch (TRUE) {
          case (!$forms->report['hex_integer']):
          case ($forms->report['hex_integer'] != intval($forms->report['hex_integer'])):
			$forms->error('hex_integer');
			break;
		  default:
			$forms->report['hex_hex']=dechex($forms->report['hex_integer']);
		}
		break;
	  case "integer":
		$forms->report['hex_hex']=strtolower(trim($_POST['hex_hex']));
        switch (TRUE) {
		  case (!$forms->report['hex_hex']):
          case (preg_replace("/[0-9,a-f]/","",$forms->report['hex_hex'])):
			$forms->error('hex_hex');
            break;
		  default:
			$forms->report['hex_integer']=hexdec($forms->report['hex_hex']);
        }
		break;
	}
	break;
  case "base64":
	$replace=new preg_replace_class();
	$forms->report['base64_action']=$_POST['base64_action'];
    switch ($forms->report['base64_action']) {
	  case "":
		$forms->report['base64_action']="decode";
		break;
	  case "encode":
	    $forms->report['base64_text']=trim($_POST['base64_text']);
	    $replace->load('/{nul}/',chr(0));
	    $replace->load('/{soh}/',chr(1));
	    $replace->load('/{stx}/',chr(2));
	    $replace->load('/{etx}/',chr(3));
	    $replace->load('/{eot}/',chr(4));
	    $replace->load('/{enq}/',chr(5));
	    $replace->load('/{ack}/',chr(6));
	    $replace->load('/{bel}/',chr(7));
	    $replace->load('/{bs}/',chr(8));
	    $replace->load('/{ht}/',chr(9));
	    $replace->load('/{lf}/',chr(10));
	    $replace->load('/{vt}/',chr(11));
	    $replace->load('/{ff}/',chr(12));
	    $replace->load('/{cr}/',chr(13));
	    $replace->load('/{so}/',chr(14));
	    $replace->load('/{si}/',chr(15));
	    $replace->load('/{dle}/',chr(16));
	    $replace->load('/{dc1}/',chr(17));
	    $replace->load('/{dc2}/',chr(18));
	    $replace->load('/{dc3}/',chr(19));
	    $replace->load('/{dc4}/',chr(20));
	    $replace->load('/{nak}/',chr(21));
	    $replace->load('/{syn}/',chr(22));
	    $replace->load('/{etb}/',chr(23));
	    $replace->load('/{can}/',chr(24));
	    $replace->load('/{em}/',chr(25));
	    $replace->load('/{sub}/',chr(26));
	    $replace->load('/{esc}/',chr(27));
	    $replace->load('/{fs}/',chr(28));
	    $replace->load('/{gs}/',chr(29));
	    $replace->load('/{rs}/',chr(30));
	    $replace->load('/{us}/',chr(31));
	    $replace->load('/{sp}/',chr(32));
		$forms->report['base64_encoded']=base64_encode($replace->filter(trim($forms->report['base64_text'])));
		break;
	  case "decode":
	    $forms->report['base64_encoded']=trim($_POST['base64_encoded']);
	    $replace->load('/\x00/','{nul}');
	    $replace->load('/\x01/','{soh}');
	    $replace->load('/\x02/','{stx}');
	    $replace->load('/\x03/','{etx}');
	    $replace->load('/\x04/','{eot}');
	    $replace->load('/\x05/','{enq}');
	    $replace->load('/\x06/','{ack}');
	    $replace->load('/\x07/','{bel}');
	    $replace->load('/\x08/','{bs}');
	    $replace->load('/\x09/','{ht}');
	    $replace->load('/\x0a/','{lf}');
	    $replace->load('/\x0b/','{vt}');
	    $replace->load('/\x0c/','{ff}');
	    $replace->load('/\x0d/','{cr}');
	    $replace->load('/\x0e/','{so}');
	    $replace->load('/\x0f/','{si}');
	    $replace->load('/\x10/','{dle}');
	    $replace->load('/\x11/','{dc1}');
	    $replace->load('/\x12/','{dc2}');
	    $replace->load('/\x13/','{dc3}');
	    $replace->load('/\x14/','{dc4}');
	    $replace->load('/\x15/','{nak}');
	    $replace->load('/\x16/','{syn}');
	    $replace->load('/\x17/','{etb}');
	    $replace->load('/\x18/','{can}');
	    $replace->load('/\x19/','{em}');
	    $replace->load('/\x1a/','{sub}');
	    $replace->load('/\x1b/','{esc}');
	    $replace->load('/\x1c/','{fs}');
	    $replace->load('/\x1d/','{gs}');
	    $replace->load('/\x1e/','{rs}');
	    $replace->load('/\x1f/','{us}');
	    $replace->load('/\x20/','{sp}');
	    $forms->report['base64_text']=$replace->filter(base64_decode($forms->report['base64_encoded']));
		break;
    }
	break;
  case "browscap":
	switch (TRUE) {
	  case (!function_exists('get_browser')):
      case (!ini_get("browscap")):
		$forms->error('action','',"PHP build does not include browscap.ini");
        break;
	  default:
		$forms->report['browscap_agent']=trim($_POST['browscap_agent']);
        if (!$forms->report['browscap_agent']) $forms->report['browscap_agent']=$_SERVER['HTTP_USER_AGENT'];
	}
	break;
  case "filedump":
	$forms->report['filedump_output']=$_POST['filedump_output'];
    if (!$forms->report['filedump_output']) break;
	switch (TRUE) {
	  case (intval($_FILES['filedump_file']['error'])):
	  case (!intval($_FILES['filedump_file']['size'])):
		$forms->error('filedump_file','',"Error: " . $_FILES['filedump_file']['error'] . " Size: " .  $_FILES['filedump_file']['size']);
        break;
    }
	break;
  case "file":
	$forms->report['file_action']=$_POST['file_action'];
	$forms->report['file_folder']=trim($_POST['file_folder']);
	$forms->report['file_subfolder']=trim($_POST['file_subfolder']);
	$forms->report['file_name']=trim($_POST['file_name']);
	$forms->report['file_date_type']=$_POST['file_date_type'];
	$forms->report['file_from_date']=fn_date($_POST['file_from_date'],"mdy");
	$forms->report['file_thru_date']=fn_date($_POST['file_thru_date'],"mdy");
	if ( ($forms->report['file_thru_date']) && ($forms->report['file_from_date'] > $forms->report['file_thru_date']) ) $forms->error('file_thru_date');
	$forms->report['file_from_size']=fn_number($_POST['file_from_size'],0);
	$forms->report['file_thru_size']=fn_number($_POST['file_thru_size'],0);
	if ( ($forms->report['file_thru_size']) && ($forms->report['file_from_size'] > $forms->report['file_thru_size']) ) $forms->error('file_thru_size');
  	break;
  case "script":
	$forms->report['script_table']=$_POST['script_table'];
	$forms->report['script_timeout']=$_POST['script_timeout'];
	$forms->report['script_cleanup']=$_POST['script_cleanup'];
	if ($forms->report['script_timeout'] > 30) set_time_limit($forms->report['script_timeout']);
    if ( ($forms->report['script_timeout']) && (!$forms->report['script_table']) ) $forms->error('script_table');
	break;
  case "ip":
	$forms->report['ip_action']=$_POST['ip_action'];
    switch ($forms->report['ip_action']) {
	  case "ip":
	    $forms->report['ip_address']=fn_text($_POST['ip_address']);
		$forms->report['ip_integer']=ip2long($forms->report['ip_address']);
		if (!strlen($forms->report['ip_integer'])) $forms->error('ip_address');
		break;
	  case "integer":
	    $forms->report['ip_integer']=fn_number($_POST['ip_integer']);
	    $forms->report['ip_address']=long2ip($forms->report['ip_integer']);
		break;
      default:
		$forms->report['ip_action']="ip";
	    $forms->report['ip_address']=$_SERVER['REMOTE_ADDR'];
	    $forms->report['ip_integer']=ip2long($forms->report['ip_address']);
	}
	break;
  case "timestamp":
	$forms->report['timestamp_action']=$_POST['timestamp_action'];
    $query=array("select");
    $query[]="timediff(now(), utc_timestamp) as 'timediff',";
    $query[]="@@system_time_zone as 'timezone'";
    $database->temp->query($query);
    $database->temp->fetch(TRUE);
    switch ($forms->report['timestamp_action']) {
	  case "":
		$forms->report['timestamp_action']="date";
	    $forms->report['timestamp_time']=strtotime("now");
	    $forms->report['timestamp_date']=date("m/d/Y g:i:s A",$forms->report['timestamp_time']);
        break;
	  case "date":
	    $forms->report['timestamp_date']=$_POST['timestamp_date'];
		$forms->report['timestamp_time']=strtotime($forms->report['timestamp_date']);
        if (!$forms->report['timestamp_time']) {
        	$forms->error('timestamp_date');
		  } else {
			$forms->report['timestamp_date']=date("m/d/Y g:i:s A",$forms->report['timestamp_time']);
		}
		break;
	  case "time":
	    $forms->report['timestamp_time']=intval($_POST['timestamp_time']);
        if (!$forms->report['timestamp_time']) {
			$forms->error('timestamp_time');
		  } else {
			$forms->report['timestamp_date']=date("m/d/Y g:i:s A",$forms->report['timestamp_time']);
		}
		break;
    }
	$forms->report['timestamp_gmdate']=gmdate("m/d/Y g:i:s A",$forms->report['timestamp_time']);
	$forms->report['timestamp_gmtime']=strtotime($forms->report['timestamp_gmdate']);
	break;
  case "hexdump":
	$forms->report['hexdump']=trim($_POST['hexdump']);
	break;
  case "registry":
	$purge_list=array();
	foreach ($_POST as $key => $value) {
		list($field,$id)=preg_split("/\t/",base64_decode($key));
        if ($field=="registry") $purge_list[]=$id;
    }
	if (!sizeof($purge_list)) break;
    $query_where=array();
    $query_where[]=new query_where("id","in",$purge_list);
    $query =
    	"delete from registry \n" .
		$database->where($query_where);
	$database->registry->query($query);
    $forms->message[]=sizeof($purge_list) . " record(s) deleted";
	break;
  case "phperror":
    switch (TRUE) {
	  case ($_POST['phperror_calculate']):
		$forms->report['phperror_reporting']=0;
        foreach ($_POST as $key => $value) {
			if (substr($key,0,14)=="phperror_code_")  $forms->report['phperror_reporting'] += intval($value);
        }
        break;
	  case (intval($_POST['phperror_reporting'])):
		$forms->report['phperror_reporting']=fn_number($_POST['phperror_reporting']);
        break;
      default:
		$forms->report['phperror_reporting']=0;
    }
	require_once "classes/phperror.php";
	$phperror=new phperror_class($forms->report['phperror_reporting']);
	$forms->report['phperror_reporting']=$phperror->error_decode;
	break;
  case "color":
	$forms->report['foreground_name']=$_POST['foreground_name'];
	$forms->report['background_name']=$_POST['background_name'];
	$forms->report['foreground_code']=strtolower($_POST['foreground_code']);
	if (($forms->report['foreground_code']) && (!array_key_exists($forms->report['foreground_code'],$colortable))) $forms->error('foreground_code');
	$forms->report['background_code']=strtolower($_POST['background_code']);
	if (($forms->report['background_code']) && (!array_key_exists($forms->report['background_code'],$colortable))) $forms->error('background_code');
    break;
}
$menu->head();
print $forms->message();

?>
<script>
function fn_action(obj,action,id,name) {
	if ( (action=='delete') && (!confirm("Delete: #" + id + " " + name)) ) {
		obj.checked=false;
        return;
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.submit();
}
function fn_phperror() {
	document.scs_form.action.value='phperror';
	document.scs_form.phperror_calculate.value=1;
	document.scs_form.submit();
}
function location_get() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(location_show);
  } else {
	jQuery("#geocode_location").html("Geolocation is not supported by this browser.");
  }
}
function location_show(position) {
	console.log(position);
	jQuery("#geocode_location").html("Latitude: " + position.coords.latitude + " Longitude: " + position.coords.longitude);
}
if (typeof div_show != 'function') {
    function div_show(div,show) {
        switch (true) {
          case (!document.getElementById(div)):
            return;
          case (!show):
            document.getElementById(div).style.display='none';
            break;
          default:
            document.getElementById(div).style.display='block';
        }
    }
}
function fn_cookie(name, text) {
	jQuery("#cookie_name").val(name);
	jQuery("#cookie_text").val(text);
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print "<table class='standard noborder'>";
print "<tr>";
print "<td width='20%'>Selection</td>";
print "<td width='80%'>" . $forms->optgroup("action",$toolbox_options,$forms->report['action'],TRUE,array("onchange"=>"document.scs_form.submit();")) . " " . $forms->submit("Go!") . "</td>";
print "</tr>";
print "</table>";

switch (TRUE) {
  case ($forms->report['action']=="email"):
    print $forms->hidden("email_action", "email");
    print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='20%'>Send from</td>";
    $text=array("");
    $text[$_SERVER['SERVER_ADMIN']]="Server Admin";
    if (property_exists($database->registry, "email")) $text[$database->registry->email->webmaster]="Webmaster";
    print "<td width='80%'>" . $forms->select("email_sender", $text, $forms->report['email_sender'], FALSE) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='20%'>Subject</td>";
    print "<td width='80%'>" . $forms->text("email_subject", $forms->report['email_subject'], 40, 40,"", array("placeholder"=>"Mandatory")) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='20%'>Message</td>";
    print "<td width='80%'>" . $forms->textarea("email_message", $forms->report['email_message'], 3, 120, array("placeholder"=>"Mandatory")) . "</td>";
    print "</tr>";
    switch (TRUE) {
      case (!$forms->report['email_action']):
      case (sizeof($forms->error)):
        $text="";
        break;
      case (!$forms->report['email_results']):
        $text="Error, email not sent";
        break;
      default:
        $text="Email sent to {$_SESSION['user']->email}";
    }
    if ($text) {
        print "<tr>";
        print "<td>Status</td>";
        print "<td>{$text}</td>";
        print "</tr>";
    }
    print "</table>";
    break;
  case ($forms->report['action']=="urlencode"):
    $text=array();
    $text[]=$forms->radio("urlencode_action","decode",$forms->report['urlencode_action']) . "Encoded text:";
    $text[]=$forms->textarea("urlencode_encoded",$forms->report['urlencode_encoded'],4,60,array("onfocus"=>"document.scs_form.urlencode_action[0].checked=true;"));
    $text[]=$forms->radio("urlencode_action","encode",$forms->report['urlencode_action']) . "Plain text:";
    $text[]=$forms->textarea("urlencode_decoded",$forms->report['urlencode_decoded'],4,60,array("onfocus"=>"document.scs_form.urlencode_action[1].checked=true;"));
    print "<p>" . implode("<br>", $text) . "</p>";
    break;
  case ($forms->report['action']=="image"):
    print "<br>";
    print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='20%'>Image file</td>";
    $text=array();
    $text[]=$forms->file("image_file");
    if ($forms->error_text['image_file']) $text[]=$forms->error_text['image_file'];
    print "<td width='80%'>" . implode("<br>", $text) . "</td>";
    print "</tr>";
    if (property_exists($forms->constant, "image_type")) {
        $src=array("data:");
        $src[]=$forms->constant->image_type;
        $src[]=";base64,";
        $src[]=$forms->constant->image_base64;
        $img="<img src=\"" . implode("", $src) . "\">";
        print $forms->hidden("image_src", $img);
        print "<tr>";
        print "<td>" . fn_href("Copy to clipboard","javascript:navigator.clipboard.writeText(jQuery('#image_src').val());") . "</td>";
        print "<td>{$img}</td>";
        print "</tr>";
    }
    print "</table>";
    break;
  case ($forms->report['action']=="json"):
    $text=array("JSON Content");
    $text[]=$forms->textarea("json_content", $forms->report['json_content'], 10, 100);
    print "<p>" . implode("<br>", $text) . "</p>";
    $obj=json_decode($forms->report['json_content']);
    $text=array();
    switch (TRUE) {
      case (!strlen($forms->report['json_content'])):
        break;
      case (is_array($obj)):
      case (is_object($obj)):
        $text[]=(is_object($obj)) ? "Object" : "Array:";
        $text[]="<pre>" . print_r($obj, TRUE) . "</pre>";
        break;
      default:
        $text[]="Malformed JSON content";
    }
    print "<p>" . implode("<br>", $text) . "</p>";
    break;
  case ($forms->report['action']=="mx_record"):
    print "<p>Domain name: " . $forms->text("mx_record",$forms->report['mx_record'],60) . "</p>";
	if (sizeof($forms->constant->results)) print implode("",$forms->constant->results);
  	break;
  case ($forms->report['action']=="os"):
    print "<p>" . php_uname() . "</p>";
    break;
  case ($forms->report['action']=="exec"):
	$text=array("Execute Command");
    if ($forms->error_text['exec_command']) $text[]=$forms->error_text['exec_command'];
    $text[]=$forms->textarea("exec_command",$forms->report['exec_command'],6,100);
    print "<p>" . implode("<br>", $text) . "</p>";
    if ($forms->report["exec_results"]) print $forms->report["exec_results"];
    break;
  case ($forms->report['action']=="recaptcha"):
    $results=array();
	if (file_exists($forms->constant->recaptcha)) {
	    $ip="";
	    $contents=file($forms->constant->recaptcha);
	    foreach ($contents as $item) {
	        $item=trim($item);
	        switch (TRUE) {
	          case (!strlen($item)):
	            break;
	          case (!strlen($ip)):
	            $ip=$item;
	            $items=array();
	            break;
	          default:
	            $items[]=$item;
	            if ($item == "}") {
	                $obj=new stdClass();
	                $obj->ip=$ip;
	                $obj->json=json_decode(implode("",$items),TRUE);
	                $results[]=$obj;
	                $ip="";
	            }
	        }
	    }
	}
    $text=array();
    $text[]="File: " . $forms->constant->recaptcha;
    $text[]="Size: " . number_format(filesize($forms->constant->recaptcha));
    $text[]="Purge? " . $forms->checkbox("recaptcha_purge",1,0);
	print "<p>" . implode("<br>",$text) . "</p>";
    if (sizeof($results)) {
	    print "<table class='standard border tablesorter'>";
	    print "<thead>";
	    print "<tr>";
	    print "<th>IP Address</th>";
	    print "<th>Success</th>";
	    print "<th>Timestamp</th>";
	    print "<th>Host Name</th>";
	    print "<th>Score</th>";
	    print "<th>Action</th>";
	    print "</tr>";
	    print "</thead>";
	    print "<tbody>";
	    foreach ($results as $obj) {
	        print "<tr " . (is_array($obj->json['error-codes']) ? $forms->background->yellow : "") . ">";
	        print "<td>" . $obj->ip . "</td>";
	        print "<td>" . (($obj->json['success']) ? "Yes" : "No") . "</td>";
	        if (is_array($obj->json['error-codes'])) {
	            print "<td colspan=4>" . implode("<br>",$obj->json['error-codes']) . "</td>";
	          } else {
	            print "<td>" . date("m/d/Y h:i A",strtotime($obj->json['challenge_ts'])) . "</td>";
	            print "<td>" . $obj->json['hostname'] . "</td>";
	            print "<td>" . $obj->json['score'] . "</td>";
	            print "<td>" . $obj->json['action'] . "</td>";
	        }
	        print "</tr>";
	    }
	    print "</tbody>";
	    print "</table>";
	}
    break;
  case ($forms->report['action']=="input"):
	print "<table class='standard border'>";
    print "<tr><th width='20%'>Input Type</th><th width='40%'>Input</th><th width='40%'>Results</th></tr>";
	print "<tr>";
  	print "<td>color</td>";
    print "<td><input name=input_color type='color' value=" . fn_escape($forms->report['input_color']) . "></td>";
    print "<td>" . $forms->report['input_color'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>date</td>";
    print "<td><input name=input_date type='date' value=" . fn_escape($forms->report['input_date']) . "></td>";
    print "<td>" . $forms->report['input_date'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>datetime-local</td>";
    print "<td><input name=input_datetime_local type='datetime-local' value=" . fn_escape($forms->report['input_datetime_local']) . "></td>";
    print "<td>" . $forms->report['input_datetime_local'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>time</td>";
    print "<td><input name=input_time type='time' value=" . fn_escape($forms->report['input_time']) . "></td>";
    print "<td>" . $forms->report['input_time'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>month</td>";
    print "<td><input name=input_month type='month' value=" . fn_escape($forms->report['input_month']) . "></td>";
    print "<td>" . $forms->report['input_month'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>week</td>";
    print "<td><input name=input_week type='week' value=" . fn_escape($forms->report['input_week']) . "></td>";
    print "<td>" . $forms->report['input_week'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>range</td>";
    print "<td>0 <input name=input_range type='range' min='0' max='30' value=" . fn_escape($forms->report['input_range']) . "> 30</td>";
    print "<td>" . $forms->report['input_range'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>email</td>";
    print "<td><input name=input_email type='email' size=60 value=" . fn_escape($forms->report['input_email']) . "></td>";
    print "<td>" . $forms->report['input_email'] . "</td>";
    print "</tr>";
	print "<tr>";
  	print "<td>phone</td>";
    print "<td><input name=input_phone type='phone' size=20 value=" . fn_escape($forms->report['input_phone']) . " pattern='[0-9]{3}-[0-9]{3}-[0-9]{4}' placeholder='999-999-9999'></td>";
    print "<td>" . $forms->report['input_phone'] . "</td>";
    print "</tr>";
    print "</table>";
    print "<div>";
    print "Progress: <progress></progress>";
    print "</div>";
  	break;
  case ($forms->report['action']=="hex2file"):
    $text=array("Convert hexadecimal to file");
    $text[]=$forms->text("hex2file_file",$forms->report['hex2file_file'],30,30);
    if ($forms->error_text['hex2file_file']) $text[]=$forms->error_text['hex2file_file'];
    print "<p>" . implode("<br>",$text) . "</p>";
    $text=array("Hexadecimal content");
    if ($forms->error_text['hex2file_content']) $text[]=$forms->error_text['hex2file_content'];
    $text[]=$forms->textarea("hex2file_content",$forms->report['hex2file_content'],20,100);
    print "<p>" . implode("<br>",$text) . "</p>";
  	break;
  case ($forms->report['action']=="unlock"):
	$text=array();
    $text['code']=$forms->radio("unlock_action","code",$forms->report['unlock_action']) . "Unlock code ";
    $text['code'] .= $forms->text("unlock_code",$forms->report['unlock_code'],8,8,"",array("onfocus"=>"document.scs_form.unlock_action[0].checked=true;"));
    if ($forms->error_text['unlock_code']) $text['code'] .= " <b>" . $forms->error_text['unlock_code'] . "</b>";
    $text['timestamp']=$forms->radio("unlock_action","timestamp",$forms->report['unlock_action']) . "Timestamp to unlock ";
    $text['timestamp'] .= $forms->text("unlock_timestamp",$forms->report['unlock_timestamp'],10,10,"",array("onfocus"=>"document.scs_form.unlock_action[1].checked=true;"));
    if ($forms->error_text['unlock_timestamp']) $text['timestamp'] .= " <b>" . $forms->error_text['unlock_timestamp'] . "</b>";
    $text['date']=$forms->radio("unlock_action","date",$forms->report['unlock_action']) . "Date/time to unlock ";
    $text['date'] .= $forms->text("unlock_date",$forms->report['unlock_date'],24,24,"",array("onfocus"=>"document.scs_form.unlock_action[2].checked=true;"));
    if ($forms->error_text['unlock_code']) $text['date'] .= " <b>" . $forms->error_text['unlock_code'] . "</b>";
	print "<p>" . implode("<br>",$text) . "</p>";
	break;
  case ($forms->report['action']=="ftp"):
  	print $forms->hidden("ftp_action",1);
	print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='20%'>Command</td>";
    $text=array($forms->select("ftp_command",$menu->ftp->constant->command,$menu->ftp->command));
    if ($forms->error_text['ftp_command']) $text[]=$forms->error_text['ftp_command'];
    print "<td width='80%'>" . implode("",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Host</td>";
    $text=array($forms->text("ftp_host",$menu->ftp->host,60));
    if ($forms->error_text['ftp_host']) $text[]=$forms->error_text['ftp_host'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Port#</td>";
    print "<td>" . $forms->text("ftp_port",$menu->ftp->port,5,5,"decimal:0") . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>User name</td>";
    $text=array($forms->text("ftp_user",$menu->ftp->user,60));
    if ($forms->error_text['ftp_user']) $text[]=$forms->error_text['ftp_user'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Password</td>";
    print "<td>" . $forms->text("ftp_password",$menu->ftp->password,60) . "</td>";
    print "</tr>";

    print "<tr>";
    print "<td>Remote Folder</td>";
    $text=array($forms->text("ftp_remote_folder",$menu->ftp->remote_folder,60));
    if ($forms->error_text['ftp_remote_folder']) $text[]=$forms->error_text['ftp_remote_folder'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Local Folder</td>";
    $text=array($forms->text("ftp_local_folder",$menu->ftp->local_folder,60));
    if ($forms->error_text['ftp_local_folder']) $text[]=$forms->error_text['ftp_local_folder'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Files</td>";
    $text=array();
    if ($forms->error_text['ftp_files']) $text[]=$forms->error_text['ftp_files'];
    $text[]=$forms->textarea("ftp_files",$menu->ftp->files,5,60);
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Local File</td>";
    $text=array($forms->text("ftp_local_file",$menu->ftp->local_file,60));
    if ($forms->error_text['ftp_local_file']) $text[]=$forms->error_text['ftp_local_file'];
    print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "</tr>";
    print "<tr>";
    print "<td>Binary?</td>";
    print "<td>" . $forms->checkbox("ftp_binary",1,$menu->ftp->binary) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>PASV?</td>";
    $text=array($forms->checkbox("ftp_pasv",1,$menu->ftp->pasv));
    if ($forms->error_text['ftp_pasv']) $text[]=$forms->error_text['ftp_pasv'];
    print "<td>" . implode(" ",$text) . "</td>";
    print "</tr>";
    print "</table>";
    if ($menu->ftp->status) print "<div class=standard>Status: " . $menu->ftp->status . "</div>";
    if (sizeof($menu->ftp->results)) print "<div class=standard>" . implode("",$menu->ftp->results) . "</div>";
	break;
  case ($forms->report['action']=="cookie"):
	print "<table class='standard noborder'>";
    print $forms->hidden("cookie_action",1);
	print "<tr>";
	print "<td width='20%'>PHP Version</td>";
	print "<td width='80%'>" . PHP_VERSION_ID . "</td>";
	print "</tr>";
    if ($_POST['cookie_action']) {
	    print "<tr>";
	    print "<td width='20%'>Cookie Set Response</td>";
	    print "<td width='80%'>" . (($forms->report['cookie_response']) ? "Success" : "Failed") . "</td>";
	    print "</tr>";
    }
	print "<tr>";
	print "<td>Cookie Name</td>";
    $text=array($forms->text("cookie_name",$forms->report['cookie_name'],60,0,"",array("placeholder"=>"Mandatory")));
    if ($forms->error_text['cookie_name']) $text[]=$forms->error_text['cookie_name'];
	print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Cookie Value</td>";
	print "<td width='80%'>" . $forms->textarea("cookie_text",$forms->report['cookie_text'],3,60,array("placeholder"=>"Cookie Value or blank to remove")) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Cookie Expires</td>";
    $text=array($forms->text("cookie_expires",$forms->report['cookie_expires'],60,0,"",array("placeholder"=>"e.g. +1 day; Last Tuesday")));
	if ($forms->error_text['cookie_expires']) {
    	$text[]=$forms->error_text['cookie_expires'];
	  } else {
      	$text[]=date("m/d/Y h:i.s A", strtotime($forms->report['cookie_expires']));
	}
	print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Path</td>";
	print "<td width='80%'>" . $forms->text("cookie_path",$forms->report['cookie_path'],60,0,"",array("placeholder"=>"Default /")) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Domain</td>";
	print "<td width='80%'>" . $forms->text("cookie_domain",$forms->report['cookie_domain'],60,0,"",array("placeholder"=>"example.com for all subdomains")) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Secure?</td>";
	print "<td width='80%'>" . $forms->checkbox("cookie_secure",1,$forms->report['cookie_secure']) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>HTTP Only?</td>";
	print "<td width='80%'>" . $forms->checkbox("cookie_httponly",1,$forms->report['cookie_httponly']) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Same Site</td>";
	print "<td width='80%'>" . $forms->select("cookie_samesite",array("None","Lax","Strict"),$forms->report['cookie_samesite'],FALSE) . " (PHP Version 70300 required) </td>";
	print "</tr>";
    print "<tr><td colspan=2><b>Existing Cookies</b></td></tr>";
	$cookie=$_COOKIE;
    ksort($cookie);
	foreach ($cookie as $key => $value) {
    	print "<tr>";
        $text=array();
        $text[]=$forms->radio("cookie",$key,"",array("onclick"=>"fn_cookie(" . fn_escape($key) . "," . fn_escape(urlencode($value)) . ");"));
        $text[]=$key;
        print "<td>" . implode(" ",$text) . "</td>";
        print "<td>" . urlencode($value) . "</td>";
        print "</tr>";
    }
	print "</table>";
  	break;
  case ($forms->report['action']=="session"):
	print "<table class='standard noborder'>";
    print $forms->hidden("session_action",1);
	print "<tr>";
	print "<td width='20%'>Name</td>";
    $text=array("scs_toolbox");
    $text[]=( (isset($_SESSION['scs_toolbox'])) ? "(Set)" : "(Not Set)");
	print "<td width='80%'>" . implode(" ",$text) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Session Value</td>";
	print "<td width='80%'>" . $forms->text("session_value",$_SESSION['scs_toolbox'],20,20,"",array("placeholder"=>"Value or blank to remove")) . "</td>";
	print "</tr>";
	print "</table>";
  	break;
  case ($forms->report['action']=="geocode"):
  	print $forms->hidden("geocode",1);
	print "<table class='standard noborder'>";
	print "<tr>";
	print "<td width='20%'>From</td>";
    $content=array();
    $content[]="Address:";
    $content[]=$forms->text("geocode_location1_address",$forms->report['geocode_location1_address'],50);
    $content[]="Zip:";
    $content[]=$forms->text("geocode_location1_zip",$forms->report['geocode_location1_zip'],10,10);
	$text=array(implode(" ",$content));
	if (array_key_exists("geocode_location1_results",$forms->report)) $text[]=$forms->report['geocode_location1_results']->detail;
    switch (TRUE) {
      case ($forms->error_text['geocode_location1_address']):
      	$text[]=$forms->error_text['geocode_location1_address'];
        break;
      case ($forms->error_text['geocode_location1_zip']):
      	$text[]=$forms->error_text['geocode_location1_zip'];
        break;
	}
	print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>To</td>";
    $content=array();
    $content[]="Address:";
    $content[]=$forms->text("geocode_location2_address",$forms->report['geocode_location2_address'],50);
    $content[]="Zip:";
    $content[]=$forms->text("geocode_location2_zip",$forms->report['geocode_location2_zip'],10,10);
	$text=array(implode(" ",$content));
	if (array_key_exists("geocode_location2_results",$forms->report)) $text[]=$forms->report['geocode_location2_results']->detail;
    switch (TRUE) {
      case ($forms->error_text['geocode_location2_address']):
      	$text[]=$forms->error_text['geocode_location2_address'];
        break;
      case ($forms->error_text['geocode_location2_zip']):
      	$text[]=$forms->error_text['geocode_location2_zip'];
        break;
	}
	print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td width='20%'>Distance</td>";
    $text=array();
    if ( ($_POST['geocode']) && (!sizeof($forms->error)) ) $text[]=geocode_distance($forms->report['geocode_location1_results']->location, $forms->report['geocode_location2_results']->location, $forms->report['geocode_miles'],2 );
    $text[]=$forms->select("geocode_miles",array(1=>"Miles",0=>"KM"),$forms->report['geocode_miles'],FALSE,array(),FALSE);
	print "<td width='80%'>" . implode(" ",$text) . "</td>";
	print "</tr>";
	print "</table>";
    print "<p>" . $forms->button("Get Current Location",array("onclick"=>"location_get();")) . " " . "<span id=geocode_location></span></p>";
    break;
  case ($forms->report['action']=="dns_get_record"):
    print "<p>Domain name: " . $forms->text("dns_domain",$forms->report['dns_domain'],60) . "</p>";
    if ( (sizeof($forms->error)) || (!$forms->report['dns_domain']) ) break;
    $results=array();
    $dns_type=array(
		"A"=>"An IPv4 addresses in dotted decimal notation.",
        "MX"=>"Priority of mail exchanger. Lower numbers indicate greater priority. target: FQDN of the mail exchanger.",
        "CNAME"=>"FQDN of location in DNS namespace to which the record is aliased.",
        "NS"=>"FQDN of the name server which is authoritative for this hostname.",
        "PTR"=>"Location within the DNS namespace to which this record points.",
        "TXT"=>"Arbitrary string data associated with this record.",
        "HINFO"=>"IANA number designating the CPU of the machine referenced by this record. os: IANA number designating the Operating System on the machine referenced by this record.",
        "SOA"=>"FQDN of the machine from which the resource records originated. rname: Email address of the administrative contain for this domain. serial: Serial # of this revision of the requested domain. refresh: Refresh interval (seconds) secondary name servers should use when updating remote copies of this domain. retry: Length of time (seconds) to wait after a failed refresh before making a second attempt. expire: Maximum length of time (seconds) a secondary DNS server should retain remote copies of the zone data without a successful refresh before discarding. minimum-ttl: Minimum length of time (seconds) a client can continue to use a DNS resolution before it should request a new resolution from the server. Can be overridden by individual resource records.",
        "AAAA"=>"IPv6 address",
        "A6"=>"Length (in bits) to inherit from the target specified by chain. ipv6: Address for this specific record to merge with chain. chain: Parent record to merge with ipv6 data.",
		"SRV"=>"(Priority) lowest priorities should be used first. weight: Ranking to weight which of commonly prioritized targets should be chosen at random. target and port: hostname and port where the requested service can be found. For additional information see: � RFC 2782",
        "NAPTR"=>"Equivalent to pri and weight above. flags, services, regex, and replacement: Parameters as defined by � RFC 2915."
    );
    $results[]="<table class='border'>";
    $results[]="<tr>";
    $results[]="<th width='10%'>Item</th>";
    $results[]="<th width='20%'>Key</th>";
    $results[]="<th width='80%'>Value</th>";
    $results[]="</tr>";
    foreach ($dns_results as $i => $items) {
    	$row=0;
    	foreach ($items as $key => $value) {
	        $results[]="<tr>";
            if (!$row) $results[]="<td rowspan=" . sizeof($items) . " class=center>{$i}</td>";
	        $results[]="<td>{$key}</td>";
            $content=array();
            if (is_array($value)) {
            	foreach ($value as $item_key => $item_value) {
                	$content[]="{$item_key}: {$item_value}";
                }
              } else {
              	$content[]=$value;
            }
	        $results[]="<td>" . implode("<br>",$content) . "</td>";
	        $results[]="</tr>";
            $row++;
		}
    }
    $results[]="</table>";
	print implode("",$results);
	break;
  case ($forms->report['action']=="phplog"):
  	$logfile=ini_get("error_log");
    print "<table class='standard noborder'>";
    print $forms->caption();
    print "<tr>";
    print "<td width='20%'>Logfile</td>";
    print "<td width='80%'>{$logfile}</td>";
    print "</tr>";
    print "<tr>";
    print "<td>File size</td>";
    print "<td>" . ( (file_exists($logfile)) ? number_format(filesize($logfile)) : "Not found") . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Action</td>";
    $action=array("Clear","Upload");
    if ($logfile) $action[]="View";
    $text=array($forms->select("phplog",$action,""));
    if ($forms->error_text['phplog']) $text[]=$forms->error_text['phplog'];
    print "<td>" . implode(" ",$text) . "</td>";
    print "</tr>";
    print "</table>";
    if ( ($_POST['phplog'] == "View") && (!sizeof($forms->error)) ) print $forms->div(implode("<br>",$forms->constant->phplog));
	break;
  case ($forms->report['action']=="hotfix"):
  	$query=array("select * from registry");
    $query[]="where module in('hotfix','hotfix_local')";
    $query[]="order by field,item,module";
	$database->registry->query($query);
    print "<table class='standard border'>";
    print "<tr>";
    print "<th width='5%'>Del?</th>";
    print "<th width='10%'>Date</th>";
    print "<th width='10%'>Source</th>";
    print "<th width='75%'>Description</th>";
    print "</tr>";
    while ($database->registry->fetch = $database->registry->fetch_array() ) {
    	$database->registry->fetch();
        print "<tr>";
        print "<td class=center>" . $forms->checkbox(fn_base64(array("hotfix",$database->registry->fetch['id'])),1,0) . "</td>";
        print "<td class=center>" . $database->registry->fetch['field'] . "</td>";
        print "<td>" . $database->registry->fetch['module'] . "</td>";
        print "<td>" . str_replace("\n","<br>",$database->registry->fetch['data']) . "</td>";
        print "</tr>";
    }
    print "</table>";
    break;
  case ($forms->report['action']=="curl"):
  	$text=curl_version();
  	print "<table class='standard border'>";
    foreach ($text as $key => $value) {
    	print "<tr>";
        print "<td width='20%'>{$key}</td>";
        print "<td width='80%'>" . (is_array($value) ? implode(", ",$value) : $value ) . "</td>";
        print "</tr>";
    }
    print "</table>";
	break;
  case ($forms->report['action']=="url_build"):
	if (strlen($forms->constant->url)) print "<p><b>" . $forms->constant->url . "</b></p>";
  	print "<table class='standard border'>";
    print "<tr>";
    print "<td width='20%'>Scheme</td>";
    print "<td width='80%'>" . $forms->select("url_scheme",array("https://","http://"),$forms->report['url_scheme'],FALSE) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Host</td>";
    $text=array( $forms->text("url_host",$forms->report['url_host'],60) );
    if ($forms->error_text['url_host']) $text[]=$forms->error_text['url_host'];
	print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Path /</td>";
    $text=array( $forms->text("url_path",$forms->report['url_path'],60) );
    if ($forms->error_text['url_path']) $text[]=$forms->error_text['url_path'];
	print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
    print "<tr><td colspan=2>Query Field(s)</td></tr>";
    for ($i=0; $i < 10; $i++) {
    	print "<tr>";
    	$field="url_key{$i}";
        $text=array($forms->text($field,$forms->constant->url_query[$i]->key,20));
        if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
        print "<td>" . implode("<br>",$text) . "</td>";
    	$field="url_value{$i}";
        $text=array($forms->text($field,$forms->constant->url_query[$i]->value,60));
        if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
        print "<td>" . implode("<br>",$text) . "</td>";
        print "</tr>";
    }
    print "<tr>";
    print "<td>Fragment #</td>";
    $text=array( $forms->text("url_fragment",$forms->report['url_fragment'],60) );
    if ($forms->error_text['url_fragment']) $text[]=$forms->error_text['url_fragment'];
	print "<td>" . implode("<br>",$text) . "</td>";
    print "</tr>";
  	print "</table>";
	break;
/*
https://colsongroup-embedded.partcommunity.com/3d-cad-models/?info=colsongroup%2Falbion%2Fcasters%2F02_asmtab.prj&varset=%7BSER%3D02+Series%7D%2C%7BMO%3DS%7D%2C%7BSIZE%3D%7D%2C%7BTT%3DTM+-+Phenolic%7D%2C%7BD%3D3.00%7D%2C%7BW%3D1.25000000%7D%2C%7BWB%3D01%7D%2C%7BBR%3D%7D&hidePortlets=briefcase
*/
  case ($forms->report['action']=="url_parse"):
  	print "<table class='standard border'>";
    print "<tr>";
    print "<td width='20%'>URL</td>";
    print "<td colspan=2 width='80%'>" . $forms->textarea("url",$forms->report['url'],3,80) . "</td>";
    print "</tr>";
    if ($forms->report['url']) {
    	foreach ($forms->constant->url as $key => $value) {
			if ($key == "query") {
            	parse_str($value,$url_query);
                $count=0;
                foreach ($url_query as $query_key => $query_value) {
                    print "<tr>";
                    if (!$count) print "<td rowspan=" . sizeof($url_query) . " width='20%'>{$key}</td>";
                    print "<td width='20%'>{$query_key}</td>";
                    print "<td width='60%'>{$query_value}</td>";
                    print "</tr>";
                	$count++;
                }
              } else {
	            print "<tr>";
	            print "<td width='20%'>{$key}</td>";
	            print "<td colspan=2>{$value}</td>";
	            print "</tr>";
			}
        }
    }
  	print "</table>";
	break;
  case ($forms->report['action']=="unserialize"):
	print "<p>" . $forms->button("Clear serialize content",array("onclick"=>"document.getElementById('unserialize').value = '';")) . "<br>" . $forms->textarea("unserialize",$_POST['unserialize'],8,80) . "</p>";
    $content=trim($_POST['unserialize']);
	if (!strlen($content)) break;
    $results=unserialize($content);
    print "<pre>" . print_r($results,TRUE) . "</pre>";
	break;
  case ($forms->report['action']=="jquery"):
  	print "<table class='standard border'>";
    print "<tr>";
    print "<th width='10%'>Class</th>";
    print "<th width='20%'>Other</th>";
    print "<th width='80%'>Example</th>";
    print "</tr>";
    print "<tr>";
    print "<td>datepicker</td>";
    print "<td>&nbsp;</td>";
    print "<td>Date: " . $forms->text("date","",10,10,"date") . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>tabs</td>";
    print "<td></td>";
    print "<td>";
	$tabs=new jquery_tab_class("standard");
    $tabs->item("1st","This is my 1st tab");
    $tabs->item("2nd","<p>This is my 2nd tab</p><p>More content</p>");
    $tabs->item("3rd","<p>This is my last tab</p><p>Even more content</p><p>Expands to fit size");
    print $tabs->output();
    print "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>sortable</td>";
    print "<td></td>";
    print "<td>";

    print "<ol class='sortable standard'>";
    print "<li class='ui-state-default'>Cerritos, CA</li>";
    print "<li class='ui-state-default'>Aurora, OH</li>";
    print "<li class='ui-state-default'>Memphis, TN</li>";
    print "<li class='ui-state-default'>Helena, MT</li>";
    print "<li class='ui-state-default'>Bangor, ME</li>";
    print "<li class='ui-state-default'>Indianapolis, IN</li>";
	print "</ol>";

    print "</td>";
    print "</tr>";

    print "<tr>";
    print "<td>accordian</td>";
    print "<td></td>";
    print "<td>";

    print "<div class='accordion'>";
    print "<h3>John Jones</h3>";
    print "<p>Memphis, TN";
    print "<br>Helena, MT</p>";
    print "<h3>David Grant</h3>";
    print "<p>Cerritos, CA";
    print "<br>Aurora, OH";
    print "<br>Bangor, ME";
    print "<br>Indianapolis, IN</p>";
	print "</div>";

    print "</td>";
    print "</tr>";

    print "<tr>";
    print "<td>tablesorter</td>";
    print "<td>tablesorter.css<br>tablesorter.js</td>";
    print "<td>";

	print "<table class='standard border tablesorter'>";
    print "<thead>";
    print "<tr><th>ID</th><th>City</th><th>State</th><th>Rank</th></tr>";
    print "</thead>";
    print "<tbody>";
    print "<tr><td>2492</td><td>Cerritos</td><td>CA</td><td class=right>1</td></tr>";
    print "<tr><td>2055</td><td>Aurora</td><td>OH</td><td class=right>2</td></tr>";
    print "<tr><td>2419</td><td>Memphis</td><td>TN</td><td class=right>3</td></tr>";
    print "<tr><td>1189</td><td>Helena</td><td>MT</td><td class=right>4</td></tr>";
    print "<tr><td>1352</td><td>Bangor</td><td>ME</td><td class=right>5</td></tr>";
    print "<tr><td>1019</td><td>Indianapolis</td><td>IN</td><td class=right>6</td></tr>";
    print "</tbody>";
    print "</table>";

    print "</td>";
    print "</tr>";

  	print "</table>";
	break;
  case ($forms->report['action']=="ini"):
	print $forms->hidden("ini_action",TRUE);
    if ( ($forms->report['ini_action']) && (!sizeof($forms->error)) ) {
		$tempfile=$_SERVER['DOCUMENT_ROOT'] . "/scs.tmp";
		if (file_exists($tempfile)) unlink($tempfile);
        move_uploaded_file($_FILES['ini_file']['tmp_name'],$tempfile);
        $contents=file($tempfile);
        unlink($tempfile);
		$text=array();
        $text[]="File name: <b>" . $_FILES['ini_file']['name'] . "</b>";
        $text[]="File size: <b>" . number_format(intval($_FILES['ini_file']['size'])) . "</b>";
        $text[]="Records: <b>" . number_format(sizeof($contents)) . "</b>";
		print "<p>" . implode("<br>",$text) . "</p>\n";
        $results=array();
        $results['empty']=array();
        $module="empty";
        foreach ($contents as $line) {
			$line=trim($line);
            switch (TRUE) {
              case (!strlen($line)):
              case (substr($line,0,1) == ";"):
              	break;
              case (substr($line,0,1) == "["):
            	$module=$line;
                if (!array_key_exists($module,$results)) {
					$results[$module]=array();
				  } else {
                  	$results[$module][]=$forms->font("red","Duplicate module");
				}
                break;
              case (!strlen($module)):
              	break;
			  default:
				$results[$module][]=$line;
                break;
            }
        }
	    foreach ($results as $module => $items) {
	        if (sizeof($items)) {
	            print "<p><b>$module</b><br>" . implode("<br>\n",$items) . "</p>\n";
	        }
	    }
        print "<hr>\n";
	}
    $text=array();
    $text[]=$forms->file("ini_file");
    if (array_key_exists("ini_file",$forms->error_text)) $text[]="<b>" . $forms->error_text['ini_file'] . "</b>";
    $text[]=$forms->submit("Go!");
    print "<p>" . implode("<br>",$text) . "</p>\n";
	break;

  case ($forms->report['action']=="extensions"):
	$text=get_loaded_extensions();
    print "<p class=standard>" . implode("<br>",$text) . "</p>";
	break;
  case ($forms->report['action']=="http_headers"):
	$forms->report['http_headers'][]=headers_list();
    $results=array();
	for ($i=0; $i < 2; $i++) {
	    foreach ($forms->report['http_headers'][$i] as $item) {
			list($code, $value)=preg_split("/:/",$item,2);
	        if (!array_key_exists($code,$results)) $results[$code]=array();
	        $results[$code][$i]=$value;
	    }
	}
	print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<th width='33%'>Code</th>\n";
    print "<th width='33%'>Default</th>\n";
    print "<th width='33%'>Sent</th>\n";
    print "</tr>\n";
    foreach ($results as $code => $item) {
	    print "<tr>\n";
	    print "<td>$code</td>\n";
	    print "<td>" . $item[0] . "</td>\n";
	    print "<td>" . $item[1] . "</td>\n";
	    print "</tr>\n";
    }
    print "</table>\n";
	break;
  case ($forms->report['action']=="password"):
	print $forms->hidden("password_action",TRUE);
    if ( ($forms->report['password_action']) && (!sizeof($forms->error)) ) {
    	print "<p>Password: <b>" . implode("",$forms->report['password_output']) . "</b></p>\n";
    	print "<p>Dataset: <b>" . $forms->constant->password_dataset . "</b></p>\n";
	}
	$results=array();
	$results[]=$forms->select_number('password_upper_size',$forms->report['password_size']['upper'],0,16,array("onchange"=>"fn_password_length();")) . " Upper case";
	$results[]=$forms->select_number('password_lower_size',$forms->report['password_size']['lower'],0,16,array("onchange"=>"fn_password_length();")) . " Lower case?";
	$results[]=$forms->select_number('password_numeric_size',$forms->report['password_size']['numeric'],0,16,array("onchange"=>"fn_password_length();")) . " Numeric?";
	$results[]=$forms->select_number('password_symbol_size',$forms->report['password_size']['symbol'],0,16,array("onchange"=>"fn_password_length();")) . " Symbols?";
	$results[]=$forms->select_number('password_punctuation_size',$forms->report['password_size']['punctuation'],0,16,array("onchange"=>"fn_password_length();")) . " Punctuation?";
    $results[]="<b><span id='password_length_id'></span></b> Overall size";
    $results[]="";
	$results[]=$forms->checkbox('password_similar',1,$forms->report['password_similar']) . " Similar characters?";
	$results[]=$forms->checkbox('password_duplicate',1,$forms->report['password_duplicate']) . " Duplicate characters?";
	print "<p>" . implode("\n<br>",$results) . "</p>\n";
    print "<script type='text/javascript'>\n";
    print "fn_password_length();\n";
    print "function fn_password_length() {\n";
	print "  var password_length=0;\n";
    print "  password_length +=  parseInt(document.getElementById('password_upper_size').value);\n";
    print "  password_length +=  parseInt(document.getElementById('password_lower_size').value);\n";
    print "  password_length +=  parseInt(document.getElementById('password_numeric_size').value);\n";
    print "  password_length +=  parseInt(document.getElementById('password_symbol_size').value);\n";
    print "  password_length +=  parseInt(document.getElementById('password_punctuation_size').value);\n";
    print "  document.getElementById('password_length_id').innerHTML=password_length;\n";
    print "}\n";
    print "</script>\n";
	break;
  case ($forms->report['action']=="hex"):
    print
        "<p>" . $forms->radio("hex_action","hex",$forms->report['hex_action']) . "Integer: " .
		$forms->text("hex_integer",$forms->report['hex_integer'],20,20,"",array("onfocus"=>"document.scs_form.hex_action[0].checked=true;")) .
    	"<br>" . $forms->radio("hex_action","integer",$forms->report['hex_action']) . "Hex: " .
		$forms->text("hex_hex",$forms->report['hex_hex'],20,20,"",array("onfocus"=>"document.scs_form.hex_action[1].checked=true;")) .
		"</p>";
	break;
  case ($forms->report['action']=="base64"):
    print
        "<p>" . $forms->radio("base64_action","decode",$forms->report['base64_action']) . "Encoded text " .
		"<br>" . $forms->textarea("base64_encoded",$forms->report['base64_encoded'],4,60,array("onfocus"=>"document.scs_form.base64_action[0].checked=true;")) .
    	"<br>" . $forms->radio("base64_action","encode",$forms->report['base64_action']) . "Plain text " .
		"<br>" . $forms->textarea("base64_text",$forms->report['base64_text'],4,60,array("onfocus"=>"document.scs_form.base64_action[1].checked=true;")) .
		"</p>";
	    $text=array();
	    $text[]="Use the following to represent special characters:";
	    $text[]="{nul} (Null) 0x00";
	    $text[]="{soh} (Start of header) 0x01";
	    $text[]="{stx} (Start of text) 0x02";
	    $text[]="{etx} (End of text) 0x03";
	    $text[]="{eot} (End of Transmission) 0x04";
	    $text[]="{enq} (Enquire) 0x05";
	    $text[]="{ack} (Acknowledge) 0x06";
	    $text[]="{bel} (Bell) 0x07";
	    $text[]="{bs} (Backspace) 0x08";
	    $text[]="{ht} (Horizontal tab) 0x09";
	    $text[]="{lf} (Line feed) 0x0A";
	    $text[]="{vt} (Vertical tab) 0x0B";
	    $text[]="{ff} (Form feed) 0x0C";
	    $text[]="{cr} (Carriage return) 0x0D";
	    $text[]="{so} (Shift out) 0x0E";
	    $text[]="{si} (Shift in) 0x0F";
	    $text[]="{dle} (Data link escape) 0x10";
	    $text[]="{dc1} (Device control 1) 0x11";
	    $text[]="{dc2} (Device control 2) 0x12";
	    $text[]="{dc3} (Device control 3) 0x13";
	    $text[]="{dc4} (Device control 4) 0x14";
	    $text[]="{nak} (Negative ACK) 0x15";
	    $text[]="{syn} (Synchronous idle) 0x16";
	    $text[]="{etb} (End of trans block) 0x17";
	    $text[]="{can} (Cancel) 0x18";
	    $text[]="{em} (End of medium) 0x19";
	    $text[]="{sub} (Substitute) 0x1A";
	    $text[]="{esc} (Escape) 0x1B";
	    $text[]="{fs} (File separator) 0x1C";
	    $text[]="{gs} (Group separator) 0x1D";
	    $text[]="{rs} (Record separator) 0x1E";
	    $text[]="{us} (Unit separator) 0x1F";
	    $text[]="{sp} (Space) 0x20";
		print "<p class=standard>" . implode("<br>",$text) . "</p>\n";
	break;
	break;
  case ($forms->report['action']=="browscap"):
	$results=array();
    $results[]="<b>Browser Agent</b>";
    $results[]=$forms->textarea("browscap_agent",$forms->report['browscap_agent'],3,80);
    if (ini_get("browscap")) {
		$filename=ini_get("browscap");
    	$results[]="Browscap: $filename";
        if (file_exists($filename)) {
			$stats=stat($filename);
            $results[]="Revised: " . date("m/d/Y h:i A",$stats['mtime']);
            $results[]="Size: " . number_format($stats['size']);
        }
    }
	print "<p class=standard>" . implode("<br>",$results) . "</p>\n";

    if ($forms->report['browscap_agent']) {
	    $agent=get_browser($forms->report['browscap_agent']);
	    $results=array();
	    foreach ($agent as $key => $value) {
	        $results[]="$key: $value";
	    }
	    print "<p class=standard><b>Results:</b><br>" . implode("<br>",$results) . "</p>\n";
	}
	break;
  case ($forms->report['action']=="filedump"):
	print "<table class='standard noborder'>\n";
    print $forms->caption();
    print "<tr>\n";
    print "<td width='20%'>Upload file</td>\n";
    print "<td width='80%'>" . $forms->file("filedump_file",60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Output</td>\n";
    print "<td>"  . $forms->select("filedump_output",array("table","dump"),$forms->report['filedump_output'],FALSE) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    if ( (!$forms->report['filedump_output']) || (sizeof($forms->error)) ) break;
    print
    	"<p class=standard>" . "File name: " . $_FILES['filedump_file']['name'] .
		"<br>Type: " . $_FILES['filedump_file']['type'] .
		"<br>Size: " . number_format(intval($_FILES['filedump_file']['size'])) .
		"</p>\n";
    if (file_exists("scs.tmp")) unlink("scs.tmp");
    move_uploaded_file($_FILES['filedump_file']['tmp_name'],"scs.tmp");
	$contents=file_get_contents("scs.tmp");
	if ($forms->report['filedump_output']=="table") {
		$hex_array=array(0,1,2,3,4,5,6,7,8,9,'A','B','C','D','E','F');
		print "<table class='standard border even_odd'>\n";
        print "<tr>\n";
        print "<th width='20%'>Row#</th>\n";
        foreach ($hex_array as $item) {
			print "<th width='5%'>$item</th>\n";
        }
        print "</tr>\n";
		$rows=str_split($contents,16);
        foreach ($rows as $row => $row_data) {
	        print "<tr>\n";
	        print "<td class='right' rowspan=2>$row</td>\n";
			$data=str_split($row_data);
            foreach ($data as $data_char) {
				switch (TRUE) {
                  case ($data_char <  "\x0f"):
                  case ($data_char >  "\x7e"):
                  	$text="&#9899;";
					break;
                  case ($data_char== "\x20"):
                  	$text="&sdot;";
					break;
                  default:
                  	$text=$data_char;
				}
				print "<td class=center>$text</td>\n";
            }
            print "<tr>\n";
            foreach ($data as $data_char) {
				print "<td class=center>" . bin2hex($data_char) . "</td>\n";
            }
            print "</tr>\n";
            print "</tr>\n";
        }
        print "</table>\n";
	  } else {
 		print "<p class=standard>\n\n";
        print bin2hex($contents);
        print "\n\n</p>";
    }
    unlink("scs.tmp");
	break;
  case ($forms->report['action']=="file"):
    $file_folder=array();
    $file_folder[]="/";
	$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT']),RecursiveIteratorIterator::SELF_FIRST);
	foreach ($files as $object) {
		if ($object->isDir()) $file_folder[]=preg_replace(array("/" . preg_quote($_SERVER['DOCUMENT_ROOT'],"/") . "/i","/\\\/"),array("","/"),$object->getPathname() );
	}
	print $forms->hidden("file_action","list");
	print "<table class='standard noborder'>\n";
    print $forms->caption();
    print "<tr>\n";
    print "<td width='20%'>Folder</td>\n";
    print "<td width='80%'>" . $forms->select("file_folder",$file_folder,$forms->report['file_folder']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Include subfolders?</td>\n";
    print "<td>"  . $forms->checkbox("file_subfolder",1,$forms->report['file_subfolder']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>File name</td>\n";
    print "<td>"  . $forms->text("file_name",$forms->report['file_name'],60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Date Range</td>\n";
    print
    	"<td>" . $forms->select("file_date_type",array("create"=>"Creation Date","change"=>"Change Date"),$forms->report['file_date_type'],FALSE) .
        " From: "  . $forms->text("file_from_date",$forms->report['file_from_date'],10,10,"date") .
        " Thru: " . $forms->text("file_thru_date",$forms->report['file_thru_date'],10,10,"date") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Size Range</td>\n";
    print
    	"<td>From: "  . $forms->text("file_from_size",$forms->report['file_from_size'],12,12,"decimal:0") .
        " Thru: " . $forms->text("file_thru_size",$forms->report['file_thru_size'],12,12,"decimal:0") . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	if ( (!$forms->report['file_action']) || (sizeof($forms->error)) ) break;
	$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . $forms->report['file_folder']));
    $results=array();
    foreach ($files as $object) {
		if ($object->isDir()) continue;
		if ($forms->report['file_date_type'] == "create") {
			$compare_date=date("Y/m/d",$object->getCTime());
		  } else {
			$compare_date=date("Y/m/d",$object->getMTime());
        }
        $foldername=preg_replace(array("/" . preg_quote($_SERVER['DOCUMENT_ROOT'],"/") . "/i","/\\\/"),array("","/"),$object->getPath() );
        if (!$foldername) $foldername="/";
		switch (TRUE) {
          case ( ($forms->report['file_name']) && (!preg_match("/" . preg_quote($forms->report['file_name'],"/") . "/i",$object->getFilename())) ):
          case ( ($forms->report['file_from_date']) && ($compare_date < $forms->report['file_from_date']) ):
          case ( ($forms->report['file_thru_date']) && ($compare_date > $forms->report['file_thru_date']) ):
          case ( ($forms->report['file_from_size']) && ($object->getSize() < $forms->report['file_from_size']) ):
          case ( ($forms->report['file_thru_size']) && ($object->getSize() > $forms->report['file_thru_size']) ):
		  case ( ($forms->report['file_folder']) && (!$forms->report['file_subfolder']) && ($foldername != $forms->report['file_folder']) ):
			break;
		default:
            if (!isset($foldername,$results)) $results[$foldername]=array();
			$results[$foldername][]=$object;
		}
	}
    if (!sizeof($results)) {
		print "<p class='standard center'>No files found</p>\n";
		break;
    }
	ksort($results);
    print "<table class='standard border'>\n";
    print $forms->caption("<br>Scanning folder: " . $_SERVER['DOCUMENT_ROOT'] . $forms->report['file_folder']);
    print "<tr>\n";
    print "<th>Folder</th>\n";
    print "<th>File name</th>\n";
    print "<th>Type</th>\n";
    print "<th>Size</th>\n";
    print "<th>Create Date/Time</th>\n";
    print "<th>Change Date/Time</th>\n";
    print "<th>Permissions</th>\n";
    print "</tr>\n";
	$count=0;
    foreach ($results as $foldername => $folder) {
		foreach ($folder as $item => $object) {
			$count++;
	        print "<tr>\n";
            if (!$item) print "<td rowspan=" . sizeof($folder) . ">$foldername</td>\n";
	        print "<td>" . $object->getFilename() . "</td>\n";
	        print "<td>" . $object->getExtension() . "</td>\n";
	        print "<td class='right nobr'>" . number_format($object->getSize()) . "</td>\n";
	        print "<td class='center nobr'>" . date("m/d/Y h:i A",$object->getCTime()) . "</td>\n";
	        print "<td class='center nobr'>" . date("m/d/Y h:i A",$object->getMTime()) . "</td>\n";
	        print "<td class='center nobr'>" . fn_perms($object->getPerms())  . "</td>\n";
	        print "</tr>\n";
	    }
	}
	print "</table>\n";
    print "<p class='standard center'>" . number_format($count) . " File(s) found in " . number_format(sizeof($results)) . " Folder(s)</p>\n";
	break;
  case ($forms->report['action']=="script"):
	$script_table=array();
	$database->temp->query("show table status");
	while ($database->temp->fetch = $database->temp->fetch_array()) {
	    $script_table[ $database->temp->fetch['Name'] ]=$database->temp->fetch['Name'] . " (" . number_format($database->temp->fetch['Rows']) . ")";
	}
	$database->temp->free_result();
    print "<table class='standard noboder'>\n";
    print "<tr>\n";
  	print "<td width='20%'>Table</td>\n";
    print "<td width='80%'>" . $forms->select("script_table",$script_table,$forms->report['script_table']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
  	print "<td>Timeout</td>\n";
    print "<td> " . $forms->select("script_timeout",array(30,60,90,120,150,180),$forms->report['script_timeout'],FALSE) . " seconds" . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
  	print "<td>Cleanup table?</td>\n";
    print "<td>" . $forms->checkbox("script_cleanup",1,$forms->report['script_cleanup']) . "</td>\n";
    print "</tr>\n";
  	print "</table>\n";
	switch (TRUE) {
	  case (sizeof($forms->error)):
      case (!$forms->report['script_table']):
		break;
	  default:
		$database->temp2=new database_temp_class();
		$table_fields=array();
	    $database->temp->query("show fields from `" . $forms->report['script_table'] . "`");
	    while ($database->temp->fetch = $database->temp->fetch_array()) {
			$table_fields[]=$database->temp->fetch['Field'];
	    }
	    $database->temp->free_result();
		$table_keys=array();
	    $database->temp->query("show index from `" . $forms->report['script_table'] . "`");
	    while ($database->temp->fetch = $database->temp->fetch_array() ) {
			if (!array_key_exists($database->temp->fetch['Key_name'],$table_keys)) $table_keys[ $database->temp->fetch['Key_name'] ]=array();
	        $table_keys[ $database->temp->fetch['Key_name'] ][]=$database->temp->fetch['Column_name'];
	    }
	    $database->temp->free_result();
        $results=array();
	    $database->temp->query("select * from " . $forms->report['script_table'] . " order by " . implode(", ",$table_keys['PRIMARY']));
		while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$record_error=array();
            foreach ($table_fields as $field) {
				if (preg_match("/(<script)(.*)(>)/i",$database->temp->fetch[$field])) $record_error[]=$field;
            }
			if (!sizeof($record_error)) continue;
            $record_keys=array();
            foreach ($table_keys['PRIMARY'] as $field) {
                $record_keys[]=$database->temp->fetch[$field];
            }
            $results[ implode("\t",$record_keys) ]=$record_error;
            if (!$forms->report['script_cleanup']) continue;
            $query_fields=array();
            foreach ($record_error as $field) {
				$query_fields[]=$field . "=" . fn_escape(trim(preg_replace("/(<script)(.*)(>)/i","",$database->temp->fetch[$field])));
            }
            $query_where=array();
            foreach ($table_keys['PRIMARY'] as $field) {
                $query_where[]=new query_where($field,"=",$database->temp->fetch[$field]);
            }
            $query=
            	"update " . $forms->report['script_table'] . " set \n" .
                implode(",\n",$query_fields) . " \n" .
                $database->where($query_where);
			$database->temp2->query($query);
        }
	    $database->temp->free_result();
		if (!sizeof($results)) {
			print "<p>No scripts found</p>\n";
		  } else {
	        if ( ($forms->report['script_cleanup']) && (method_exists($database->log,"ipc")) ) {
				$data=array();
                $data[]="Table: " . $forms->report['script_table'];
                $data[]="Record(s): " . sizeof($results);
	            $database->log->ipc("MySQL: Cleanup Script Injection",$data);
	        }
			print "<table class='standard border'>\n";
            print "<caption>&nbsp;</caption>\n";
            print "<tr>\n";
			print "<th width='50%' colspan=" . sizeof($table_keys['PRIMARY']) . ">Key(s)</th>\n";
            print "<th width='50%' rowspan=2>Field(s)</th>\n";
            print "</tr>\n";
            print "<tr>\n";
	        foreach ($table_keys['PRIMARY'] as $field) {
				print "<th>$field</th>\n";
            }
            print "</tr>\n";
            foreach ($results as $key => $value) {
            	print "<tr>\n";
				$fields=preg_split("/\t/",$key);
                foreach ($fields as $field) {
                	print "<td>$field</td>\n";
                }
                print "<td>" . implode("<br>",$value) . "</td>\n";
            	print "</tr>\n";
            }
            print "</table>\n";
        }
    }
	break;
  case ($forms->report['action']=="ip"):
	$results=array();
    $text=array();
    $text[]=$forms->radio("ip_action","ip",$forms->report['ip_action']);
    $text[]="IP address to integer";
    $text[]=$forms->text("ip_address",$forms->report['ip_address'],16,16,"",array("onfocus"=>"document.scs_form.ip_action[0].checked=true;"));
    $results[]=implode(" ",$text);
	if ( ($forms->report['ip_action']) && (!sizeof($forms->error)) ) $results[]="DNS: " . gethostbyaddr($forms->report['ip_address']);
    $text=array();
    $text[]=$forms->radio("ip_action","integer",$forms->report['ip_action']);
    $text[]="Integer to IP address";
    $text[]=$forms->text("ip_integer",$forms->report['ip_integer'],16,16,"",array("onfocus"=>"document.scs_form.ip_action[1].checked=true;"));
    $results[]=implode(" ",$text);
	print "<p>"  . implode("<br>",$results) . "</p>";
	break;
  case ($forms->report['action']=="timestamp"):
  	print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='20%'>" . $forms->radio("timestamp_action","date",$forms->report['timestamp_action']) . " Date/time to timestamp</td>";
    print "<td width='80%'>" . $forms->text("timestamp_date",$forms->report['timestamp_date'],24,24,"",array("onfocus"=>"document.scs_form.timestamp_action[0].checked=true;")) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='20%'>" . $forms->radio("timestamp_action","time",$forms->report['timestamp_action']) . " Timestamp to date/time</td>";
    print "<td width='80%'>" . $forms->text("timestamp_time",$forms->report['timestamp_time'],14,14,"",array("onfocus"=>"document.scs_form.timestamp_action[1].checked=true;")) . "</td>";
    print "</tr>";
    print "<tr><td colspan=2>&nbsp;</td></tr>";
    print "<tr>";
    print "<td width='20%'>PHP Timezone</td>";
    print "<td width='80%'>" . date("P")  . ":00 (" . date_default_timezone_get() . ")</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='20%'>MySQL Timezone</td>";
    print "<td width='80%'>" . $database->temp->fetch['timediff'] . " (" . $database->temp->fetch['timezone'] . ")</td>";
    print "</tr>";
    print "</table>";
	break;
  case ($forms->report['action']=="hexdump"):
	$html=new preg_replace_class(TRUE);
	print "<p class=standard>Text:<br>" . $forms->textarea("hexdump",$forms->report['hexdump'],6,100) . "</p>\n";
    print "<p>Filters:<br>";
    print implode("<br>",$html->filter_html());
    print "<br>" . $forms->submit("Go!") . "</p>\n";
	if (!$forms->report['hexdump']) break;
	toolbox_hexdump("Raw",$forms->report['hexdump']);
    if (sizeof($html->post_filter)) toolbox_hexdump("Filtered",$html->filter($forms->report['hexdump']));
	break;
  case ($forms->report['action']=="registry"):
    $query =
    	"select \n" .
        "case \n" .
        "when domain='global' then 1 \n" .
        "when domain=" . fn_escape($database->registry->domain) . " then 2 \n" .
        "else 3 \n" .
        "end as sort_key, \n" .
        "registry.* from registry \n" .
        "order by module,sort_key,field,item";
	$database->temp->query($query);
    $found_items=array();
	print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<th>Delete?</th>\n";
    print "<th>Domain</th>\n";
    print "<th>Module</th>\n";
    print "<th>Field</th>\n";
    print "<th>Item</th>\n";
    print "<th>Data</th>\n";
    print "</tr>\n";
	while ($database->temp->fetch = $database->temp->fetch_array() ) {
    	$domain=$database->temp->fetch['domain'];
        $module=$database->temp->fetch['module'];
        $field=$database->temp->fetch['field'];
		if (!array_key_exists($module,$found_items)) $found_items[$module]=array();
		if (!array_key_exists($field,$found_items[$module])) $found_items[$module][$field]=$domain;
		if ( in_array($domain,array("global",$database->registry->domain))) {
        	$bg=$forms->background->limegreen;
		  } else {
          	$bg="";
  		}
		print "<tr $bg>\n";
        print "<td class=center>" . $forms->checkbox( base64_encode("registry" . "\t" . $database->temp->fetch['id']), $database->temp->fetch['id'], 0) . "</td>\n";
        print "<td>$domain</td>\n";
        print "<td>$module</td>\n";
        print "<td>$field</td>\n";
        print "<td>" . $database->temp->fetch['item'] . "</td>\n";
        print "<td>" . $database->temp->fetch['data'] . "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
    print "<p>Legend: <span " . $forms->background->limegreen . ">&nbsp;&nbsp;Loaded Values&nbsp;&nbsp;</span></p>\n";
    print $forms->hidden("registry_action","update");
    print "<p>" . $forms->submit("Remove registry entries");
  	break;
  case ($forms->report['action']=="phperror"):
	print $forms->hidden("phperror_calculate",0);
    print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='20%'>Error reporting</td>";
    print "<td width='80%'>" . $forms->text('phperror_reporting',$forms->report['phperror_reporting'],6,6);
    print " " . $forms->submit("Parse value");
	print " " . $forms->button("Recalculate",array("onclick"=>"fn_phperror();"));
    if ($forms->report['phperror_reporting'] != $phperror->error_reporting) print " (Current value: " . $phperror->error_reporting . ")";
    print "</td>";
    print "</tr>";
    print "<tr>";
    print "<td colspan=2>error_reporting(" . $forms->report['phperror_reporting'] . ");</td>";
    print "</tr>";
    print "<tr>";
    $options=array();
    foreach ($phperror->code as $code => $value) {
		if ( ($value->name != "E_ALL") && (!in_array($value->name,$phperror->decode)) ) $options[]=$value->name;
    }
    if (sizeof($options)) {
		$text="^(" . implode(" | ",$options) . ") ";
	  } else {
		$text="";
    }
	print "<td colspan=2>error_reporting( E_ALL $text );</td>";
    print "</tr>";
    print "<tr>";
    print "<td colspan=2>error_reporting(" . implode(" | ",$phperror->decode) . ");</td>";
    print "</tr>";
    if ($phperror->error_log) {
    	print "<tr>";
        print "<td>Clear error log?</td>";
        print "<td>" . $phperror->error_log;
        if (file_exists($phperror->error_log)) print " (" . number_format((filesize($phperror->error_log))) . ")";
        print "</td>";
	    print "</tr>";
	}
    print "</td>";
    print "</tr>";
    print "</table>";
 	print "<table class='standard border'>";
    print "<caption>&nbsp;</caption>";
    print "<tr>";
    print "<th>?</th>";
    print "<th>Name</th>";
    print "<th>Code#</th>";
    print "<th>Description</th>";
    print "</tr>";
    foreach ($phperror->code as $code => $obj) {
	    print "<tr>";
	    print "<td class=center>" . $forms->checkbox("phperror_code_{$code}",1,array_key_exists($code,$phperror->decode)) . "</td>";
	    print "<td>" . $obj->name . "</td>";
	    print "<td class=center>$code</td>";
	    print "<td>" . $obj->description . "</td>";
	    print "</tr>";
	}
    print "</table>";
	break;
  case ($forms->report['action']=="ie"):
	$query=
	    "select distinct agent from log \n" .
	    "where agent <> '' \n" .
	    "order by agent";
	$database->temp->query($query);
	print "<table class='small border'>\n";
	print "<tr>\n";
	print "<th>Old</th>\n";
	print "<th>New</th>\n";
	print "<th>Match</th>\n";
	print "<th>Agent</th>\n";
	print "</tr>\n";
	while ($database->temp->fetch = $database->temp->fetch_array()) {
	    $agent=$database->temp->fetch['agent'];
	    $results=array();
	    $browser=explode(" ",strtoupper($agent));
	    if (in_array("MSIE",$browser)) {
	        $results['old']=1;
	      } else {
	        $results['old']=0;
	    }
	    $results['new']=preg_match("/(trident|msie)/i",$agent,$results['matches']);
	    $results['msie']=preg_match("/msie/i",$agent);
	    $results['trident']=preg_match("/trident/i",$agent);
	    switch (TRUE) {
	      case ($results['old'] != $results['new']):
	        $bg=$forms->background->yellow;
	        break;
	      case ($results['old']):
	        $bg=$forms->background->green;
	        break;
	      default:
	        $bg="";
	    }
	    print "<tr $bg>\n";
	    print "<td class=center>" . $results['old'] . "</td>\n";
	    print "<td class=center>" . $results['new'] . "</td>\n";
	    print "<td class=center>" . $results['matches'][0] . "</td>\n";
	    print "<td>" . $agent . "</td>\n";
	    print "</tr>\n";
	}
	print "</table>\n";
	break;
  case ($forms->report['action'] == "color"):
    print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<td width='50%'>Foreground: " . $forms->select('foreground_name',$colortable,$forms->report['foreground_name']);
    if ($forms->report['foreground_name']) print " (" . $forms->report['foreground_name'] . ")";
    print "<br>Background: " . $forms->select('background_name',$colortable,$forms->report['background_name']);
    if ($forms->report['background_name']) print " (" . $forms->report['background_name'] . ")";
    print "</td>\n";
    print "<td width='50%'>Foreground: #" . $forms->text('foreground_code',$forms->report['foreground_code'],6,6);
    if (array_key_exists($forms->report['foreground_code'],$colortable)) print " (" . $colortable[$forms->report['foreground_code']] . ")";
    print "<br>Background: #" . $forms->text('background_code',$forms->report['background_code'],6,6);
    if (array_key_exists($forms->report['background_code'],$colortable)) print " (" . $colortable[$forms->report['background_code']] . ")";
    print "</td>\n";
    print "</tr>\n";
    $text_sample="The quick brown fox jumped over the lazy dog.";
    $text="";
    for ($i=0; $i < 20; $i++) {
        $text .= $text_sample;
    }
    $style_name="";
    if ($forms->report['foreground_name']) $style_name .= "color: " . $colortable[$forms->report['foreground_name']] . ";";
    if ($forms->report['background_name']) $style_name .= "background-color: " . $colortable[$forms->report['background_name']] . ";";
    if ($style_name) $style_name=" style='" . $style_name . "'";
    $style_code="";
    if ($forms->report['foreground_code']) $style_code .= "color: #" . $forms->report['foreground_code'] . ";";
    if ($forms->report['background_code']) $style_code .= "background-color: #" . $forms->report['background_code'] . ";";
    if ($style_code) $style_code=" style='" . $style_code . "'";
    print "<tr>\n";
    print "<td $style_name>$text</td>\n";
    print "<td $style_code>$text</td>\n";
    print "</tr>\n";
    print "</table>\n";
    break;
  case ($forms->report['action'] == "colorview"):
	print "<p>Color code (#000, #000000) or name: " . $forms->text("colorview",$_POST['colorview'],20) . "</p>\n";
    if (strlen($_POST['colorview'])) {
		print "<table class='standard border'>\n";
        print "<tr>\n";
        print "<td style='color: " . $_POST['colorview'] . "; background-color: #ffffff;'>Sample text</td>\n";
        print "<td style='color: " . $_POST['colorview'] . "; background-color: #000000;'>Sample text</td>\n";
        print "<td style='color: #ffffff; background-color: " . $_POST['colorview'] . ";'>Sample text</td>\n";
        print "<td style='color: #000000; background-color: " . $_POST['colorview'] . ";'>Sample text</td>\n";
        print "<tr>\n";
        print "</table>\n";
    }
	break;
  case ($forms->report['action'] == "colorwheel"):
    print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<th>Field</th>\n";
    print "<th>Color</th>\n";
    print "<th colspan=2>Foreground</th>\n";
    print "<th colspan=2>Background</th>\n";
    print "</tr>\n";
    foreach ($forms->background as $field => $background) {
        $color=preg_replace("/^style='color: #(.*);'/","$1",$forms->foreground->$field);
        print "<tr>\n";
        print "<td>$field</td>\n";
        print "<td class=center>$color</td>\n";
        print "<td style='color: #$color; background-color: #ffffff;'>Sample text</td>\n";
        print "<td style='color: #$color; background-color: #000000;'>Sample text</td>\n";
        print "<td style='color: #ffffff; background-color: #$color;'>Sample text</td>\n";
        print "<td style='color: #000000; background-color: #$color;'>Sample text</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
    break;
  case ($forms->report['action']=="jsession"):
	print "<table id=jsession_table class='standard border'>";
    print "<thead>";
	print "<tr>";
    print "<th width='20%'>Session ID</th>";
    print "<th width='80%'>Value</th>";
	print "</tr>";
    print "</thead>";
    print "<tbody>";
?>
	<script>
	Object.keys(sessionStorage).forEach(jsession_each);
    function jsession_each(key, id) {
	    var table = document.getElementById('jsession_table');
	    var row = table.insertRow(id + 1);
	    var cell1 = row.insertCell(0);
	    var cell2 = row.insertCell(1);
	    cell1.innerHTML = key;
        obj=sessionStorage.getItem(key);
        try {
			cell2.innerHTML = JSON.parse(sessionStorage.getItem(key));
          } catch {
			cell2.innerHTML = sessionStorage.getItem(key);
		}
    }
	</script>
<?php
    print "</tbody>";
    print "</table>";
	break;
}
print $forms->close();
$menu->copyright();
function toolbox_hexdump($div,$content) {
	global $forms;
	print
    	"<p style='background-color: lightsteelblue; border:1px solid #000; padding: 3px;'>" .
		" $div: Content? " . $forms->checkbox("{$div}_content_check",1,0,array("onclick"=>"div_show('{$div}_content',this.checked);")) .
		" ASCII? " . $forms->checkbox("{$div}_ascii_check",1,0,array("onclick"=>"div_show('{$div}_ascii',this.checked);")) .
        "</p>\n";
	print "<div id={$div}_content style='display:none;'>\n";
	print "<h1>$title</h1>\n";
    print "<div>$content</div>\n";
    print "</div>\n";
	print "<div id={$div}_ascii style='display:none;'>\n";
    print
    	"<p class=standard>Legend: " .
        "<span " . $forms->background->green . ">&nbsp;&nbsp;CR/LF&nbsp;&nbsp;</span>" .
        "&nbsp;&nbsp;" .
        "<span " . $forms->background->yellow . ">&nbsp;&nbsp;Hex 00-1F&nbsp;&nbsp;</span>" .
        "&nbsp;&nbsp;" .
        "<span " . $forms->background->orange . ">&nbsp;&nbsp;Hex 7F-FF&nbsp;&nbsp;</span>" .
        "</p>\n";
	$data=str_split($content);
	$results=array();
	$row=0;
	$col=0;
	foreach ($data as $item) {
	    $results[$row][$col]=$item;
	    if ($col==15) {
	        $col=0;
	        $row++;
	      } else {
	        $col++;
	    }
	}
	print "<table class='standard border'>\n";
	foreach ($results as $row => $item) {
	    print "<tr>\n";
        print "<td>$row</td>\n";
	    foreach ($item as $col) {
			switch (TRUE) {
              case ($col=="\x0d"):
              case ($col=="\x0a"):
            	$bg=$forms->background->green;
				break;
              case ($col < "\x20"):
            	$bg=$forms->background->yellow;
				break;
              case ($col > "\x7e"):
            	$bg=$forms->background->orange;
				break;
              default:
              	$bg="";
			}
	        print "<td class=center $bg>$col<br>" . bin2hex($col) . "</td>\n";
	    }
	    print "</tr>\n";
	}
	print "</table>\n";
    print "</div>\n";
}
function fn_perms($perms) {
	$results=array();
    switch (TRUE) {
      case (($perms & 0xC000) == 0xC000):
        $results[]='s'; //socket
        break;
      case (($perms & 0xA000) == 0xA000):
        $results[]='l'; // Symbolic Link
        break;
      case (($perms & 0x8000) == 0x8000):
        $results[]='-'; // Regular
        break;
      case (($perms & 0x6000) == 0x6000):
        $results[]='b'; // Block special
        break;
      case (($perms & 0x4000) == 0x4000):
        $results[]='d'; // Directory
        break;
      case (($perms & 0x2000) == 0x2000):
        $results[]='c'; // Character special
		break;
      case (($perms & 0x1000) == 0x1000):
        $results[]='p'; // FIFO pipe
        break;
	  default:
        $results[]='u'; // Unknown
	}

	$results[]=(($perms & 0x0100) ? 'r' : '-');
	$results[]=(($perms & 0x0080) ? 'w' : '-');
	$results[]=(($perms & 0x0040) ?
	            (($perms & 0x0800) ? 's' : 'x' ) :
	            (($perms & 0x0800) ? 'S' : '-'));

	// Group
	$results[]=(($perms & 0x0020) ? 'r' : '-');
	$results[]=(($perms & 0x0010) ? 'w' : '-');
	$results[]=(($perms & 0x0008) ?
	            (($perms & 0x0400) ? 's' : 'x' ) :
	            (($perms & 0x0400) ? 'S' : '-'));

	// World
	$results[]=(($perms & 0x0004) ? 'r' : '-');
	$results[]=(($perms & 0x0002) ? 'w' : '-');
	$results[]=(($perms & 0x0001) ?
	            (($perms & 0x0200) ? 't' : 'x' ) :
	            (($perms & 0x0200) ? 'T' : '-'));
	return implode("",$results);
}
?>