<?php
require_once "classes/country.php";
require_once "map_rev3.php";
class country_utility_class {
	function scs_table_version() {
		$results=array();
        $results['11/04/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role,$country=TRUE) {
    global $database, $forms, $menu;
	    $database->user->access($role);
	    $forms->title("Country Utility");
	    $forms->message[]="Revised " . key($this->scs_table_version());
	    $forms->report['action']=$_REQUEST['action'];
	    $forms->report['function']=$_REQUEST['function'];
	    $forms->report['file_type']=$_POST['file_type'];
	    $forms->report['import_file']=$_POST['import_file'];
	    $forms->report['country']="";
	    $function_list=array(
	        "explain"=>"Show Values",
	        "download"=>"WWW => Desktop",
	        "review"=>"Desktop => WWW (Review only)",
	        "upload"=>"Desktop => WWW (Update)"
	    );
	    $menu->map=new map_class("country",$forms->report);
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
    var download=false;
    var upload=false;
    var explain=false;
    switch (true) {
      case ($("#function").val()  == "download"):
      	download=true;
        break;
      case ($("#function").val()  == "review"):
      case ($("#function").val()  == "upload"):
      	upload=true;
        break;
      case ($("#function").val()  == "explain"):
      	explain=true;
        break;
    }
	if ( (download) ? $("#download_row").show() : $("#download_row").hide() );
	if ( (upload) ? $("#upload_row").show() : $("#upload_row").hide() );
	if ( (explain) ? $("#explain_table").show() : $("#explain_table").hide() );
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
	        print "<td width='70%'>" . $forms->select("function",$function_list,$forms->report['function'],TRUE,array("onchange"=>"utility_screen();")) . "</td>";
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
	        print "<th>ISO Code</th>";
	        print "<th>Name</th>";
	        print "<th>Alias(s)</th>";
	        foreach ($database->country->constant->free_field as $code => $obj) {
	            print "<th>" . $obj->name . "</th>";
	        }
	        print "</tr>";
	        print "</thead>";
	        print "<tbody>";
	        $database->country->query($database->country->export_query());
	        while ($database->country->fetch = $database->country->fetch_array()) {
	            $database->country->fetch();
	            print "<tr " . ( (!$database->country->data->active) ? $forms->background->yellow : "") . ">";
	            print "<td class=center>" . $database->country->data->country . "</td>";
	            print "<td>" . $database->country->data->name . "</td>";
	            print "<td>" . implode("<br>",$database->country->data->alias) . "</td>";
	        	foreach ($database->country->constant->free_field as $code => $obj) {
	                print "<td>" . $database->country->data->free_field[$code] . "</td>";
	            }
	        }
	        print "</tbody>";
	        print "</table>";

	    }
	    print $forms->close();
	    $menu->copyright();
	}
}
?>