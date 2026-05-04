<?php
$database->shipto=new shipto_class();
class shipto_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['04/01/2025']="Add hotfix capability";
        $results['06/13/2024']="PHP 8 compliant";
        $results['05/05/2022']="Add js";
        $results['07/27/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new shipto_data_class();
        $this->constant=new shipto_constant_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new shipto_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->self=$this->fetch['self'];
	    $this->data->email=$this->fetch['email'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->title=$this->fetch['title'];
	    $this->data->company_name=$this->fetch['company_name'];
	    $this->data->address=$this->fetch['address'];
	    $this->data->city=$this->fetch['city'];
	    $this->data->state=$this->fetch['state'];
	    $this->data->zip=$this->fetch['zip'];
	    $this->data->country_code=$this->fetch['country_code'];
	    $this->data->phone=$this->fetch['phone'];
	    $this->data->fax=$this->fetch['fax'];
	    $this->data->ranking=$this->fetch['ranking'];
    }
    function read($id,$field="id") {
        $query=array("select * from shipto");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new shipto_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="user_id=" . fn_escape($this->data->user_id,FALSE);
	    $fields[]="self=" . fn_escape($this->data->self,"null");
	    $fields[]="email=" . fn_escape($this->data->email);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="title=" . fn_escape($this->data->title);
	    $fields[]="company_name=" . fn_escape($this->data->company_name);
	    $fields[]="address=" . fn_escape($this->data->address);
	    $fields[]="city=" . fn_escape($this->data->city);
	    $fields[]="state=" . fn_escape($this->data->state);
	    $fields[]="zip=" . fn_escape($this->data->zip);
	    $fields[]="country_code=" . fn_escape($this->data->country_code);
	    $fields[]="phone=" . fn_escape($this->data->phone);
	    $fields[]="fax=" . fn_escape($this->data->fax);
	    $fields[]="ranking=" . fn_escape($this->data->ranking,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update shipto set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into shipto set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from shipto where id=" . fn_escape($id));
		$this->query($query);
    }
    function self($data) {
		$this->read($data->id,"self");
        $this->data->user_id=$data->id;
        $this->data->self=$data->id;
        $this->data->ranking=0;
        $address=new address_class("shipto",$this->data);
        $address->load($data);
		$this->update($this->meta->rows);
    }
    function js($options=array()) {
    	$results=array("");
        $results[]="<script>";
    	$url=(array_key_exists("url",$options)) ? "url: " . $options['url'] . "," : "";
	    $results[]="function rolodex_ajax(subroutine, shipto_id, div_id) {";
	    $results[]="    data=new Object();";
	    $results[]="    switch (true) {";
	    $results[]="      case (subroutine == 'delete'):";
	    $results[]="        if (!confirm('Delete? ' + jQuery('#rolodex_body_' + shipto_id).text())) return;";
	    $results[]="        break;";
	    $results[]="      case (subroutine == 'update'):";
	    $results[]="        obj=jQuery('[id^=shipto_]');";
	    $results[]="        for (var key in obj) {";
	    $results[]="            if ( (!obj.hasOwnProperty(key)) || (!obj[key].id) ) continue;";
	    $results[]="            id=obj[key].id;";
	    $results[]="            data[id]=jQuery('#' + id).val();";
	    $results[]="        }";
	    $results[]="        break;";
	    $results[]="      case (subroutine == 'move'):";
	    $results[]="        i=0;";
	    $results[]="        obj=jQuery('[id^=rolodex_row_]');";
	    $results[]="        for (var key in obj) {";
	    $results[]="            if ( (!obj.hasOwnProperty(key)) || (!obj[key].id) ) continue;";
	    $results[]="            data[i]=obj[key].id;";
	    $results[]="            i++";
	    $results[]="        }";
	    $results[]="        break;";
	    $results[]="    }";
	    $results[]="    jQuery.ajax({";
        if (array_key_exists("url",$options)) $results[]="        url: " . $options['url'] . ",";
	    $results[]="        dataType: 'json',";
	    $results[]="        method: 'post',";
	    $results[]="        data: { action: 'rolodex', subroutine: subroutine, shipto_id: shipto_id, data: data },";
	    $results[]="        success: function(response) {";
	    $results[]="            for (key in response) {";
	    $results[]="                jQuery('#' + key).html(response[key]);";
	    $results[]="            }";
	    $results[]="            if (subroutine == 'delete') jQuery('#' + div_id).remove();";
	    $results[]="            jQuery('#rolodex_sortable').sortable( {refresh: rolodex_sortable} );";
	    $results[]="        },";
	    $results[]="        error: function(err) {";
	    $results[]="            console.log(err);";
	    $results[]="            jQuery('#scscpq_rolodex_input').html('&nbsp;');";
	    $results[]="        }";
	    $results[]="    })";
	    $results[]="}";
	    $results[]="jQuery(function() {";
	    $results[]="    jQuery('#rolodex_sortable').sortable({";
	    $results[]="        stop: function() { rolodex_ajax('move',0,'') }";
	    $results[]="    })";
	    $results[]="})";
        $results[]="</script>";
        $results[]="";
        return implode("\n",$results);
    }
    function rolodex_ajax() {
    global $forms;
    	$response=array();
        $response['scscpq_rolodex_status']="";
        $this->read($_REQUEST['shipto_id']);
        $this->address=new address_class("shipto",$this->data);
    	switch (TRUE) {
          case (!$_SESSION['user']->id):
          case ( ($_REQUEST['shipto_id']) && ($this->data->user_id != $_SESSION['user']->id) ):
          case ( ($_REQUEST['shipto_id']) && (!$this->meta->rows) ):
            $response['scscpq_rolodex_status']="Access denied";
			$response['scscpq_rolodex_input']="";
            break;
          case ($_REQUEST['subroutine'] == "edit"):
          	$results=array();
            $results[]="<div class=scscpq_rolodex_item>";
			$results[]="<table class='standard noborder'>";
	        $options=array();
	        $options['width']=array();
	        $options['width']['field']=30;
	        $options['width']['value']=70;
            $options['address_js']=FALSE;
            $options['legend']=TRUE;
			$results[]=$this->address->input($options);
            $results[]="</table>";
            $buttons=array();
            $buttons[]=$forms->button( (($_REQUEST['shipto_id']) ? "Update" : "Add New"), array("onclick"=>"rolodex_ajax('update'," . fn_escape($this->data->id) . ",'');") );
            $buttons[]=$forms->button("Cancel", array("onclick"=>"rolodex_ajax('cancel',0,'','');"));
            $results[]="<p>" . implode(" ",$buttons) . "</p>";
            $results[]="</div>";
			$response['scscpq_rolodex_input']=implode("",$results);
			break;
          case ($_REQUEST['subroutine'] == "update"):
        	$this->address->verify(array("input"=>$_REQUEST['data']));
	        if (sizeof($forms->error_text)) {
	            $text=$forms->error_text;
	            array_unshift($text,"<b>Please correct the following errors:</b>");
	            $response['scscpq_rolodex_status']="<p>" . implode("<br>",$text) . "</p>";
			   } else {
	            $this->update($_REQUEST['shipto_id']);
	            $options=array();
	            foreach ($_REQUEST['data'] as $key => $value) {
	                $field=explode("_",$key);
	                if ( ($field[0] == "shipto") && ($field[1] == "options") ) $options[$field[2]]=$value;
	            }
	            $response['scscpq_rolodex_items']=$this->rolodex($options);
	            $response['scscpq_rolodex_input']="Ship to address " . ( ($_REQUEST['shipto_id']) ? "Updated" : "Added");
	        }
			break;
          case ($_REQUEST['subroutine'] == "cancel"):
			$response['scscpq_rolodex_status']="";
			$response['scscpq_rolodex_input']="Request cancelled";
          	break;
          case ($_REQUEST['subroutine'] == "delete"):
			$this->delete($_REQUEST['shipto_id']);
			$response['scscpq_rolodex_status']="Item deleted";
          	break;
          case ($_REQUEST['subroutine'] == "move"):
          	if (!is_array($_REQUEST['data'])) break;
	        foreach ($_REQUEST['data'] as $ranking => $item) {
	            $text=explode("_",$item);
	            $query_where=array();
	            $query_where[]=new query_where("user_id","=",$_SESSION['user']->id);
	            $query_where[]=new query_where("id","=",$text[2]);
	            $query=array("update shipto");
	            $query[]="set ranking=" . fn_escape($ranking);
	            $query[]=$this->where($query_where);
	            $this->query($query);
	        }
			break;
		}
        die(json_encode($response));
    }
    function rolodex($options=array()) {
    global $database, $forms;
		if (!array_key_exists("self",$options)) $options['self']=TRUE;
		if (!array_key_exists("select",$options)) $options['select']=TRUE;
		if (!array_key_exists("match",$options)) $options['match']=0;
        $results=array();
        foreach ($options as $key => $value) {
        	$results[]=$forms->hidden("shipto_options_{$key}",$value);
        }
		$results[]="<div id=rolodex_sortable>";
        $query=array("select");
        $query[]="case";
        $query[]="when id=" . fn_escape($options['match']) . " then 1";
        $query[]="when self=1 then 2";
        $query[]="else 3";
        $query[]="end as sort_key,";
        $query[]="shipto.* from shipto";
        $query[]="where user_id=" . fn_escape($_SESSION['user']->id);
        if (!$options['self']) $query[]="and isnull(self)";
        $query[]="order by sort_key, ranking, id";
        $this->query($query);
        while ($this->fetch = $this->fetch_array() ) {
        	$this->fetch();
            $item=array();
			$row_id="rolodex_row_" . $this->data->id;
            $address=new address_class("shipto",$this->data);
            $js=array("action"=>"","id"=>$this->data->id,"div_id"=>$row_id,"name"=>"");
	        if ($options['select']) {
            	$js['action']="select";
                if (!intval($options['match'])) $options['match']=$this->data->id;
            	$item[]=$forms->span($forms->radio("rb_shipto",$this->data->id,$options['match'],array("class"=>"scscpq_rolodex_select scscpq_input")));
			}
	        $body=array();
            $body[]="<span id=rolodex_body_" . $this->data->id . ">" . $this->rolodex_body($address) . "</span>";
            if (!$this->data->self) {
	            $buttons=array();
	            $js['action']="edit";
	            $buttons[]=fn_href("Edit","javascript:rolodex_ajax(" . fn_escape_array($js) . ");");
	            $js['action']="delete";
	            $buttons[]=fn_href("Delete","javascript:rolodex_ajax(" . fn_escape_array($js) . ");");
	            if (sizeof($options)) $body[]=implode(" | ",$buttons);
			}
	        $item[]=implode("<br>",$body);
			$results[]=$forms->div($item,array("class"=>"scscpq_rolodex_item","id"=>$row_id));
        }
		$results[]=$forms->div(fn_href("Add new","javascript:rolodex_ajax('edit','0','');"),array("class"=>"scscpq_rolodex_item","id"=>"rolodex_row_0"));
        $results[]="</div>";
		return implode("",$results);
	}
    function rolodex_body($address) {
    global $database, $forms;
        $results=array();
        foreach ($address->fields as $field => $name) {
            switch (TRUE) {
              case (!strlen($address->data->$field)):
                break;
              case ($field == "country_code"):
                $database->country->read($address->data->country_code,"");
                $results[]=$database->country->data->name;
                break;
              default:
                $results[]=$address->data->$field;
            }
        }
        if ($this->data->self) $results[]="<b>(Default)</b>";
        $results[0]="<b>" . $results[0] . "</b>";
        return implode(", ",$results);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("shipto", "Ship To Address");
        $this->hotfix->version("07/27/2021", "Initial release");
        $this->hotfix->process($execute);
    }
}
class shipto_data_class {
	var $id=0;
	var $user_id=0;
    var $self;
	var $email;
	var $name;
	var $title;
	var $company_name;
	var $address;
	var $city;
	var $state;
	var $zip;
	var $country_code;
	var $phone;
    var $fax;
    var $ranking=999;
    function __construct() {
    	$this->user_id=$_SESSION['user']->id;
    }
}
class shipto_constant_class {
}
/*
insert into shipto select 0,id,id,'','',name,company_name,address,city,state,zip,country_code,phone,'',1 from user

CREATE TABLE `shipto` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(10) unsigned DEFAULT '0',
  `self` varchar(6) DEFAULT 'null',
  `email` varchar(60) NOT NULL DEFAULT '',
  `name` varchar(100) DEFAULT '',
  `title` varchar(100) DEFAULT '',
  `company_name` varchar(100) DEFAULT '',
  `address` varchar(100) DEFAULT '',
  `city` varchar(40) DEFAULT '',
  `state` char(40) DEFAULT '',
  `zip` varchar(20) DEFAULT '',
  `country_code` varchar(2) DEFAULT '',
  `phone` varchar(40) DEFAULT '',
  `fax` varchar(40) DEFAULT '',
  `ranking` int(3) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `self_key` (`self`),
  KEY `user_index` (`user_id`,`ranking`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Ship To address (07/27/2021)'
*/
?>