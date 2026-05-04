<?php
$forms->title("Ship Via Codes");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->shipvia->read($forms->report['record_id']);
  } else {
	$database->shipvia->data=new shipvia_data_class();
}

switch (TRUE) {
  case ($forms->report['action'] == ""):
	$database->shipvia->hotfix();
  	break;
  case ($forms->report['action'] == "cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] == "maintain"):
    break;
  case ($forms->report['action'] == "delete"):
    $database->shipvia->delete($_POST['record_id']);
    $forms->message[]="Shipvia " . $database->shipvia->data->name . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	$database->shipvia->data->name=trim($_POST['name']);
    if (!strlen($database->shipvia->data->name)) $forms->error("name","Cannot be blank");
	$database->shipvia->data->ranking=fn_number($_POST['ranking'],0);
	$database->shipvia->data->account=$_POST['account'];
	$database->shipvia->data->active=$_POST['active'];
    if (sizeof($forms->error)) break;
    $database->shipvia->update($database->shipvia->meta->rows);
    switch (TRUE) {
      case (!$database->shipvia->meta->errno):
      	$forms->message[]="Record maintained";
        break;
      default:
      	$forms->error("name","Duplicate name");
	}
	break;
}
$menu->head();
$forms->message();
?>
<script>
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
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");

switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
    print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='30%'>Name</td>";
    $text=array($forms->text("name",$database->shipvia->data->name,60,60));
    if ($forms->error_text['name']) $text[]=$forms->error_text['name'];
    print "<td width='70%'>" . implode("<br>",$text)  . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Freight Account?</td>";
    print "<td>" . $forms->checkbox("account",1,$database->shipvia->data->account) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Ranking</td>";
    print "<td>" . $forms->text("ranking",$database->shipvia->data->ranking,3,3,"decimal:0") . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Active?</td>";
    print "<td>" . $forms->checkbox("active",1,$database->shipvia->data->active) . "</td>";
    print "</tr>";
    print "</table>";
    $text=array();
	$text[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
    $text[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p>" . implode(" ",$text) . "</p>";
    break;
  default:
	print "<table class='standard border tablesorter'>";
    print "<thead>";
	print "<tr>";
	print "<th width='70%'>Name</th>";
	print "<th width='10%'>Freight Account?</th>";
	print "<th width='10%'>Ranking</th>";
	print "<th width='10%'>Del?</th>";
    print "</tr>";
    print "</thead>";
	print "<tr><td colspan=4>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td></tr>";
    print "<tbody>";
	$database->shipvia->query("select * from shipvia order by name");
    while ($database->shipvia->fetch = $database->shipvia->fetch_array() ) {
    	$database->shipvia->fetch();
        print "<tr " . ( ($database->shipvia->data->active) ? "" : $forms->background->yellow) . ">";
        print "<td>" . fn_href($database->shipvia->data->name,"javascript:fn_action(this,'maintain'," . fn_escape($database->shipvia->data->id) . ",'');") . "</td>";
        print "<td class=center>" . ( ($database->shipvia->data->account) ? "Yes" : "No") . "</td>";
        print "<td class=right>" . $database->shipvia->data->ranking . "</td>";
        if ($database->shipvia->data->active) {
            $text="&nbsp;";
          } else {
            $text=$forms->radio("delete",$database->shipvia->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->shipvia->data->id) . "," . fn_escape($database->shipvia->data->name) . ");"));
        }
        print "<td class=center>" . $text . "</td>";
        print "</tr>";
    }
    print "</tbody>";
    print "</table>";
}
print $forms->close();
$menu->copyright();
?>