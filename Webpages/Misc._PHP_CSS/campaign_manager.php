<?php
include "/Webpages/Header_Footer/scs_header.php";
include_once "classes/campaign.php";
include_once "classes/document.php";
$menu->access();
$database->connect();
$menu->title="Email Campaign Manager";
$menu->messages[]=$menu->title;
$report_status=array("Only Current","Only Open Ending","Only Active","All");
$report['action']=$_POST['action'];
$report['report_status']=$_POST['report_status'];
$report['record_id']=$_POST['record_id'];
if ($report['record_id']) $database->campaign->read($report['record_id']);
switch ($report['action']) {
  case "":
    break;
  case "cancel":
    $menu->messages[]="Request cancelled";
    break;
  case "delete":
    $database->campaign->delete($report['record_id']);
    $menu->messages[]="Campaign #" . $database->campaign->data->id . " deleted";
    break;
  case "maintain":
    if (!$database->campaign->data->id) {
    	$database->campaign->data=new campaign_data_class();
    }
    break;
  case "update":
	$database->campaign->data->description=trim($_POST['description']);
    if (!$database->campaign->data->description) $menu->errors['description']=$menu->background['red'];
	$database->campaign->data->from_date=fn_date("mdy",$_POST['from_date']);
    switch (TRUE) {
      case (!$date_ok):
      case (!$database->campaign->data->from_date):
      	$menu->errors['from_date']=$menu->background['red'];
	}
	$database->campaign->data->thru_date=fn_date("mdy",$_POST['thru_date']);
    switch (TRUE) {
      case (!$date_ok):
      case (($database->campaign->data->thru_date) && ($database->campaign->data->thru_date < $database->campaign->data->from_date)):
      	$menu->errors['thru_date']=$menu->background['red'];
	}
	$database->campaign->data->document_id=intval($_POST['document_id']);
    if (!$database->campaign->data->document_id) $menu->errors['document_id']=$menu->background['red'];
	$database->campaign->data->restricted=intval($_POST['restricted']);
	$database->campaign->data->active=intval($_POST['active']);
	if (sizeof($menu->errors)) break;
	$database->campaign->update($database->campaign->meta->rows);
	if (mysql_errno()) {
        $menu->errors['mysql']=$menu->background['red'];
        $menu->messages[]=mysql_error();
    }
	break;
}
$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_action(obj,action,id,name) {
	if (action=='delete') {
	    if (!confirm("Delete: #" + id + " " + name)) {
	        obj.checked=false;
            return;
	    }
    }
	document.maintain_form.action.value=action;
	document.maintain_form.record_id.value=id;
	document.maintain_form.submit();
}
</script>
<?php
print "<form name=maintain_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
print "<input type=hidden name=record_id>\n";
print "<input type=hidden name=return_url " . fn_html_value($report['return_url']) . ">\n";
switch (TRUE) {
  case ($report['action'] == "maintain"):
  case (($report['action'] == "update") && (sizeof($menu->errors))):
	print "<input type=hidden name=report_status " . fn_html_value($report['report_status']) . ">\n";
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width=30%>Description</td>\n";
    print "<td width=70%><input type=text name=description size=60 " . fn_html_value($database->campaign->data->description,"","",$menu->errors['description']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>From Date</td>\n";
    print "<td><input type=text name=from_date size=10 maxlength=10 " . fn_html_value($database->campaign->data->from_date,"date","ymd",$menu->errors['from_date']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Thru Date</td>\n";
    print "<td><input type=text name=thru_date size=10 maxlength=10 " . fn_html_value($database->campaign->data->thru_date,"date","ymd",$menu->errors['thru_date']) . "></td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Landing page</td>\n";
    print "<td>";
    $database->document->select($database->campaign->data->document_id,"document_id","text/html");
    print "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<td>Restricted?</td>\n";
    print "<td>" . $menu->checkbox("restricted",$database->campaign->data->restricted,1) . "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<td>Active?</td>\n";
    print "<td>" . $menu->checkbox("active",$database->campaign->data->active,1) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p class='standard center'>\n";
	print "<input class=fbutton type=button value='Update' onclick=\"javascript:fn_action(this,'update','" . $database->campaign->data->id . "','');\"> \n";
    if ($report['return_url']) {
		print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:location.href('" . $report['return_url'] . "');\">\n";
      } else {
		print "<input class=fbutton type=button value='Cancel' onclick=\"javascript:fn_action(this,'cancel','','');\">\n";
    }
	print "</p>\n";
  	break;
  default:
	print "<p class='large center'>";
    print "Campaign status " . $menu->select("report_status",$report_status,$report['report_status'],FALSE);
    print " <input class=fbutton type=button value='List' onclick=\"javascript:fn_action(this,'list','','');\">\n";
	print "<p>";
	print "<table class='standard border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<th>Description</th>\n";
    print "<th>Document#</th>\n";
    print "<th>From Date</th>\n";
    print "<th>Thru Date</th>\n";
    print "<th>URL Code</th>\n";
    print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td colspan=6><a href=\"javascript:fn_action(this,'maintain','0','');\">Add new</a></td>\n";
    print "</tr>\n";
    if ($report['action'] == "list") {
 		$query_where=array();
        switch ($report['report_status']) {
          case "Only Current":
			$query_where[]=new query_where("active","=",1);
			$query_where[]=new query_where("thru_date",">=",date("Y/m/d"));
            break;
          case "Only Open Ending":
			$query_where[]=new query_where("active","=",1);
			$query_where[]=new query_where("thru_date","=","0000/00/00");
            break;
          case "Only Active":
			$query_where[]=new query_where("active","=",1);
            break;
		}
	    $query =
	        "select * from campaign \n" .
            $database->where($query_where) .
	        "order by from_date";
	    $database->campaign->query($query);
	    while ($database->campaign->fetch = mysql_fetch_array($database->campaign->meta->handle)) {
	        $database->campaign->fetch();
	        print "<tr>\n";
	        print
	            "<td>" .
	            "<a href=\"javascript:fn_action(this,'maintain','" . $database->campaign->data->id . "','');\">" .
	            $database->campaign->data->id . ". " . $database->campaign->data->description;
	            "</a></td>\n";
	        print "<td class=center>" . $database->campaign->data->document_id . "</td>\n";
	        print "<td class=center>" . fn_date("ymd",$database->campaign->data->from_date) . "</td>\n";
	        print "<td class=center>" . fn_date("ymd",$database->campaign->data->thru_date) . "</td>\n";
	        print "<td class=center>campaign.php?id=" . $database->campaign->url_encode() . "&email=(client email)</td>\n";
	        print "<td class=center>";
			if ($database->campaign->data->active) {
	            print "&nbsp;";
			  } else {
	            print "<input type=radio name='delete' onclick=\"javascript:fn_action(this,'delete','" . $database->campaign->data->id . "','" . $database->campaign->data->description . "');\">\n";
	        }
	        print "</td>\n";
	        print "</tr>\n";
	    }
	}
    print "</table>\n";
	$database->campaign->free_result();
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
?>