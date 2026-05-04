<?php
require_once "scs_header.php";
require_once "classes/scsmail.php";
switch (TRUE) {
  case (!$_SESSION['user']->id):
	$text="Email Entry";
    break;
  case (isset($_SESSION['user']->bot_id)):
	fn_url_redirect($menu->page->home->url);
 default:
	$text="New User Setup";
}
$forms->title($text);
unset($forms->message[0]);

$forms->report['action']=$_REQUEST['action'];
$forms->report['return_url']=$_REQUEST['return_url'];

if ($_SESSION['impersonate']) {
  	$database->user->data=$_SESSION['user'];
	$forms->message[]="User impersonating / no update allowed.";
  } else {
	$query=array("select * from user");
    $query[]="where ip=" . fn_escape($_SERVER['REMOTE_ADDR']);
    $query[]="and email=''";
	$database->user->query($query);
    if ($database->user->meta->rows) {
	    $database->user->fetch(TRUE);
	    $database->user->free_result();
	  } else {
	    $database->user->data=new user_data_class();
	}
}
switch ($forms->report['action']) {
  case "":
	$forms->report['email']=$_COOKIE['harcourt'];
	break;
  case "login":
    $email=new email_class($_POST['email'],TRUE);
	$forms->report['email']=$email->address;
    if ($email->error) $forms->error('email');
    if (sizeof($forms->error)) break;
    $menu->cookie($forms->report['email']);
	$database->log->update("user:email");
	if ($forms->report['return_url']) fn_url_redirect($forms->report['return_url']);
	break;
  case "register":
	$database->user->data->first_name=fn_text($_POST['first_name']);
    if (!strlen($database->user->data->first_name)) $forms->error('first_name');
	$database->user->data->last_name=fn_text($_POST['last_name']);
    if (!strlen($database->user->data->last_name)) $forms->error('last_name');
	$database->user->data->company_name=fn_text($_POST['company_name']);
    if (!strlen($database->user->data->company_name)) $forms->error('company_name');
	$database->user->data->name=$database->user->data->first_name . " " . $database->user->data->last_name;
    $email=new email_class($_POST['email'],TRUE);
	$database->user->data->email=$email->address;
    if ($email->error) $forms->error('email');

	$database->user->data->city=fn_text($_POST['city']);
	$database->user->data->state=fn_text($_POST['state']);
	$database->user->data->revenue=fn_text($_POST['revenue']);
	$database->user->data->employees=fn_text($_POST['employees']);

	if ( (sizeof($forms->error)) || ($_SESSION['impersonate']) ) break;
    $database->user->update(FALSE);
	if ($database->user->meta->error) {
    	$forms->message[]=$database->user->meta->error;
    	break;
    }
	$_SESSION['user']=$database->user->data;
    $menu->cookie($database->user->data->email);
	$options=array();
    $options['user_id']=$database->user->data->id;
    $options['email']=$database->user->data->email;
	$options['comment']=array();
	$options['comment'][]="Company name: " . $database->user->data->company_name;
	$options['comment'][]="Name: " . $database->user->data->name;
	$database->log->update("user:signup",$options);
	$mail=new scsmail_class("register",$database->user->data);
	$mail->recipient($database->user->data->email, $database->user->data->name);
	$html=array();
    $html[]="First name: " . $database->user->data->first_name;
    $html[]="Last name: " . $database->user->data->last_name;
    $html[]="Company: " . $database->user->data->company_name;
    $html[]="Email: " . $database->user->data->comment;
    $html[]="Browser: " . $_SERVER['HTTP_USER_AGENT'];
    $html[]="IP Address: " . $_SERVER['REMOTE_ADDR'];
    $html[]="DNS Name: " . gethostbyaddr($_SERVER['REMOTE_ADDR']);
    $html[]="Date/Time: " . date('m/d/Y h:i A');
	$mail->html("<p class='standard'>" . implode("<br>",$html) . "</p>");
	break;
}
if (sizeof($forms->error)) $forms->message[]="Correct " . sizeof($forms->error) . " Error(s)";
$menu->head(array("scs_div"=>FALSE));
$results=array();
$results[]="<div id='login'>";
$results[]="<div id='main-wrapper' class='wrapper'>";
$results[]="<div id='main-wrapper-bg'></div>";
$results[]="<div class='outerContainer'>";
$results[]="<div class='container'>";
$results[]="<div class='half login'>";
switch (TRUE) {
  case ( ($forms->report['action']=="register") && (!sizeof($forms->error)) ):
	$text="Thank you!";
    break;
  case ($_SESSION['user']->id):
	$text="Login";
	break;
  default:
	$text="New User?";
}
$results[]="<form id='register' method='post' name='submitNewUser'>";
$results[]="<h2 class='page-subtitle editable h2-login'>" . $text . "</h2>";
if (sizeof($forms->message)) $results[]="<p>" . implode("<br>\n",$forms->message) . "</p>";
switch (TRUE) {
  case ( ($forms->report['action']=="register") && (!sizeof($forms->error)) ):
	$results[]="<p>Your request is being processed and you will be contacted further.</p>";
	break;
  case ( ($forms->report['action']=="login") && (!sizeof($forms->error)) ):
	$results[]="<p>You may now access our products pages.</p>";
	break;
  case ($_SESSION['user']->id):
	$results[]=$forms->hidden("action","login");
	$results[]="<h4 class='solutions-h4'>Welcome " . $_SESSION['user']->company_name . "</h4>";
	$results[]="<p>To access your products portal, please enter your email address for verification.</p>";
	$results[]="<div>";
	$results[]="<label for='email'>Email Address</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text("email",$forms->report['email'],50,50) . "</div>";
	$results[]="<div class='half second'>";
	$results[]="<button class='red-button submit'>Submit</button>";
	$results[]="</div>";
	$results[]="<p class='disclaimer'>Disclaimer: This should be a one-time verification only. Your email address will be kept strictly confidential and will not be disclosed to a third party or used outside the terms of our Privacy Policy.</p>";
	break;
  default:
	$results[]=$forms->hidden("action","register");
	$results[]=$forms->hidden("return_url",$forms->report['return_url']);
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='first_name'>First Name</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('first_name',$database->user->data->first_name,50,50) . "</div>";
	$results[]="</div>";
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='last_name'>Last Name</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('last_name',$database->user->data->last_name,50,50) . "</div>";
	$results[]="</div>";
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='company'>Company</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('company_name',$database->user->data->company_name,60,60) . "</div>";
	$results[]="</div>";
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='email'>Email Address</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text("email",$database->user->data->email,60,60) . "</div>";
	$results[]="</div>";
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='city'>City</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('city',$database->user->data->city,60,60) . "</div>";
	$results[]="</div>";
	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='state'>State</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('state',$database->user->data->state,20,20) . "</div>";
	$results[]="</div>";

	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='revenue'>Annual Revenue</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('revenue',$database->user->data->revenue,20,20) . "</div>";
	$results[]="</div>";

	$results[]="<div class='register'>";
	$results[]="<div>";
	$results[]="<label for='revenue'>Employees</label>";
	$results[]="</div>";
	$results[]="<div class='half first'>" . $forms->text('employees',$database->user->data->employees,20,20) . "</div>";
	$results[]="</div>";

	$results[]="<div class='register'>";
	$results[]="<div class='half second'>";
	$results[]="<button class='red-button submit'>Submit</button>";
	$results[]="</div>";
	$results[]="</div>";
	$results[]="<p>Please complete this form to become an approved Harcourt &reg; user. Once you have completed the form you will be contacted further.</p>";
	$results[]="<p>Thank you!</p>";
}
$results[]=$forms->close();
$results[]="</div> <!-- .half login -->";
$results[]="</div> <!-- .container -->";
$results[]="</div> <!-- .outerContainer -->";
$results[]="</div> <!-- #main-wrapper -->";
$results[]="</div> <!-- #login -->";
print implode("\n",$results);
$menu->copyright();
?>