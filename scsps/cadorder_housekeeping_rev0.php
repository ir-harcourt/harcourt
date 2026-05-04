<?php
require_once "classes/cadorder.php";
require_once "classes/cadorder_update.php";
$forms->title("CAD Cart Housekeeping");
$forms->report['action']=$_POST['action'];
$action_list=$database->cadorder->constant->housekeeping;
$action_list['housekeeping']="Perform all houskeeping tasks";
$results=array();

if ($forms->report['action']) {
	$cadorder_update=new cadorder_update_class($forms->report['action']);
    $results=$cadorder_update->results;
}
$menu->head();
$forms->message();

if (sizeof($results)) {
	foreach ($results as $action => $action_results) {
		$query_where=array();
        $query_where[]=new query_where("cadorder.id","in",$action_results);
        $query =
        	"select \n" .
            "user.name as 'user.name', \n" .
            "user.company_name as 'user.company_name', \n" .
            "cadorder.* from cadorder \n" .
            "left join user on user.id=cadorder.user_id \n" .
            $database->where($query_where) .
            "order by cadorder.id";
		$database->cadorder->query($query);
	    print "<table class='standard border'>\n";
        print $forms->caption("<br><b>Update Journal: " . $action_list[$action] . "</b>");
	    print "<tr>\n";
	    print "<th rowspan=2>Order#</th>\n";
	    print "<th rowspan=2>Initial Date/Time</th>\n";
	    print "<th rowspan=2>User/Company Name</th>\n";
	    print "<th rowspan=2>SKU</th>\n";
	    print "<th rowspan=2>CAD Format</th>\n";
	    print "<th colspan=2>Status</th>\n";
        print "</tr>\n";
        print "<tr>\n";
        print "<th>Name</th>\n";
        print "<th>Code</th>\n";
        print "</tr>\n";
        while ($database->cadorder->fetch = $database->cadorder->fetch_array() ) {
			$database->cadorder->fetch();
            print "<tr>\n";
            print "<td class=center>" . $database->cadorder->data->id . "</td>\n";
            print "<td class=center>" . date("m/d/Y g:i A",$database->cadorder->data->initial_date) . "</td>\n";
			print "<td>" . $database->cadorder->fetch['user.name'] . "<br>" . $database->cadorder->fetch['user.company_name'] . "</td>\n";
            print "<td>" . $database->cadorder->data->sku . "</td>\n";
            print "<td>" . $database->cadorder->data->cad_code . "</td>\n";
            print "<td>" . $database->cadorder->data->status . "</td>\n";
            print "<td class=center>" . $database->cadorder->data->status_code . "</td>\n";
            print "</tr>\n";
		}
	    print "</table>\n";
	}
}

print $forms->open();
print "<table class='standard border'>\n";
print $forms->caption("<br>");
print "<tr>\n";
print "<th>Action</th>\n";
print "<th>Function</th>\n";
print "<th>Frequency</th>\n";
print "<th>Next Scheduled</th>\n";
print "<th>Comment</th>\n";
print "</tr>\n";
foreach ($action_list as $action => $action_name) {
	print "<tr>\n";
	print "<td class='center'>" . $forms->radio("action",$action,$forms->report['action']) . "</td>\n";
	print "<td>$action_name</td>\n";
    if (isset($database->registry->cadcart->housekeeping_poll[$action])) {
    	$text=$database->registry->cadcart->housekeeping_poll[$action] . " minutes";
	  } else {
      	$text="";
	}
	print "<td class=right>$text</td>\n";
    if (isset($database->registry->cadcart->housekeeping_next[$action])) {
    	$text=$database->registry->cadcart->housekeeping_next[$action];
	  } else {
      	$text="";
	}
	print "<td class=center>$text</td>\n";
    switch ($action) {
      case "file":
		$text=$database->registry->cadcart->partserver_delay . " minute delay";
      	break;
      default:
		$text="";
	}
	print "<td>$text</td>\n";
	print "</tr>\n";
}
print "</table>\n";
print "<p>" . $forms->submit("Go!") . "</p>\n";

print $forms->close();
$menu->copyright();
?>