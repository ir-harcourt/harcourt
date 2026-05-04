<?php
include_once "../Webpages/Header_Footer/scs_header.php";
include_once "../classes/user.php";
include_once "../classes/email_message.php";
$menu->title="New User Setup";
$database->connect();
$_POST=fn_stripslashes($_POST);
$report['action']=$_POST['action'];
if ($_SESSION['impersonate']) {
  	$database->user->data=$_SESSION['user'];
	$menu->messages[]="User impersonating / no update allowed";
  } else {
	$database->user->read($_SERVER['REMOTE_ADDR'],"ip");
}

switch (TRUE) {
  case $report['action']:
	$report['spam']=FALSE;
	$post_spam=$_POST;
    foreach ($post_spam as $key => $value) {
    	$post_spam[$key]=strip_tags($value);
    }
	if ($post_spam != $_POST) {
		$report['spam']=TRUE;
    	break 2;
	}
  	$database->user->data->first_name=$_POST['first_name'];
    if (!$database->user->data->first_name) $menu->errors['first_name']="First name cannot be blank";
  	$database->user->data->last_name=$_POST['last_name'];
    if (!$database->user->data->last_name) $menu->errors['last_name']="Last name cannot be blank";
  	$database->user->data->company=$_POST['company'];
    if (!$database->user->data->company) $menu->errors['company']="Company name cannot be blank";
    $email=new email_class($_POST['email']);
  	$database->user->data->email=$email->address;
    if ($email->error) $menu->errors['email']="Email error: " . $email->error;
    if (!sizeof($menu->errors)) {
		$database->user->data->status="Pending";
        $database->user->data->last_login=strtotime("now");
        if (!$_SESSION['impersonate']) $database->user->update($database->user->meta->rows);
		$email_message=new email_message_class();
		$email_message->from=$database->user->data->email;
        $email_message->from_name=$database->user->data->first_name . " " . $database->user->data->last_name;
        $email_message->to="dale@harcourtind.com";
        $email_message->to_name="Dale";
        $email_message->subject="New user login request";
        $email_text=
	        "First name:  " . $database->user->data->first_name . "\n" .
	        "Last name:   " . $database->user->data->last_name . "\n" .
	        "Company:    " . $database->user->data->company . "\n" .
	        "Email:      " . $database->user->data->email . "\n" .
	        "Browser:    " . $_SERVER['HTTP_USER_AGENT'] . "\n" .
	        "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n" .
			"DNS Name: "   . gethostbyaddr($_SERVER['REMOTE_ADDR']);
	        "Date:       " . date('m/d/y') . "\n" .
	        "Time:       " . date('H:i:s') . "";
        $email_message->text($email_text);
//        $email_message->send();
	}
}

$menu->head();
switch (TRUE) {
  case ($_SESSION['user']->id):
  case ($report['spam']):
  case (($report['action']) && (!sizeof($menu->errors))):
	$menu->document_viewer(159);
	break;
  default:
	$menu->document_viewer(158);
	if (sizeof($menu->errors)) {
    	print "<p class='large'>Correct the following errors:";
    	foreach ($menu->errors as $value) {
			print "<br>$value\n";
        }
        print "</p>\n";
    }
    break;
}
print "</table>\n";
print "</form>\n";
$menu->copyright();
$database->disconnect();
?>