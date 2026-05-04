<?php
require_once "scs_header.php";
require_once "classes/carrier.php";
$database->user->access("Administrator");
$forms->title("Carrier Codes");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->carrier->read($forms->report['record_id']);
  } else {
    $database->carrier->data=new carrier_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
    break;
  case ($forms->report['action'] == "delete"):
    $database->carrier->delete($forms->report['record_id']);
    $forms->message[]="Carrier " . $database->carrier->data->name . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	$database->carrier->data->name=trim($_POST['name']);
    if (!$database->carrier->data->name) $forms->error('name');
	$database->carrier->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->carrier->update($database->carrier->meta->rows);

    if (!$database->carrier->meta->error) {
		$forms->message[]="Record maintained";
	  } else {
		$forms->error('name','',$database->user->meta->error);
    }
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: #" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.record_id.value=id;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");

switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print "<table class='large noborder'>";
    print "<tr>";
    print "<td width='30%'>Name</td>";
    print "<td width='70%'>" . $forms->text("name",$database->carrier->data->name,45,45) .  "</td>";
    print "</tr>";
	print "<tr>";
	print "<td>Active?</td>";
	print "<td>" . $forms->checkbox("active",1,$database->carrier->data->active) . "</td>";
	print "</tr>";
	print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->carrier->data->id) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode("\n",$buttons) . "</p>\n";
  	break;
  default:
	print "<table class='standard border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th width='90%'>Name</th>\n";
    print "<th width='10%'>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=3>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") ."</td>\n";
    print "</tr>\n";
    $query =
        "select * from carrier \n" .
        "order by name";
    $database->carrier->query($query);
    while ($database->carrier->fetch = $database->carrier->fetch_array()) {
	    $database->carrier->fetch();
	    print "<tr>\n";
	    print "<td>" . fn_href($database->carrier->data->name,"javascript:fn_action(this,'maintain'," . fn_escape($database->carrier->data->id) . ",'');") . "</td>\n";
	    if ($database->carrier->data->active) {
			$text="";
		  } else {
	        $text=$forms->radio("delete",$database->carrier->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->carrier->data->id) . "," . fn_escape($database->carrier->data->name) . ");"));
	    }
	    print "<td class=center>$text</td>\n";
	    print "</tr>\n";
	}
	$database->carrier->free_result();
	print "</table>\n";
}
print $forms->close();
$menu->copyright();
?>