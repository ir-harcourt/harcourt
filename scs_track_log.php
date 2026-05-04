<?php
require_once "scs_header.php";

$forms->title("Tracking Log");
$forms->report['action']=$_REQUEST['action'];
$forms->report['page']=$_POST['page'];
$forms->report['type']="Track";
switch (TRUE) {
  case (!$forms->report['action']):
	$forms->report['from_timestamp']=strtotime("-7 days");
	$forms->report['thru_timestamp']=0;
	$forms->report['subtype']=$database->registry->local->track;
	break;
/*
  case ($forms->report['action']=="login"):
  	$forms->report['user_id']=$_REQUEST['user_id'];
	$forms->report['from_timestamp']=date("Y/m/d",strtotime("-7 days"));
	$forms->report['thru_timestamp']=date("Y/m/d");
	break;
*/
  default:
  	$forms->report['from_timestamp']=strtotime($_POST['from_timestamp']);
  	$forms->report['thru_timestamp']=strtotime($_POST['thru_timestamp']);
    if ( ($forms->report['thru_timestamp']) && ($forms->report['from_timestamp'] > $forms->report['thru_timestamp']) ) $forms->error('thru_timestamp');
  	$forms->report['subtype']=$_POST['subtype'];
  	$forms->report['ip']=trim($_POST['ip']);
  	$forms->report['clear']=intval($_POST['clear']);
    if (sizeof($forms->error)) break;
    if (!$_POST['clear']) break;
    $query_where=array();
	$query_where[]=new query_where("type","=",$forms->report['type']);
    if ($forms->report['subtype']) $query_where[]=new query_where("subtype","=",$forms->report['subtype']);
    if ($forms->report['ip']) $query_where[]=new query_where("ip","=",$forms->report['ip']);
    if ($forms->report['from_timestamp']) $query_where[]=new query_where("log.date_time",">=",$forms->report['from_timestamp']);
    if ($forms->report['thru_timestamp']) $query_where[]=new query_where("log.date_time","<=",$forms->report['thru_timestamp']);
    $query=array("delete from log");
    $query[]=$database->where($query_where);
    $database->temp->query($query);
	$forms->message[]=number_format($database->temp->affected_rows()) . " records deleted";

}
$menu->head();
print $forms->message();
print $forms->open();
print $forms->hidden("action","list");
print $forms->hidden("page");
$menu->tabs=new jquery_tab_class();
if (isset($_SESSION['scscpq_wp'])) $menu->tabs->meta->options->lineheight="auto";
$menu->tabs->item("Input",tabs_input());
if ( $forms->report['action'] && (!sizeof($forms->error)) ) $menu->tabs->item("Results",tabs_results());
print $menu->tabs->output();
print $forms->close();
$menu->copyright();
function tabs_input() {
global $database,$forms,$menu;
	$results=array();
	$results[]="<table class='standard noborder'>";
	$results[]="<tr>";
	$results[]="<td width='30%'>From Date</td>";
	$results[]="<td width='70%'>" . $forms->text("from_timestamp",( ($forms->report['from_timestamp']) ? date("m/d/Y h:i A",$forms->report['from_timestamp']) : ""),25) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Thru Date</td>";
	$results[]="<td>" . $forms->text("thru_timestamp",( ($forms->report['thru_timestamp']) ? date("m/d/Y h:i A",$forms->report['thru_timestamp']) : ""),25) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Track type</td>";
	$results[]="<td>" . $forms->select("subtype",$database->log->constant->track,$forms->report['subtype']) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>IP address</td>";
	$results[]="<td>" . $forms->text("ip",$forms->report['ip'],20) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Clear Activity?</td>";
	$results[]="<td>" . $forms->checkbox("clear",1,0) . "</td>";
	$results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->submit("Go!") . "</p>";
	return $results;
}
function tabs_results() {
global $database,$forms,$menu;
	$menu->tabs->landing_tab();
	$database->log->query(log_query(),TRUE);
    if (!$database->log->meta->rows) return "<p>No records found</p>";
	$results=array();
	$results[]="<table class='small tablesorter border'>";
    $results[]="<caption>&nbsp;</caption>";
    $results[]="<thead>";
    $results[]="<tr>";
	$results[]="<th>IP Address</th>";
	$results[]="<th>Company Name</th>";
	if (!$forms->report['subtype']) $results[]="<th>Subtype</th>";
    $results[]="<th>Date/Time</th>";
    $results[]="<th>Comment</th>";
    $results[]="</tr>";
    $results[]="</thead>";
    $results[]="<tbody>";
	while ($database->log->fetch = $database->log->fetch_array() ) {
	    $database->log->fetch();
		$results[]="<tr>";
		$results[]="<td>" . $database->log->data->ip . "</td>";
		$results[]="<td>" . $database->log->fetch['company.name'] . "</td>";
        if (!$forms->report['subtype']) $results[]="<td>" . $database->log->data->subtype . "</td>";
		$results[]="<td class='center nobr'>" . date("m/d/Y h:i A",$database->log->data->date_time) . "</td>";
		$results[]="<td>" . str_replace("\n","<br>",$database->log->data->comment) . "</td>";
		$results[]="</tr>";
	}
    $results[]="</tbody>";
    $results[]="</table>";
	$database->log->free_result();
    $page_break=new page_item_break($database->log->meta->found_rows);
	$results[]=$page_break->page($forms->report['page']);
    return $results;
}
function log_query() {
global $database,$forms,$menu;
    $query_where=array();
    if ($forms->report['from_timestamp']) $query_where[]=new query_where("log.date_time",">=",$forms->report['from_timestamp']);
    if ($forms->report['thru_timestamp']) $query_where[]=new query_where("log.date_time","<=",$forms->report['thru_timestamp']);
	$query_where[]=new query_where("log.type","=",$forms->report['type']);
    if ($forms->report['subtype']) $query_where[]=new query_where("log.subtype","=",$forms->report['subtype']);
    if ($forms->report['ip']) $query_where[]=new query_where("log.ip","=",$forms->report['ip']);
    $query=array();
    $query[]="select";
    $query[]="ifnull(user.company_name,'Unknown') as 'company.name',";
    $query[]="log.* from log";
    $query[]="left join user on user.ip=log.ip";
    $query[]=$database->where($query_where);
    $query[]="order by log.ip, log.subtype, log.date_time";
    $query[]=$database->page_limit($forms->report['page']);
    return $query;
}
?>