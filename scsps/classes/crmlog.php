<?php
/*
crmlog.php:
	advance/scs_header.php
    balseal/scs_header.php
    barksdale/scs_header.php
    colson/beta_api.php
    helix/scs_header.php
    iconn/hubspot.php
    iconn/scscpq_api.php
    kollmorgen/scs_header.php
    nopak/scs_header.php
    peninsular/scs_header.php
    welker/scs_header.php

    crmlog_api_rev0.php
    crmlog_imap_rev0.php
    crmlog_import_rev0.php
    crmlog_list_rev0.php
    crmlog_process_rev0.php

partcommunity:
	iconn/hubspot_api.php
import_crm:
	crmlog_api_rev0.php
    crmlog_imap_rev0.php
    crmlog_import_rev0.php
    cmrlog_process_rev0.php
import_crm_single:
	none
update_crm:
	colson/beta_api.php

*/
require_once "classes/cad.php";
require_once "classes/crmblacklist.php";
$database->crmlog=new crmlog_class();
class crmlog_class extends database_class {
	var $trace=array();
	function scs_table_version() {
		$results=array();
        $results['05/09/2025']="Add cad:options";
        $results['12/05/2024']="Add function: cad";
        $results['02/10/2024']="Request, free_field now an object";
        $results['10/18/2023']="Add options";
        $results['07/31/2023']="Add crm_status";
        $results['05/12/2022']="Add consent";
        $results['02/08/2022']="PHP 7.4 compliant";
        $results['07/07/2021']="Add crmlog_multiple";
        $results['11/11/2020']="Add crmblacklist.track";
        $results['04/25/2020']="Add user_id";
        $results['11/28/2019']="Add fax";
        $results['10/29/2019']="Add blacklist";
        $results['09/04/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
        global $database;
    	$this->data=new crmlog_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new crmlog_constant_class();
        $database->crmblacklist->meta->error_abort=FALSE;
	}
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new crmlog_data_class();
	    $this->data->id=intval($this->fetch['id']);
	    $this->data->timestamp=intval($this->fetch['timestamp']);
	    $this->data->transaction_id=$this->fetch['transaction_id'];
	    $this->data->source=$this->fetch['source'];
	    $this->data->sku=$this->fetch['sku'];
	    if (strlen($this->fetch['cad_code'])) $this->data->cad_code=explode(",",$this->fetch['cad_code']);
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->email=$this->fetch['email'];
	    $this->data->name_first=$this->fetch['name_first'];
	    $this->data->name_last=$this->fetch['name_last'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->company_name=$this->fetch['company_name'];
	    $this->data->address=$this->fetch['address'];
	    $this->data->city=$this->fetch['city'];
	    $this->data->state=$this->fetch['state'];
	    $this->data->zip=$this->fetch['zip'];
	    $this->data->country_code=$this->fetch['country_code'];
	    $this->data->phone=$this->fetch['phone'];
	    $this->data->fax=$this->fetch['fax'];
	    $this->data->consent=$this->fetch['consent'];
        $obj=json_decode($this->fetch['free_field']);
        if (is_object($obj)) $this->data->free_field=$obj;
        $obj=json_decode($this->fetch['request']);
        if (is_object($obj)) $this->data->request=$obj;
	    $this->data->status=$this->fetch['status'];
	    $this->data->error=$this->fetch['error'];
	    $this->data->link=$this->fetch['link'];
	    $this->data->crm_status=$this->fetch['crm_status'];
	    $this->data->crm_service=$this->fetch['crm_service'];
	    $this->data->crm_server=$this->fetch['crm_server'];
	    $this->data->crm_table=$this->fetch['crm_table'];
	    $this->data->crm_contact_id=$this->fetch['crm_contact_id'];
	    $this->data->crm_transaction_id=$this->fetch['crm_transaction_id'];
	    $this->data->crm_error=$this->fetch['crm_error'];
    }
    function read($id,$field="id") {
        $query=array("select * from crmlog");
        $query[]="where $field=" . fn_escape($id);
        $this->query($query);
        $this->fetch(TRUE);
    }
    function update($update=FALSE) {
	    $fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="timestamp=" . fn_escape($this->data->timestamp,FALSE);
	    $fields[]="transaction_id=" . fn_escape($this->data->transaction_id,"null");
	    $fields[]="source=" . fn_escape($this->data->source,"null");
	    $fields[]="sku=" . fn_escape($this->data->sku,"null");
	    $fields[]="cad_code=" . fn_escape(implode(",",$this->data->cad_code),"null");
	    $fields[]="user_id=" . fn_escape($this->data->user_id,FALSE,"null");
	    $fields[]="email=" . fn_escape($this->data->email,"null");
	    $fields[]="name_first=" . fn_escape($this->data->name_first,"null");
	    $fields[]="name_last=" . fn_escape($this->data->name_last,"null");
	    $fields[]="name=" . fn_escape($this->data->name,"null");
	    $fields[]="company_name=" . fn_escape($this->data->company_name,"null");
	    $fields[]="address=" . fn_escape($this->data->address,"null");
	    $fields[]="city=" . fn_escape($this->data->city,"null");
	    $fields[]="state=" . fn_escape($this->data->state,"null");
	    $fields[]="zip=" . fn_escape($this->data->zip,"null");
	    $fields[]="country_code=" . fn_escape($this->data->country_code,"null");
	    $fields[]="phone=" . fn_escape($this->data->phone,"null");
	    $fields[]="fax=" . fn_escape($this->data->fax,"null");
	    $fields[]="consent=" . fn_escape($this->data->consent,FALSE);
        $fields[]="free_field=" . fn_escape(json_encode($this->data->free_field));
	    $fields[]="request=" . fn_escape(json_encode($this->data->request) );
	    $fields[]="status=" . fn_escape($this->data->status,"null");
	    $fields[]="error=" . fn_escape($this->data->error,"null");
	    $fields[]="link=" . fn_escape($this->data->link,"null");
	    $fields[]="crm_status=" . fn_escape($this->data->crm_status,"null");
	    $fields[]="crm_service=" . fn_escape($this->data->crm_service,"null");
	    $fields[]="crm_server=" . fn_escape($this->data->crm_server,"null");
	    $fields[]="crm_table=" . fn_escape($this->data->crm_table,"null");
	    $fields[]="crm_contact_id=" . fn_escape($this->data->crm_contact_id,"null");
	    $fields[]="crm_transaction_id=" . fn_escape($this->data->crm_transaction_id,"null");
	    $fields[]="crm_error=" . fn_escape($this->data->crm_error,"null");
        $query=array();
    	if ($update) {
          	$query[]="update crmlog set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into crmlog set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
        if ( (!$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from crmlog where");
	    $query[]="id=" . fn_escape($id);
		$this->query($query);
    }
    function cad($generate, $runtime_options=array()) {
        global $database;
        $this->read($generate->results->transaction_id, "transaction_id");
        $id=$this->data->id;
        $this->data=new crmlog_data_class();
	    $this->data->id=$id;
        $this->data->timestamp=strtotime("now");
	    $this->data->transaction_id=$generate->results->transaction_id;
        $this->data->source="partserver";
	    $this->data->sku=$generate->sku;
        $this->data->cad_code[]=$generate->cad_code;
	    $this->data->user_id=$generate->user->id;
	    $this->data->email=$generate->user->email;
	    $this->data->name_first=$generate->user->name_first;
	    $this->data->name_last=$generate->user->name_last;
	    $this->data->name=$generate->user->name;
        $this->data->company_name=$generate->user->company_name;
	    $this->data->address=$generate->user->address;
	    $this->data->city=$generate->user->city;
	    $this->data->state=$generate->user->state;
	    $this->data->zip=$generate->user->zip;
	    $this->data->country_code=$generate->user->country_code;
	    $this->data->phone=$generate->user->phone;
	    $this->data->fax=$generate->user->fax;
        $this->data->consent=1;
        if ($generate->error) {
            $this->data->status="Error";
            $this->data->error=$generate->error;
          } else {
            $this->data->status="Update";
        }
	    $this->data->link=$generate->results->cad_link;
	    $this->data->crm_status="N/A";
        $this->update($id);
        if ($generate->error) {
            $comment=array();
            $comment[]="Error: {$generate->error}";
            $comment[]="CAD Code: {$generate->cad_code}";
            $database->log->update("API Error:CAD", $comment, 0, $generate->sku);
            return "An internal error has occured";
        }
        $comment=array($generate->cad_name);
        if ($generate->generate) $comment[]="Emailed to: {$generate->user->email}";
        $comment[]=$generate->results->cad_link;
        $database->log->update("PartSolutions:CAD", $comment,0, $generate->sku);
        $url=($runtime_options['url']) ? $runtime_options['url'] : "scs_get_cad.php";
        $url_query=array();
        if (array_key_exists("action", $runtime_options)) $url_query['action']=$runtime_options['action'];
        $url_query['transaction_id']=$generate->results->transaction_id;
        return ($generate->generate) ? "{$generate->sku} Emailed to: {$generate->user->email}" : fn_href("Download {$generate->sku}", $url , $url_query, array("target"=>"_blank","title"=>"Download {$generate->sku}"));
    }
    function import_crm() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
    	set_time_limit(600); // 5 minutes
        if  (!property_exists($this, "crm")) return;
        if (!$this->crm->login()) return;
        $items=array();
		$query=array("select * from crmlog");
        $query[]="where status='pending'";
        $query[]="order by email, timestamp";
        $this->query($query);
    	if (isset($menu->stopwatch)) $menu->stopwatch->split(__METHOD__,number_format($database->temp->meta->rows) . " records found");
        switch (TRUE) {
          case (!$this->meta->rows):
          	break;
          case (method_exists($this->crm,"crmlog_multiple")):
        	$this->import_crm_multiple();
            break;
          default:
        	$this->import_crm_single();
		}
    }
    function import_crm_single() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        $items=array();
        while ($database->crmlog->fetch = $database->crmlog->fetch_array()) {
            $database->crmlog->fetch();
			$items[]=$database->crmlog->data->id;
        }
        $database->crmlog->free_result();
        if (!sizeof($items)) return;
        if (!$this->crm->login()) return;
        foreach ($items as $item) {
        	$this->read($item);
            if (isset($menu->stopwatch)) $menu->stopwatch->split("Import " . $this->data->transaction_id,$this->data->email);
			$this->update_crm();
            if (isset($menu->stopwatch)) {
	            $comment=array();
	            $comment[]="Status: " . $this->data->crm_status;
	            if ($this->data->crm_contact_id) $comment[]="Table: " . $this->data->crm_table . " ID: " . $this->data->crm_contact_id;
	            if ($this->data->crm_error) $comment[]="Error: " . $this->data->crm_error;
	            $menu->stopwatch->split_comment($comment);
			}
        }
	}
    function update_crm() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        if (!property_exists($this, "crm")) return;
    	$ipc=$this->crm->crmlog($this->data);
        $this->data->status="Update";
		$this->data->crm_service=$ipc->service;
		$this->data->crm_server=$ipc->server;
		$this->data->crm_table=$ipc->table;
		$this->data->crm_contact_id=$ipc->contact_id;
		$this->data->crm_transaction_id=$ipc->transaction_id;
        switch (TRUE) {
          case ($ipc->blacklist):
          	$this->data->crm_status="Blacklist";
        	$this->data->error=$ipc->error;
            break;
          case ($ipc->error):
        	$this->data->crm_status="Error";
			$this->data->crm_error=$ipc->error;
            break;
		  default:
          	$this->data->crm_status="Update";
            $this->data->crm_error="";
		}
	    $this->update(TRUE);
    }
    function import_crm_multiple() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        $items=array();
        while ($database->crmlog->fetch = $database->crmlog->fetch_array()) {
            $database->crmlog->fetch();
	        if (!array_key_exists($database->crmlog->data->email,$items)) $items[$database->crmlog->data->email]=array();
	        $items[$database->crmlog->data->email][$database->crmlog->data->id]=$database->crmlog->data;
        }
        $database->crmlog->free_result();
        foreach ($items as $email => $items) {
            if (isset($menu->stopwatch)) $menu->stopwatch->split("Import " . $email);
			$this->crm->crmlog_multiple($items);
            $this->update_crm_multiple($items);
        }
	}
    function update_crm_multiple($items) {
        global $database, $menu;
        switch (TRUE) {
          case ($this->crm->ipc->blacklist):
          	$crm_status="Blacklist";
            break;
          case ($this->crm->ipc->error):
        	$crm_status="Error";
            break;
		  default:
          	$crm_status="Update";
            $crm_error="";
		}
        $fields=array();
		$fields[]="status=" . fn_escape("Update");
		$fields[]="crm_status=" . fn_escape($crm_status);
		$fields[]="crm_service=" . fn_escape($this->crm->ipc->service);
        $fields[]="crm_server=" . fn_escape($this->crm->ipc->server);
		$fields[]="crm_table=" . fn_escape($this->crm->ipc->table);
		$fields[]="crm_contact_id=" . fn_escape($this->crm->ipc->contact_id);
		$fields[]="crm_transaction_id=" . fn_escape($this->crm->ipc->transaction_id);
		$fields[]="crm_error=" . fn_escape($this->crm->ipc->error);
        $query_where=array();
        $query_where[]=new query_where("id","in",array_keys($items));
        $query=array("update crmlog set");
        $query[]=implode(",\n",$fields);
        $query[]=$database->where($query_where);
		$database->temp->query($query);
    }
    function map($options) {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        $this->map=new table_map_class($options);
        switch (TRUE) {
          case (!$this->map->options['report']):
	        $this->map->item('orderno',array('name'=>'Orderno','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('timestamp',array('name'=>'Process end','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('process_time',array('name'=>'Process time','required'=>TRUE,'type'=>'decimal:0'));
	        $this->map->item('catalog',array('name'=>'Catalog','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('server',array('name'=>'Server type','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('category',array('name'=>'Server category','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('consent',array('name'=>'Supplier contact consent','required'=>TRUE,'type'=>'uppercase'));
	        $this->map->item('distributor',array('name'=>'Server detail','required'=>TRUE,'type'=>'lowercase'));
	        $this->map->item('email',array('name'=>'Email','required'=>TRUE,'type'=>'email'));
	        $this->map->item('language',array('name'=>'Desktop language','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('company',array('name'=>'Firm','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('name',array('name'=>'Name','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('address',array('name'=>'Street','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('zip',array('name'=>'Zip','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('city',array('name'=>'City','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('state',array('name'=>'State','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('country',array('name'=>'Country','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('phone',array('name'=>'Phone','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('fax',array('name'=>'Fax','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('cad_format',array('name'=>'CAD name','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('cad_version',array('name'=>'CAD version','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('prj',array('name'=>'Projectpath','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('sku',array('name'=>'Standardname','required'=>TRUE,'type'=>'alphanumeric'));
	        $this->map->item('error',array('name'=>'Error','required'=>TRUE,'type'=>'boolean'));
	        $this->map->item('assembly',array('name'=>'Assembly','required'=>TRUE,'type'=>'boolean'));
	        $this->map->item('gx_location',array('name'=>'gx_location','required'=>TRUE,'type'=>'alphanumeric'));
            break;
          case ($this->map->options['request']):
          	$this->map->request=array('id','orderno','timestamp');
			$this->query($options['query']);
            while ($this->fetch = $this->fetch_array()) {
            	$this->fetch();
                foreach ($this->data->request as $field => $field_value) {
                	switch (TRUE) {
                      case ($field == "user"):
				    	foreach ($field_value as $user_field => $user_value) {
                        	if (!in_array($user_field,$this->map->request)) $this->map->request[]=$user_field;
                        }
  						break;
                      case (!in_array($field,$this->map->request)):
                      	$this->map->request[]=$field;
                        break;
					}
                }
            }
            foreach ($this->map->request as $field) {
				$this->map->item($field,array("width"=>40));
            }
          	break;
          default:
	        $this->map->item('timestamp',array('name'=>'Timestamp','type'=>'timestamp','width'=>22));
	        $this->map->item('transaction_id',array('name'=>'Transaction ID','type'=>'alphanumeric','width'=>40));
	        $this->map->item('source',array('name'=>'Source','type'=>'alphanumeric','width'=>30));
	        $this->map->item('sku',array('name'=>'SKU','type'=>'alphanumeric','width'=>50));
	        $this->map->item('cad',array('name'=>'CAD Formats','type'=>'alphanumeric','width'=>40));
	        $this->map->item('user_id',array('name'=>'User ID','type'=>'decimal:0','width'=>16));
	        $this->map->item('email',array('name'=>'Email','type'=>'email','width'=>60));
	        $this->map->item('name_first',array('name'=>'First Name','type'=>'alphanumeric','width'=>40));
	        $this->map->item('name_last',array('name'=>'Last Name','type'=>'alphanumeric','width'=>40));
	        $this->map->item('company_name',array('name'=>'Company Name','type'=>'alphanumeric','width'=>60));
	        $this->map->item('address',array('name'=>'Address','type'=>'alphanumeric','width'=>60));
	        $this->map->item('city',array('name'=>'City','type'=>'alphanumeric','width'=>40));
	        $this->map->item('state',array('name'=>'State','type'=>'alphanumeric','width'=>20));
	        $this->map->item('zip',array('name'=>'Zip/Postal Code','type'=>'alphanumeric','width'=>20));
	        $this->map->item('country_code',array('name'=>'Country','type'=>'alphanumeric','width'=>30));
	        $this->map->item('phone',array('name'=>'Phone','type'=>'alphanumeric','width'=>30));
	        $this->map->item('fax',array('name'=>'FAX','type'=>'alphanumeric','width'=>30));
	        $this->map->item('consent',array('name'=>'Supplier contact consent','required'=>TRUE,'type'=>'boolean'));
	        $this->map->item('status',array('name'=>'Status','type'=>'alphanumeric'));
	        $this->map->item('error',array('name'=>'Error','type'=>'alphanumeric','width'=>80));
            if ($options['crm']) {
	            $this->map->item('crm_status',array('name'=>'CRM Status','type'=>'alphanumeric'));
	            $this->map->item('crm_service',array('name'=>'CRM Service','type'=>'alphanumeric'));
	            $this->map->item('crm_server',array('name'=>'CRM Server','type'=>'alphanumeric'));
	            $this->map->item('crm_table',array('name'=>'CRM Table','type'=>'alphanumeric'));
	            $this->map->item('crm_contact_id',array('name'=>'CRM Contact ID','type'=>'alphanumeric','width'=>40));
	            $this->map->item('crm_transaction_id',array('name'=>'CRM Transaction ID','type'=>'alphanumeric','width'=>40));
	            $this->map->item('crm_error',array('name'=>'CRM Error','type'=>'alphanumeric','width'=>80));
            }
		}
    }
    function upload_update($data) {
		switch (TRUE) {
          case ($this->map->meta->abort):
          	break;
          case (!$this->map->header):
        	$this->map->header=TRUE;
            if (!$this->map->complete) {
				$fields=array();
                foreach ($this->map->fields as $item) {
                	if (!property_exists($item,"column")) $fields[]=$item->name;
                }
            	$this->map->update=FALSE;
            	$this->map->meta->abort=TRUE;
                $this->map->meta->error="Missing field(s): " . implode(", ",$fields);
            }
			break;
          default:
			$this->import_update($data);
		}
    }
    function export_data() {
		if ($this->map->options['request']) {
			$id=$this->data->id;
			$request=$this->data->request;
            $this->data=new stdClass();
			$this->data->id=$id;
            foreach ($request as $field => $field_value) {
            	if ($field == "user") {
                    foreach ($field_value as $user_field => $user_value) {
						$this->export_data_request($user_field, $user_value);
                    }
				  } else {
					$this->export_data_request($field, $field_value);
                }
            }
		  } else {
			$this->data->cad=implode(", ",$this->data->cad_code);
		}
    }
    function export_data_request($field, $value) {
    	$this->data->$field=(is_array($value)) ? implode(", ",$value) : $value;
    }
    function import_update($data) {
		if (!strlen($data['orderno'])) $this->map->error['orderno']="Cannot be blank";
		if (!strlen($data['email'])) $this->map->error['email']="Cannot be blank";
        switch (TRUE) {
          case (!$this->map->update):
          	break;
          case (sizeof($this->map->error)):
          	$this->map->meta->count['error']++;
            break;
          case (!$this->track($data['email'])):
          	$this->map->meta->count['skip']++;
          	break;
          default:
	        $this->data=new crmlog_data_class();
        	$this->data->request=$data;
	        $this->data->timestamp=strtotime($data['timestamp']);
	        $this->data->transaction_id=$data['orderno'];
	        $this->data->source="import";
	        $this->data->sku=$data['sku'];
	        $this->data->cad_code=array($data['cad_format']);
	        $this->data->email=$data['email'];
          	list($this->data->name_first, $this->data->name_last)=explode(" ",$data['name'],2);
	        $this->data->name=$data['name'];
	        $this->data->company_name=$data['company'];
	        $this->data->address=$data['address'];
	        $this->data->city=$data['city'];
	        $this->data->state=$data['state'];
	        $this->data->zip=$data['zip'];
	        $this->data->country_code=$data['country'];
	        $this->data->phone=$data['phone'];
	        $this->data->fax=$data['fax'];
            $this->data->consent=preg_match("/^true$/i",$data['consent']);
            $this->blacklist($data, "Pending");
            $this->update(FALSE);
            switch ($this->meta->errno) {
              case 0:
                $this->map->meta->count['add']++;
                break;
              case 1062:
                $this->map->meta->count['duplicate']++;
                break;
			  default:
                $this->map->meta->count['error']++;
            	$this->map->error['mysql']=$this->meta->error;
			}
		}
    }
    function blacklist($data, $status) {
        global $database;
		switch (TRUE) {
          case (!strlen($data['email'])):
          	$this->data->status="Error";
          	$this->data->error="Email missing";
            break;
          case (preg_match("/" . preg_quote("@partcommunity.com") . "/i",$data['email'])):
          	$this->data->status="Update";
            $this->data->crm_status="Blacklist";
            $this->data->crm_error="Domain: partcommunity.com";
            break;
          case ( (is_array($database->registry->crm->blacklist_server_category)) && (in_array($data['category'], $database->registry->crm->blacklist_server_category)) ):
          	$this->data->status="Update";
            $this->data->crm_status="Blacklist";
            $this->data->crm_error="Server category: " . $data['category'];
            break;
          case ( (is_array($database->registry->crm->blacklist_server_detail)) && (in_array($data['distributor'], $database->registry->crm->blacklist_server_detail)) ):
          	$this->data->status="Update";
            $this->data->crm_status="Blacklist";
            $this->data->crm_error="Server detail: " . $data['distributor'];
            break;
          case (!property_exists($this, "crm")):
            $this->data->status="Update";
            break;
          default:
          	$this->data->status=$status;
		}
    }
    function import_complete() {
        global $database;
    	$this->trace[]=__FUNCTION__;
    }
    function track($email) {
        global $database;
    	return ( (method_exists($database->crmblacklist,"track")) ? $database->crmblacklist->track($email) : TRUE);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("crmlog", "CRM Transaction Log");
        $this->hotfix->version("02/10/2024", "Request, free_field now an object");
        $this->hotfix->version("07/31/2023", "Add crm_status");
        $this->hotfix->version("05/12/2022", "Initial release");
        $this->hotfix->instructions("This is a instructions example");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20240210($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $count=500;
        $eof=FALSE;
        while ( !$eof) {
            $items=array();
            $query=array("select id, request, free_field from crmlog");
            $query[]="limit {$meta->count}, {$count}";
            $this->query($query);
            while ($this->fetch = $this->fetch_array()) {
                $obj=array();
                $obj['request']=unserialize($this->fetch['request']);
                parse_str( $this->fetch['free_field'], $arr);
                $obj['free_field']=(object) $arr;
                $items[ $this->fetch['id'] ]=$obj;
            }
            foreach ($items as $id => $obj) {
                $fields=array();
                $fields[]="request=" . fn_escape(json_encode($obj['request']));
                $fields[]="free_field=" . fn_escape(json_encode($obj['free_field']));
                $query=array("update crmlog set");
                $query[]=implode(",\n", $fields);
                $query[]="where id=" . fn_escape($id);
                $this->query($query);
            }
            $meta->count += sizeof($items);
            if (sizeof($items) != $count) $eof=TRUE;
        }
    }
    function scs_hotfix_20230731($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $query = <<< EOT
ALTER TABLE `crmlog`
CHANGE COLUMN `source` `source` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `sku` `sku` VARCHAR(255) NULL DEFAULT NULL ,
CHANGE COLUMN `cad_code` `cad_code` VARCHAR(120) NULL DEFAULT NULL ,
CHANGE COLUMN `email` `email` VARCHAR(256) NULL DEFAULT NULL,
CHANGE COLUMN `company_name` `company_name` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `state` `state` CHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `zip` `zip` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `country_code` `country_code` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `phone` `phone` VARCHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `fax` `fax` VARCHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `status` `status` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `link` `link` VARCHAR(512) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_service` `crm_service` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_server` `crm_server` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_table` `crm_table` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_contact_id` `crm_contact_id` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_transaction_id` `crm_transaction_id` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_error` `crm_error` VARCHAR(512) NULL DEFAULT NULL ,
ADD COLUMN `crm_status` VARCHAR(20) NULL DEFAULT 'N/A' AFTER `link`;
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set crm_status="Update"
where status="Update"
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set status="Update", crm_status="Error"
where status="Error" and crm_error <> ""
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set status="Update", crm_status="Error", crm_error="Internal Error"
where status="Error"
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set status="Update", crm_status="Blacklist", crm_error=error, error=""
where status="Blacklist" and crm_error=""
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set status="Update", crm_status="Blacklist"
where status="Blacklist"
EOT;
        $this->query($query);

        $query = <<< EOT
update crmlog
set status='Error', error="Internal Error", crm_status="N/A"
where status <> 'Update'
EOT;
        $this->query($query);

        $this->query("select count(*) as count from crmlog");
        $this->fetch=$this->fetch_array();
        $meta->count=$this->fetch['count'];
    }
}
class crmlog_constant_class {
	var $status=array("Update", "Pending", "Error");
	var $crm_status=array("Update", "Blacklist", "Error", "N/A");
	var $source=array("partcommunity"=>"PARTcommunity","partserver"=>"PARTserver","import"=>"Import","legacy"=>"Legacy");
    var $count=array("add", "duplicate", "skip", "error");
    var $crm_server=array("production"=>"Production","sandbox"=>"Sandbox");
	var $crm_table=array("Contact","Lead");
    var $transaction=array();
    function __construct() {
    	$this->transaction["id"]="Record ID";
    	$this->transaction["name"]="Name/TItle";
    	$this->transaction["sku"]="SKU/Part number";
    	$this->transaction["cad_code"]="CAD Format";
    	$this->transaction["transaction_id"]="PARTsolutions Order ID";
    	$this->transaction["timestamp"]="Transaction timestamp";
    	$this->transaction["contact_id"]="Contact ID";
    	$this->transaction["lead_id"]="Lead ID";
    }
}
class crmlog_data_class {
	var $id=0;
	var $timestamp=0;
	var $transaction_id;
	var $source;
	var $sku;
	var $cad_code=array();
    var $user_id=0;
	var $email;
	var $name_first;
	var $name_last;
	var $name;
	var $company_name;
	var $address;
	var $city;
	var $state;
	var $zip;
	var $country_code;
	var $phone;
	var $fax;
	var $consent=1;
	var $status;
	var $error;
    var $link;
    var $crm_status="N/A";
	var $crm_service;
	var $crm_server;
	var $crm_table;
	var $crm_contact_id;
	var $crm_transaction_id;
    var $crm_error;
    var $free_field;
	var $request;
    function __construct() {
        $this->timestamp=strtotime("now");
        $this->free_field=new stdClass();
        $this->request=new stdClass();
    }
}
class crmlog_ipc {
	var $service;
	var $server;
	var $table;
	var $contact_id;
	var $transaction_id;
    var $blacklist=FALSE;
    var $blacklist_condition;
    var $track=TRUE;
	var $error;
    function __construct($data) {
		switch (TRUE) {
          case (!strlen($data->email)):
          	$this->error="Email missing";
            $this->track=FALSE;
            break;
          case (preg_match("/" . preg_quote("@partcommunity.com") . "/i",$data->email)):
            $this->blacklist=TRUE;
            $this->track=FALSE;
        	$this->error="Domain: partcommunity.com";
            break;
          default:
          	$this->blacklist($data);
		}
    }
    function blacklist($data) {
    global $database;
		list($account,$domain)=explode("@",$data->email);
        $query_where=array();
        $query_where[]=new query_where("account","in",array("",$account));
        $query_where[]=new query_where("domain","=",$domain);
		$query=array("select * from crmblacklist");
        $query[]=$database->where($query_where);
        $query[]="order by account desc";
        $query[]="limit 1";
        $database->crmblacklist->query($query);
        if ($database->crmblacklist->meta->rows) {
			$database->crmblacklist->fetch(TRUE);
            if (!sizeof($database->crmblacklist->data->condition)) {
	            $this->blacklist=TRUE;

			  } else {
              	foreach ($database->crmblacklist->data->condition as $this->blacklist_condition) {
					if (!strlen($data->{$this->blacklist_condition})) {
			            $this->blacklist=TRUE;
                        break;
                    }
                }
            }
            if ($this->blacklist) {
	            $this->track=$database->crmblacklist->data->track;
                $text=array();
                $text[]=( (strlen($database->crmblacklist->data->account)) ? "Email: " . $database->crmblacklist->email() : "Domain: " . $database->crmblacklist->data->domain) ;
                if (strlen($this->blacklist_condition)) $text[]="Blank: " . $this->blacklist_condition;
	            $this->error=implode(" ",$text);
			}
		}
    }
    function service($service, $server, $error="") {
    	$this->service=$service;
        $this->server=$server;
        $this->error=$error;
    }
}
/*
ALTER TABLE `crmlog`
CHANGE COLUMN `source` `source` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `sku` `sku` VARCHAR(255) NULL DEFAULT NULL ,
CHANGE COLUMN `cad_code` `cad_code` VARCHAR(120) NULL DEFAULT NULL ,
CHANGE COLUMN `email` `email` VARCHAR(256) NULL DEFAULT NULL,
CHANGE COLUMN `company_name` `company_name` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `state` `state` CHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `zip` `zip` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `country_code` `country_code` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `phone` `phone` VARCHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `fax` `fax` VARCHAR(40) NULL DEFAULT NULL ,
CHANGE COLUMN `status` `status` VARCHAR(20) NULL DEFAULT NULL ,
CHANGE COLUMN `link` `link` VARCHAR(512) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_service` `crm_service` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_server` `crm_server` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_table` `crm_table` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_contact_id` `crm_contact_id` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_transaction_id` `crm_transaction_id` VARCHAR(60) NULL DEFAULT NULL ,
CHANGE COLUMN `crm_error` `crm_error` VARCHAR(512) NULL DEFAULT NULL ,
ADD COLUMN `crm_status` VARCHAR(20) NULL DEFAULT 'N/A' AFTER `link`,
COMMENT = 'CRM Transaction Log (02/10/2024)' ;

update crmlog
set crm_status="Update"
where status="Update"

update crmlog
set status="Update", crm_status="Error"
where status="Error" and crm_error <> ""

update crmlog
set status="Update", crm_status="Error", crm_error="Internal Error"
where status="Error"

update crmlog
set status="Update", crm_status="Blacklist", crm_error=error, error=""
where status="Blacklist" and crm_error=""

update crmlog
set status="Update", crm_status="Blacklist"
where status="Blacklist"

update crmlog
set status='Error', error="Internal Error", crm_status="N/A"
where status <> 'Update'



Older versions

ALTER TABLE `crmlog`
CHANGE COLUMN `email` `email` VARCHAR(256) NULL DEFAULT NULL ;

ALTER TABLE `crmlog`
ADD COLUMN `consent` int(1) NULL DEFAULT '1' AFTER `fax`,
ENGINE=InnoDB,
COMMENT = 'CRM Transaction Log (05/12/2022)' ;

CREATE TABLE `crmlog` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `timestamp` bigint(12) DEFAULT '0',
  `transaction_id` varchar(60) DEFAULT '',
  `source` varchar(20) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `cad_code` varchar(120) DEFAULT NULL,
  `user_id` bigint(10) DEFAULT '0',
  `email` varchar(256) DEFAULT NULL,
  `name_first` varchar(45) DEFAULT NULL,
  `name_last` varchar(45) DEFAULT NULL,
  `name` varchar(100) DEFAULT '',
  `company_name` varchar(100) DEFAULT NULL,
  `address` mediumtext,
  `city` varchar(40) DEFAULT '',
  `state` char(40) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `country_code` varchar(60) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `fax` varchar(40) DEFAULT NULL,
  `consent` int(1) DEFAULT '1',
  `free_field` mediumtext,
  `request` mediumtext,
  `status` varchar(20) DEFAULT NULL,
  `error` varchar(512) DEFAULT NULL,
  `link` varchar(512) DEFAULT NULL,
  `crm_status` varchar(20) DEFAULT 'N/A',
  `crm_service` varchar(60) DEFAULT NULL,
  `crm_server` varchar(60) DEFAULT NULL,
  `crm_table` varchar(60) DEFAULT NULL,
  `crm_contact_id` varchar(60) DEFAULT NULL,
  `crm_transaction_id` varchar(60) DEFAULT NULL,
  `crm_error` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id_key` (`transaction_id`),
  KEY `status_key` (`status`),
  KEY `timestamp_index` (`timestamp`),
  KEY `user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='CRM Transaction Log (07/31/2023)'

Convert from scspslog

insert into crmlog select
id,
timestamp,
transaction_id,
source,
sku,
cad_code,
0,
email,
name_first,
name_last,
name,
company_name,
address,
city,
state,
zip,
country_code,
phone,
null,
null,
user_defined,
request,
status,
error,
link,
'N/A',
crm_service,
crm_server,
crm_table,
crm_contact_id,
crm_transaction_id,
crm_error from scspslog


Colson:
update crmlog set status='Update', crm_status='Update' where status='salesforce'
update crmlog set status='Update', crm_status='Error' where status='download'
*/
?>
