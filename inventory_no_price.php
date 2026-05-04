<?php
require_once "scs_header.php";
require_once "map_rev3.php";
$database->user->access("Administrator");
$forms->title("Inventory w/Missing Prices");
$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['subcategory_id']=$_POST['subcategory_id'];
$forms->report['file_type']=$_POST['file_type'];
$forms->report['no_price']=1;
$map=new map_class("inventory",$forms->report);

switch (TRUE) {
  case ($forms->report['action']=="cancel"):
	$forms->message[]="Request Cancelled";
	break;
  case (($forms->report['function']=="download") && ($forms->report['action']=="prelist")):
	$query=$database->inventory->export_query();
	$database->inventory->query($query);
	$forms->report['count']=$database->inventory->meta->rows;
    switch (TRUE) {
      case (!$forms->report['count']):
        $forms->error('type');
        $forms->message[]="No records found";
      	break;
    }
    break;
  case (($forms->report['function']=="download") && ($forms->report['action']=="update")):
    $map->export();
 	break;
}
$menu->head();
print $forms->message();
print $forms->open();
print $forms->hidden("action");
?>
<script>
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
  default:
	print $forms->hidden("function","download");
	print "<table class='standard noborder'>";
	print "<tr>";
	print "<tr>";
	print "<td width='30%'>Subcategory</td>";
	print "<td width='70%'>" . $database->subcategory->select($forms->report['subcategory_id']) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td>File type</td>";
	print "<td>" . $forms->select("file_type",$map->file_type_list,$forms->report['file_type'],FALSE) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td>&nbsp;</td>";
	print "<td>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');")) . "</td>";
	print "</tr>";
	print "</table>";
}
print $forms->close();
$menu->copyright();
?>