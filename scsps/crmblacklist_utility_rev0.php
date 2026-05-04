<?php
require_once "classes/crmblacklist.php";
require_once "map_rev3.php";
class crmblacklist_utility_class {
	function scs_table_version() {
		$results=array();
        $results['11/11/2020']="Add track";
        $results['10/31/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role) {
    global $database, $forms, $menu;
	    $database->user->access($role);
	    $forms->title("CRM Blacklist Utility");
	    $forms->message[]="Revised " . key($this->scs_table_version());
	    $forms->report['action']=$_REQUEST['action'];
	    $forms->report['function']=$_REQUEST['function'];
	    $forms->report['file_type']=$_POST['file_type'];
	    $forms->report['import_file']=$_POST['import_file'];
		$forms->report['explain']=TRUE;
	    $data=array();
	    $menu->map=new map_class("crmblacklist",$forms->report);
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        break;
	      case ($forms->report['function'] == "download"):
	        $options=array();
	        $options['template']=$template;
	        $menu->map->export("",$options);
	        break;
	      case (($forms->report['function']=="pending") && ($forms->report['action']=="prelist")):
	      die('pending / prelist');
	        $menu->map->pending();
	        break;
	      case (($forms->report['function']=="pending") && ($forms->report['action']=="update")):
	      die('pending / update');
	        $menu->map->pending_update();
	        break;

	      case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
	      case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	        if (!$menu->map->upload()) $forms->error('upload');
	        if ($menu->map->message) $forms->message[]=$menu->map->message;
	        break;
	       case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
	      case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	        $forms->message[]=$menu->map->import();
	        break;
	    }
	    $menu->head();
	    $forms->message();
?>
<script type='text/javascript'>
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
function fn_record_type(record_type) {
	document.scs_form.action.value='prelist';
	document.scs_form.record_type.value=record_type;
	document.scs_form.submit();
}
function utility_screen() {
    var explain=false;
    var download=false;
    var upload=false;
    switch (true) {
      case (jQuery("#function").val()  == "download"):
      	download=true;
        break;
      case (jQuery("#function").val()  == "review"):
      case (jQuery("#function").val()  == "upload"):
      	upload=true;
        break;
      case (jQuery("#function").val()  == "explain"):
      	explain=true;
        break;
    }
	if ( (download) ? jQuery("#download_row").show() : jQuery("#download_row").hide() );
	if ( (upload) ? jQuery("#upload_row").show() : jQuery("#upload_row").hide() );
	if ( (explain) ? jQuery("#explain_table").show() : jQuery("#explain_table").hide() );
}
$(document).ready(function() {
	utility_screen();
});
</script>
<?php
	    print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action");
	    switch (TRUE) {
	      case (($forms->report['action']=="prelist") && ($forms->report['function']=="review") && (!sizeof($forms->error))):
	      case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	        $menu->map->upload_html();
	        break;
	      case (($forms->report['action']=="prelist") && ($forms->report['function']=="pending") && (!sizeof($forms->error))):
	        $menu->map->pending_html();
	        break;
	      case (($forms->report['action']=="update") && ($forms->report['function']=="review")):
	      case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	        $menu->map->import_html();
	        break;
	      default:
	        $default_page=TRUE;
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>Action</td>";
	        print "<td width='70%'>" . $forms->select("function",$menu->map->function_list,$forms->report['function'],TRUE,array("onchange"=>"utility_screen();")) . "</td>";
	        print "</tr>";
	        print "<tr id='upload_row'>";
	        print "<td>Import File</td>";
	        print "<td>". $forms->file('upload',40) . "</td>";
	        print "</tr>";
	        print "<tr id='download_row'>";
	        print "<td>Export file type</td>";
	        print "<td>" . $forms->select("file_type",$menu->map->file_type_list,$forms->report['file_type'],FALSE) . "</td>";
	        print "</tr>";
	        print "</table>";
	        print "<p>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</p>";


	        print "<table id='explain_table' class='standard border tablesorter'>";
	        print "<thead>";
	        print "<tr>";
	        print "<th width='30%'>Account</th>";
	        print "<th width='60%'>Domain</th>";
	        print "<th width='10%'>Track?</th>";
	        print "</tr>";
	        print "</thead>";
	        print "<tbody>";
	        $database->crmblacklist->query($database->crmblacklist->export_query());
	        while ($database->crmblacklist->fetch = $database->crmblacklist->fetch_array()) {
	            $database->crmblacklist->fetch();
	            print "<tr>";
	            print "<td>" . $database->crmblacklist->data->account . "</td>";
	            print "<td>" . $database->crmblacklist->data->domain . "</td>";
	            print "<td class=center>" . ( ($database->crmblacklist->data->track) ? "Yes" : "No") . "</td>";
	        }
	        print "</tbody>";
	        print "</table>";
	    }
	    print $forms->close();
	    $menu->copyright();
	}
}
?>