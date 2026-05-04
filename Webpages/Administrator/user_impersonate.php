<?php
include "scs_header.php";
include_once "classes/user.php";
$menu->access();
$menu->title="User Impersonate";
$menu->messages[]=$menu->title;
$database->connect();
$report['action']=$_POST['action'];
switch ($report['action']) {
  case ("update"):
	switch ($_POST['impersonate_type']) {
	  case "New":
		$database->user->data = new user_data_class();
	    $database->user->data->ip="255.255.255.255";
        break;
	  case "Pending":
	  case "Active":
	  case "Denied":
		$database->user->data = new user_data_class();
        $database->user->data->id=-1;
	    $database->user->data->ip="255.255.255.255";
	    $database->user->data->company="Unknown company";
	    $database->user->data->first_name=$_POST['impersonate_type'];
	    $database->user->data->last_name="User";
	    $database->user->data->name=$_POST['impersonate_type'] . " User";
	    $database->user->data->status=$_POST['impersonate_type'];
		break;
      default:
  		$database->user->read($_POST['user_id'],"id");
	}
	$_SESSION['user']=$database->user->data;
	$database->document->session();
	$_SESSION['initial']=TRUE;
    $_SESSION['impersonate']=TRUE;
    if ($_POST['reset_cookie']) {
		setcookie("email","",(time() - 3600));
//		setcookie("email","",(time() - 3600),"/","harcourtind.com");
        unset($_SESSION['uemail']);
	}
	session_write_close();
    $menu->landing_page();
	break;
}
$menu->head();
$menu->message();

print "<form name=scs_form method=post>\n";
print "<input type=hidden name=action value='update'>\n";
print "<p class=large><b>Select the user you wish to log in as:</b><br>\n";
print "<input type=radio name=impersonate_type value='Registered' checked> Registered user \n";
$query =
	"select * from user \n" .
	"order by company,ip";
$database->user->query($query);
print "<select name=user_id>\n";
$first_pass=TRUE;
while ($database->user->fetch = mysql_fetch_array($database->user->meta->handle)) {
    $database->user->fetch();
	switch (TRUE) {
	  case ($first_pass):
	  case ($database->user->data->company != $company):
      	if ($first_pass) {
        	$first_pass=FALSE;
		  } else {
          	print "</optgroup>\n";
		}
        $company=$database->user->data->company;
		print "<optgroup label=" . fn_escape($company) . ">\n";
    }
    if ($database->user->data->ip == $_SERVER['REMOTE_ADDR']) {
    	$selected="selected";
      } else {
    	$selected="";
    }
    print "<option value='" . $database->user->data->id . "' $selected>" . $database->user->data->ip . " (" . $database->user->data->status . ")</option>\n";
}
$database->user->free_result();
if (!$first_pass) print "</optgroup>\n";
print "</select>\n";
print "<br><input type=radio name=impersonate_type value='New'> 1st time access\n";
print "<br><input type=radio name=impersonate_type value='Pending'> Pending user\n";
print "<br><input type=radio name=impersonate_type value='Active'> Active user\n";
print "<br><input type=radio name=impersonate_type value='Denied'> Denied user\n";
print "</p>\n";
print
	"<p  class=large>Current cookie: " .
    "<b>" . $_COOKIE['email'] . "</b>" .
    "<br>Reset? " .
	"<input type=checkbox name=reset_cookie value='1'>" .
    "</p>\n";
print "<p><input type=submit class=fbutton value='Submit'>\n";
print "</form>\n";
$database->disconnect();
$menu->copyright();
?>