<?php
$database->cpqdomain=new cpqdomain_class();
class cpqdomain_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['02/02/2025']="Hot fix";
        $results['01/26/2025']="Hot fix";
        $results['12/16/2024']="Initial release";
        return $results;
    }
	function __construct() {
    	$this->data=new cpqdomain_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new cpqdomain_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cpqdomain_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->company_name=$this->fetch['company_name'];
        $this->data->address=$this->fetch['address'];
	    $this->data->city=$this->fetch['city'];
	    $this->data->state=$this->fetch['state'];
	    $this->data->zip=$this->fetch['zip'];
	    $this->data->country_code=$this->fetch['country_code'];
	    $this->data->phone=$this->fetch['phone'];
	    $this->data->fax=$this->fetch['fax'];
        $this->data->tollfree=$this->fetch['tollfree'];
        $this->data->website=$this->fetch['website'];
        $this->data->external=$this->fetch['external'];
        $this->data->role=$this->fetch['role'];
        $this->data->discount_pct=floatval($this->fetch['discount_pct']);
        $this->data->html=$this->fetch['html'];
        $this->data->token=$this->fetch['token'];
        $this->data->logo=$this->fetch['logo'];
        $this->data->variable=$this->fetch_json($this->fetch["variable"]);
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from cpqdomain");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new cpqdomain_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id, FALSE);
        $fields[]="domain=" . fn_escape($this->data->domain);
        $fields[]="company_name=" . fn_escape($this->data->company_name);
	    $fields[]="address=" . fn_escape($this->data->address);
	    $fields[]="city=" . fn_escape($this->data->city);
	    $fields[]="state=" . fn_escape($this->data->state);
	    $fields[]="zip=" . fn_escape($this->data->zip);
	    $fields[]="country_code=" . fn_escape($this->data->country_code);
	    $fields[]="phone=" . fn_escape($this->data->phone);
	    $fields[]="fax=" . fn_escape($this->data->fax);
        $fields[]="tollfree=" . fn_escape($this->data->tollfree, "null");
        $fields[]="website=" . fn_escape($this->data->website, "null");
        $fields[]="external=" . fn_escape($this->data->external, FALSE);
        $fields[]="role=" . fn_escape($this->data->role);
        $fields[]="discount_pct=" . fn_escape($this->data->discount_pct, FALSE);
        $fields[]="html=" . fn_escape($this->data->html, "null");
        $fields[]="token=" . fn_escape($this->data->token);
        $fields[]="logo=" . fn_escape($this->data->logo,"null");
        $fields[]="variable=" . fn_escape(json_encode($this->data->variable, JSON_FORCE_OBJECT));
        $fields[]="active=" . fn_escape($this->data->active, FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update cpqdomain set";
            $query[]=implode(",\n",$fields);
            $query[]=" where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into cpqdomain set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from cpqdomain where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare="",$select_options=array(),$forms_options=array()) {
        global $database, $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="cpqdomain_id";
        if (!array_key_exists("active",$select_options)) $select_options['active']=FALSE;
    	if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        $query_where=array();
        if ($select_options['active']) $query_where[]=new query_where("active", "=", 1);
        $query=array("select * from cpqdomain");
        $query[]=$this->where($query_where);
        $query[]="order by domain";
        $this->query($query);
        $results=array();
        while ($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            $results[$this->data->id]=$this->data->domain;
        }
        return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
    function locate($email) {
        $email=new email_class($email);
        $this->read($email->domain, "domain");
        return $this->data->id;
    }
    function blacklist($domain) {
        global $database;
        $blacklist=$database->registry->cpqdomain->blacklist;
        if (!is_array($blacklist)) $blacklist=array();
        return in_array($domain, $blacklist);
    }
    function ajax($search_terms, $limit, $options=array()) {
	    $query_where=array();
	    foreach ($search_terms as $item) {
	        $query_where[]=new query_where("concat(domain,' ',company_name)","like","%" . $item . "%");
	    }
        return $this->ajax_results($this->ajax_query($query_where, $limit));
	}
    function ajax_fill($keys=array()) {
		if (func_num_args()) {
	        $query_where=array();
	        $query_where[]=new query_where("id","=",$keys[0]);
	        return $this->ajax_results($this->ajax_query($query_where));
		}
	}
    function ajax_query($ajax_where=array(), $limit=0) {
	    $query_where=array();
        $query_where[]=new query_where("active", "=", 1);
        $results=array();
        $results[]="select * from cpqdomain";
        $results[]=$this->where(array_merge($query_where, $ajax_where));
		if ($limit) $results[]="limit {$limit}";
        return $results;
    }
    function ajax_results($query) {
		$this->query($query);
        $results=array();
	    while($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            $text=array($this->data->domain);
            $text[]="(" . $this->data->company_name . ")";
            $obj=new stdClass();
            $obj->value=$this->data->id;
            $obj->label=implode(" ",$text);
            $obj->id=$this->data->id;
            $obj->domain=$this->data->domain;
            $obj->company_name=$this->data->company_name;
            $results[]=$obj;
        }
		return $results;
    }
    function confirm($data) {
        global $database;
        $this->read($data->cpqdomain_id);
        if (!$this->meta->rows) return;
        $mail=new scsmail_class($database->registry->cpqdomain->mailform_confirm,$data);
        $mail->user_id=$data->id;
        $mail->recipient($data->email, $data->name);

        $parse=parse_url($_SERVER['HTTP_REFERER']);
        $url=array();
        $url[]="{$parse['scheme']}://";
        $url[]=$parse['host'];
        if ($parse['port']) $url[]=":{$parse['port']}";

        $url_login=$url;
        $url_login[]="/login.php";

        $url_confirm=$url;
        $url_confirm[]="/user_confirm.php";

        $url_query=array();
        $url_query['token']=$this->data->token;
        $url_query['code']=fn_base64(array($data->login_timestamp, $data->id));

        $html=array();
        $html[]="<ol>Acknolwedge instructions:";
        $html[]="<li>Not logged in? " . fn_href("Click here", implode("", $url_login)) . "</li>";
        $html[]="<li>" . fn_href("Click here", implode("", $url_confirm), $url_query) . " to complete registration.</li>";
        $html[]="</ol>";
        $mail->html($html);
        $mail->send();
        return ($mail->error) ? "Internal error, email not sent" : "Please follow the instructions in the email sent by {$database->registry->email->webmaster} to complete your approval";
    }

    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("cpqdomain", "CPQ Domain");
        $this->hotfix->version("02/02/2025", "Add tollfree, website");
        $this->hotfix->version("01/26/2025", "Internal now external");
        $this->hotfix->version("12/27/2024", "Add image");
        $this->hotfix->version("12/13/2024", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20250202($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD COLUMN `tollfree` varchar(40) DEFAULT NULL AFTER `fax`";
        $fields[]="ADD COLUMN `website` varchar(120) DEFAULT NULL AFTER `tollfree`";
        $meta->count=$this->hotfix->alter($fields);
    }
    function scs_hotfix_20250126($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="CHANGE COLUMN `internal` `external` INT(1) NULL DEFAULT '0'";
        $meta->count=$this->hotfix->alter($fields);
        $fields=array();
        $fields[]="external=if(external=0,1,0)";
        $meta->count=$this->hotfix->update($fields);
    }
    function scs_hotfix_20241227($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD COLUMN `internal` int(1) DEFAULT '0' AFTER `fax`";
        $fields[]="ADD COLUMN `logo` varchar(80) NULL AFTER `token`";
        $fields[]="CHANGE COLUMN `json` `variable` MEDIUMTEXT NULL DEFAULT NULL";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class cpqdomain_data_class {
    var $id=0;
    var $domain;
    var $company_name;
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country_code;
    var $phone;
    var $fax;
    var $tollfree;
    var $website;
    var $external=0;
    var $role;
    var $discount_pct=0;
    var $html;
    var $token;
    var $logo;
    var $variable;
    var $active=1;
    function __construct() {
    	$this->variable=new stdClass();
    }
}
class cpqdomain_constant_class {
}
/*
CREATE TABLE `cpqdomain` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `domain` varchar(120) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT '',
  `address` varchar(100) DEFAULT '',
  `city` varchar(40) DEFAULT '',
  `state` char(40) DEFAULT '',
  `zip` varchar(20) DEFAULT '',
  `country_code` varchar(2) DEFAULT '',
  `phone` varchar(40) DEFAULT '',
  `fax` varchar(40) DEFAULT '',
  `tollfree` varchar(40) DEFAULT NULL,
  `website` varchar(120) DEFAULT NULL,
  `external` int(1) DEFAULT '0',
  `role` varchar(20) DEFAULT NULL,
  `discount_pct` decimal(4,4) DEFAULT '0.0000',
  `html` mediumtext,
  `token` varchar(40) DEFAULT NULL,
  `logo` varchar(80) DEFAULT NULL,
  `variable` mediumtext,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_UNIQUE` (`domain`),
  UNIQUE KEY `token_UNIQUE` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='CPQ Domain (02/02/2025)'
*/
?>