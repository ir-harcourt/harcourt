<?php
require_once "scs_header.php";
require_once "map_rev3.php";
$database->user->access("Administrator");
$forms->title("Competitor SKU Utility");

$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['custom_format']=$_POST['custom_format'];
$forms->report['update_timestamp']=$_POST['update_timestamp'];
$forms->report['file_type']=$_POST['file_type'];

$map=new map_class("inventory_xref",$forms->report);
unset($map->function_list['template']);

switch (TRUE) {
// Initial
  case (!$forms->report['action']):
	$forms->report['function']="upload";
//	$forms->report['custom_format']=TRUE;
	break;

// Cancel
  case ($forms->report['action']=="cancel"):
	$forms->message[]="Request Cancelled";
	break;

// Download functions begin
  case (($forms->report['function']=="download") && ($forms->report['action']=="prelist")):
    switch (TRUE) {
      case (!$forms->report['type']):
        break;
      case (!$forms->report['count']):
        $forms->error('type');
        $forms->message[]="No records found";
      	break;
    }
    break;
  case (($forms->report['function']=="download") && ($forms->report['action']=="update")):
	$query_where=array();
    $query=array("select * from inventory_xref");
    $query[]=trim($database->where($query_where));
    $query[]="order by sku,competitor";
    $map->export($query);
 	break;
// Download functions end

// Upload/Preview functions begin
  case (($forms->report['function']=="preview") && ($forms->report['action']=="prelist")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	switch (TRUE) {
	  case ($forms->report['custom_format']):
		if (!$map->upload_open()) {
			$forms->error('upload');
        	break;
		}
	    $map->fhw=fopen($map->internal_file,"w");
	    while ($line = fgetcsv($map->fhr,0,$map->delimiter,"\"")) {
	        if (!$map->headers) {
	            fputcsv($map->fhw,$map->upload_line(array('Harcourt SKU','competitor name','competitor sku')),"\t","\"");
	            $fields=array();
	            foreach ($line as $key => $value) {
	                $value=str_replace("\n"," ",trim($value));
	                if (($value) && (!in_array($value,$fields))) $fields[$key]=$value;
	            }
	          } else {
	            foreach ($line as $key => $value) {
	                $value=trim($value);
	                if ($value=="-") $value="";
	                switch (TRUE) {
	                  case (!$key):
	                    $sku=$value;
	                  case (!$value):
	                  case ($key < 2): // sku & Price
	                  case (!array_key_exists($key,$fields)):
	                  case (!$sku):
	                    break;
	                  default:;
	                    fputcsv($map->fhw,$map->upload_line(array($sku,$fields[$key],$value)),"\t","\"");
	                }
	            }
	        }
	    }
	    fclose($map->fhw);
	    $map->upload_close();
        break;
	  default:
		if (!$map->upload("upload")) $forms->error('upload');
	}
    if ($map->message) $forms->message[]=$map->message;
    break;
   case (($forms->report['function']=="preview") && ($forms->report['action']=="update")):
   case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	$forms->message[]=$map->import();
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function review_overwrite() {
     if (confirm('Replace all records with import file?')) {
     	fn_action('update');
     }
}
function fn_action(action) {
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
switch (TRUE) {
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="download") && (!sizeof($forms->error))):
	$map->export_html($forms->report['title'], $forms->report['count']);
	break;
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="preview") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="convert") && (!sizeof($forms->error))):
	$map->upload_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="preview")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="convert")):
	$map->import_html();
	break;
  default:
	$default_page=TRUE;
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width=30%>Function</td>\n";
	print "<td width=70%>" . $forms->select("function",$map->function_list,$forms->report['function'],FALSE,array("onchange"=>"import_screen();")) . "</td>\n";
    print "</tr>\n";
	print "<tr id='row_file_type'>\n";
	print "<td>File type (export only)</td>\n";
	print "<td>" . $forms->select("file_type",$map->file_type_list,$forms->report['file_type'],FALSE) . "</td>\n";
	print "</tr>\n";
	print "<tr id='row_xr'>\n";
	print "<td>XR File?</td>\n";
	print "<td>" . $forms->checkbox("custom_format",1,$forms->report['custom_format']) . "</td>\n";
	print "</tr>\n";
	print "<tr id='row_update_timestamp'>\n";
	print "<td>Remove existing records?</td>\n";
	print "<td>" . $forms->checkbox("update_timestamp",1,$forms->report['update_timestamp']) . "</td>\n";
	print "</tr>\n";
	print "<tr id='row_update'>\n";
	print "<td>Upload File</td>\n";
	print "<td>" . $forms->file("upload") . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
    print "<td>&nbsp;</td>\n";
	print "<td>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</td>\n";
    print "</tr>\n";
	print "</table>\n";
?>
<script type='text/javascript'>
function import_screen() {
	var row_file_type=0;
	var row_xr=0;
	var row_update_timestamp=0;
	var row_upload=0;
	switch (document.getElementById('function').value ) {
	  case 'download':
		row_file_type=1;
		break;
      default:
		row_xr=1;
		row_update_timestamp=1;
		row_upload=1;
	}
    div_show('row_file_type',row_file_type,'');
    div_show('row_xr',row_xr,'');
    div_show('row_update_timestamp',row_update_timestamp,'');
    div_show('row_upload',row_upload,'');
}
import_screen();
</script>
<?php
}
if (!$default_page) {
	print $forms->hidden("function",$forms->report['function']);
	print $forms->hidden("file_type",$forms->report['file_type']);
}
print $forms->close();
$menu->copyright();
?>