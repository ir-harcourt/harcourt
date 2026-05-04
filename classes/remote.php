<?php
$database->remote=new remote_class();
class remote_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['05/07/2025']="Add ajax";
        $results['07/29/2022']="Extend expiry";
        $results['12/07/2020']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new remote_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new remote_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new remote_data_class();
	    $this->data->email=$this->fetch['email'];
	    $this->data->expiry=$this->fetch['expiry'];
	    $this->data->token=$this->fetch['token'];
	    $this->data->unlock_code=$this->fetch['unlock_code'];
    }
    function read($id,$field="email") {
        $query=array("select * from remote");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new remote_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="email=" . fn_escape($this->data->email);
	    $fields[]="expiry=" . fn_escape($this->data->expiry,FALSE);
	    $fields[]="token=" . fn_escape($this->data->token);
	    $fields[]="unlock_code=" . fn_escape($this->data->unlock_code);
        $query=array();
    	if ($update) {
          	$query[]= "update remote set";
            $query[]=implode(",\n",$fields);
            $query[]="where email=" . fn_escape($this->data->email);
		  } else {
        	$query[]="insert into remote set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
    }
    function delete($email) {
	    $query="delete from remote where email=" . fn_escape($email);
		$this->query($query);
    }
    function token() {
        $this->data->token=bin2hex(openssl_random_pseudo_bytes(40));
    }
    function expiry() {
        global $database;
        $this->data->expiry=strtotime("+" . intval($database->registry->local->remote_days) . " days");
    }
    function unlock_code($minutes=15) {
        global $database;
        $this->data->unlock_code=dechex(strtotime("+{$minutes} minutes"));
    }
    function access($email, $token='') {
        global $database;
    	$this->data=new remote_data_class();
		list($account, $domain)=explode("@",$email,2);
		if ( (!strlen($account)) || (!strlen($domain)) ) return;
		$database->user->read($domain,"domain");
		$this->read($email);
        if ( (!$database->user->meta->rows) || ($database->user->data->status != "Active") ) return;
        switch (TRUE) {
          case (!$this->meta->rows):
          	$this->data->access="remote";
            return;
          case ($this->data->token != $token):
          	$this->data->access="token";
            return;
          case ($this->data->expiry < strtotime("now")):
          	$this->data->access="expiry";
            return;
          default:
          	$this->expiry();
            $this->update(TRUE);
			$this->data->access="allowed";
            return TRUE;
        }
    }

    function ajax($search_terms, $limit, $crap=array(), $options=array()) {
	    $query_where=array();
        $query_where[]=new query_where("email","like","%{$search_terms[0]}%");
        $fields=array();
        $fields[]="user.company_name as 'company_name'";
        $fields[]="substring_index(email, '@', -1) as 'remote_domain'";
        $fields[]="remote.*";
	    $query=array("select");
        $query[]=implode(",\n", $fields);
        $query[]="from remote";
        $query[]="left join user on user.domain=substring_index(email, '@', -1)";
        $query[]=trim($this->where($query_where));
        $query[]="order by remote.email";
		if ($limit) $query[]="limit {$limit}";
		$this->query($query);
	    $results=array();
	    while($this->fetch = $this->fetch_array() ) {
			$this->fetch();
	        $results[]=$this->ajax_fill();
		}
		$this->free_result();
		return $results;
    }
    function ajax_fill($ajax=TRUE) {
        $results=array();
        $results['value']=$this->data->email;
		$results['label']=$this->data->email;
        if ($ajax) {
            $results['company_name']=$this->fetch['company_name'];
            $results['domain']=$this->fetch['remote_domain'];
        }
		$results['token']=$this->data->token;
		$results['expiry']=($this->data->expiry > strtotime("now")) ?  date("m/d/Y H:i T",$this->data->expiry) : "Expired";
        $expiry=hexdec($this->data->unlock_code);
        $remaining=$expiry - strtotime("now");
        if ($remaining > 0) {
            $results['unlock_code']=$this->data->unlock_code;
            $results['unlock_expiry']=date("m/d/Y H:i T", $expiry);
            $minutes=floor($remaining / 60);
            $seconds=($remaining % 60);
            $text=array("Remaining:");
            if ($minutes) $text[]="{$minutes} minutes";
            if ($seconds) $text[]="{$seconds} seconds";
            $results['unlock_status']=implode(" ", $text);
          } else {
            $results['unlock_code']="Expired";
            $results['unlock_expiry']="Expired";
            $results['unlock_status']="";
        }
        $results['api_status']="";
        return $results;
    }
}
class remote_data_class {
    var $email;
    var $expiry=0;
    var $token;
    var $unlock_code;
// Internal variable
    var $access;
}
class remote_constant_class {
}
/*
CREATE TABLE `remote` (
  `email` varchar(128) NOT NULL DEFAULT '',
  `expiry` bigint(10) NOT NULL DEFAULT '0',
  `token` varchar(128) NOT NULL DEFAULT '',
  `unlock_code` varchar(8) NOT NULL DEFAULT '',
  PRIMARY KEY (`email`),
  KEY `token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Remote users (12/07/2020)'
*/
?>