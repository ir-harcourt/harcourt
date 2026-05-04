<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/inventory.php";
require_once "classes/partcommunity.php";
$database->user->access("Administrator");
$forms->title("PARTcommunity Import");
set_time_limit(900);

$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];

$map=new map_class("partcommunity",$forms->report);
unset($map->function_list['download'],$map->function_list['template']);

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
	$forms->message[]=$map->import();
	break;
// Upload/Preview functions end

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
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="preview") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	$map->upload_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="preview")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	$map->import_html();
	break;
  default:
	print "<table class='standard noborder'>";
	print "<tr>";
	print "<td width=30%>Function</td>";
	print "<td width=70%>" . $forms->select("function",$map->function_list,$forms->report['function'],TRUE) . "</td>";
    print "</tr>";
	print "<tr id='upload_row'>";
	print "<td>Upload File</td>";
	print "<td>" . $forms->file("upload") . "</td>";
	print "</tr>";
	print "<tr>";
    print "<td>&nbsp;</td>";
	print "<td>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</td>";
    print "</tr>";
	print "</table>";
}
$forms->hidden_fields(array("function"=>$forms->report['function']));
print $forms->close();
$menu->copyright();
?>