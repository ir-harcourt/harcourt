<?php
require_once "scs_header.php";
require_once "classes/crmlog.php";
require_once "map_rev3.php";
class crmlog_import_class {
	var $options=array();
	function scs_table_version() {
		$results=array();
        $results['01/29/2021']="Add options";
        $results['11/11/2020']="Add crmblacklist.track";
        $results['11/05/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role, $options=array()) {
    global $database, $forms, $menu;
		$database->user->access($role);
        $this->options=$options;
        if (!isset($this->options['title'])) $this->options['title']="CRM Import";
        $menu->stopwatch=new stopwatch_class($this->options['title']);
	    $forms->title($this->options['title']);
	    $forms->message[]="Revised " . key($this->scs_table_version());
	    $forms->report['action']=$_REQUEST['action'];
	    $forms->report['function']="upload";
	    $forms->report['zipfile']=".csv";
	    $menu->map=new map_class("crmlog",$forms->report);
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        break;
	      case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
	      case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	        if (!$menu->map->upload("upload")) $forms->error('upload');
	        if ($menu->map->message) $forms->message[]=$menu->map->message;
	        break;
	       case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
	       case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
           	$options=array();
			$options['log_type']="CRM Update:Batch";
	        $forms->message[]=$menu->map->import($options);
	        if ($forms->report['action']=="update") $forms->message[]=$database->crmlog->import_crm();
	        break;
	    }
	    $menu->head();
	    $forms->message();
	    ?>
<script>
function fn_action(action) {
	if ( (action=='update') && (!confirm("Continue?")) ) return;
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
	    <?php
	    print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action");
	    $menu->tabs=new jquery_tab_class();
	    switch (TRUE) {
	      case (($forms->report['action']=="prelist") && ($forms->report['function']=="review") && (!sizeof($forms->error))):
	      case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	        $menu->tabs->item("Review",$menu->map->upload_html(TRUE));
	        break;
	        break;
	      case (($forms->report['action']=="update") && ($forms->report['function']=="review")):
	      case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	        $menu->tabs->item("Update",$menu->map->import_html(TRUE));
	        break;
	      default:
            $menu->tabs->item("Input",$this->input());
	    }
        $menu->tabs->item("Stopwatch",$menu->stopwatch->results());
        $menu->tabs->output();
        $forms->close();
	    $menu->copyright();
	}
    function input() {
    global $database, $forms, $menu;
    	$results=array();
	    $results[]="<table class='standard noborder'>";
	    $results[]="<tr>";
	    $results[]="<td width='30%'>Salesforce Server:</td>";
	    $results[]="<td width='70%'><b>". $database->registry->salesforce->server . "</b></td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td width='30%'>Import File</td>";
	    $results[]="<td width='70%'>". $forms->file('upload',40) . "</td>";
	    $results[]="</tr>";
	    $results[]="</table>";
	    $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</p>";
        return $results;
    }
}
?>