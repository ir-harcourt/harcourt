<?php
$forms->title("Access Log Purge");

$forms->report['action']=$_POST['action'];
$type_list=array();
if (!$forms->report['action']) {
	$forms->report['date']=date("Y/m/d",strtotime("-30 days"));
  } else {
	$forms->report['date']=fn_date($_POST['date'],"mdy");
    foreach ($_POST as $key => $value) {
		list ($type,$field)=explode("\t",base64_decode($key));
        if (($value) && ($type=="type")) $type_list[]=$field;
    }
}
switch ($forms->report['action']) {
  case "review":
	switch (TRUE) {
	  case (!$date_ok):
	  case (!$forms->report['date']):
	  case ($forms->report['date'] > date("Y/m/d")):
		$forms->error("date");
    }
	if (!sizeof($type_list)) {
		$forms->message[]="No record types selected";
    	$forms->error("type");
        break;
	}
    $query_where=array();
    $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')","<=",$forms->report['date']);
    $query_where[]=new query_where("type","in",$type_list);
	$query=
	    "select \n" .
	    "type as 'type', \n" .
	    "subtype as 'subtype', \n" .
	    "count(*) as 'count' \n" .
	    "from log \n" .
        $database->where($query_where) .
	    "group by type,subtype \n" .
	    "order by type,subtype";
	$database->log->query($query);
    $count_list=array();
	while ($database->log->fetch = $database->log->fetch_array()) {
		$count_list[$database->log->fetch['type']]=$database->log->fetch['count'];
    }
    if (!array_sum($count_list)) {
		$forms->message[]="No records found";
    	$forms->error("type");
    }
	break;
  case "update":
    $query_where=array();
    $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')","<=",$forms->report['date']);
    $query_where[]=new query_where("type","in",$type_list);
    $query =
    	"delete from log \n" .
        $database->where($query_where);
	$database->log->query($query);
	$purge_comment=number_format($database->affected_rows()) . " record(s) delete from log";
    $forms->message[]=$purge_comment;
	if (method_exists($database->log,"ipc")) {
	    $comment=array();
	    $comment[]="Access log purge";
	    $comment[]=$purge_comment;
	    $comment[]=$query;
		$database->log->ipc("Administrator: Purge",$comment);
    }
	break;
}
$menu->head();
$forms->message();
?>
<script type="text/javascript">
function fn_purge(action) {
	if (action == "update") {
	    if (!confirm("Permanently remove selected records?")) {
	        return;
	    }
	}
	document.scs_form.action.value=action;
	document.scs_form.submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
switch (TRUE) {
  case (($forms->report['action']=="review") && (!sizeof($forms->error))):
	print $forms->hidden("date",$forms->report['date'],"date:ymd");
	print "<p>Remove selected activity thru <b>" . date("l F d, Y",strtotime($forms->report['date'])) . "</b></p>\n";
    print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<td width='10%'><b>Count</b></td>\n";
    print "<td width='90%'><b>Record Type</b></td>\n";
    print "</tr>\n";
    foreach ($type_list as $field) {
		print $forms->hidden(base64_encode("type" . "\t". $field),1);
        print "<tr>\n";
        print "<td class=right>" . number_format($count_list[$field]) . "</td>\n";
        print "<td>$field</td>\n";
        print "</tr>\n";
    }
    print "<tr>\n";
    print "<td class=right><b>" . number_format(array_sum($count_list)) . "</b></td>\n";
    print "<td><b>Records to be purged</b></td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p>" . $forms->button("<< Back",array("onclick"=>"fn_purge('list');"));
	print " " . $forms->button("Permanently purge selected records",array("onclick"=>"fn_purge('update');"));
	print "</p>";
	break;
  default:
	$query=
	    "select \n" .
	    "type as 'type' \n" .
	    "from log \n" .
	    "group by type \n" .
	    "order by type";
	$database->log->query($query);
    if (!$database->log->meta->rows) {
		print "<p class=standard><b>Log is empty</b></p>\n";
	  } else {
		print "<p class=standard>Remove all activity thru date: " . $forms->text("date",$forms->report['date'],10,10,"date") . "</p>\n";
        print "<p>Record types:";
	    while ($database->log->fetch = $database->log->fetch_array()) {
	        if ($text) $text .="<br>";
	        if (in_array($database->log->fetch['type'],$type_list)) {
	            $checked=1;
	          } else {
	            $checked=0;
	        }
	        print "<br>" . $forms->checkbox(base64_encode("type" . "\t" . $database->log->fetch['type']),1,$checked) . " " .  $database->log->fetch['type'];
		}
        $database->log->free_result();
        print "</p>\n";
        print "<p>" . $forms->button("Next >>",array("onclick"=>"fn_purge('review');")) . "</p>\n";
	}
}
print $forms->close();
$menu->copyright();
?>