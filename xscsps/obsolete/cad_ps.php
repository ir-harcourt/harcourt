<?php
require_once "classes/cad.php";
/*  Retrieve in XML format
http://www.partcommunity.com/cadqualifier.asp?firm=colsongroup
*/
$forms->title("Partsolutions CAD Codes");
$forms->report['action']=$_POST['action'];
$forms->report['page']=$_POST['page'];
$forms->report['item']=$_POST['item'];
if ($_POST['record_id']) {
	$database->cad->read($_POST['record_id']);
  } else {
	$database->cad->data=new cad_data_class();
}

switch ($forms->report['action']) {
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
    $database->cad->delete($_POST['record_id']);
    $forms->message[]="CAD code " . $database->cad->data->code . " deleted";
    break;
  case "update":
    $database->cad->data->code=trim($_POST['code']);
    if (!$database->cad->data->code) $forms->error('code');
    $database->cad->data->name=trim($_POST['name']);
    if (!$database->cad->data->name) $forms->error('name');
    $database->cad->data->type=$_POST['type'];
    $database->cad->data->instructions=trim($_POST['instructions']);
    $database->cad->data->revised_date=fn_date($_POST['revised_date'],"mdy");
    if (!$date_ok) $forms->error('revised_date');
    $database->cad->data->active=fn_number($_POST['active']);
	if (sizeof($forms->error)) break;
    $database->cad->update($database->cad->meta->rows);
    if ($database->cad->meta->errno) {
    	$forms->error("code","",$database->cad->meta->error);
	  } else {
		$forms->message[]="Record maintained";
	}
	break;
}
$menu->head();
$forms->message();
?>
<script type="text/javascript">
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
print $forms->hidden("page");
print $forms->hidden("item");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
    print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>Part-Solutions Code</td>\n";
    print "<td width='70%'>" . $forms->text("code",$database->cad->data->code,40,40) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Name</td>\n";
    print "<td>" . $forms->text("name",$database->cad->data->name,40,40) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Type</td>\n";
	print "<td>" . $forms->select("type",$database->cad->constant->type,$database->cad->data->type,FALSE) . "</td>\n";
	print "</tr>\n";
    print "<tr>\n";
    print "<td>Instructions</td>\n";
    print "<td>" . $forms->textarea("instructions",$database->cad->data->instructions,6,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Revised date</td>\n";
    print "<td>" . $forms->text("revised_date",$database->cad->data->revised_date,10,10,"date") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $forms->checkbox("active",1,$database->cad->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p class='standard center'>\n";
    print $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->cad->data->id) . ",'');"));
	print " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
    print "</p>\n";
	break;
  default:
	print "<table class='small border'>\n";
	print "<tr>\n";
	print "<th>Part-Solutions Code</th>\n";
	print "<th>Name</th>\n";
	print "<th>2D/3D</th>\n";
	print "<th>Revised</th>\n";
	print "<th>Del?</th>\n";
	print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=5>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td>\n";
    print "</tr>\n";
	$query =
    	"select * from cad order by name" .
        $database->page_limit($forms->report['page']);
    $database->cad->query($query,TRUE);
	while ($database->cad->fetch = $database->cad->fetch_array()) {
        $database->cad->fetch();
        print "<tr>\n";
        print "<td>" . fn_href($database->cad->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->cad->data->id) . ",'');") . "</td>\n";
        print "<td>" . $database->cad->data->name . "</td>\n";
        print "<td class=center>" . $database->cad->data->type . "</td>\n";
        print "<td class=center>" . fn_date($database->cad->data->revised_date,"ymd") . "</td>\n";
        print "<td class=center>";
        if ($database->cad->data->active) {
            print "&nbsp;";
          } else {
            print $forms->radio("delete",$database->cad->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->cad->data->id) . "," . fn_escape($database->cad->data->code) . ");"));
        }
        print "</td>\n";
        print "</tr>\n";
    }
    $database->cad->free_result();
    print "</table>\n";
	$page_break=new page_item_break($database->cad->meta->found_rows,"fn_page_item('page',%page%,0);");
	$page_break->page($forms->report['page']);
}
print $forms->close();
$menu->copyright();
?>