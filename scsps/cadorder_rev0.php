<?php
require_once "classes/cad.php";
require_once "classes/cadorder.php";
$forms->title("CAD Order Maintain");
$forms->report['action']=$_POST['action'];
$forms->report['page']=$_POST['page'];
$forms->report['item']=$_POST['item'];
$forms->report['report_status']=$_POST['report_status'];
$forms->report['report_date']=fn_date($_POST['report_date'],"mdy");
if (!$date_ok) $forms->error('report_date');
$forms->report['ajax_user_id']=$_POST['ajax_user_id'];
$forms->report['report_firm']=$_POST['report_firm'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->cadorder->read($forms->report['record_id']);
  } else {
	$database->cadorder->data=new cadorder_data_class();
}
switch ($forms->report['action']) {
  case "":
    if (!$database->user->access("Super User",FALSE)) $forms->report['report_firm']=$_SESSION['company']->firm;
	break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "maintain":
	if (!$forms->report['record_id']) {
		$database->cadorder->data->user_id=$forms->report['ajax_user_id'];
    }
	break;
  case "delete":
	$database->cadorder->delete($forms->report['record_id']);
    $forms->message[]="CAD Order #" . $forms->report['record_id'] . " deleted";
    break;
  case "update":
	$database->cadorder->data->firm=$_POST['firm'];
    if (!$database->cadorder->data->firm) $forms->error('firm');
	$database->cadorder->data->user_id=fn_number($_POST['user_id']);
    $database->user->read($database->cadorder->data->user_id);
    if (!$database->user->meta->rows) $forms->error('user_id');
	$database->cadorder->data->sku=trim(strtoupper($_POST['sku']));
	$database->cadorder->data->sku_name=trim(strtoupper($_POST['sku_name']));
	$database->cadorder->data->cad_code=$_POST['cad_code'];
    if (!$database->cadorder->data->cad_code) $forms->error('cad_code');
	$database->cadorder->data->response_url=trim($_POST['response_url']);
	$database->cadorder->data->ps_message=explode("\n",trim($_POST['ps_message']));
	$database->cadorder->data->status=$_POST['status'];
    if (!$database->cadorder->data->status) $forms->error('status');
	$database->cadorder->data->status_code=strtoupper($_POST['status_code']);
    if (!$database->cadorder->data->status_code) $forms->error('status_code');
	$database->cadorder->data->initial_date=fn_date($_POST['initial_date'],"timestamp_unix");
    if ( (!$database->cadorder->data->initial_date) || ($database->cadorder->data->initial_date > strtotime("now")) ) $forms->error('initial_date');
	$database->cadorder->data->status_date=fn_date($_POST['status_date'],"timestamp_unix");
    if ( ($database->cadorder->data->status_date < $database->cadorder->data->initial_date) || ($database->cadorder->data->initial_date > strtotime("now")) ) $forms->error('status_date');
	if (sizeof($forms->error)) break;
    $database->cadorder->update($database->cadorder->meta->rows);
    if ($database->cadorder->meta->errno) {
    	$forms->error("null","",$database->cadorder->meta->error);
	  } else {
		$forms->message[]="Record maintained";
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
print $forms->hidden("page");
print $forms->hidden("item");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print $forms->hidden("report_firm",$forms->report['report_firm']);
    print $forms->hidden("report_status",$forms->report['report_status']);
	print $forms->hidden("ajax_user_id",$forms->report['ajax_user_id']);
	print $forms->hidden("report_date",$forms->report['report_date'],"date:ymd");
    print "<table class='standard noborder'>\n";
	if ($forms->report['record_id']) {
	    print "<tr>\n";
	    print "<td width='30%'>Order#</td>\n";
	    print "<td width='70%'><b>" . $database->cadorder->data->id . "</b></td>\n";
	    print "</tr>\n";
	}
    print "<tr>\n";
    print "<td>Firm</td>\n";
    print "<td>" . $database->company->select($database->cadorder->data->firm,array("field"=>"firm","firm"=>TRUE,"blank"=>FALSE)) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td width='30%'>User ID</td>\n";
    print "<td width='70%'>" . $forms->text("user_id",$database->cadorder->data->user_id,8,8);
    if ($database->cadorder->data->user_id) {
		$database->user->read($database->cadorder->data->user_id);
        print " <b>" . $database->user->data->name . "/" . $database->user->data->company_name . "</b>";
    }
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>SKU</td>\n";
    print "<td>" . $forms->text("sku",$database->cadorder->data->sku,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>SKU Name</td>\n";
    print "<td>" . $forms->text("sku_name",$database->cadorder->data->sku_name,60) . "</td>\n";
    print "</tr>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>CAD Code</td>\n";
    print "<td>" . $database->cad->select($database->cadorder->data->cad_code) . "</td>\n";
    print "</tr>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Initial Date/Time</td>\n";
    print "<td>" . $forms->text("initial_date",$database->cadorder->data->initial_date,20,0,"date:unix_timestamp") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Status</td>\n";
    print "<td>" . $forms->select("status",$database->cadorder->constant->status,$database->cadorder->data->status,FALSE) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Last Status Code</td>\n";
    print "<td>" . $forms->text("status_code",$database->cadorder->data->status_code,4,4) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Status Date/Time</td>\n";
    print "<td>" . $forms->text("status_date",$database->cadorder->data->status_date,20,0,"date:unix_timestamp") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Response URL</td>\n";
    print "<td>" . $forms->textarea("response_url",$database->cadorder->data->response_url,3,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>CAD Order#</td>\n";
    print "<td>" . $forms->text("ps_order",$database->cadorder->data->ps_order,20,20) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Zip File</td>\n";
    print "<td>" . $forms->text("ps_zipfile",$database->cadorder->data->ps_zipfile,60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Message</td>\n";
    print "<td>" . $forms->textarea("ps_message",$database->cadorder->data->ps_message,3,60) . "</td>\n";
    print "</tr>\n";
    print "<td><td colspan=2>&nbsp;</td></tr>\n";
    print "<tr>\n";
    print "<td>Parameters</td>\n";
    print "<td>\n";
    print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<th width='20%'>Key</th>\n";
    print "<th width='80%'>Value</th>\n";
    print "</tr>\n";
    foreach ($database->cadorder->data->parameters as $key => $value) {
		print "<tr>";
		print "<td>$key</td>";
        print "<td>$value</td>";
        print "</tr>\n";
    }
    print "</table>\n";
    print "</td>\n";
	print "</tr>\n";
    if (sizeof($database->cadorder->data->ps_contents)) {
	    print "<td><td colspan=2>&nbsp;</td></tr>\n";
	    print "<tr>\n";
	    print "<td>File Contents</td>\n";
	    print "<td>\n";
	    print "<table class='standard border'>\n";
	    print "<tr>\n";
	    print "<th>File</th>\n";
	    print "<th>View</th>\n";
	    print "<th>Format</th>\n";
	    print "<th>Version</th>\n";
	    print "</tr>\n";
	    foreach ($database->cadorder->data->ps_contents as $contents) {
	        print "<tr>";
	        print "<td>" . $contents->file . "</td>";
	        print "<td>" . $contents->view . "</td>";
	        print "<td>" . $contents->cad_code . "</td>";
	        print "<td>" . $contents->cad_version . "</td>";
	        print "</tr>\n";
	    }
	    print "</table<\n";
	    print "</td>\n";
	    print "</tr>\n";
    }
    print "</table>\n";
	print
    	"<p class='standard'>" .
        $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->cadorder->data->id) . ",'');")) .
        " " . $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');")) .
		"</p>\n";
    break;
  default:
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<th>Firm</th>\n";
    print "<th>Status</th>\n";
    print "<th>From Date</th>\n";
    print "<th width='30%' class=left>User</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    if ($database->user->access("Super User",FALSE)) {
		$blank=TRUE;
	  } else {
		$blank=FALSE;
	}
    print "<td class=center>" . $database->company->select($forms->report['report_firm'],array("firm"=>TRUE,"field"=>"report_firm","blank"=>$blank)) . "</td>\n";
    print "<td class=center>" . $forms->select("report_status",$database->cadorder->constant->status,$forms->report['report_status']) . "</td>\n";
    print "<td class=center>" . $forms->text("report_date",$forms->report['report_date'],10,10,"date:ymd") . "</td>\n";
    print "<td>" . $forms->text("ajax_user_id",$forms->report['ajax_user_id'],10) . "<b><span id='ajax_user_name'></span></b></td>\n";
    print "<td class=center>" . $forms->button("Go!",array("onclick"=>"fn_action(this,'list','','');")) . "</td>\n";
    print "</table>\n";
	if ($forms->report['ajax_user_id']) {
		print "<script type='text/javascript'>\n";
        print "ajax_user_fill(" . fn_escape($forms->report['ajax_user_id']) . ");\n";
        print "</script>\n";
	}
    print "<table class='small border'>\n";
    print $forms->caption("<br>");
    $colspan=9;
    print "<thead>\n";
    print "<tr>\n";
    print "<th rowspan=2>Order#</th>\n";
    print "<th rowspan=2>Order Date/Time</th>\n";
    if (!$forms->report['report_firm']) {
    	$colspan++;
		print "<th rowspan=2>Firm</th>\n";
	}
    if (!$forms->report['user_id']) {
    	$colspan++;
		print "<th rowspan=2>User/Company Name</th>\n";
	}
    print "<th colspan=3>Status</th>\n";
    print "<th rowspan=2>Order#</th>\n";
    print "<th rowspan=2>SKU</th>\n";
    print "<th rowspan=2>CAD Format</th>\n";
    print "<th rowspan=2>Delete?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<th>Name</th>\n";
    print "<th>Date/Time</th>\n";
    print "<th>Code</th>\n";
    print "</tr>\n";
    print "</thead>\n";
    print "<tbody>\n";
    print "<tr>\n";
    print "<td colspan=$colspan>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td>\n";
    print "</tr>\n";
	if ( ($forms->report['action']=="list") && (!sizeof($forms->error)) ) {
		$query_where=array();
        if ($forms->report['report_firm']) $query_where[]=new query_where("cadorder.firm","=",$forms->report['report_firm']);
        if ($forms->report['report_status']) $query_where[]=new query_where("cadorder.status","=",$forms->report['report_status']);
        if ($forms->report['ajax_user_id']) $query_where[]=new query_where("cadorder.user_id","=",$forms->report['ajax_user_id']);
        if ($forms->report['report_date']) $query_where[]=new query_where("date_format(from_unixtime(cadorder.status_date),'%Y/%m/%d')",">=",$forms->report['report_date']);
        $query =
        	"select \n" .
            "user.name as 'user.name', \n" .
            "user.company_name as 'user.company_name', \n" .
            "cadorder.* from cadorder \n" .
            "left join user on user.id=cadorder.user_id \n" .
            $database->where($query_where) .
            "order by cadorder.id " .
            $database->page_limit($forms->report['page']);
		$database->cadorder->query($query,TRUE);
        while ($database->cadorder->fetch = $database->cadorder->fetch_array() ) {
			$database->cadorder->fetch();
            print "<tr>\n";
            print "<td class=center>" . fn_href($database->cadorder->data->id,"javascript:fn_action(this,'maintain'," . fn_escape($database->cadorder->data->id) . ",'');")  . "</td>\n";
            print "<td class=center>" . date("m/d/Y g:i A",$database->cadorder->data->initial_date) . "</td>\n";
			if (!$forms->report['report_firm']) print "<td>" . $database->cadorder->data->firm . "</td>\n";
            if (!$forms->report['user_id']) print "<td>" . $database->cadorder->fetch['user.name'] . "<br>" . $database->cadorder->fetch['user.company_name'] . "</td>\n";
            print "<td>" . $database->cadorder->data->status . "</td>\n";
            print "<td class=center>" . date(" m/d/Y g:i A",$database->cadorder->data->status_date) . "</td>\n";
            print "<td class=center>" . $database->cadorder->data->status_code . "</td>\n";
            print "<td>" . $database->cadorder->data->ps_order . "</td>\n";
            print "<td>" . $database->cadorder->data->sku . "</td>\n";
            print "<td>" . $database->cadorder->data->cad_code . "</td>\n";
            print "<td class=center>" . $forms->radio("delete",$database->cadorder->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->cadorder->data->id) . ",'');")) . "</td>\n";
            print "</tr>\n";
        }
		$database->cadorder->free_result();
    }
    print "</tbody>\n";
    print "</table>\n";
    if ($forms->report['action']=="list") {
	    $page_break=new page_item_break($database->cadorder->meta->found_rows);
	    print $page_break->page($forms->report['page']);
	}
}

print $forms->close();
$menu->copyright();
?>