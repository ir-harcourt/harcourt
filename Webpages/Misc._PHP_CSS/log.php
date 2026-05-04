<?php
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/log.php";
include_once "classes/user.php";
$menu->access();
$menu->title="Access Log";
$database->connect();
$menu->messages[]=$menu->title;
$option_order=array("Date","Record Type","IP Address");
$option_output=array("txt","csv");
$report['action']=$_REQUEST['action'];
switch (TRUE) {
  case (!$report['action']):
	$report['from_date']=date("Y/m/d",strtotime("-7 days"));
	$report['thru_date']=date("Y/m/d");
	break;
  case ($report['action']=="login"):
  	$report['ip']=$_REQUEST['ip'];
	$report['from_date']=date("Y/m/d",strtotime("-7 days"));
	$report['thru_date']=date("Y/m/d");
	break;
  default:
  	$report['from_date']=fn_date("mdy",$_POST['from_date']);
    if (!$date_ok) $menu->errors['from_date']=$menu->background['red'];
  	$report['thru_date']=fn_date("mdy",$_POST['thru_date']);
    switch (TRUE) {
      case (!$date_ok):
      case (($report['thru_date']) && ($report['from_date'] > $report['thru_date'])):
      	$menu->errors['thru_date']=$menu->background['red'];
	}
  	$report['record_type']=$_POST['record_type'];
  	$report['ip']=$_POST['ip'];
  	$report['output']=$_POST['output'];
}
if (($report['output']) && (!sizeof($menu->errors))) {
	$file_id="log." . $report['output'];
	if ($report['output']=="txt") {
	    $field_delimiter="\t";
	  } else {
	    $field_delimiter=",";
	}
    $fields=array();
    $fields[]="IP Address";
    $fields[]="Email";
    $fields[]="Company Name";
    $fields[]="Date/Time";
    $fields[]="Record Type";
    $fields[]="Search Term";
    $fields[]="SKU";
    $fields[]="CAD Format";
    $fh=fopen($file_id,"w");
    fputcsv($fh,$fields,$field_delimiter,"\"");
    $query_where=array();
    if ($report['from_date']) $query_where[]=new query_where("from_unixtime(log.date_time,'%Y/%m/%d')",">=",$report['from_date']);
    if ($report['thru_date']) $query_where[]=new query_where("from_unixtime(log.date_time,'%Y/%m/%d')","<=",$report['thru_date']);
    if ($report['record_type']) $query_where[]=new query_where("log.record_type","=",$report['record_type']);
    if ($report['ip']) $query_where[]=new query_where("log.ip","=",$report['ip']);
    if ($report['company_name']) $query_where[]=new query_where("user.company","=",$report['company_name']);
    $query =
    	"select user.company as 'user_company', \n" .
        "log.* from log \n" .
        "left join user on user.ip=log.ip \n" .
        $database->where($query_where) .
        "order by log.date_time desc";
	$database->log->query($query);
	while ($database->log->fetch = mysql_fetch_array($database->log->meta->handle)) {
	    $database->log->fetch();
        $fields=array();
	    $fields[]=$database->log->data->ip;
	    $fields[]=$database->log->data->email;
	    $fields[]=$database->log->fetch['user_company'];
	    $fields[]=date("m/d/Y h:m:s A",$database->log->data->date_time);
	    $fields[]=$database->log->data->record_type;
	    $fields[]=$database->log->data->search_term;
	    $fields[]=$database->log->data->sku;
	    $fields[]=$database->log->data->cad_format;
        fputcsv($fh,$fields,$field_delimiter,"\"");
    }
    $database->log->free_result();
    fclose($fh);
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=\"$file_id");
    readfile($file_id);
    unlink($file_id);
    die;
}

$menu->head();
$menu->message();
?>
<script type="text/javascript">
function fn_submit() {
	document.scs_form.action.value="list";
	document.scs_form.submit();
}
</script>
<?php
print "<form name=scs_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action value='list'>\n";
print "<table class='standard noborder'>\n";
print "<tr>\n";
print "<th>From Date</th>\n";
print "<th>Thru Date</th>\n";
print "<th>Record Type</th>\n";
print "<th>IP Address</th>\n";
print "<th>Upload?</th>\n";
print "<th>&nbsp;</th>\n";
print "</tr>\n";
print "<tr>\n";
print "<td class=center><input type=text name=from_date size=10 maxlength=10 " . fn_html_value($report['from_date'],"date","ymd",$menu->errors['from_date']) . "></td>\n";
print "<td class=center><input type=text name=thru_date size=10 maxlength=10 " . fn_html_value($report['thru_date'],"date","ymd",$menu->errors['thru_date']) . "></td>\n";
print "<td class=center>" . $menu->select("record_type",$database->log->constant->record_type,$report['record_type']) . "</td>\n";
$user_table=array();
$query =
    "select user.* from log \n" .
    "left join user on user.ip=log.ip \n" .
    "where user.ip <> '' \n" .
    "group by user.ip \n" .
    "order by user.company";
$database->user->query($query);
while ($database->user->fetch = mysql_fetch_array($database->user->meta->handle)) {
    $database->user->fetch();
    $user_table[$database->user->data->ip]=$database->user->data->ip . " (" . substr($database->user->data->company,0,20) . ")";
}
$database->user->free_result();
print "<td class=center>" . $menu->select("ip",$user_table,$report['ip']) . "</td>\n";
print "<td class=center>" . $menu->select("output",$option_output,$report['output']) . "</td>\n";
print "</td>\n";
print "<td class=center><input type=button class=nav_button value='Go!' onclick=\"javascript:fn_submit();\"></td>\n";
print "</tr>\n";
print "</table>\n";

if (($report['action']) && (!sizeof($menu->errors))) {
	print "<table class='small border'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
	print "<th>Email</th>\n";
	print "<th>Company Name</th>\n";
    print "<th>Date/Time</th>\n";
    if (!$report['record_type']) print "<th>Record Type</th>\n";
    print "<th>Search Term</th>\n";
    print "<th>SKU</th>\n";
    print "<th>CAD Format</th>\n";
    print "</tr>\n";
    $query_where=array();
    if ($report['from_date']) $query_where[]=new query_where("from_unixtime(log.date_time,'%Y/%m/%d')",">=",$report['from_date']);
    if ($report['thru_date']) $query_where[]=new query_where("from_unixtime(log.date_time,'%Y/%m/%d')","<=",$report['thru_date']);
    if ($report['record_type']) $query_where[]=new query_where("log.record_type","=",$report['record_type']);
    if ($report['ip']) $query_where[]=new query_where("log.ip","=",$report['ip']);
    if ($report['company_name']) $query_where[]=new query_where("user.company","=",$report['company_name']);
    $query =
    	"select user.company as 'user_company', \n" .
        "log.* from log \n" .
        "left join user on user.ip=log.ip \n" .
        $database->where($query_where) .
        "order by log.date_time desc";
	$database->log->query($query);
	while ($database->log->fetch = mysql_fetch_array($database->log->meta->handle)) {
	    $database->log->fetch();
		print "<tr>\n";
		print "<td>" . $database->log->data->email . "</td>\n";
		print "<td>" . $database->log->fetch['user_company'] . "</td>\n";
		print "<td class=center>" . date("m/d/Y h:m:s A",$database->log->data->date_time) . "</td>\n";
        if (!$report['record_type']) print "<td>" . $database->log->data->record_type . "</td>\n";
		print "<td>" . $database->log->data->search_term . "</td>\n";
		print "<td>" . $database->log->data->sku . "</td>\n";
		print "<td>" . $database->log->data->cad_code . "</td>\n";
        print "</tr>\n";
	}
	$database->log->free_result();
    print "</table>\n";
}

print "</form>\n";
$database->disconnect();
$menu->copyright();
?>