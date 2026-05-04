<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/inventory.php";
$database->user->access("Administrator");
$forms->title("ERP Price Import");

$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['erp']=1;

$map=new map_class("inventory",$forms->report);
$map->function_list("download");
$map->function_list("template");

switch (TRUE) {
// Cancel
  case ($forms->report['action']=="cancel"):
	$forms->message[]="Request Cancelled";
	break;

// Upload/Preview functions begin
  case (($forms->report['function']=="preview") && ($forms->report['action']=="prelist")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	if (!$map->upload("upload")) $forms->error('upload');
    if ($map->message) $forms->message[]=$map->message;
    break;
   case (($forms->report['function']=="preview") && ($forms->report['action']=="update")):
   case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	set_time_limit(60);
	$forms->message[]=$map->import();
	break;
// Upload/Preview functions end
}
$menu->head();
print $forms->message();
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
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
switch (TRUE) {
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="download") && (!sizeof($forms->error))):
	$map->export_html($forms->report['title'], $forms->report['count']);
	break;
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="preview") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	$map->upload_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="preview")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	$map->import_html();
	break;
  default:
	$default_page=TRUE;
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width=30%>Function</td>\n";
	print "<td width=70%>" . $forms->select("function",$map->function_list,$forms->report['function'],TRUE) . "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
	print "<tr id='upload_row'>\n";
	print "<td>Upload File</td>\n";
	print "<td>" . $forms->file("upload") . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
    print "<td>&nbsp;</td>\n";
	print "<td>" . $forms->submit("Go!",array("onclick"=>"fn_action('prelist');")) . "</td>\n";
    print "</tr>\n";
	print "</table>\n";
}
if (!$default_page) {
	print $forms->hidden("function",$forms->report['function']);
}
print $forms->close();
$menu->copyright();
?>