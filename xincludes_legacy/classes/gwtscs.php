<?php
require_once "classes/gwtdata.php";
class gwtscs_class {
    var $login=FALSE;
    var $download=FALSE;
    var $count=0;
    var $change=FALSE;
    var $domain=array();
	var $type=array("TOP_QUERIES"=>"Query","TOP_PAGES"=>"Page");
	var $color=array("active"=>"Active","inactive"=>"Inactive","default"=>"Not Assigned");
    var $error;
	var $gwt;
	function scs_table_version() {
		$results=array();
        $results['02/21/2015']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
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
    function download($from_date,$thru_date) {
    global $database;
    	if (!$this->login) return;
        $database->temp->query("drop table if exists gwttemp");
        $query=array();
        $query[]="create table `gwttemp` (";
        $query[]="`type` varchar(20) NOT NULL DEFAULT '',";
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

        $query[]="PRIMARY KEY (`type`,`code`)";
        $query[]=")";
		$query[]="ENGINE=MyISAM COMMENT='Google Webmaster Tools Temporary Date (" . key($this->scs_table_version()) . "'";
        $database->temp->query(implode("\n",$query));
	    $urls=$this->gwt->GetDownloadUrls($database->registry->gwt->domain);
		if (!is_array($urls)) return;
        foreach ($this->type as $type_code => $type_name) {
			if (!array_key_exists($type_code,$urls)) continue;
			$url=$urls[$type_code] . "&prop=ALL&db=" . preg_replace("/\//","",$from_date) . "&de=" . preg_replace("/\//","",$thru_date) . "&more=true";
	        if (!$lines=explode("\n",trim($this->gwt->GetData($url))) ) continue;
	        if (!sizeof($lines)) continue;
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
                    $fields[]="type=" . fn_escape($type_code);
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
        }
        $database->temp->query("delete from gwttemp where impressions=0");
        $database->temp->query("update gwttemp set ctr=round((clicks / impressions),4)");
        $database->temp->query("select * from gwttemp");
		$this->count=$database->temp->meta->found_rows;
		$this->download=TRUE;
	}
    function order($history=FALSE) {
		$results=array();
        $results["code"]="Query/Page";
        $results["impressions"]="Impressions";
        $results["clicks"]="Clicks";
        $results["ctr"]="CTR";
        $results["position"]="Position";
        if (!$history) {
	        $results["change_impressions"]="Impressions (Change%)";
	        $results["change_clicks"]="Clicks (Change)";
	        $results["change_ctr"]="CTR (Change)";
	        $results["change_position"]="Position (Change)";
        }
		return $results;
    }
}
?>