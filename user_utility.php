<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/industry.php";
$database->user->access("Administrator");
$forms->title("User Table Utility");
$function_list=array(
    "download"=>"WWW => Desktop",
    "review"=>"Desktop => WWW (Review only)",
    "upload"=>"Desktop => WWW (Update)");

$forms->report['action']=$_POST['action'];
$forms->report['function']=$_POST['function'];
$forms->report['file_type']=$_POST['file_type'];
$forms->report['industry_id']=$_POST['industry_id'];
$forms->report['status']=array();
$forms->report['type']=array();
if (!$forms->report['action']) {
    foreach ($database->user->constant->status as $status) {
		$forms->report['status'][]=$status;
    }
    foreach ($database->user->constant->type as $type) {
		$forms->report['type'][]=$type;
    }
  } else {
  	foreach ($_POST as $key => $value) {
		list($field,$code)=preg_split("/\t/",base64_decode($key));
        if ( (strlen($code)) && (array_key_exists($field,$forms->report)) ) $forms->report[$field][]=$code;
    }
    if (!sizeof($forms->report['status'])) $forms->error('status');
    if (!sizeof($forms->report['type'])) $forms->error('type');
    $forms->report['login_date_from']=fn_date($_POST['login_date_from'],"mdy");
    $forms->report['login_date_thru']=fn_date($_POST['login_date_thru'],"mdy");
}
$map=new map_class("user",$forms->report);
$map->forms_map(array("login_date_from"=>"date:ymd","login_date_thru"=>"date:ymd"));
switch (TRUE) {
  case (!$forms->report['action']):
  case (!$forms->report['function']):
	break;
  case ($forms->report['action'] == "cancel"):
	$forms->message[]="Request Cancelled";
	break;
  case (($forms->report['function']=="download") && ($forms->report['action']=="prelist")):
    if ( ($forms->report['login_date_thru']) && ($forms->report['login_date_from'] > $forms->report['login_date_thru']) ) $forms->error('login_date_thru');
	if (sizeof($forms->error)) break;
	$database->user->query($database->user->export_query());
    $database->user->free_result();
    if (!$database->user->meta->rows) $forms->error('null','',"No records found");
	break;
  case ( ($forms->report['function']=="download") && ($forms->report['action']=="update") ):
	$map->export();
   	die;
  case (($forms->report['function']=="review") && ($forms->report['action']=="prelist")):
  case (($forms->report['function']=="upload") && ($forms->report['action']=="prelist")):
	if (!$map->upload("upload")) $forms->error('upload');
    if ($map->message) $forms->message[]=$map->message;
    break;
   case (($forms->report['function']=="review") && ($forms->report['action']=="update")):
   case (($forms->report['function']=="upload") && ($forms->report['action']=="update")):
   	set_time_limit(600);
	$forms->message[]=$map->import();
	break;
}
$menu->head();
$forms->message();
?>
<script type='text/javascript'>
function fn_action(action) {
	if (action=='update') {
	    if (!confirm("Continue?")) {
	        obj.checked=false;
            return;
	    }
    }
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
function function_change() {
	switch (document.getElementById('function').value) {
	  case '':
		download=0;
		upload=0;
        break;
	  case 'download':
		download=1;
		upload=0;
        break;
	  default:
      	download=0;
		upload=1;
    }
    div_show('row_industry',download,'');
    div_show('row_type',download,'');
    div_show('row_status',download,'');
    div_show('row_login_date',download,'');
    div_show('row_file_type',download,'');
    div_show('row_upload',upload,'');
    div_show('utility_button',upload + download,'');
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
switch (TRUE) {
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="review") && (!sizeof($forms->error))):
  case (($forms->report['action']=="prelist") && ($forms->report['function']=="upload") && (!sizeof($forms->error))):
	$map->upload_html();
	break;
  case (($forms->report['action']=="update") && ($forms->report['function']=="review")):
  case (($forms->report['action']=="update") && ($forms->report['function']=="upload")):
	$map->import_html();
	break;
  case (($forms->report['action']=="prelist") && (!sizeof($forms->error))):
	$map->export_html("User Export Utility",$database->user->meta->rows);
	break;
  default:
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width='30%'>Action</td>\n";
    print "<td width='70%'>" . $forms->select("function",$function_list,$forms->report['function'],TRUE,array("onchange"=>"function_change();")) . "</td>\n";
    print "</tr>\n";
	print "<tr id='row_industry'>\n";
	print "<td>Selected industry</td>\n";
	print "<td>" . $database->industry->select($forms->report['industry_id']) . "</td>\n";
	print "</tr>\n";
    if (array_key_exists("type",$forms->error)) {
    	$bg=$forms->background->yellow;
	  } else {
		$bg="";
	}
	print "<tr id='row_type'>\n";
	print "<td>User Roles</td>\n";
    $text=array();
    foreach ($database->user->constant->type as $type) {
		$text[]=$forms->checkbox(base64_encode("type" . "\t" . $type),1,in_array($type,$forms->report['type'])) . " {$type}? ";
    }
	print "<td $bg>" . implode("<br>",$text) . "</td>\n";
	print "</tr>\n";
    if (array_key_exists("status",$forms->error)) {
    	$bg=$forms->background->yellow;
	  } else {
		$bg="";
	}
	print "<tr id='row_status'>\n";
	print "<td>User Status</td>\n";
    $text=array();
    foreach ($database->user->constant->status as $status) {
		$text[]=$forms->checkbox(base64_encode("status" . "\t" . $status),1,in_array($status,$forms->report['status'])) . " {$status}? ";
    }
	print "<td $bg>" . implode("<br>",$text) . "</td>\n";
	print "</tr>\n";
	print "<tr id='row_login_date'>\n";
	print "<td>Last login date</td>\n";
    $text=array();
    $text[]=$forms->text("login_date_from",$forms->report['login_date_from'],10,10,"date:ymd");
    $text[]=" - ";
    $text[]=$forms->text("login_date_thru",$forms->report['login_date_thru'],10,10,"date:ymd");
	print "<td>" . implode("",$text) . "</td>\n";
	print "</tr>\n";

	print "<tr id='row_file_type'>\n";
	print "<td>Export file type</td>\n";
	print "<td>" . $forms->select("file_type",$map->file_type_list,$forms->report['file_type'],FALSE) . "</td>\n";
	print "</tr>\n";
	print "<tr id='row_upload'>\n";
	print "<td>Import File</td>\n";
	print "<td>". $forms->file('upload',40) . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "<p>" . $forms->button("Go!",array("onclick"=>"fn_action('prelist');","id"=>"utility_button")) . "</p>\n";
    print "<script type='text/javascript'>\n";
    print "function_change();\n";
    print "</script>\n";
}
print $forms->close();
$menu->copyright();
/*
function user_export_query() {
global $database, $forms;
	$query_where=array();
    $query_where[]=new query_where("status","in",$forms->report['status']);
    $query_where[]=new query_where("type","in",$forms->report['type'],$forms->report['industry_id']);
	if ($forms->report['industry_id']) $query_where[]=new query_where("industry_id","=",$forms->report['industry_id']);
    if ($forms->report['login_date_from'])  $query_where[]=new query_where("date_format(from_unixtime(last_login),'%Y/%m/%d')",">=",$forms->report['login_date_from']);
    if ($forms->report['login_date_thru'])  $query_where[]=new query_where("date_format(from_unixtime(last_login),'%Y/%m/%d')","<=",$forms->report['login_date_thru']);
	$query=
		"select * from user \n" .
        $database->where($query_where) .
        "order by id";
	return $query;
}
*/
?>
