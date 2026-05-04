<?php
require_once "map_rev3.php";
class salesforce_picklist_class {
	function scs_table_version() {
		$results=array();
        $results['01/28/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role,$options=array()) {
    global $database, $forms, $menu;
		$database->user->access($role);
	    $forms->title("Salesforce Picklist");
	    $forms->message[]="Revised " . key($this->scs_table_version());
	    $forms->report['action']=$_POST['action'];
	    $forms->report['function']=$_POST['function'];
	    $forms->report['file_type']=$_POST['file_type'];
	    $forms->report['import_file']=$_POST['import_file'];
	    $forms->report['record_type']=$_POST['record_type'];
	    $forms->report['explain']=TRUE;
		$menu->map=new map_class("sfpicklist",$forms->report);
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        break;
	      case ($forms->report['function'] == "download"):
	        if ( (!$forms->report['record_type']) && ($forms->report['file_type'] != ".xlsx") ) {
            	$forms->error('record_type');
                break;
          	}
            $options=array();
            if (!$forms->report['record_type']) {
	            $options['multiple_codes']=$database->sfpicklist->constant->record_type;
	            $options['xls_multiple']=TRUE;
	            $query=array("select record_type as 'code',count(*) as 'count' from sfpicklist");
	            $query[]="group by record_type";
	            $query[]="order by record_type";
	          } else {
	            $query="";
	        }
	        $menu->map->export($query,$options);
	        break;
	      case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
	      case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
         	if (!$forms->report['record_type']) {
            	$forms->error('record_type');
			  } else {
    			if (!$menu->map->upload("upload",array("xls_worksheet_name"=>$forms->report['record_type']))) $forms->error('upload');
			}
	        break;
	       case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
	      case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	        $forms->message[]=$menu->map->import();
	        break;
	    }

	    $menu->head();
	    $forms->message();
?>
<script>
jQuery(document).ready(function() {
    utility_screen();
});
function fn_action(action) {
	if (action=='update') {
	    if (!confirm("Continue?")) return;
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function utility_screen() {
    var file_type_show=0;
    var upload_show=0;
    var record_type_show=0;
    if (document.getElementById('function').value == 'download') file_type_show=1;
    switch (document.getElementById('function').value) {
      case 'review':
      case 'upload':
		upload_show=1;
	}
    div_show('file_type_row',file_type_show,'');
    div_show('upload_row',upload_show,'');
}
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
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>Action</td>";
	        print "<td width='70%'>" . $forms->select("function",$menu->map->function_list,$forms->report['function'],TRUE,array("onchange"=>"utility_screen();")) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Record Type</td>";
	        print "<td>". $forms->select("record_type",$database->sfpicklist->constant->record_type,$forms->report['record_type']) . "</td>";
	        print "</tr>";
	        print "<tr id='upload_row'>";
	        print "<td>Import File</td>";
	        print "<td>". $forms->file('upload',40) . "</td>";
	        print "</tr>";
	        print "<tr id='file_type_row'>";
	        print "<td>Export file type</td>";
	        print "<td>" . $forms->select("file_type",$menu->map->file_type_list,$forms->report['file_type'],FALSE,array("onchange"=>"utility_screen();")) . "</td>";
	        print "</tr>";
	        print "</table>";
	        print "<p>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</p>";
        }
        if ( ($forms->report['function'] == "explain") && ($forms->report['record_type']) ) print $this->explain();
	    print $forms->close();
	    $menu->copyright();
    }
    function explain() {
    global $database, $forms, $menu;
    	$query=array("select * from sfpicklist");
        $query[]="where record_type =" . fn_escape($forms->report['record_type']);
        $query[]="order by name";
        $database->sfpicklist->query($query);
        if (!$database->sfpicklist->meta->rows) return "<p class=center>No records found</p>";
        $results=array();
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th rowspan=2>Name</th>";
        $results[]="<th colspan=" . sizeof($database->sfpicklist->constant->map) . ">Code(s)</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        foreach ($database->sfpicklist->constant->map as $field) {
        	$results[]="<th>{$field}</th>";
        }
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        while ($database->sfpicklist->fetch = $database->sfpicklist->fetch_array() ) {
			$database->sfpicklist->fetch();
            $results[]="<tr>";
            $results[]="<td>" . $database->sfpicklist->data->name . "</td>";
            foreach ($database->sfpicklist->constant->map as $field) {
            	$results[]="<td>" . $database->sfpicklist->data->code[$field] . "</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
        return implode("",$results);
    }
}

/*









    switch (TRUE) {
      case (sizeof($forms->error)):
		break;
      case ($forms->report['function'] == "explain"):
		print "<table class='standard border tablesorter'>";
        print "<thead>";
        print "<tr>";
        print "<th width='30%'>Part Number</th>";
        print "<th width='70%'>Legacy Part Number</th>";
        print "</tr>";
        print "</thead>";
        print "<tbody>";
		$query=array("select * from legacy");
        $query[]="order by sku";
		$database->legacy->query($query);
	    while ($database->legacy->fetch = $database->legacy->fetch_array()) {
            $database->legacy->fetch();
			print "<tr>";
            print "<td>" . $database->legacy->data->sku . "</td>";
            print "<td>" . $database->legacy->data->legacy_sku . "</td>";
            print "</tr>";
		}
		$database->legacy->free_result();
        print "</tbody>";
        print "</table>";
	}
}


*/
?>