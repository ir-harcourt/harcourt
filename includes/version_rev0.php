<?php
$forms->title("SCS Program Version (Revised 03/18/2104)");
$menu->head();
$forms->message();
$results=array();
$results[]=new scs_version_class("","Internal functions","functions",FALSE,TRUE);
$results[]=new scs_version_class($forms,"Forms handling","forms");

require_once "classes/scsmail.php";
$email=new scsmail_class("");
$results[]=new scs_version_class($email,"Email client","scsmail");

require_once "classes/crawl.php";
$crawl=new crawl_class();
$results[]=new scs_version_class($crawl,"Site crawler","crawl_class");
$results[]=new scs_version_class($database,"Database","database_rev1");
$database->temp->query("show table status");
while ($database->temp->fetch = $database->temp->fetch_array()) {
	$results[]=new scs_version_class("",$database->temp->fetch['Comment'],$database->temp->fetch['Name'],TRUE);
}
$database->temp->free_result();

foreach ($results as $module) {
	print "<table class='standard noborder'>\n";
    print $forms->caption("<br><b>" . $module->title . "<b>",array("class"=>"left"));
	if ($module->error) {
	    print "<tr " . $module->error_class . ">\n";
        print "<td>" . $module->error . "</td>\n";
	    print "</tr>\n";
	  } else {
        foreach ($module->version as $version_date => $version_comment) {
        	print "<tr>\n";
            print "<td width='10%'>$version_date</td>\n";
            print "<td width='90%'>" . $version_comment . "</td>\n";
            print "</td>\n";
            print "</tr>\n";
		}
 	}
    print "</table>\n";
}
$menu->copyright();
class scs_version_class {
	var $title;
    var $version=array();
    var $error_class;
	var $error;
    function __construct($obj,$title,$file,$table=FALSE,$internal=FALSE) {
	global $database,$forms;
		$filename=$file;
        $tablename="";
        $version=array();
        switch (TRUE) {
          case ($internal):
          case (!$table):
			break;
          default:
            $filename="Not found";
			$paths=explode(PATH_SEPARATOR,get_include_path());
            foreach ($paths as $path) {
				if ($path==".") {
                	$temp_filename="classes/{$file}.php";
				  } else {
                	$temp_filename="$path/classes/{$file}.php";
				}
				if (file_exists($temp_filename)) {
                	include_once $temp_filename;
                    $filename=$temp_filename;
                    break;
				}
            }
            $obj=$database->$file;
	    }
        switch (TRUE) {
		  case (($internal) && (!function_exists("scs_version"))):
			$this->error_class=$forms->background->yellow;
            $this->error="Not implemented (scs_table_version)";
            break;
		  case ($internal):
          	$version=scs_version();
          	break;
 		  case (!is_object($obj)):
			$this->error_class=$forms->background->red;
            $this->error="Missing file $filename";
          	break;
		  case (($table) && (!method_exists($obj,"scs_table_version"))):
			$this->error_class=$forms->background->yellow;
            $this->error="Not implemented (scs_table_version)";
          	break;
          case ($table):
			$version=$obj->scs_table_version();
            break;
		  case (!method_exists($obj,"scs_version")):
			$this->error_class=$forms->background->yellow;
            $this->error="Not implemented (scs_version)";
          	break;
          default:
			$version=$obj->scs_version();
        }
        if (is_array($version)) {
			foreach ($version as $key => $value) {
				switch (TRUE) {
				  case ($key=="file"):
					$filename=$version['file'];
                    break;
                  case (is_array($value)):
                  	$this->version[$key]=implode("<br>",$value);
                    break;
                  default:
                  	$this->version[$key]=$value;
                }
            }
		  } else {
			$this->error_class=$forms->background->red;
            $this->error="Returned string instead of array";
		}
        $this->title=$title;
        if  ($tablename) $this->title .= " (Table: $tablename)";
        if  ($filename) $this->title .= " (File: $filename)";
    }
}
?>