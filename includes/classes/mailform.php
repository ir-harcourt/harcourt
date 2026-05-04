<?php
$database->mailform=new mailform_class();
class mailform_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['02/21/2025']="Improve select";
        $results['03/26/2023']="Expand name to 60";
        $results['10/29/2022']="Add internal to select";
        $results['04/20/2021']="Hotfix: Add recipient, bcc";
        $results['04/19/2013']="Add subject";
        $results['03/25/2013']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new mailform_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new mailform_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new mailform_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->name=$this->fetch['name'];
        $this->data->recipient=$this->fetch['recipient'];
        if (strlen($this->fetch['bcc'])) $this->data->bcc=json_decode($this->fetch['bcc']);
	    $this->data->css=$this->fetch['css'];
	    $this->data->subject=$this->fetch['subject'];
	    $this->data->header_html=$this->fetch['header_html'];
	    $this->data->footer_html=$this->fetch['footer_html'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from mailform");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new mailform_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="domain=" . fn_escape($this->data->doman);
        $fields[]="code=" . fn_escape($this->data->code);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="recipient=" . fn_escape($this->data->recipient);
        $fields[]="bcc=" . fn_escape( (sizeof($this->data->bcc) ? json_encode($this->data->bcc) : ""),"null");
        $fields[]="css=" . fn_escape($this->data->css);
        $fields[]="subject=" . fn_escape($this->data->subject);
        $fields[]="header_html=" . fn_escape($this->data->header_html);
        $fields[]="footer_html=" . fn_escape($this->data->footer_html);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update mailform set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into mailform set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$update) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query="delete from mailform where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare="",$select_options=array(),$forms_options=array()) {
        global $forms;
		if (!isset($select_options['field'])) $select_options['field']="mailform";
		if (!isset($select_options['blank'])) $select_options['blank']="Select Document";
        $query_where=array();
        switch (TRUE) {
          case (!isset($select_options['internal'])):
          	break;
          case ($select_options['internal']):
          	$query_where[]=new query_where("recipient","<>","");
            break;
          default:
          	$query_where[]=new query_where("recipient","=","");
		}
		$query=array("select * from mailform");
        $query[]=$this->where($query_where);
        $query[]="order by name";
	    $this->query($query);
        $results=array();
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            $text=array($this->data->name);
            $text[]=($this->data->recipient) ? "(To Us)" : "(To Visitor)";
            if (sizeof($this->data->bcc)) $text[]="(w/BCC)";
			$results[$this->data->code]=implode(" ", $text);
	    }
	    $this->free_result();
        return $forms->select($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("mailform", "Email Documents");
        $this->hotfix->version("03/26/2023", "Enlarge name");
        $this->hotfix->version("04/19/2021", "Add BCC");
        $this->hotfix->version("04/19/2013", "Initial Release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20230326($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="CHANGE COLUMN `name` `name` VARCHAR(60) NULL DEFAULT ''";
        $meta->count=$this->hotfix->alter($fields);
    }
    function scs_hotfix_20210419($meta) {
        $this->hotfix->trace[]=__FUNCTION__;

		$query=array("select");
        $query[]="user.email as 'email',";
        $query[]="mailform.* from mailform";
        $query[]="left join user on user.id=mailform.bcc_user_id and user.status='active'";
        $query[]="where mailform.bcc_user_id <> 0";
        $query[]="and not isnull(user.email)";
        $this->query($query);
        $items=array();
        while ($this->fetch = $this->fetch_array()) {
        	$items[$this->fetch['id']]=$this->fetch['email'];
        }
        $fields=array();
        $fields[]="ADD COLUMN `recipient` VARCHAR(128) NOT NULL DEFAULT '' AFTER `name`";
        $fields[]="ADD COLUMN `bcc` MEDIUMTEXT AFTER `recipient`";
        $fields[]="DROP COLUMN `from_user`";
        $fields[]="DROP COLUMN `user_id`";
        $meta->count=$this->hotfix->alter($fields);
        foreach ($items as $id => $email) {
        	$this->read($id);
            $this->data->bcc[]=$email;
            $this->update(TRUE);
        }
    }
}
class mailform_data_class {
	var $id=0;
	var $domain;
	var $code;
    var $name;
    var $recipient;
    var $bcc=array();
	var $css;
    var $subject;
    var $header_html;
    var $footer_html;
    var $active=1;
    function __construct() {

    }
}
class mailform_constant_class {
}
/*
CREATE TABLE `mailform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) DEFAULT '',
  `code` varchar(45) DEFAULT '',
  `name` varchar(60) DEFAULT '',
  `recipient` varchar(128) NOT NULL DEFAULT '',
  `bcc` mediumtext,
  `bcc_user_id` int(11) DEFAULT '0',
  `css` varchar(255) DEFAULT '',
  `subject` varchar(80) DEFAULT '',
  `header_html` mediumtext,
  `footer_html` mediumtext,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`domain`,`code`),
  UNIQUE KEY `name_key` (`domain`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Email Documents (03/26/2023)'
*/
?>