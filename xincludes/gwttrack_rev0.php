<?php
require_once "classes/gwtscs.php";
require_once "classes/gwttrack.php";
$forms->title("Google Webmaster Tools (GWT) Tracking");

$gwt=new gwtscs_class();

$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
$forms->report['page']=$_POST['page'];
$forms->report['report_type']=$_POST['report_type'];
$forms->report['report_active']=$_POST['report_active'];
if ($forms->report['record_id']) {
	$database->gwttrack->read($forms->report['record_id']);
  } else {
	$database->gwttrack->data=new gwttrack_data_class();
	$database->gwttrack->data->domain=$database->registry->gwt->domain;
	$database->gwttrack->data->type=$forms->report['report_type'];
}
switch ($forms->report['action']) {
  case "":
	$forms->report['report_active']=TRUE;
	break;
  case "list":
	if (!$forms->report['report_type']) $forms->error('report_type');
    break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "maintain":
	break;
  case "delete":
    $query_where=array();
    $query_where[]=new query_where("domain","=",$database->gwttrack->data->domain);
    $query_where[]=new query_where("type","=",$database->gwttrack->data->type);
    $query_where[]=new query_where("code","=",$database->gwttrack->data->code);
    $query=
    	"delete from gwtlog \n" .
        $database->where($query_where);
	$database->temp->query($query);
	$database->gwttrack->delete($forms->report['record_id']);
    $forms->message[]=$database->gwttrack->data->type . ": " . $database->gwttrack->data->code . " deleted";
    break;
  case "update":
    if ($forms->report['report_type']=="TOP_QUERIES") {
		$database->gwttrack->data->code=strtolower(trim($_POST['code']));
	  } else {
		$database->gwttrack->data->code=trim($_POST['code']);
	}
    switch (TRUE) {
	  case (!$database->gwttrack->data->code):
		$forms->error("code");
	}
	$database->gwttrack->data->comment=trim($_POST['comment']);
	$database->gwttrack->data->active=$_POST['active'];

	if (sizeof($forms->error)) break;
	$database->gwttrack->update($database->gwttrack->meta->rows);
	if ($database->gwttrack->meta->error) {
    	$forms->error('code',"",$database->gwttrack->meta->error);
	  } else {
		$forms->message[]="Record maintained";
	}
	break;
}

$menu->head();
print $forms->message();
?>
<script language=javascript>
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete " + name + "?")) {
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
	print $forms->hidden("report_type",$forms->report['report_type']);
	print $forms->hidden("report_active",$forms->report['report_active']);
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>Domain</td>\n";
    print "<td width='70%'><b>" . $database->gwttrack->data->domain . "</b></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>" . $gwt->type[ $database->gwttrack->data->type ] . "</td>\n";
    print "<td>" . $forms->text("code",$database->gwttrack->data->code,100,512) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Comment</td>\n";
    print "<td>" . $forms->textarea("comment",$database->gwttrack->data->comment,2,80) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Initial Date</td>\n";
    print "<td><b>" . fn_date($database->gwttrack->data->initial_date,"ymd") . "</b></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $forms->checkbox("active",1,$database->gwttrack->data->active) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	$buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel',0,'');"));
    print "<p class=center>" . implode(" ",$buttons) . "</p>\n";
	break;
  default:
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>Domain</td>\n";
    print "<td width='70%'><b>" . $database->registry->gwt->domain . "</b></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Type</td>\n";
    print "<td>" . $forms->select("report_type",$gwt->type,$forms->report['report_type'],TRUE) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Only Active?</td>\n";
    print "<td>" . $forms->checkbox("report_active",1,$forms->report['report_active']) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print "<p>" . $forms->button("List",array("onclick"=>"fn_action(this,'list','','');")) . "</p>\n";
    if ( (sizeof($forms->error)) || (!$forms->report['report_type']) ) break;
    print "<table class='standard border'>\n";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>" . $gwt->type[ $forms->report['report_type'] ] . "</th>\n";
    print "<th>Initial Date</th>\n";
    print "<th>Comment</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=4>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td>\n";
    print "</tr>\n";
    print "</thead>\n";
    print "<tbody>\n";
    $query_where=array();
    $query_where[]=new query_where("gwttrack.domain","=",$database->registry->gwt->domain);
    $query_where[]=new query_where("gwttrack.type","=",$forms->report['report_type']);
    if ($forms->report['report_active'])  $query_where[]=new query_where("gwttrack.active","=",1);
    $query=
    	"select \n" .
        "gwttrack.* from gwttrack \n" .
        $database->where($query_where) .
        "order by gwttrack.code";
	$database->gwttrack->query($query);
	while ($database->gwttrack->fetch = $database->gwttrack->fetch_array() ) {
		$database->gwttrack->fetch();
        if ($database->gwttrack->data->active) {
			$bg="";
		  } else {
			$bg=$forms->background->{$database->registry->gwt->color['inactive']};
        }
		print "<tr $bg>\n";
        print "<td>" . fn_href($database->gwttrack->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->gwttrack->data->id) . ",'');") . "</td>\n";
        print "<td class=center>" . fn_date($database->gwttrack->data->initial_date,"ymd") . "</td>\n";
        print "<td>" . str_replace("\n","<br>",$database->gwttrack->data->comment) . "</td>\n";
        if ($database->gwttrack->data->active) {
        	$text="";
		  } else {
          	$text=$forms->radio("delete",1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->gwttrack->data->id) . "," . fn_escape($database->gwttrack->data->code) . ");"));
        }
        print "<td class=center>$text</td>\n";
        print "</tr>\n";
    }
    print "</tbody>\n";
    print "</table>\n";
}

print $forms->close();
$menu->copyright();
?>