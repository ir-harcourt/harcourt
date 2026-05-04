<?php
require_once "scs_header.php";
require_once "classes/crmlog.php";
class crmlog_process_class {
	var $input;
    var $options=array();
    var $constant;
	function scs_table_version() {
		$results=array();
        $results['01/29/2021']="Add options";
        $results['11/22/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role, $options=array()) {
    global $database, $forms, $menu;
    	$this->options=$options;
        if (!isset($this->options['title'])) $this->options['title']="Reprocess CRM Transactions";

		$this->input=new stdClass();
        $this->constant=new stdCLass();
		$this->constant->status=array("import"=>"Imported","update"=>"Updated/Processed","error"=>"Import errors");
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) $this->json();
		$database->user->access($role);
        $menu->stopwatch=new stopwatch_class("Reprocess CRM Transactions");
        $menu->stopwatch->comment(__FILE__ .  " ( " . key($this->scs_table_version()) . " ) ");
	    $forms->title($this->options['title']);
	    $forms->message[]="Revised: " . key($this->scs_table_version());
	    $this->input->action=$_POST['action'];
	    switch (TRUE) {
	      case (!$this->input->action):
          	$this->input->date=date("Y/m/d");
	    	$this->input->status=array("import");
	        break;
	      case ($this->input->action == "update"):
          	$this->input->date=fn_date($_POST['date'],"mdy");
            if (!$forms->date_ok) $forms->error('date');
            foreach ($this->constant->status as $code => $name) {
            	if ($_POST["{$code}_process"]) $this->input->status[]=$code;
            }
	        if (!sizeof($this->input->status)) $forms->error("null","","Please select status");
	        if (sizeof($forms->error)) break;
            $version=$database->crmlog->scs_table_version();
            $menu->stopwatch->split("CRM Log",$version['file'] . " ( " . key($version) . " )");
            $menu->stopwatch->split_comment("Date: " . fn_date($this->input->date,"ymd"),"Status: " . implode(", ",$this->input->status));
	        $query_where=array();
	        $query_where[]=new query_where("from_unixtime(crmlog.timestamp,'%Y/%m/%d')","=",$this->input->date);
	        $query_where[]=new query_where("status","in",$this->input->status);
	        $query=array("update crmlog");
	        $query[]="set status='import',error='',crm_service='',crm_server='',crm_table='',crm_contact_id='',crm_transaction_id='',crm_error=''";
	        $query[]=$database->where($query_where);
	        $database->temp->query($query);
            $menu->stopwatch->split_comment(number_format($database->affected_rows()) . " records reverted to import");
	        $database->crmlog->import_crm();
	        $forms->message[]="Processing complete";
		}
	    $menu->head();
	    $forms->message();
        $this->script();
        print $forms->open();
	    print $forms->hidden("action");
	    $menu->tabs=new jquery_tab_class();
        $menu->tabs->item("Input",$this->input());
        $menu->tabs->item("Stopwatch",$menu->stopwatch->results());
        $menu->tabs->output();
        $forms->close();
	    $menu->copyright();
	}
    function input() {
    global $database, $menu, $forms;
	    $results=array();
	    $results[]="<tr>";
	    $results[]="<td width='30%'>Salesforce Server:</td>";
	    $results[]="<td width='70%'><b>". $database->registry->salesforce->server . "</b></td>";
	    $results[]="</tr>";
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>Date</td>";
        $results[]="<td width='70%'>" . $forms->text("date",$this->input->date,10,10,"date",array("onchange"=>"fn_error_count();")) . "</td>";
        $results[]="</tr>";
        foreach ($this->constant->status as $code => $name) {
        	$results[]="<tr>";
	        $results[]="<td width='30%'>" . $forms->checkbox("{$code}_process",1,in_array($code,$this->input->status)) . " {$name}</td>";
	        $results[]="<td width='70%'><b><span id={$code}_count></span></b></td>";
        	$results[]="</tr>";
        }
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("name"=>"button_id","onclick"=>"fn_action('update');")) . "</p>";
	    return $results;
    }
    function json() {
    global $database, $menu, $forms;
		$results=array();
        foreach ($this->constant->status as $code => $name) {
        	$results["{$code}_count"]="None";
        }
    	$this->input->date=fn_date($_REQUEST['date'],"mdy");
    	$query_where=array();
        $query_where[]=new query_where("from_unixtime(crmlog.timestamp,'%Y/%m/%d')","=",$this->input->date);
        $query_where[]=new query_where("status","in",array_keys($this->constant->status));
        $query=array("select");
        $query[]="status as 'status', count(*) as 'count'";
        $query[]="from crmlog";
        $query[]=$database->where($query_where);
        $query[]="group by status";
        $database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array()) {
        	$results[ strtolower($database->temp->fetch['status']) . "_count" ]=number_format($database->temp->fetch['count']);
        }
        die(json_encode($results));
    }
    function script() {
	    ?>
<script>
function fn_action(action) {
	if (action=='update') {
	    if (!confirm("Continue?")) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
jQuery(document).ready(function() {
	fn_error_count();
});
function fn_error_count() {
    jQuery.ajax({
        dataType: 'json',
        method: 'post',
        data: { action: "count", date: jQuery("#date").val() },
        success: function(data) {
			jQuery("#import_count").html(data['import_count']);
			jQuery("#update_count").html(data['update_count']);
			jQuery("#error_count").html(data['error_count']);
        }
    });
}
</script>
	    <?php
    }
}
?>