<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");
$forms->title("Inventory Import/Export");

$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['subcategory_id']=$_POST['subcategory_id'];
$forms->report['file_type']=$_POST['file_type'];
if ($forms->report['subcategory_id']) {
	$database->subcategory->read($forms->report['subcategory_id']);
	$database->category->read($database->subcategory->data->category_id);
    $forms->report['title']=$database->category->data->name . ": " . $database->subcategory->data->name;
  } else {
    $forms->report['title']="All records";
}

$map=new map_class("inventory",$forms->report,$forms->report['subcategory_id']);

switch (TRUE) {
// Cancel
  case ($forms->report['action']=="cancel"):
	$forms->message[]="Request Cancelled";
	break;
// Template begins
  case ($forms->report['function']=="template"):
	$map->template();
	break;
// Template ends

// Download functions begin
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
// Download functions end

// Upload/review functions begin
  case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	if (!$map->upload("upload")) $forms->error('upload');
    if ($map->message) $forms->message[]=$map->message;
    break;
   case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
   case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
	$forms->message[]=$map->import();
	break;
// Upload/review functions end
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
jQuery(document).ready(function() {
    import_screen();
});
function import_screen() {
	file_type_row=0;
    upload_row=0;
	switch ( jQuery("#function").val() ) {
      case "":
      	break;
	  case "download":
		file_type_row=1;
		break;
      default:
		upload_row=1;
	}
    if (file_type_row) {
		jQuery("#file_type_row").show();
	  } else {
		jQuery("#file_type_row").hide();
	}
    if (upload_row) {
		jQuery("#upload_row").show();
	  } else {
		jQuery("#upload_row").hide();
	}
}
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
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="review") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	$map->upload_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="review")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	$map->import_html();
	break;
  default:
	print "<table class='standard noborder'>";
	print "<tr>";
	print "<td width=30%>Function</td>";
	print "<td width=70%>" . $forms->select("function",$map->function_list,$forms->report['function'],TRUE,array("onchange"=>"import_screen();")) . "</td>";
    print "</tr>";
	print "<tr>";
	print "<td>Subcategory</td>";
	print "<td>" . $database->subcategory->select($forms->report['subcategory_id']) . "</td>";
	print "</tr>";
	print "<tr id='file_type_row'>";
	print "<td>File type</td>";
	print "<td>" . $forms->select("file_type",$map->file_type_list,$forms->report['file_type'],FALSE) . "</td>";
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
$fields=array();
$fields["function"]=$forms->report['function'];
$fields["file_type"]=$forms->report['file_type'];
$forms->hidden_fields($fields);
print $forms->close();
$menu->copyright();
?>