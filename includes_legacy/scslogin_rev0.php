<?php
include_once "classes/scsmail.php";
$forms->title("Account Login");
if (isset($database->user->constant->username)) {
    $forms->report['login_method']="L";
    $forms->report['login_text']="User Name";
  } else {
    $forms->report['login_method']="E";
    $forms->report['login_text']="Email Address";
}
if ( (isset($database->blacklist)) && (isset($database->registry->www->blacklist_ip)) ) {
	$database->blacklist->read(ip2long($_SERVER['REMOTE_ADDR']),"ip_address");
    if ($database->blacklist->data->type != "White List") $forms->report['blacklist']=$database->blacklist->data->type;
}
switch (TRUE) {
  case (($_SESSION['user']->id) && ($_GET['action']=="logoff")):
	session_destroy();
	fn_url_redirect($menu->page->home);
  case (($_SESSION['user']->id) && ($database->registry->login->success=="menu") && (isset($menu->page->menu))):
	fn_url_redirect($menu->page->menu);
  case ($_SESSION['user']->id):
	fn_url_redirect($menu->page->home);
  case ($_POST['source']=="scs_menu"):
	$forms->report['action']="current";
	if ($database->registry->login->cookie) list($email,$forms->report['last_login'])=explode("\t",$_COOKIE[$database->registry->login->cookie]);
	$forms->report['username']=trim($_POST['login_username']);
	$forms->report['password']=trim($_POST['login_password']);
	$forms->report['remember_me']=TRUE;
	$forms->report['return_url']=$_POST['HTTP_REFERER'];
	break;
  case ($_SERVER['REQUEST_METHOD']=="POST"):
	$forms->report['action']=$_POST['action'];
    $forms->report['username']=trim($_POST['username']);
    $forms->report['password']=trim($_POST['password']);
	$forms->report['remember_me']=$_POST['remember_me'];
	$forms->report['popup']=$_POST['popup'];
	$forms->report['return_url']=$_POST['HTTP_REFERER'];
    break;
  case ($_SERVER['REQUEST_METHOD'] == "GET"):
	$forms->report['remember_me']=1;
	$forms->report['popup']=$_GET['popup'];
	$forms->report['return_url']=$_SERVER['HTTP_REFERER'];
	if ($_COOKIE[$database->registry->login->cookie]) {
	    list($forms->report['username'],$forms->report['last_login'])=explode("\t",$_COOKIE[$database->registry->login->cookie]);
        if (!preg_match("/@/",$forms->report['username'])) $forms->report['username']="";
	}
    break;
}
if ($forms->report['username']) {
	$query_where=array();
	if ($forms->report['login_method']=="L") {
		$query_where[]=new query_where("username","=",$forms->report['username']);
	  } else {
		$query_where[]=new query_where("email","=",$forms->report['username']);
    }
	$query=
	    "select \n" .
		"password(" . fn_escape($forms->report['password']) . ") as 'login_password', \n" .
        "user.* from user \n" .
		$database->where($query_where);
	$database->user->query($query);
	$database->user->fetch(TRUE);
	$database->user->free_result();
}
switch (TRUE) {
  case (($_SERVER['REQUEST_METHOD'] == "GET") && (!$forms->report['username'])):
  case (($_SERVER['REQUEST_METHOD'] == "GET") && (!$database->user->data->auto_login)):
 	break;
  case ($forms->report['action'] == "new"):
	fn_url_redirect($menu->page->myaccount);
    die;
  case (isset($forms->report['blacklist'])):
	$forms->error('username','',$forms->report['login_text'] .  " (#BX" . substr($forms->report['blacklist'],0,1) . ")");
	break;
  case (!$forms->report['username']):
	$forms->error('username','',$forms->report['login_text'] . " cannot be blank (#B1" . $forms->report['login_method'] . ")");
	break;
  case ($forms->report['action']=="forgot"):
    switch (TRUE) {
	  case (!$database->user->meta->rows):
      case ($database->user->data->status == "Reject"):
		$forms->error('username','',$forms->report['login_text'] .  " (#F1" . $forms->report['login_method'] . ")");
    	break;
      case ($database->user->data->status == "Blacklist"):
		$forms->error('username','',$forms->report['login_text'] .  " (#F2" . $forms->report['login_method'] . ")");
    	break;
      default:
      	$mail=new scsmail_class("forgot",$database->user->data);
        $mail->user_id=$database->user->data->id;
		$mail->recipient($database->user->data->email,$database->user->data->name);
        $message="<p class='standard'>";
        if ($forms->report['login_method']=="L") $message .= "Your username is: <b>" . $database->user->data->username . "</b><br>";
        $message .= "Your unlock code is: <b>" . dechex($database->user->data->last_login) . "</b></p>";
 		$mail->html($message);
		$forms->message[]=$mail->send(FALSE);
		$forms->report['action']="unlock";
    }
    break;
  case ($forms->report['action'] == "unlock"):
	$forms->report['unlock_code']=trim($_POST['unlock_code']);
	$forms->report['unlock_date']=hexdec($_POST['unlock_code']);
    if ($forms->report['unlock_date'] == $database->user->data->last_login) {
		$forms->report['login']="Login";
	  } else {
		$forms->error('unlock_code','',"Invalid unlock code (#U1" . $forms->report['login_method'] . ")");
    }
	break;
  case (!$database->user->meta->rows):
  case ($database->user->data->status == "Reject"):
    $forms->error('username','',"Invalid " . $forms->report['login_text'] .  " (#R1" . $forms->report['login_method'] . ")");
    break;
  case ($database->user->data->status == "Blacklist"):
    $forms->error('username','',"Invalid " . $forms->report['login_text'] .  " (#R2" . $forms->report['login_method'] . ")");
    break;
  case ( ($database->user->data->ip_address) && ($_SERVER['REMOTE_ADDR'] != long2ip($database->user->data->ip_address)) && (!fn_development_server()) ):
    $forms->error('username','',"Invalid " . $forms->report['login_text'] .  " (#IP1" . $forms->report['login_method'] . ")");
    $results=array();
    $results[]="Required login IP " . long2ip($database->user->data->ip_address);
    if ($database->user->fetch['password'] == $database->user->fetch['login_password']) $results[]="Correct password supplied";
    $database->log->update("User: IP Deny",$results,$database->user->data->id);
    break;

  case ($database->user->data->disabled);
    $forms->error('username','',"Login has been disabled due to excess unsuccessful login attempts (#L" . $database->user->data->login_attempt . "D)");
    break;
  case (($database->user->data->auto_login) && ($database->user->data->last_login == $forms->report['last_login'])):
  case ($database->user->fetch['password']==$database->user->fetch['login_password']):
	$forms->report['login']="Login";
    break;
  case (($database->user->data->auto_login) && ($_SERVER['REQUEST_METHOD']=="GET")):
	$forms->message[]="Cannot auto login (#A1P)";
    $forms->message[]="Last login from another computer or browser";
    break;
  case (!$forms->report['password']):
	$forms->error('password','',"Password cannot be blank (#B1P)");
	break;
  default:
    $forms->error('username','',"Invalid email/password (#L" . $database->user->data->login_attempt . $forms->report['login_method'] . ")");
    $database->user->data->login_attempt++;
    if ($database->user->data->login_attempt >= $database->registry->login->max) $database->user->data->disabled=1;
	$database->user->update(TRUE);
    if ($database->user->data->disabled) $database->log->update("User: Disabled","",$database->user->data->id);
    break;

}
if ($forms->report['login']) {
    $database->user->data->login_attempt=0;
    $database->user->data->disabled=0;
    $database->user->data->last_login=strtotime("now");
	$database->user->update(TRUE);
	$database->user->session($forms->report['remember_me']);
    switch (TRUE) {
	  case (method_exists($menu,"user_menu")):
		$menu->user_menu();
		break;
	  case (method_exists($database->user,"menu")):
		$database->user->menu();
		break;
	}
    switch (TRUE) {
	  case ($forms->report['unlock_code']):
      	$action="Login w/unlock code";
		break;
	  case ($database->user->data->auto_login):
		$action="Login automatic";
		break;
	  default:
		$action="Login";
    }
    $database->log->update("User: $action");
    $options=array();
	if (isset($database->registry->address->user)) {
		foreach ($database->registry->address->user as $field => $required) {
			if (($required == "required") && (!$database->user->data->{$field})) $options[$field]=TRUE;
        }
    }
    switch (TRUE) {
      case ($forms->report['password'] == $database->user->constant->password):
		$options['password']="default";
        break;
	  case ( ($database->registry->password->reset_date) && ($database->user->data->password_date < $database->registry->password->reset_date) ):
      	$options['password']='reset';
        break;
	  case ( ($database->registry->password->expiry) && (strtotime(date("Y/m/d",$database->user->data->password_date) . " + " . $database->registry->password->expiry . " days") < strtotime("now")) ):
      	$options['password']='expiry';
        break;
	  case ($forms->report['action']=="unlock"):
      	$options['password']='unlock';
	}
    if (sizeof($options)) {
    	$_SESSION['user']->type="";
        $_SESSION['myaccount']=$options;
	}
    switch (TRUE) {
	  case (sizeof($options)):
	  case ($forms->report['unlock_code']):
		if ($forms->report['return_url']) $options['return_url']=$forms->report['return_url'];
		$url=$menu->page->myaccount->page;
        break;
      case ($forms->report['popup']):
      	$url="";
		break;
      case ((($database->registry->login->menu == "return_url") && $forms->report['return_url'])):
		$url=$forms->report['return_url'];
        break;
      case ((!$database->registry->login->menu) && (isset($menu->page->menu))):
		$url=$menu->page->menu->page;
        break;
      default:
		$url=$menu->page->home->page;
    }
    switch (TRUE) {
	  case (isset($menu->page->login_verify->page)):
		fn_url_redirect($menu->page->login_verify->page,array("_login"=>base64_encode($database->user->data->id . "\t" . $database->user->data->last_login . "\t" . $url)));
		break;
      case ($forms->report['popup']):
	    print "<script type='text/javascript'>\n";
	    print "window.close();\n";
	    print "</script> \n";
		break;
       default:
		fn_url_redirect($url);
    }
}
if ($forms->report['popup']) {
	$menu->head(FALSE);
  } else {
	$menu->head();
}
$forms->message();
print $forms->open();
print $forms->hidden("popup", $forms->report['popup']);
print $forms->hidden("return_url", $forms->report['return_url']);
print "<p><b>Please enter your " . $forms->report['login_text'] . ":</b><br>\n";
print $forms->text("username",$forms->report['username'],40,60,"email");
print "</p>\n";
print
	"<p><b>Do you already have an account?</b><br>\n" .
    $forms->radio("action","current",$forms->report['action'],array("onclick"=>"fn_action(this.value);")) .
    " Yes, my password is: " . $forms->text("password",$forms->report['password'],12,12,"password",array("onfocus"=>"fn_current();")) .
    " Remember me? " . $forms->checkbox("remember_me",1,$forms->report['remember_me']) . "</p>\n";
print
	"<p>" . $forms->radio("action","forgot",$forms->report['action'],array("onclick"=>"fn_action(this.value);")) .
	" Yes, but I forgot my password</p>\n";
print
	"<p>" . $forms->radio("action","unlock",$forms->report['action'],array("onclick"=>"fn_action(this.value);")) .
	" Enter unlock code " . $forms->text("unlock_code",$forms->report['unlock_code'],8,8,"",array("onfocus"=>"fn_unlock();")) . "</p>\n";
print
	"<p>" . $forms->radio("action","new",$forms->report['action'],array("onclick"=>"fn_action(this.value);")) .
	" No, please sign me up as a new user</p>\n";
print "<p>" . $forms->submit("",array("id"=>"login_button")) . "</p>\n";
print $forms->close();
?>
<script type='text/javascript'>
function fn_action(value) {
	switch (value) {
      case 'forgot':
		document.getElementById('login_button').value='Retrieve unlock code';
		break;
      case 'unlock':
		document.getElementById('login_button').value='Enter unlock code';
		break;
      case 'new':
		document.getElementById('login_button').value='Create new account';
		break;
      default:
		document.getElementById('login_button').value='Login';
    }
}
function fn_current() {
	document.getElementById('action_current').checked=true;
	fn_action("current");

}
function fn_unlock() {
	document.getElementById('action_unlock').checked=true;
	fn_action("unlock");

}
<?php
print "fn_action('" . $forms->report['action'] . "');\n";
print "</script>\n";
$menu->copyright();
?>