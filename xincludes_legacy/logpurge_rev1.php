<?php
$forms->title("Access Log Purge");
$forms->message[]="Revised 05/08/2018";

$forms->report['action']=$_POST['action'];
$type_list=array();
$forms->report['date']=fn_date($_POST['date'],"mdy");
switch ($forms->report['action']) {
  case "":
	$forms->report['date']=date("Y/m/d",strtotime("-30 days"));
	break;
  case "review":
    foreach ($_POST as $key => $value) {
		list ($field,$type,$subtype)=explode("\t",base64_decode($key));
        switch (TRUE) {
          case (!$value):
          case ($field != "field"):
          case (!$type):
          case (!$subtype):
			break;
           default:
			$type_list[$type][]=$subtype;
		}
    }
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
    $group_where=array();
    foreach ($type_list as $group => $group_list) {
		$group_where[$group][]=new query_where("type","=",$group,"or");
		$group_where[$group][]=new query_where("subtype","in",$group_list);
	}
    $query_where=array();
    $query_where['date']=new query_where("log.date_time","<=",strtotime($forms->report['date'] . " +1 day") - 1);
	$query_where['group']=new query_where($database->where($group_where,"omit"),"","","and_group");
	$query=array();
	$query[]="select";
	$query[]="type as 'type',";
	$query[]="subtype as 'subtype',";
	$query[]="count(*) as 'count'";
	$query[]="from log";
	$query[]=trim($database->where($query_where));
	$query[]="group by type,subtype";
	$query[]="order by type,subtype";
	$database->log->query($query);
    $count_list=array();
	while ($database->log->fetch = $database->log->fetch_array()) {
		$count_list[$database->log->fetch['type']][$database->log->fetch['subtype']]=$database->log->fetch['count'];
    }
    if (!sizeof($count_list)) {
		$forms->message[]="No records found";
    	$forms->error("type");
    }
	break;
  case "update":
    foreach ($_POST as $key => $value) {
		list ($field,$type,$subtype)=explode("\t",base64_decode($key));
        switch (TRUE) {
          case (!$value):
          case ($field != "purge"):
          case (!$type):
          case (!$subtype):
			break;
           default:
			$type_list[$type][]=$subtype;
		}
    }
    $group_where=array();
    foreach ($type_list as $group => $group_list) {
		$group_where[$group][]=new query_where("type","=",$group,"or");
		$group_where[$group][]=new query_where("subtype","in",$group_list);
	}
    $query_where=array();
    $query_where['date']=new query_where("log.date_time","<=",strtotime($forms->report['date'] . " +1 day") - 1);
	$query_where['group']=new query_where($database->where($group_where,"omit"),"","","and_group");

	$query=array();
    $query[]="delete from log";
    $query[]=trim($database->where($query_where));
	$database->log->query($query);
	$purge_comment=number_format($database->affected_rows()) . " record(s) delete from log";
    $forms->message[]=$purge_comment;
	if (method_exists($database->log,"ipc")) {
	    $comment=array();
	    $comment[]="Access log purge";
	    $comment[]=$purge_comment;
	    $comment[]=implode("\n",$query);
		$database->log->ipc("Administrator:Purge",$comment);
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
function purge_group(group,field_list) {
	fields=field_list.split(",");
	for (var i=0; i< fields.length; i++) {
		document.getElementById(fields[i]).checked=group.checked;
	}
}
function purge_item(item,group) {
	if ((!item.checked) && (document.getElementById(group).checked)) document.getElementById(group).checked=false;
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
switch (TRUE) {
  case (($forms->report['action']=="review") && (!sizeof($forms->error))):
	print $forms->hidden("date",$forms->report['date'],"date:ymd");
    foreach ($_POST as $key => $value) {
		list ($field,$type,$subtype)=explode("\t",base64_decode($key));
        switch (TRUE) {
          case (!$value):
          case ($field != "field"):
          case (!$type):
			break;
           default:
			print $forms->hidden($key,$value);
		}
	}
	print "<p>Remove selected activity thru <b>" . date("l F d, Y",strtotime($forms->report['date'])) . "</b></p>\n";
    print "<table class='standard border'>\n";
    print "<tr>\n";
    print "<th width='20%'>Group</th>\n";
    print "<th width='20%'>Type</th>\n";
    print "<th width='10%'>Count</th>\n";
    print "</tr>\n";
    $count=0;
    foreach ($count_list as $type => $type_list) {
		$first_pass=FALSE;
		foreach ($type_list as $subtype => $subtype_count) {
	        print $forms->hidden(base64_encode("purge" . "\t" . $type . "\t" . $subtype),1);
	        print "<tr>\n";
            if (!$first_pass) {
            	$first_pass=TRUE;
                print "<td rowspan=" . sizeof($type_list) . ">$type</td>\n";
            }
	        print "<td>$subtype</td>\n";
	        print "<td class=right>" . number_format($subtype_count) . "</td>\n";
	        print "</tr>\n";
            $count+=$subtype_count;
		}
		print "<tr><td colspan=3>&nbsp;</td></tr>\n";
    }
    print "<tr>\n";
    print "<td colspan=2><b>Records to be purged</b></td>\n";
    print "<td class=right><b>" . number_format($count) . "</b></td>\n";
    print "</tr>\n";
    print "</table>\n";
	print "<p>" . $forms->button("<< Back",array("onclick"=>"fn_purge('list');"));
	print " " . $forms->button("Permanently purge selected records",array("onclick"=>"fn_purge('update');"));
	print "</p>";
	break;
  default:
	$query=array();
	$query[]="select count(*) as 'count', log.* from log";
	$query[]="group by type,subtype";
	$query[]="order by type,subtype";
	$database->log->query($query);
    $results=array();
	while ($database->log->fetch=$database->log->fetch_array()) {
        $database->log->fetch();
		if (!isset($results[$database->log->data->type])) $results[$database->log->data->type]=new logpurge_class($database->log->fetch['type'],"");
		$results[$database->log->data->type]->subtype[$database->log->data->subtype]=new logpurge_class($database->log->fetch['type'],$database->log->fetch['subtype']);
    }
    if (sizeof($results)) {
		foreach ($results as $type) {
			$type->complete();
            foreach ($type->subtype as $subtype) {
				$type->js[]=$subtype->field;
            }
        }
    }
	if (!sizeof($results)) {
		print "<p class=standard><b>Log is empty</b></p>\n";
	  } else {
		print "<table class='standard noborder'>\n";
        print "<tr>\n";
        print "<td width='30%'>Remove all activity thru date</td>\n";
        print "<td width='70%'>" . $forms->text("date",$forms->report['date'],10,10,"date") . "</td>\n";
        print "</tr>\n";
        foreach ($results as $type => $type_item) {
			print "<tr>\n";
            print "<td>" . $forms->checkbox($type_item->field,1,$_POST[$type_item->field],array("onclick"=>"purge_group(this," . fn_escape(implode(",",$type_item->js)) . ");")) . " $type</td>\n";
            print "<td>\n";
            foreach ($type_item->subtype as $subtype => $subtype_item) {
				print $forms->checkbox($subtype_item->field,1,$_POST[$subtype_item->field],array("onclick"=>"purge_item(this," . fn_escape($type_item->field) . ");")) . " $subtype</br>\n";
            }
            print "</td>\n";
            print "</tr>\n";
            print "<tr><td colspan=2>&nbsp;</td></tr>\n";
        }
        print "</table>\n";
        print "<p>" . $forms->button("Next >>",array("onclick"=>"fn_purge('review');")) . "</p>\n";
	}
}
print $forms->close();
$menu->copyright();
class logpurge_class {
	var $count=0;
	var $purge=FALSE;
    var $field;
    var $js=array();
    var $subtype=array();
    function __construct($type,$subtype) {
		$this->count=$fetch['count'];
		$this->field=base64_encode("field" . "\t" . $type . "\t" . $subtype);
    }
    function complete() {
		$this->count=0;
        foreach ($this->subtype as $subtype => $item) {
			$this->count += $item->count;
        }
    }
}
?>