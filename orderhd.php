<?php
require_once "scs_header.php";
require_once "classes/scsmail_rev1.php";
$database->user->access("Executive");

$forms->title("PO Manager");
$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];
$forms->report['return_url']=$_REQUEST['return_url'];

$forms->report['page']=$_POST['page'];
$forms->report['report_status']=$_POST['report_status'];
$forms->report['report_order_date']=fn_date($_POST['report_order_date'],"mdy");

switch (TRUE) {
  case ($forms->report['record_id']):
	$database->orderhd->read($forms->report['record_id']);
    break;
  default:
	$database->orderhd->data=new orderhd_data_class();
}
switch ($forms->report['action']) {
  case "":
    break;
  case "cancel":
	if ($forms->report['return_url']) fn_url_redirect($forms->report['return_url']);
    $forms->message[]="Request cancelled";
    break;
  case "maintain":
    break;
  case "update":
	$database->orderhd->data->status=$_POST['status'];
	if ($database->orderhd->data->status == $database->orderhd->fetch['status']) $forms->error('status');
    if (sizeof($forms->error)) break;
	$database->orderhd->data->status_date=strtotime("now");
    $database->orderhd->update(TRUE);
    $forms->message[]="Record maintained";
    if ($forms->report['return_url']) fn_url_redirect($forms->report['return_url']);
	break;
}
$menu->head();
print $forms->message();
?>
<script>
function fn_action(obj,action,id) {
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
print $forms->hidden("return_url",$forms->report['return_url']);

switch (TRUE) {
  case ($forms->report['action'] == "email"):
	print $html;
    break;
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print $forms->hidden("report_status",$forms->report['report_status']);
	print $forms->hidden("report_order_date",$forms->report['report_order_date']);
	print $database->orderhd->output_header(array("status"=>TRUE));
	print $database->orderln->cart(array("order_id"=>$forms->report['record_id']));
    $buttons=array();
    $buttons[]=$forms->select("status",$database->orderhd->constant->status,$database->orderhd->data->status,FALSE);
	$buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ",'');"));
	$buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class=standard>Status: " . implode(" ",$buttons) . "</p>\n";
    break;
  default:
	print "<table class='standard noborder'>";
    print "<tr>";
    print "<td width='30%'>Status</td>";
    print "<td width='70%'>" . $forms->select("report_status",$database->orderhd->constant->status,$forms->report['report_status']) . "</td>";
    print "</tr>";
    print "<tr>";
    print "<td width='30%'>Order Date</td>";
    print "<td width='70%'>" . $forms->text("report_order_date",$forms->report['report_order_date'],10,10,"date:ymd") . "</td>";
    print "</tr>";
    print "</table>";
    print "<p>" . $forms->button("List",array("onclick"=>"fn_action(this,'list','');")) . "</p>";
    if ($forms->report['action'] =="list") {
		$query_where=array();
        if ($forms->report['report_status']) $query_where[]=new query_where("status","=",$forms->report['report_status']);
        if ($forms->report['report_order_date']) $query_where[]=new query_where("date_format(from_unixtime(order_date),'%Y/%m/%d')",">=",$forms->report['report_order_date']);
        $query=array();
        $query[]="select * from orderhd";
        $query[]=$database->where($query_where);
        $query[]="order by id";
		$database->orderhd->query($query);
        print "<table class='small border tablesorter'>";
        print "<thead>";
        print "<tr>";
		print "<th>PO#</th>";
		print "<th>Company</th>";
		print "<th>Name</th>";
		print "<th>Email</th>";
		print "<th>Order Date/Time</th>";
        if (!$forms->report['report_status']) print "<th>Status</th>";
		print "</tr>";
        print "</thead>";
        print "<tbody>";
	    while ($database->orderhd->fetch = $database->orderhd->fetch_array() ) {
	        $database->orderhd->fetch();
            print "<tr>";
	    	print "<td class=center>" . fn_href($database->orderhd->data->id,"javascript:fn_action(this,'maintain'," . fn_escape($database->orderhd->data->id) . ");") . "</td>";
	    	print "<td>" . $database->orderhd->data->company_name . "</td>";
	    	print "<td>" . $database->orderhd->data->name . "</td>";
	    	print "<td>" . $database->orderhd->data->email . "</td>";
	    	print "<td class=center>" . date("m/d/Y h:i A",$database->orderhd->data->order_date) . "</td>";
			if (!$forms->report['report_status']) print "<td>" . $database->orderhd->data->status . "</td>";
            print "</tr>";
		}
        print "</tbody>";
		$results[]="</table>";
	    $database->orderhd->free_result();
        print "</table>";
    }
}
print $forms->close();
$menu->copyright();
?>