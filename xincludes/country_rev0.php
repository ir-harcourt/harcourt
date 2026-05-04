<?php
require_once "classes/country.php";
$forms->title("Country Codes (Revised 09/22/2016)");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) $database->country->read($forms->report['record_id']);
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action']=="cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action']=="delete"):
	$query="delete from country where country=" . fn_escape($database->country->data->country) . " and state=" . fn_escape($database->country->data->state);
    $database->country->query($query); 
	$forms->message[]=$database->country->data->name . " Deleted";
    break;
  case ($forms->report['action']=="maintain"):
	if (!$forms->report['record_id']) {
		$database->country->data->active=1;
    }
    break;
  case ($forms->report['action']=="update"):
	if (!$forms->report['record_id']) {
    	$database->country->data->country=fn_text($_POST['country'],TRUE);
        if (strlen($database->country->data->country) != 2) $forms->error('country');
	}
    $database->country->data->name=fn_text($_POST['name']);
	if (!strlen($database->country->data->name)) $forms->error('name');
    $database->country->data->ps_id=fn_number($_POST['ps_id'],0);
    if (!$database->country->data->ps_id) $forms->error('ps_id');
    $database->country->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $fields=array();
    $fields[]="country=" . fn_escape($database->country->data->country);
    $fields[]="state=" . fn_escape($database->country->data->state);
    $fields[]="name=" . fn_escape($database->country->data->name);
    $fields[]="ps_id=" . fn_escape($database->country->data->ps_id,FALSE);
    $fields[]="active=" . fn_escape($database->country->data->active,FALSE);
    $query=array();
    if (!$forms->report['record_id']) {
		$query[]="insert into country set";
        $query[]=implode(",\n",$fields);
	  } else {
		$query[]="update country set";
        $query[]=implode(",\n",$fields) . "\n";
        $query[]="where country=" . fn_escape($database->country->data->country) . " and state=" . fn_escape($database->country->data->state);
    }
	$database->country->query($query);
    if ($database->country->meta->error) {
		$forms->error('null','',$database->country->meta->error);
	  } else {
      	$forms->message[]="Record Updated";
    }
	break;
}
$menu->head();
$forms->message();
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
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");

switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
	print "<td width='30%'>Code (ISO Alpha-2)</td>\n";
	print "<td width='70%'>" . 	((!$forms->report['record_id']) ? $forms->text("country",$database->country->data->country,2,2) : $database->country->data->country) . "</td> \n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Name</td>\n";
    print "<td>" . $forms->text("name",$database->country->data->name,40,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>ISO Numeric Code</td>\n";
    print "<td>" . $forms->text("ps_id",$database->country->data->ps_id,3,3) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $forms->checkbox("active",1,$database->country->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
    print "<p>" . implode(" ",$buttons) . "</p>\n";
	break;
  default:
	$query=array();
	$query[]="select * from country where state='' order by name";
	$database->country->query($query);
    print "<table class='standard border tablesorter'>\n";
    print "<thead>\n";
    print "<tr>";
	print "<th width='15%'>Country Code</th>\n";
	print "<th width='80%'>Name</th>\n";
    print "<th width='5%'>Delete?</th>\n";
    print "</tr>\n";
    print "</thead>\n";
    print "<tr><td colspan=3>" . fn_href("Add new", "javascript:fn_action(this,'maintain','','');")  . "</td></tr>\n";
    print "<tbody>\n";
    while ($database->country->fetch = $database->country->fetch_array() ) {
		$database->country->fetch();
        print "<tr>";
        print "<td>" . fn_href($database->country->data->country,"javascript:fn_action(this,'maintain'," . fn_escape($database->country->data->country) . ",'');") . "</td>";
        print "<td>" . $database->country->data->name . "</td>";
	    if ($database->country->data->active) {
			$text="&nbsp;";
	      } else {
			$text=$forms->radio("delete",$database->country->data->country,"",array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->country->data->country) . "," . fn_escape($database->country->data->name) . ");"));
	    }
	    print "<td class=center>" . $text . "</td>\n";
        print "</tr>\n";
    }
    print "</tbody>\n";
    print "</table>\n";
}
$menu->copyright();
?>