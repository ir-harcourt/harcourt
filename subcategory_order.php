<?php
require_once "scs_header.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");
$forms->title("Subcategory Order");
$forms->report['action']=$_POST['action'];
$forms->report['report_category_id']=$_POST['report_category_id'];
$database->category->read($forms->report['report_category_id']);
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case (!$database->category->meta->rows):
	$forms->error('report_category_id');
	break;
  case ($forms->report['action'] == "cancel"):
	$forms->message[]="Request cancelled";
	break;
  case ($forms->report['action'] == "maintain"):
	$query=array();
    $query[]="select * from subcategory";
    $query[]="where category_id=" . fn_escape($forms->report['report_category_id']);
    $query[]="order by sort_order,name";
    $database->subcategory->query($query);
    if (!$database->subcategory->meta->rows) {
		$forms->error('report_category_id',"","No subcategories found");
    	break;
    }
    $forms->constant->subcategory=array();
    while ($database->subcategory->fetch = $database->subcategory->fetch_array() ) {
		$database->subcategory->fetch();
        $forms->constant->subcategory[$database->subcategory->data->id]=$database->subcategory->data->name;
    }
	break;
  case ($forms->report['action']=="update"):
	$count=0;
    foreach ($_POST as $key => $id) {
    	if (!preg_match("/subcategory\_/",$key)) continue;
        $count++;
		$database->temp->query("update subcategory set sort_order=" . fn_escape($count * 5) . " where id=" . fn_escape($id));
    }
    $forms->message[]=number_format($count) . " records updated in category " . $database->category->data->name;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(action) {
	if (action=='cancel') {
    	if (!confirm('Cancel update?')) return;
	}
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");
switch (TRUE) {
  case ( ($forms->report['action'] == "maintain") && (!sizeof($forms->error)) ):
	print $forms->hidden("report_category_id",$forms->report['report_category_id']);
	print "<p>Category: <b>" . $database->category->data->name . "</b><br>Click and drag to desired order</p>\n";
    $data=array();
    foreach ($forms->constant->subcategory as $id => $name) {
    	$data[]=
            "<li class='ui-state-default'>" . $forms->hidden("subcategory_{$id}",$id) . $name . "</li>";
    }
	print "<ol class='sortable large'>\n" . implode("\n",$data) . "\n</ol>\n";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel');"));
    print "<p>" . implode(" ",$buttons) . "</p>";
  	break;
  default:
  	$buttons=array();
    $buttons[]=$database->category->select($forms->report['report_category_id'],array("field"=>"report_category_id"),array("onchange"=>"fn_action('maintain');"));
    $buttons[]=$forms->button("Go",array("onclick"=>"fn_action('maintain');"));
	print "<p>Category: " . implode(" ",$buttons) . "</p>";
}
print $forms->close();
$menu->copyright();
?>