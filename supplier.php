<?php
require_once "scs_header.php";
require_once "classes/supplier.php";
$database->user->access("Administrator");
$forms->title("Supplier Codes");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->supplier->read($forms->report['record_id']);
  } else {
    $database->supplier->data=new supplier_data_class();
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
    $database->supplier->delete($forms->report['record_id']);
    $query=array("update suppliersku set supplier_id=0 where supplier_id=" . fn_escape($forms->report['record_id']) );
    $database->temp->query($query);
    $forms->message[]="Supplier " . $database->supplier->data->name . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	$database->supplier->data->name=fn_text($_POST['name']);
    if (!$database->supplier->data->name) $forms->error('name');
	$database->supplier->data->address=fn_text($_POST['address']);
	$database->supplier->data->contact_name=fn_text($_POST['contact_name']);
    $email=new email_class($_POST['contact_email']);
	$database->supplier->data->contact_email=$email->address;
    if ($email->error) $forms->error("contact_email",$email->error);
	$database->supplier->data->phone=fn_text($_POST['phone']);
	$database->supplier->data->fax=fn_text($_POST['fax']);
	$url=new verify_url_class($_POST['url']);
	$database->supplier->data->url=$url->url;
    if ($url->error) $forms->error("url",$url->error);
	$database->supplier->data->competitor=$_POST['competitor'];
	$database->supplier->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->supplier->update($database->supplier->meta->rows);

    if (!$database->supplier->meta->error) {
		$forms->message[]="Record maintained";
	  } else {
		$forms->error('name','',$database->supplier->meta->error);
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
    print "<td width='70%'>" . $forms->text("name",$database->supplier->data->name,60,60) .  "</td>";
    print "</tr>";
	print "<tr>";
	print "<td>Address</td>";
	print "<td>" . $forms->textarea("address",$database->supplier->data->address,3,60) .  "</td>";
	print "</tr>";
    print "<tr>";
    print "<td>Contact Name</td>";
    print "<td>" . $forms->text("contact_name",$database->supplier->data->contact_name,60,60) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Contact Email</td>";
    $text=array($forms->text("contact_email",$database->supplier->data->contact_email,60,60));
    if (array_key_exists("contact_email", $forms->error_text)) $text[]=$forms->error_text['contact_email'];
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Contact Phone</td>";
    print "<td>" . $forms->text("phone",$database->supplier->data->phone,60,60) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Contact Fax</td>";
    print "<td>" . $forms->text("fax",$database->supplier->data->fax,60,60) .  "</td>";
    print "</tr>";
	print "<tr>";
    $text=array();
    $text[]=$forms->textarea("url",$database->supplier->data->url,2,60);
    if (array_key_exists("url", $forms->error_text)) $text[]=$forms->error_text['url'];
	print "<td>Website URL</td>";
	print "<td>" . implode("<br>",$text) .  "</td>";
	print "<tr>";
	print "<td>Competitor?</td>";
	print "<td>" . $forms->checkbox("competitor",1,$database->supplier->data->competitor) . "</td>";
	print "</tr>";
	print "<tr>";
	print "<td>Active?</td>";
	print "<td>" . $forms->checkbox("active",1,$database->supplier->data->active) . "</td>";
	print "</tr>";
	print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->supplier->data->id) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode("\n",$buttons) . "</p>\n";
  	break;
  default:
	print "<table class='standard border'>";
    print "<caption>&nbsp;</caption>";
    print "<tr>";
    print "<th width='90%'>Name</th>";
    print "<th width='10%'>Del?</th>";
    print "</tr>";
    print "<tr>";
    print "<td colspan=3>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") ."</td>";
    print "</tr>";
    $query=array("select * from supplier");
    $query[]="order by active desc, name";
    $database->supplier->query($query);
    while ($database->supplier->fetch = $database->supplier->fetch_array()) {
	    $database->supplier->fetch();
        $bg=( ($database->supplier->data->active) ? "" : $forms->background->tomato);
	    print "<tr $bg>";
	    print "<td>" . fn_href($database->supplier->data->name,"javascript:fn_action(this,'maintain'," . fn_escape($database->supplier->data->id) . ",'');") . "</td>";
	    if ($database->supplier->data->active) {
			$text="";
		  } else {
	        $text=$forms->radio("delete",$database->supplier->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->supplier->data->id) . "," . fn_escape($database->supplier->data->name) . ");"));
	    }
	    print "<td class=center>$text</td>";
	    print "</tr>";
	}
	$database->supplier->free_result();
	print "</table>";
}
print $forms->close();
$menu->copyright();
?>