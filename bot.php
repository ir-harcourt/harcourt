<?php
require_once "scs_header.php";
require_once "classes/portal.php";
$database->user->access("Administrator");
$forms->title("BOT Manager");

$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];
if ($forms->report['record_id']) {
	$database->bot->read($forms->report['record_id']);
  } else {
	$database->bot->data=new bot_data_class();
}
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
    $database->bot->delete($forms->report['record_id']);
    $forms->message[]="BOT " . $database->bot->data->code . " deleted";
    break;
  case "maintain":
    break;
  case "update":
	$database->bot->data->code=trim(strtolower($_POST['code']));
	$code_parse=parse_url($database->bot->data->code);
    $code=preg_split("/\./",$database->bot->data->code);
    switch (TRUE) {
      case (!$database->bot->data->code):
      case ($database->bot->data->code != $code_parse['path']):
      case (sizeof($code) < 2):
      case ($code[0]==""):
		$forms->error('code');
		break;
	}
	$database->bot->data->pattern="/" . preg_quote("." . $database->bot->data->code,"/") . "$/i";
	$database->bot->data->name=$_POST['name'];
	if (!$database->bot->data->name) $forms->error('name');
	$database->bot->data->comment=$_POST['comment'];
	$database->bot->data->status=$_POST['status'];
    if (!$database->bot->data->status) $forms->error('status');
	$database->bot->data->catalog=$_POST['catalog'];
	$database->bot->data->portal_id=$_POST['portal_id'];
	if (sizeof($forms->error)) break;
	if ($database->bot->data->status != "Active") {
	    $database->bot->data->catalog=0;
	    $database->bot->data->portal_id=0;
    }
    $database->bot->update($database->bot->meta->rows);
    if ($database->bot->meta->error) {
		$forms->error('name','',$database->bot->meta->error);
	  } else {
    	$forms->message[]="Record maintained";
	}
	break;
}
$menu->head();
print $forms->message();
?>
<script type='text/javascript'>
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm('Delete: #' + id + ' ' + name)) {
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
    print "<td width='30%'>DNS Name</td>\n";
    print "<td width='70%'>" . $forms->text("code",$database->bot->data->code,60,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<tr>\n";
    print "<td>Search Engine Name</td>\n";
    print "<td>" . $forms->text("name",$database->bot->data->name,60,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Comment</td>\n";
    print "<td>" . $forms->textarea("comment",$database->bot->data->comment,3,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Status</td>\n";
    print "<td>" . $forms->select("status",$database->bot->constant->status,$database->bot->data->status) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Catalog?</td>\n";
    print "<td>" . $forms->checkbox("catalog",1,$database->bot->data->catalog) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Portal</td>\n";
    print "<td>" . $database->portal->select($database->bot->data->portal_id) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update',"  . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode(" ",$buttons) . "</p>\n";
  	break;
  default:
	print "<table class='standard border tablesorter'>\n";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>DNS</th>\n";
    print "<th>Search Engine</th>\n";
    print "<th>Portal</th>\n";
    print "<th>Status</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "</thead>\n";
    print "<tbody>\n";
    print "<tr>\n";
    print "<td colspan=5>" . fn_href("Add New","javascript:fn_action(this,'maintain','0','');") . "</td></tr>\n";
	$query =
		"select \n" .
        "portal.name as 'portal_name',\n" .
        "bot.* from bot \n" .
        "left join portal on portal.id=bot.portal_id \n" .
	    "order by bot.name,bot.code";
	    $database->bot->query($query);
    while ($database->bot->fetch = $database->bot->fetch_array() ) {
        $database->bot->fetch();
        print "<tr>\n";
        print "<td>" . fn_href($database->bot->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->bot->data->id) . ",'');") . "</td>\n";
        print "<td>" . $database->bot->data->name . "</td>\n";
        print "<td>" . $database->bot->fetch['portal_name'] . "</td>\n";
        print "<td>" . $database->bot->data->status . "</td>\n";
        if ($database->bot->data->status == "Active") {
            $text="&nbsp;";
          } else {
            $text=$forms->radio("delete",$database->bot->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->bot->data->id) . "," . fn_escape($database->bot->data->code) . ");"));
        }
        print "<td class=center>" . $text . "</td>\n";
        print "</td>\n";
        print "</tr>\n";
    }
    print "</tbody>\n";
    print "</table>\n";
	$database->bot->free_result();
}
print $forms->close();
$menu->copyright();
?>