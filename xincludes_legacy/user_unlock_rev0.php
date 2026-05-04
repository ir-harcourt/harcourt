<?php
$forms->title("Reset Locked Accounts");
$forms->message[]="Revised 09/13/2015";
$forms->report['action']=$_POST['action'];
switch (TRUE) {
  case (!$forms->report['action']):
	break;
  default:
	$update_array=array();
    foreach ($_POST as $key => $value) {
		$data=preg_split("/\t/",base64_decode($key));
        if ($data[0]=="user_id") {
        	$update_array[]=$data[1];
			if (method_exists($database->log,"ipc")) $database->log->ipc("User: Unlock","Unlocked by " . $_SESSION['user']->name,$data[1]);
		}
    }
    if (!sizeof($update_array)) {
		$forms->error('null','',"No records selected");
        break;
    }
    $query_where=array();
    $query_where[]=new query_where("id","in",$update_array);
    $query=
        "update user \n" .
        "set disabled=0,login_attempt=0 \n" .
        $database->where($query_where);
	$database->user->query($query);
    $forms->message[]=sizeof($update_array) . " record(s) unlocked";
}
$menu->head();
print $forms->message();
print $forms->open();
print $forms->hidden("action","update");

$query_order=array();
if (isset($database->registry->address->user['company_name'])) $query_order['company_name']="";
if (isset($database->user->data->name_first)) {
	$query_order['name_last']="";
	$query_order['name_first']="";
  } else {
	$query_order['name']="desc";
}
$query =
	"select * from user \n" .
    "where disabled=1 \n" .
    "and status='Active'\n" .
	$database->order($query_order);
$database->user->query($query);
if ($database->user->meta->rows) {
	print "<table class='small border'>\n";
	print "<tr>\n";
	print "<th>Unlock?</th>\n";
	print "<th>Email</th>\n";
	print "<th>Name</th>\n";
	if (isset($database->registry->address->user['company_name'])) print "<th>Company Name</th>\n";
    if (isset($forms->report['unlock_code'])) print "<th>Unlock Code</th>\n";
	print "<th>Last Login</th>\n";
	print "</tr>\n";
	while ($database->user->fetch = $database->user->fetch_array()) {
	    $database->user->fetch();
	    print "<tr>\n";
	    print "<td class=center>" . $forms->checkbox(base64_encode("user_id" . "\t" . $database->user->data->id),$database->user->data->id,0) . "</td>\n";
	    print "<td>" . $database->user->data->email . "</td>\n";
	    print "<td>" . $database->user->data->name . "</td>\n";
	    if (isset($database->registry->address->user['company_name'])) print "<td>" . $database->user->data->company_name . "</td>\n";
		if (isset($forms->report['unlock_code'])) {
			if ($database->user->data->last_login) {
	            $unlock_code=dechex($database->user->data->last_login);
	            $options=array();
	            $options['subject']=$database->registry->company->name . " Unlock code";
	            $options['body']="Your unlock code is $unlock_code";
                $text=fn_href($unlock_code,"mailto:" . $database->user->data->email,$options);
			  } else {
              	$text="&nbsp;";
            }
        	print "<td class=center>$text</td>\n";

		}
	    print "<td class=center>";
        if ($database->user->data->last_login) {
        	print date("m/d/Y h:i A",$database->user->data->last_login);
		  } else {
          	print "&nbsp;";
		}
        print "</td>\n";
	    print "</tr>\n";
	}
	$database->user->free_result();
	print "</table>\n";
	print "<p class='center'>" . $forms->submit("Update") . "</p>\n";
  } else {
	print "<p class='center standard'>There are no locked accounts</p>\n";
}
print $forms->close();
$menu->copyright(FALSE);
?>