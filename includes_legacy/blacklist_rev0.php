<?php
$forms->title("Black List/White List");
$forms->message[]="Revised 06/05/2014";
$forms->report['action']=$_POST['action'];
$forms->report['report_type']=$_POST['report_type'];
$forms->report['record_id']=$_POST['record_id'];
$forms->report['page']=intval($_POST['page']);
if ($forms->report['record_id']) {
	$database->blacklist->read($forms->report['record_id']);
  } else {
	$database->blacklist->data=new blacklist_data_class();
}
switch ($forms->report['action']) {
  case "":
	break;
  case "maintain":
	if (!$forms->report['record_id']) {
		$database->blacklist->data->type=$_POST['report_type'];
    }
	break;
  case "cancel":
	$forms->message[]="Request cancelled";
    break;
  case "delete":
	$database->blacklist->delete($database->blacklist->data->id);
	$forms->message[]="Deleted: " . long2ip($database->blacklist->data->ip_address);
    break;
  case "update":
	$database->blacklist->data->ip_address=ip2long(fn_text($_POST['ip_address']));
    if (!$database->blacklist->data->ip_address) $forms->error('ip_address');
	$database->blacklist->data->type=$_POST['type'];
    if (!$database->blacklist->data->type) $forms->error('type');
	$database->blacklist->data->comment=fn_text($_POST['comment']);
    if (sizeof($forms->error)) break;
	$database->blacklist->data->date_time=strtotime("now");
	$database->blacklist->data->user_id=$_SESSION['user']->id;
	$database->blacklist->update($database->blacklist->meta->rows);
	if ($database->blacklist->meta->error) {
		$forms->error('ip_address',$database->blacklist->meta->error);
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
	    if (!confirm("Delete IP: " + name)) {
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
  case ($forms->report['action']=="maintain"):
  case ( ($forms->report['action']=="update") && (sizeof($forms->error)) ):
	print $forms->hidden("report_type",$forms->report['report_type']);
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>IP Address</td>\n";
    if ($database->blacklist->data->ip_address) {
		$ip_address=long2ip($database->blacklist->data->ip_address);
	  } else {
      	$ip_address="";
    }
    print "<td width='70%'>" . $forms->text("ip_address",$ip_address,15,15) . "</td>\n";
    print "</tr>\n";
    if ($database->blacklist->data->ip_address) {
		print "<tr>\n";
        print "<td>DNS</td>\n";
        print "<td>" . gethostbyaddr($ip_address) . "</td>\n";
        print "</tr>\n";
    }
    print "<tr>\n";
    print "<td>Type</td>\n";
    print "<td>" . $forms->select("type",$database->blacklist->constant->type,$database->blacklist->data->type) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Comment</td>\n";
    print "<td>" . $forms->text("comment",$database->blacklist->data->comment,60,255) . "</td>\n";
    print "</tr>\n";
    if ($database->blacklist->data->date_time) {
		print "<tr>\n";
        print "<td>Created</td>\n";
        print "<td>" . date("m/d/Y h:i A",$database->blacklist->data->date_time) . "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
	print "<p>\n";
    print $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->blacklist->data->id) . ",'');"));
    print $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));    print "</p>\n";
	break;
  default:
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>Record type</td>\n";
    print "<td width='70%'>" . $forms->select("report_type",$database->blacklist->constant->type,$forms->report['report_type']) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print "<p class='standard'>" . $forms->button("Go!",array("onclick"=>"fn_action(this,'list','0','');")) . "</p>\n";
    print "<table class='standard border'>\n";
	print "<tr>\n";
    print "<th>IP</th>\n";
    print "<th>Type</th>\n";
    print "<th>Comment</th>\n";
    print "<th>Date</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=5>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") . "</td>\n";
    print "</tr>\n";
	switch (TRUE) {
	  case (sizeof($forms->error)):
      case ($forms->report['action'] != "list"):
		break;
	  default:
		$query_where=array();
        if ($forms->report['report_type']) $query_where[]=new query_where("type","=",$forms->report['report_type']);
        $query =
        	"select * from blacklist \n" .
            $database->where($query_where) .
            "order by type,ip_address " .
            $database->page_limit($forms->report['page']);
		$database->blacklist->query($query,TRUE);
		$page_break=new page_item_break($database->blacklist->meta->found_rows,"fn_page_item('list','%page%','');");
        while ($database->blacklist->fetch = $database->blacklist->fetch_array() ) {
			$database->blacklist->fetch();
            $ip_address=long2ip($database->blacklist->data->ip_address);
            print "<tr>\n";
            print "<td>" . fn_href($ip_address,"javascript:fn_action(this,'maintain'," . fn_escape($database->blacklist->data->id) . ",'');") . "</td>\n";
            print "<td>" . $database->blacklist->data->type . "</td>\n";
            print "<td>" . $database->blacklist->data->comment . "</td>\n";
            print "<td class=center>" . date("m/d/Y h:i A",$database->blacklist->data->date_time) . "</td>\n";
			print "<td class=center width='5%'>" . $forms->radio('delete',$database->blacklist->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->blacklist->data->id) . "," . fn_escape($ip_address) . ");")) . "</td>\n";
            print "</tr>\n";
        }
		$database->free_result();
    }
    print "</table>\n";
    if (isset($page_break)) print $page_break->page($forms->report['page']);
}
print $forms->close();
$menu->copyright();
?>