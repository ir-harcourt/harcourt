<?php
$database->gwtlog=new gwtlog_class();
class gwtlog_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['02/03/2015']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new gwtlog_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new gwtlog_constant_class();
        $this->fetch=array();
//        $this->process=new gwtlog_process_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new gwtlog_data_class();
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->date=fn_date($this->fetch['date'],"mysql");
	    $this->data->code=$this->fetch['code'];
	    $this->data->impressions=intval($this->fetch['impressions']);
	    $this->data->clicks=intval($this->fetch['clicks']);
	    $this->data->position=floatval($this->fetch['position']);
        $this->data->ctr=round( (($this->data->clicks / $this->data->impressions) * 100), 2);
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="domain=" . fn_escape($this->data->domain);
        $fields[]="type=" . fn_escape($this->data->type);
        $fields[]="date=" . fn_escape($this->data->date,TRUE);
        $fields[]="code=" . fn_escape(substr($this->data->code,0,512));
        $fields[]="impressions=" . fn_escape($this->data->impressions,FALSE);
        $fields[]="clicks=" . fn_escape($this->data->clicks,FALSE);
        $fields[]="position=" . fn_escape($this->data->position,FALSE);
    	if ($update) {
			$query=
            	"update gwt set \n" .
                implode(",\n",$fields) . "\n" .
                "where id=" . fn_escape($this->data->id);
		  } else {
          	$query=
			"insert into gwt set \n" .
            implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$this->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from gwt where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
}
class gwtlog_data_class {
    var $domain;
    var $type;
	var $date;
	var $code;
    var $impressions=0;
	var $clicks=0;
	var $position=0;
    var $ctr=0;
}
class gwtlog_constant_class {
	var $type=array("TOP_QUERIES"=>"Queries","TOP_PAGES"=>"Pages");
    function __construct() {

    }
}
/*
class gwtlog_process_class {
    var $login=FALSE;
    var $upload=FALSE;
    var $change=FALSE;
    var $domain=array();
    var $error;
	var $gwt;
    function __construct() {
		$this->gwt=new GWTdata();
    }
	function login() {
	global $database;
	    if (!func_num_args()) {
	        $user=$database->registry->gwt->user;
	        $password=$database->registry->gwt->password;
	      } else {
	        $user=func_get_arg(0);
	        $password=func_get_arg(1);
	    }
	    if ( (!$user) || (!$password) ) return;
		$this->login=$this->gwt->LogIn($user, $password);
		if ($this->login) $this->domain=$this->gwt->GetSites();
	}
    function upload($domain,$type,$from_date,$thru_date) {
    global $database;
    	if (!$this->login) return;
        $database->temp->query("drop temporary table if exists gwttemp");
        $query=array();
        $query[]="create temporary table `gwttemp` (";
        $query[]="`code` varchar(512) NOT NULL DEFAULT '',";
        $query[]="`impressions` int(12) NOT NULL DEFAULT '0',";
        $query[]="`clicks` int(12) NOT NULL DEFAULT '0',";
        $query[]="`ctr` decimal(7,4) NOT NULL DEFAULT '0.00',";
        $query[]="`position` decimal(6,2) NOT NULL DEFAULT '0.00',";
        $query[]="`change_impressions` decimal(6,2) NOT NULL DEFAULT '0.00',";
        $query[]="`change_clicks` decimal(6,2) NOT NULL DEFAULT '0.00',";
        $query[]="`change_ctr` decimal(6,2) NOT NULL DEFAULT '0.00',";
        $query[]="`change_position` decimal(6,2) NOT NULL DEFAULT '0.00',";

        $query[]="`raw_impressions` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_clicks` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_ctr` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_position` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_change_impressions` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_change_clicks` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_change_ctr` varchar(12) NOT NULL DEFAULT '',";
        $query[]="`raw_change_position` varchar(12) NOT NULL DEFAULT '',";

        $query[]="PRIMARY KEY (`code`)";
        $query[]=")";
		$query[]="ENGINE=MyISAM";
        $database->temp->query(implode("\n",$query));

	    $urls=$this->gwt->GetDownloadUrls($domain);
	    switch (TRUE) {
	      case (!is_array($urls)):
	      case (!array_key_exists($type,$urls)):
	        return;
	    }
	    if (!$lines= explode("\n",trim($this->gwt->GetData($urls[$type] . "&prop=ALL&db=" . preg_replace("/\//","",$from_date) . "&de=" . preg_replace("/\//","",$thru_date) . "&more=true"))) ) return FALSE;
		if (!sizeof($lines)) return;
        $map=array();
        foreach ($lines as $line) {
			$data=str_getcsv($line,",");
            switch (TRUE) {
			  case (sizeof($map)):
				$fields=array();
				foreach ($map as $map_key => $map_code) {
					if ($map_code == "code") {
						$fields[]="$map_code=" . fn_escape(trim($data[$map_key]));
					  } else {
						$fields[]="$map_code=" . fn_escape(fn_number($data[$map_key],2),FALSE);
                        $fields[]="raw_{$map_code}=" . fn_escape($data[$map_key]);
                    }
                }
				$database->temp->query("insert into gwttemp set " . implode(",\n",$fields));
				break;
			  case (sizeof($data) == 5):
				$this->change=FALSE;
                $map[]="code";
                $map[]="impressions";
                $map[]="clicks";
                $map[]="ctr";
                $map[]="position";
                break;
			  case (sizeof($data) == 9):
				$this->change=TRUE;
                $map[]="code";
                $map[]="impressions";
                $map[]="change_impressions";
                $map[]="clicks";
                $map[]="change_clicks";
                $map[]="ctr";
                $map[]="change_ctr";
                $map[]="position";
                $map[]="change_position";
                break;
			  default:
				fn_pre($data,1);
            }
        }
        $database->temp->query("delete from gwttemp where impressions=0");
        $database->temp->query("update gwttemp set ctr=round((clicks / impressions),4)");
		$this->upload=TRUE;
	}
}
*/

/*
CREATE TABLE `gwtlog` (
  `domain` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `code` varchar(512) NOT NULL DEFAULT '',
  `impressions` int(12) NOT NULL DEFAULT '0',
  `clicks` int(12) NOT NULL DEFAULT '0',
  `position` decimal(6,1) NOT NULL DEFAULT '0.0',
  PRIMARY KEY (`domain`, `type`,`date`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Google Webmaster Tools Log (02/03/2015)'
*/
?>