<?php
require_once "classes/scsmail_rev1.php";
class dashboard_toolbox_class {
	var $tabs;
    var $title="Dashboard";
    var $revised;
    var $cpqdomain;
    var $landing_tab=FALSE;
    var $message;
	function scs_table_version() {
		$results=array();
        $results['08/08/2025']="Add function:user_disabled, email_error:resolved";
        $results['02/14/2025']="Enhance function:item";
        $results['01/28/2025']="Add cpqdomain";
        $results['03/23/2024']="Add lookup_error";
        $results['12/17/2023']="dashboard_functions optional";
        $results['06/03/2022']="Do not delete API errors";
        $results['05/09/2022']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct($role, $options=array()) {
        global $database, $forms, $menu;
        $this->cpqdomain=property_exists($database->registry, "cpqdomain");
        switch (TRUE) {
          case ( (!strlen($role)) || (!$database->user->access($role, FALSE)) ):
            fn_url_redirect($menu->page->home);
            break;
          case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
            $this->json();
            break;
          default:
            $this->html($options);
        }
    }
    function json() {
        global $database;
        $this->response=new stdClass();
        $function="json_" . $_POST['action'];
        if (method_exists($this, $function)) $this->$function();
        die (json_encode($this->response));
    }
    function json_confirm() {
        global $database;
        $database->user->read($_POST['id']);
        $this->response=$database->cpqdomain->confirm($database->user->data);
    }
    function html($options) {
        global $database, $forms, $menu;
        if ( ($_SERVER['REQUEST_METHOD']=="POST") && (strlen($_POST['action'])) ) {
			list($action,$message)=fn_base64($_POST['action'],FALSE);
            $function="{$action}_update";
            if (method_exists($this, $function)) $this->message=$this->$function($action, $message);
        }
        if (array_key_exists("title",$options)) $this->title=$options['title'];
        $this->revised=(array_key_exists("revised",$options)) ? $options['revised'] : key($this->scs_table_version());
		$this->tabs=new jquery_tab_class($options);
        if (function_exists("dashboard_functions")) $this->item($_SESSION['user']->role,dashboard_functions());

    }
    function item($name) {
        switch (TRUE) {
          case (func_num_args() != 2):
            return;
          case (is_object(func_get_arg(1))):
            $obj=func_get_arg(1);
            break;
          default:
            $obj=new stdClass();
            $obj->content=func_get_arg(1);
            $obj->count=0;
            $obj->landing_tab=FALSE;
        }
        if ($obj->landing_tab) $this->landing_tab();
        if ($obj->count) $name .= " (" . number_format($obj->count) . ")";
    	$this->tabs->item($name, $obj->content);
    }
    function landing_tab() {
    	if (!$this->landing_tab) {
        	$this->landing_tab=TRUE;
            $this->tabs->landing_tab();
        }
    }
    function output($js="") {
    global $database, $forms, $menu;
	    $forms->title("{$this->title} ({$this->revised})");
        if (strlen($this->message)) $forms->message[]=$this->message;
	    $menu->head();
	    $forms->message();
        print $this->js($js);
        print $forms->open();
        print $forms->hidden("action");
		$this->tabs->output();
        print $forms->close();
		$menu->copyright(TRUE);
    }
	function user_pending($role, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
        $query_where=array();
        $query_where[]=new query_where("status", "=", "pending");
        if ($this->cpqdomain) $query_where[]=new query_where("cpqdomain_id", "=", 0);
	    $query=array("select * from user");
	    $query[]=$database->where($query_where);
	    $query[]="order by name";
	    $database->user->query($query);
	    if (!$database->user->meta->rows) return;
	    $results=array();
	    $results[]="<table class='border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th>Email</th>";
	    $results[]="<th>Name</th>";
	    $results[]="<th>Company name</th>";
	    $results[]="<th>Date</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    $url_query['action']="maintain";
	    $url_query['record_id']=0;
	    while ($database->user->fetch = $database->user->fetch_array()) {
	        $database->user->fetch();
	        $results[]="<tr>";
	        $url_query['record_id']=$database->user->data->id;
	        $results[]="<td>" . fn_href($database->user->data->email,"/user_approve.php",$url_query) . "</td>";
	        $results[]="<td>" . $database->user->data->name . "</td>";
	        $results[]="<td>" . $database->user->data->company_name . "</td>";
	        $results[]="<td class=center>" . date("m/d/Y h:i A",$database->user->data->initial_timestamp) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $database->user->free_result();
	    $this->item("Pending Users (" . number_format($database->user->meta->rows) . ")",$results);
	}
    function user_confirm($role, $cpqdomain_id, $admin, $options=array()) {
	    global $database, $forms;
        if (method_exists($database->user, "access_domain")) {
            $access=$database->user->access_domain($role, $admin, FALSE);
          } else {
            $access=$database->user->access($this->role, FALSE);
        }
        if (!$access) return;
        $query_where=array();
        $query_where[]=new query_where("user.status","=","pending");
        if ($cpqdomain_id) {
            $query_where[]=new query_where("user.cpqdomain_id","=",$cpqdomain_id);
          } else {
            $query_where[]=new query_where("user.cpqdomain_id","<>",0);
        }
        $query=array("select");
        $query[]="cpqdomain.domain as 'domain',";
        $query[]="user.* from user";
        $query[]="left join cpqdomain on cpqdomain.id=user.cpqdomain_id";
        $query[]=$database->where($query_where);
        $query[]="order by domain, user.initial_timestamp";
        $database->user->query($query);
        if (!$database->user->meta->rows) return;
        $items=array();
        while ($database->user->fetch = $database->user->fetch_array()) {
            $database->user->fetch();
            if (!array_key_exists($database->user->fetch['domain'], $items)) $items[$database->user->fetch['domain']]=array();
            $items[$database->user->fetch['domain']][]=$database->user->data;
        }
        $results=array();
        $results[]="<table class=border>";
        $results[]="<tr>";
        if (!$cpqdomain_id) $results[]="<th>Domain</th>";
        $results[]="<th>Email</th>";
        $results[]="<th>Name</th>";
        $results[]="<th>Date</th>";
        $results[]="<th>Resend Confirmation?</th>";
        $results[]="</tr>";
        foreach ($items as $domain => $domain_items) {
            foreach ($domain_items as $row => $data) {
                $results[]="<tr>";
                if ( (!$cpqdomain_id) && (!$row) ) $results[]="<td rowspan=" . sizeof($domain_items). ">{$domain}</td>";
                $results[]="<td>{$data->email}</td>";
                $results[]="<td>{$data->name}</td>";
                $results[]="<td class=center>" . date("m/d/Y h:i A",$data->initial_timestamp) . "</td>";
                $text=array($forms->checkbox("confirm{$data->id}",1,0,array("onclick"=>"dashboard_confirm({$data->id}," . fn_escape($data->email) . ");")));
                $text[]="<span id=confirm{$data->id}_span></span>";
                $results[]="<td class=center>" . implode(" ", $text) . "</td>";
                $results[]="</tr>";
            }
        }
        $results[]="</table>";
        $this->item("Unconfirmed Users (" . number_format($database->user->meta->rows) . ")",$results);
    }
	function user_disabled($role, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
        $query_where=array();
        $query_where[]=new query_where("disabled", "=", "1");
        $query_where[]=new query_where("status","<>","reject");
	    $query=array("select * from user");
	    $query[]=$database->where($query_where);
	    $query[]="order by name";
	    $database->user->query($query);
	    if (!$database->user->meta->rows) return;
	    $results=array();
	    $results[]="<table class='border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th>Email</th>";
	    $results[]="<th>Name</th>";
	    $results[]="<th>Company name</th>";
	    $results[]="<th>Status</th>";
	    $results[]="<th>Last Login</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    $url_query['action']="dashboard";
	    $url_query['record_id']=0;
	    while ($database->user->fetch = $database->user->fetch_array()) {
	        $database->user->fetch();
	        $results[]="<tr>";
	        $url_query['record_id']=$database->user->data->id;
	        $results[]="<td>" . fn_href($database->user->data->email,"/user_unlock.php",$url_query) . "</td>";
	        $results[]="<td>{$database->user->data->name}</td>";
	        $results[]="<td>{$database->user->data->company_name}</td>";
	        $results[]="<td>{$database->user->data->status}</td>";
	        $results[]="<td class=center>" . date("m/d/Y h:i A",$database->user->data->login_timestamp) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $database->user->free_result();
	    $this->item("Disabled Users (" . number_format($database->user->meta->rows) . ")",$results);
	}
	function email_error($role, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
	    $query_where=array();
	    $query_where[]=new query_where("log.type","=","Email Error");
	    $query_where[]=new query_where("log.date_time",">=",strtotime("-7 days"));
	    $query=array("select * from log");
	    $query[]=$database->where($query_where);
	    $query[]="order by id desc";
	    $database->log->query($query);
	    if (!$database->log->meta->rows) return;
	    $results=array();

        $classname=fn_base64(array(__FUNCTION__,""));
        $text=array();
	    $results[]="<p>Email errors detected in the past 7 days</p>";
        $text[]=$forms->button("Mark as resolved?",array("onclick"=>"dashboard_action(" . fn_escape($classname) . "," . fn_escape("Resolve email errors?") . ");"));
        $text[]="Select All?";
        $text[]=$forms->checkbox($classname,1,0,array("onclick"=>"dashboard_checked(this," . fn_escape($classname) . ");"));
        $results[]="<p>" . implode(" ",$text) . "</p>";
	    $results[]="<table class='small border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th>Resolved?</th>";
	    $results[]="<th>Form</th>";
	    $results[]="<th>Timestamp</th>";
	    $results[]="<th>Comment</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    while ($database->log->fetch = $database->log->fetch_array() ) {
	        $database->log->fetch();
	        $results[]="<tr>";
            $text=$forms->checkbox(fn_base64(array(__FUNCTION__, $database->log->data->id)),1,0,array("class"=>$classname));
            $results[]="<td class=center>{$text}</td>";
	        $results[]="<td>{$database->log->data->subtype}</td>";
	        $results[]="<td class=center>" . date("m/d/Y h:i A",$database->log->data->date_time) . "</td>";
	        $results[]="<td>" . implode("<br>",$database->log->data->comment) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $database->log->free_result();
	    $this->item("Email Errors (" . $database->log->meta->rows . ")",$results);
	}
	function email_error_update($action) {
        global $database, $forms;
    	$items=array();
		foreach ($_POST as $key => $value) {
        	list($action, $id)=fn_base64($key,FALSE);
            if ( ($action == $action) && (strlen($id)) ) $items[]=$id;
        }
		if (!sizeof($items)) return;
		$query_where=array();
        $query_where[]=new query_where("id","in",$items);
        $query=array("update log");
        $query[]="set type=" . fn_escape("Email Error Resolved");
		$query[]=$database->where($query_where);
		$database->temp->query($query);
        return "Resolved " . number_format(sizeof($items)) . " {$subtype} Errors";
    }
	function api_error($role, $subtype, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
        $this->api_error=new stdClass();
        $this->api_error->purge=($options['purge']) ? $options['purge'] : $role;
        $this->api_error->title=($options['title']) ? $options['title'] : "{$subtype} Errors";
        if (!strlen($options['url'])) unset($options['url']);
	    $query_where=array();
	    $query_where[]=new query_where("log.type","=","API Error");
	    $query_where[]=new query_where("log.subtype","=",$subtype);
	    $query_where[]=new query_where("log.date_time",">=",strtotime("-7 days"));
	    $query=array("select");
	    $query[]="max(log.date_time) as 'date',";
	    $query[]="count(*) as 'count',";
	    $query[]="log.* from log";
	    $query[]=$database->where($query_where);
	    $query[]="group by sku,comment";
	    $query[]="order by sku, comment";
	    $database->log->query($query);
	    if (!$database->log->meta->rows) return;
        $classname=fn_base64(array(__FUNCTION__,$subtype));
	    $results=array();
	    $results[]="<p>{$subtype} Errors detected in the past 7 days</p>";
        if ($database->user->access($this->api_error->purge,FALSE)) {
        	$text=array();
            $text[]=$forms->button("Mark as resolved?",array("onclick"=>"dashboard_action(" . fn_escape($classname) . "," . fn_escape("Resolve " . $this->api_error->title . "?") . ");"));
            $text[]="Select All?";
            $text[]=$forms->checkbox($classname,1,0,array("onclick"=>"dashboard_checked(this," . fn_escape($classname) . ");"));
            $results[]="<p>" . implode(" ",$text) . "</p>";
        }
	    $results[]="<table class='small border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
        if ($database->user->access($this->api_error->purge,FALSE)) $results[]="<th>Resolved?</th>";
	    $results[]="<th>SKU</th>";
	    $results[]="<th>Error</th>";
	    $results[]="<th>Last Date</th>";
	    $results[]="<th>Count</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    while ($database->log->fetch = $database->log->fetch_array() ) {
	        $database->log->fetch();
	        $results[]="<tr>";
	        if ($database->user->access($this->api_error->purge,FALSE)) {
            	$text=$forms->checkbox(fn_base64(array(__FUNCTION__,$subtype, $database->log->data->sku)),1,0,array("class"=>$classname));
            	$results[]="<td class=center>$text</td>";
			}
            if (strlen($options['url'])) {
                $url_query=array();
                $url_query['action']="dashboard";
                $url_query['sku']=$database->log->data->sku;
                $text=fn_href($database->log->data->sku, $options['url'],$url_query,array("target"=>"configure"));
            } else {
                $text=$database->log->data->sku;
            }
	        $results[]="<td>{$text}</td>";
	        $results[]="<td>" . implode("<br>",$database->log->data->comment) . "</td>";
	        $results[]="<td class=center>" . date("m/d/Y h:i A",$database->log->fetch['date']) . "</td>";
	        $results[]="<td class=center>"  . number_format($database->log->fetch['count']) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
	    $database->log->free_result();
	    $this->item($this->api_error->title . " (" . $database->log->meta->rows . ")",$results);
	}
	function api_error_update($action, $subtype) {
	    global $database;
    	$items=array();
		foreach ($_POST as $key => $value) {
        	$text=fn_base64($key,FALSE);
            if ( ($text[0] == $action) && ($text[1]=$subtype) && (strlen($text[2])) ) $items[]=$text[2];
        }
		if (!sizeof($items)) return;
		$query_where=array();
        $query_where[]=new query_where("sku","in",$items);
        $query_where[]=new query_where("type","=","API Error");
        $query_where[]=new query_where("subtype","=",$subtype);
	    $query_where[]=new query_where("log.date_time",">=",strtotime("-7 days"));
        $query=array("update log");
        $query[]="set type=" . fn_escape("API Error Resolved");
		$query[]=$database->where($query_where);
		$database->temp->query($query);
        return "Resolved " . number_format(sizeof($items)) . " {$subtype} Errors";
    }
    function lookup_error($role, $subtype, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
        $this->lookup_error=new stdClass();
        $this->lookup_error->purge=($options['purge']) ? $options['purge'] : $role;
        $this->lookup_error->title=($options['title']) ? $options['title'] : "Lookup {$subtype}";
	    $query_where=array();
	    $query_where[]=new query_where("log.type","=","Reverse Part# Error");
	    $query_where[]=new query_where("log.subtype","=","{$subtype}");
	    $query_where[]=new query_where("log.date_time",">=",strtotime("-7 days"));
	    $query=array("select");
        $query[]="count(*) as count,";
        $query[]="max(log.date_time) as 'last_date',";
        $query[]="sku from log";
	    $query[]=$database->where($query_where);
	    $query[]="group by sku";
	    $query[]="order by sku";
	    $database->temp->query($query);
	    if (!$database->temp->meta->rows) return;
        $results=array();
        $classname=fn_base64(array(__FUNCTION__, $subtype));
	    $results=array();
	    $results[]="<p>Reverse Part# {$subtype} Errors detected in the past 7 days</p>";
        if ($database->user->access($this->api_error->purge,FALSE)) {
        	$text=array();
            $text[]=$forms->button("Mark as resolved?",array("onclick"=>"dashboard_action(" . fn_escape($classname) . "," . fn_escape("Resolve " . $this->lookup_error->title . "?") . ");"));
            $text[]="Select All?";
            $text[]=$forms->checkbox($classname,1,0,array("onclick"=>"dashboard_checked(this," . fn_escape($classname) . ");"));
            $results[]="<p>" . implode(" ",$text) . "</p>";
        }
	    $results[]="<table class='standard border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
        if ($database->user->access($this->lookup_error->purge,FALSE)) $results[]="<th>Resolved?</th>";
	    $results[]="<th>SKU</th>";
	    $results[]="<th>Last Date</th>";
	    $results[]="<th>Count</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
        $results[]="<tbody>";
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
	        $results[]="<tr>";
	        if ($database->user->access($this->lookup_error->purge,FALSE)) {
            	$text=$forms->checkbox(fn_base64(array(__FUNCTION__, $subtype, $database->temp->fetch['sku'])),1,0,array("class"=>$classname));
            	$results[]="<td class=center>$text</td>";
			}
	        $results[]="<td>" . $database->temp->fetch['sku'] . "</td>";
	        $results[]="<td class=center>" . date("m/d/Y h:i A", $database->temp->fetch['last_date']) . "</td>";
	        $results[]="<td class=center>"  . number_format($database->temp->fetch['count']) . "</td>";
	        $results[]="</tr>";
        }
        $results[]="</tbody>";
	    $results[]="</table>";
	    $this->item($this->lookup_error->title . " (" . $database->temp->meta->rows . ")",$results);
    }
    function lookup_error_update($action, $subtype) {
	    global $database;
    	$items=array();
		foreach ($_POST as $key => $value) {
        	$text=fn_base64($key,FALSE);
            if ( ($text[0] == $action) && ($text[1]=$subtype) && (strlen($text[2])) ) $items[]=$text[2];
        }
		if (!sizeof($items)) return;
		$query_where=array();
        $query_where[]=new query_where("sku","in",$items);
        $query_where[]=new query_where("type","=","Reverse Part# Error");
        $query_where[]=new query_where("subtype","=",$subtype);
	    $query_where[]=new query_where("log.date_time",">=",strtotime("-7 days"));
        $query=array("update log");
        $query[]="set type=" . fn_escape("Reverse Part# Error Resolved");
		$query[]=$database->where($query_where);
		$database->temp->query($query);
        return "Resolved " . number_format(sizeof($items)) . " {$subtype} Errors";
    }
    function crmlog($role, $options=array()) {
	    global $database, $forms;
	    if (!$database->user->access($role,FALSE)) return;
        $this->crmlog=new stdClass();
        $this->crmlog->url=(array_key_exists("url",$options)) ? $options['url'] : "/crmlog_list.php";
        switch (TRUE) {
          case (!array_key_exists("status",$options)):
          	$this->crmlog->status=array();
            break;
          case (!is_array($options['status'])):
          	$this->crmlog->status=array($options['status']);
            break;
           default:
          	$this->crmlog->status=$options['status'];
		}
        $this->crmlog->title=(array_key_exists("title",$options)) ? $options['title'] : "CRM Activity";
	    $query_where=array();
        if (sizeof($this->crmlog->status)) $query_where[]=new query_where("status","in",$this->crmlog->status);
	    $query_where[]=new query_where("timestamp",">=",strtotime("-7 days"));
	    $query=array("select * from crmlog");
	    $query[]=$database->where($query_where);
	    $query[]="order by timestamp";
	    $database->crmlog->query($query);
	    if (!$database->crmlog->meta->rows) return;
        $results=array();
	    $results[]="<table class='small border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th>Timestamp</th>";
	    $results[]="<th>Email</th>";
	    $results[]="<th>SKU</th>";
	    $results[]="<th>Source</th>";
	    $results[]="<th>Server</th>";
        $results[]="<th>Status</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    $results[]="<tbody>";
	    while ($database->crmlog->fetch = $database->crmlog->fetch_array()) {
	        $database->crmlog->fetch();
	        $results[]="<tr>";
	        $results[]="<td class=center>" . fn_href(date("m/d/Y H:i",$database->crmlog->data->timestamp),$this->crmlog->url,array("action"=>"dashboard","transaction_id"=>$database->crmlog->data->transaction_id)) . "</td>";
	        $results[]="<td>" . $database->crmlog->data->email . "</td>";
	        $results[]="<td>" . $database->crmlog->data->sku . "</td>";
	        $results[]="<td>" . $database->crmlog->data->source . "</td>";
	        $results[]="<td>" . $database->crmlog->data->crm_server . "</td>";
            switch (TRUE) {
              case ($database->crmlog->data->error):
              	$text=$database->crmlog->data->error;
                break;
              case ($database->crmlog->data->crm_error):
              	$text=$database->crmlog->data->crm_error;
                break;
              defeault:
              	$text=$database->crmlog->data->status;
            }
	        $results[]="<td>$text</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</tbody>";
	    $results[]="</table>";
		$this->item("7 Day {$this->crmlog->title} (" . number_format($database->crmlog->meta->rows) . ")",$results);
    }
    function js($js) {
        $results = <<< EOT

<script>
function dashboard_action(action, title) {
    if (!confirm(title)) return
    jQuery('#action').val(action);
    jQuery('#scs_form').submit();
}
function dashboard_checked(obj, classname) {
    jQuery('.' + classname).prop('checked', obj.checked);
}
function dashboard_confirm(id, email) {
    jQuery('#confirm' + id).prop('checked', false);
    if (!confirm('Resend Confirmation: ' + email)) return;
    field="#confirm" + id + "_span";
    jQuery(field).html("Working ...");
    jQuery.ajax({
        type: 'post',
        dataType: 'json',
        data: { action: "confirm", id: id },
        success:function(response) { jQuery(field).html(response) }
    });
}
EOT;
        $results .= "\n{$js}\n</script>";
        return $results;
    }
}
?>