<?php
require_once "classes/crmlog.php";
require_once "classes/cad.php";
require_once "map_rev3.php";
class crmlog_list_class {
	var $options=array();
	var $status=array();
	var $source;
	var $crm_server;
    var $file_type;
    var $transaction_id;
    var $email;
    var $sku;
    var $from_date;
    var $thru_date;
    var $request=0;
	function scs_table_version() {
		$results=array();
        $results['04/16/2024']="Add json query";
        $results['08/10/2023']="Add crm_status";
        $results['03/31/2023']="Add page_break_class";
        $results['05/12/2022']="Add request export";
        $results['06/22/2021']="Add transaction_id inquiry";
        $results['01/27/2021']="Add view";
        $results['10/30/2020']="Add limit to list";
        $results['04/25/2020']="Include list,title";
        $results['03/16/2020']="Add ALL option";
        $results['11/18/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role, $options=array()) {
    global $database, $forms, $menu;
		$database->user->access($role);
        $this->options=new stdClass();
        $this->options->title=(array_key_exists("title",$options)) ? $options['title'] : "CRM Transactions";
        $this->options->crm=(array_key_exists("crm",$options)) ? $options['crm'] : TRUE;
        $this->options->json=(array_key_exists("json",$options)) ? $options['json'] : array();
        $this->input($options);
	    $forms->title($this->options->title . " (" . key($this->scs_table_version()) . ")");
        $this->input->page_break=new page_break_class($this->input->action, $this->input->page, $this->input->item);
		if ( ($this->input->action == "list") && (!sizeof($forms->error)) && ($this->input->file_type) ) $this->export();
	    $menu->head();
	    $forms->message();
        print $this->js();
	    print $forms->open();
	    print $forms->hidden("action");
	    print $forms->hidden("page");
	    print $forms->hidden("item");
	    $menu->tabs=new jquery_tab_class();
	    $menu->tabs->item("Select",$this->select());
	    switch (TRUE) {
	      case ( (!$this->input->action) || (sizeof($forms->error)) ):
	        break;
	      case ($this->input->action == "item"):
	        $menu->tabs->item("Detail",$this->detail());
	        break;
          case (strlen($this->input->transaction_id)):
	        $menu->tabs->item("Detail",$this->detail());
	        break;
	      default:
	        $menu->tabs->item("Summary",$this->summary());
	    }
	    $menu->tabs->output();
	    print $forms->close();
	    $menu->copyright();
    }
    function input($options) {
    global $database, $forms;
    	$this->input=new stdClass();
	    $this->input->action=( ($options['action']) ? $options['action'] : $_POST['action']);
	    $this->input->page=intval($_POST['page']);
	    $this->input->item=intval($_POST['item']);
	    switch ($this->input->action) {
	      case "":
	      case "dashboard":
	        $this->input->from_date=date("Y/m/d",strtotime("-6 days"));
	        $this->input->thru_date=date("Y/m/d",strtotime("now"));
          	$this->input->status=$database->crmlog->constant->status;
          	$this->input->crm_status=$database->crmlog->constant->crm_status;
            $this->input->transaction_id=$options['transaction_id'];
          	return;
		}
        $this->input->status=array();
        $this->input->crm_status=array();
        foreach ($_POST as $key => $value) {
        	list($field,$status)=fn_base64($key,FALSE);
            switch ($field) {
              case "status":
              	$this->input->status[]=$status;
                break;
              case "crm_status":
              	$this->input->crm_status[]=$status;
                break;
			}
        }
        if (!sizeof($this->input->status)) $forms->error('status',"No status selected");
        if ( ($this->options->crm) && (!sizeof($this->input->crm_status)) ) $forms->error('crm_status',"No CRM status selected");
	    $this->input->source=$_POST['source'];
	    $this->input->crm_server=$_POST['crm_server'];
	    $this->input->file_type=$_POST['file_type'];
        $email=new email_class($_POST['email'],FALSE);
        $this->input->email=$email->address;
        if ($email->error) $forms->error("email",$email->error);
		$this->input->sku=fn_text($_POST['sku'],TRUE);
        $this->input->json_field=$_POST['json_field'];
        if ($this->input->json_field) {
            $this->input->json=trim($_POST['json']);
            if (!strlen($this->input->json)) $forms->error("json", "Cannot be blank");
        }
        $this->input->consent=$_POST['consent'];
	    $this->input->request=intval($_POST['request']);
        $this->input->transaction_id=fn_text($_POST['transaction_id']);
        $this->input->from_date=fn_date($_POST['from_date'],"mdy");
	    $this->input->thru_date=fn_date($_POST['thru_date'],"mdy");
        if ( ($this->input->thru_date) && ($this->input->thru_date < $this->input->from_date) ) $forms->error("thru_date");
        if (strlen($this->input->transaction_id)) {
        	$database->crmlog->read($this->input->transaction_id,"transaction_id");
            if (!$database->crmlog->meta->rows) $forms->error("transaction_id","Not found");
        }
    }
    function select() {
    global $database, $forms, $menu;
	    $results=array();
	    $results[]="<div><b><span id='results_id' class=left></span></b></div>";
	    $results[]="<table class='standard noborder'>";
	    $results[]="<tr>";
	    $results[]="<td width='20%'>Transaction ID</td>";
	    $results[]="<td width='80%'>" . $forms->text("transaction_id",$this->input->transaction_id,40) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Date Range</td>";
	    $results[]="<td>" . $forms->text("from_date",$this->input->from_date,10,10,"date") . " - " . $forms->text("thru_date",$this->input->thru_date,10,10,"date") . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
        $results[]="<td>Email address</td>";
        $text=array();
        $text[]=$forms->text("email",$this->input->email,60,0,"email");
        if ($forms->error_text['email']) $text[]=$forms->error_text['email'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
        $results[]="<td>SKU</td>";
        $results[]="<td>" . $forms->text("sku",$this->input->sku,60) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Source</td>";
	    $results[]="<td>" . $forms->select("source",$database->crmlog->constant->source,$this->input->source) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Status</td>";
        $text=array();
        if ($forms->error_text['status']) $text[]="<b>" . $forms->error_text['status'] . "</b>";
        $text[]=$forms->checkbox("status",1,( ( sizeof($this->input->status) == sizeof($database->crmlog->constant->status) ) ? 1 : 0),array("onclick"=>"crmlog_checked(this);")) . " All";
        foreach ($database->crmlog->constant->status as $status) {
        	$text[]=$forms->checkbox(fn_base64(array("status",$status)),1,in_array($status, $this->input->status),array("class"=>"status")) . " {$status}";
        }
	    $results[]="<td>" . implode("<br>",$text) . "</td>";
	    $results[]="</tr>";
        if (sizeof($this->options->json)) {
            $results[]="<tr>";
            $results[]="<td>JSON: " . $forms->select("json_field", $this->options->json, $this->input->json_field) . "</td>";
            $text=array();
            $text[]=$forms->text("json", $this->input->json, 60);
            if ($forms->error_text['json']) $text[]=$forms->error_text['json'];
            $results[]="<td>" . implode("<br>", $text) . "</td>";
            $results[]="</tr>";
        }

        if ($this->options->crm) {
	        $results[]="<tr>";
	        $results[]="<td>CRM Server</td>";
	        $results[]="<td>" . $forms->select("crm_server",$database->crmlog->constant->crm_server,$this->input->crm_server) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>CRM Status</td>";
	        $text=array();
	        if ($forms->error_text['crm_status']) $text[]="<b>" . $forms->error_text['crm_status'] . "</b>";
	        $text[]=$forms->checkbox("crmlist_status",1,( ( sizeof($this->input->crm_status) == sizeof($database->crmlog->constant->crm_status) ) ? 1 : 0),array("onclick"=>"crmlog_checked(this);")) . " All";
	        foreach ($database->crmlog->constant->crm_status as $status) {
	            $text[]=$forms->checkbox(fn_base64(array("crm_status",$status)),1,in_array($status, $this->input->crm_status),array("class"=>"crmlist_status")) . " {$status}";
	        }
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
	    $results[]="<tr>";
	    $results[]="<td>Contact Consent?</td>";
	    $results[]="<td>" . $forms->select("consent",array("Yes","No"),$this->input->consent) . "</td>";
	    $results[]="</tr>";
        if ($database->user->access("Super User",FALSE)) {
	        $results[]="<tr>";
	        $results[]="<td>Request Dump?</td>";
	        $results[]="<td>" . $forms->checkbox("request",1,$this->input->request) . "</td>";
	        $results[]="</tr>";
        }
	    $results[]="<tr>";
	    $results[]="<td>Output</td>";
	    $results[]="<td>" . $forms->select("file_type",array(".xlsx"=>"Excel (xlsx)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)"),$this->input->file_type) . "</td>";
	    $results[]="</tr>";
	    $results[]="</table>";
	    $results[]="<p class=left>" . $forms->button("Go!",array("onclick"=>"fn_action('list',0,0);")) . "</p>";
	    return $results;
    }
    function summary() {
    global $database, $forms, $menu;
	    $menu->tabs->landing_tab();
	    $query=$this->query();
	    $query[]=$this->input->page_break->limit();
	    $database->crmlog->query($query,TRUE);
	    if (!$database->crmlog->meta->rows) return "<p>No records found</p>";
	    $results=array();
	    $results[]="<table class='small border'>";
	    $results[]="<tr>";
	    $results[]="<th width='15%'>Date/Time</th>";
	    $results[]="<th width='20%'>Comapny</th>";
	    $results[]="<th width='20%'>Name</th>";
	    $results[]="<th width='15%'>SKU</th>";
	    $results[]="<th width='10%'>Status</th>";
        if (!$this->options->crm) {
	        $results[]="<th width='20%'>Reserved</th>";
		  } else {
	        $results[]="<th width='10%'>CRM Server</th>";
	        $results[]="<th width='10%'>CRM ID</th>";
		}
	    $results[]="</tr>";
		$item=0;
	    while ($database->crmlog->fetch = $database->crmlog->fetch_array()) {
	        $database->crmlog->fetch();
	        $results[]="<tr>";
	        $results[]="<td>" . fn_href(date("m/d/Y H:i",$database->crmlog->data->timestamp),"javascript:fn_action('item'," . $this->input->page . ",{$item});") . "</td>";
	        $results[]="<td>" . $database->crmlog->data->company_name . "</td>";
	        $results[]="<td>" . $database->crmlog->data->name . "</td>";
	        $results[]="<td>" . $database->crmlog->data->sku . "</td>";
	        $results[]="<td>" . $database->crmlog->data->status . "</td>";
	        switch (TRUE) {
              case (!$this->options->crm):
	            $results[]="<td>&nbsp;</td>";
	            break;
	          case ($database->crmlog->data->error):
	            $results[]="<td colspan=2>" . $database->crmlog->data->error . "</td>";
	            break;
	          case ($database->crmlog->data->crm_error):
	            $results[]="<td colspan=2>" . $database->crmlog->data->crm_error . "</td>";
	            break;
	          default:
	            $results[]="<td>" . $database->crmlog->data->crm_server . "</td>";
	            $results[]="<td>" . $database->crmlog->data->crm_transaction_id . "</td>";
	        }
	        $results[]="</tr>";
	        $item++;
	    }
	    $results[]="</table>";
	    $results[]=$this->input->page_break->output($database->crmlog->meta->found_rows,array("url"=>"fn_action('page',%page%,0);"));
	    return $results;
    }
    function detail() {
    global $database, $forms, $menu;
	    $query=$this->query();
	    $query[]=$this->input->page_break->limit();
	    $database->crmlog->query($query,TRUE);
	    $database->crmlog->fetch(TRUE);
	    if (!$database->crmlog->meta->rows) return;
	    $menu->tabs->landing_tab();
	    $results=array();
	    $results[]="<table class='standard noborder'>";
	    $results[]="<tr>";
	    $results[]="<td width='15%'>Date</td>";
	    $results[]="<td width='85%'>" . date("m/d/Y h:i A",$database->crmlog->data->timestamp) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr><td>Transaction ID</td><td>" . $database->crmlog->data->transaction_id . "</td></tr>";
	    $results[]="<tr><td>Source</td><td>" . $database->crmlog->constant->source[$database->crmlog->data->source] . "</td></tr>";
	    $results[]="<tr><td>Status</td><td>" . $database->crmlog->data->status . " " . $database->crmlog->data->error . "</td></tr>";
	    if ($database->crmlog->data->sku) $results[]="<tr><td>SKU</td><td>" . $database->crmlog->data->sku . "</td></tr>";
	    if (sizeof($database->crmlog->data->cad_code)) {
	        $text=array();
	        $query_where=array();
	        $query_where[]=new query_where("code","in",$database->crmlog->data->cad_code);
	        $query=array("select * from cad");
	        $query[]=$database->where($query_where);
	        $query[]="order by name";
	        $database->cad->query($query);
	        while ($database->cad->fetch = $database->cad->fetch_array() ) {
	            $database->cad->fetch();
	            $text[]=$database->cad->data->name . " (" . $database->cad->data->code . ")";
	        }
	        $results[]="<tr><td>CAD Code(s)</td><td>" . implode("<br>",$text) . "</td></tr>";
	    }
        if (strlen($database->crmlog->data->link)) $results[]="<tr><td>Link</td><td>" . fn_href($database->crmlog->data->link,$database->crmlog->data->link,array(),array("target"=>"_blank","title"=>"Download file")) .  "</td></tr>";
	    $text=array();
	    if ($database->crmlog->data->user_id) $text[]="<tr><td>User ID</td><td>" . $database->crmlog->data->user_id . "</td></tr>";
	    if (strlen($database->crmlog->data->email)) $text[]="<tr><td>Email</td><td>" . $database->crmlog->data->email . "</td></tr>";
	    if (strlen($database->crmlog->data->company_name)) $text[]="<tr><td>Company Name</td><td>" . $database->crmlog->data->company_name . "</td></tr>";
	    if (strlen($database->crmlog->data->name)) $text[]="<tr><td>Name</td><td>" . $database->crmlog->data->name . "</td></tr>";
	    if (strlen($database->crmlog->data->address)) $text[]="<tr><td>Address</td><td>" . $database->crmlog->data->address . "</td></tr>";
	    if (strlen($database->crmlog->data->city)) $text[]="<tr><td>City</td><td>" . $database->crmlog->data->city . "</td></tr>";
	    if (strlen($database->crmlog->data->state)) $text[]="<tr><td>State</td><td>" . $database->crmlog->data->state . "</td></tr>";
	    if (strlen($database->crmlog->data->zip)) $text[]="<tr><td>Zip</td><td>" . $database->crmlog->data->zip . "</td></tr>";
	    if (strlen($database->crmlog->data->country_code)) $text[]="<tr><td>Country</td><td>" . $database->crmlog->data->country_code . "</td></tr>";
	    if (strlen($database->crmlog->data->phone)) $text[]="<tr><td>Phone</td><td>" . $database->crmlog->data->phone . "</td></tr>";
	    if (strlen($database->crmlog->data->fax)) $text[]="<tr><td>FAX</td><td>" . $database->crmlog->data->fax . "</td></tr>";
        $text[]="<tr><td>Contact Consent?</td><td>" . (($database->crmlog->data->consent) ? "Yes" : "No") . "</td></tr>";
	    if (sizeof($text)) {
	        $results[]="<tr><td colspan=2><hr></td></tr>";
	        $results[]=implode("\n",$text);
	        $text[]="<tr><td>{$key}</td><td>{$value}</td><tr>";
	    }
		if ($this->options->crm) {
	        $text=array();
			$text[]="<tr><td>CRM Status</td><td>" . $database->crmlog->data->crm_status . "</td></tr>";
	        if (strlen($database->crmlog->data->crm_error)) $text[]="<tr><td>Error</td><td>" . $database->crmlog->data->crm_error . "</td></tr>";
	        if (strlen($database->crmlog->data->crm_service)) $text[]="<tr><td>CRM Service</td><td>" . $database->crmlog->data->crm_service . "</td></tr>";
	        if (strlen($database->crmlog->data->crm_server)) $text[]="<tr><td>Server</td><td>" . $database->crmlog->data->crm_server . "</td></tr>";
	        if (strlen($database->crmlog->data->crm_table)) {
	            $text2=array($database->crmlog->data->crm_table);
	            $text2[]="ID:";
	            $text2[]=( (strlen($database->crmlog->data->crm_contact_id)) ? $database->crmlog->data->crm_contact_id : "Missing" );
	            $text[]="<tr><td>Table</td><td>" . implode(" ",$text2) . "</td></tr>";
	        }
	        if (strlen($database->crmlog->data->crm_transaction_id)) $text[]="<tr><td>Transaction ID</td><td>" . $database->crmlog->data->crm_transaction_id . "</td></tr>";
	        if (sizeof($text)) {
	            $results[]="<tr><td colspan=2><hr></td></tr>";
	            $results[]=implode("\n",$text);
	        }
		}
	    $results[]="<tr><td colspan=2><hr></td></tr>";
	    $results[]="<tr><td colspan=2>" . implode("",$this->content($database->crmlog->data->request)) . "</td></tr>";


        $text=array();
        foreach ($database->crmlog->data->free_field as $key => $value) {
            $text[]="<tr><td>{$key}</td><td>{$value}</td></tr>";
        }

        if (sizeof($text)) {
            $results[]="<tr><td colspan=2><hr></td></tr>";
            $results[]=implode("",$text);
        }
	    $results[]="</table>";
		if ($this->input->action != "dashboard") $results[]=$this->input->page_break->output($database->crmlog->meta->found_rows,array("url"=>"fn_action('item',%page%,%item%);","return_url"=>"fn_action('page',%page%,0);"));
	    return $results;
    }
	function content($data,$text=array()) {
	    if (is_array($data)) $text[]="<ul>";
	    foreach ($data as $key => $value) {
            switch (gettype($value)) {
              case "string":
	            $text[]="<li>$key: $value </li>";
                break;
              default:
	            $text[]="<li>$key<ul>";
	            $text=$this->content($value,$text);
	            $text[]="</ul></li>";
                break;
	        }
	    }
	    if (is_array($data)) $text[]="</ul>";
	    return $text;
	}
    function query() {
    global $database, $forms, $menu;
	    $query_where=array();
        if (strlen($this->input->transaction_id)) {
			$query_where[]=new query_where("transaction_id","=",$this->input->transaction_id);
          } else {
	        if ($this->input->from_date) $query_where[]=new query_where("timestamp",">=",strtotime($this->input->from_date));
	        if ($this->input->thru_date) $query_where[]=new query_where("timestamp","<",strtotime($this->input->thru_date . " +1 day"));
	        if ($this->input->source) $query_where[]=new query_where("crmlog.source","=",$this->input->source);
            if (strlen($this->input->email)) $query_where[]=new query_where("crmlog.email","=",$this->input->email);
            if (strlen($this->input->sku)) $query_where[]=new query_where("crmlog.sku","=",$this->input->sku);
            if (strlen($this->input->consent)) $query_where[]=new query_where("crmlog.consent","=",($this->input->consent == "Yes") ? 1 : 0);
	        $query_where[]=new query_where("crmlog.status","in",$this->input->status);
            if ($this->options->crm) {
            	$query_where[]=new query_where("crmlog.crm_status","in",$this->input->crm_status);
	        	if ($this->input->crm_server) $query_where[]=new query_where("crmlog.crm_server","=",$this->input->crm_server);
			}
		}
        $query_having=array();
        if ($this->input->json_field) $query_having[]=new query_where("json", "=", $this->input->json);
	    $query=array("select");
        if ($this->input->json_field) $query[]="JSON_UNQUOTE(JSON_EXTRACT(request, '$.{$this->input->json_field}')) as json,";
        $query[]="crmlog.* from crmlog";
	    $query[]=$database->where($query_where);
        if (sizeof($query_having)) $query[]=$database->where($query_having, TRUE);
	    $query[]="order by timestamp";
	    return $query;
    }
    function export() {
    global $database, $forms, $menu;
		$options=array();
        $options['file_type']=$this->input->file_type;
        $options['report']=TRUE;
        $options['crm']=$this->options->crm;
        $options['request']=$this->input->request;
        $options['query']=$this->query();
	    $this->map=new map_class("crmlog",$options);
	    $this->map->export($this->query());
    }
    function js() {
    	$results=<<<EOT

<script>
function fn_action(action,page,item) {
	jQuery("#action").val(action);
	jQuery("#page").val(page);
	jQuery("#item").val(item);
	jQuery("#scs_form").submit();
}
function crmlog_checked(obj) {
	jQuery("input." + obj.id).prop("checked",obj.checked);
}
</script>

EOT;
		return $results;
	}
}
?>