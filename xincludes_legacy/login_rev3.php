<?php
require_once "classes/scsmail_rev1.php";
$login=new scs_login_class();
class scs_login_class {
	var $action;
    var $email;
    var $password;
    var $unlock_code;
    var $unlock_date;
    var $login_timestamp=0;
    var $remember_me=TRUE;
	var $referer=FALSE;
    var $popup=FALSE;
    var $login=FALSE;
    var $debug=FALSE;
    var $return_url;
    var $error=array();
	function scs_table_version() {
		$results=array();
        $results['07/16/2019']="Add user.status CRON";
        $results['01/25/2019']="Initial release based on login_rev2 11/27/2018";
        return $results;
    }
	function __construct() {
    global $database, $forms, $menu;
		$forms->title("Account Login");
        if ($this->debug) $forms->message[]="Debugging on";

	    switch (TRUE) {
	      case (!$_SESSION['user']->id):
	        break;
	      case ($_GET['action']=="logoff"):
	        session_destroy();
	        fn_url_redirect($menu->page->home);
	      case (isset($menu->page->dashboard)):
	        fn_url_redirect($menu->page->dashboard);
	      default:
	        fn_url_redirect($menu->page->home);
	    }
	    switch (TRUE) {
	      case ($_POST['source']=="scs_menu"):
	        $this->action="current";
	        if ($database->registry->login->cookie) list($email,$this->login_timestamp)=explode("\t",$_COOKIE[$database->registry->login->cookie]);
	        $this->email=trim($_POST['login_username']);
	        $this->password=trim($_POST['login_password']);
	        $this->remember_me=TRUE;
	        $this->referer=TRUE;
	        break;
	      case ($_SERVER['REQUEST_METHOD']=="POST"):
	        $this->action=$_POST['action'];
	        $this->email=trim($_POST['email']);
	        $this->password=trim($_POST['password']);
	        $this->remember_me=$_POST['remember_me'];
	        $this->popup=$_POST['popup'];
	        $this->return_url=$_POST['return_url'];
	        break;
	      case ($_SERVER['REQUEST_METHOD'] == "GET"):
	        $this->remember_me=TRUE;
	        $this->popup=$_GET['popup'];
	        $this->return_url=$_GET['return_url'];
	        $this->referer=TRUE;
	        if ($_COOKIE[$database->registry->login->cookie]) {
	            list($this->email,$this->login_timestamp)=explode("\t",$_COOKIE[$database->registry->login->cookie]);
	            if (!preg_match("/@/",$this->email)) $this->email="";
	        }
	        break;
	    }
	    switch (TRUE) {
	      case ($this->return_url):
	        break;
	      case (!$_SERVER['HTTP_REFERER']):
	      case (!preg_match("/^(http|https)" . preg_quote("://" . $_SERVER['HTTP_HOST'] . "/","/") . "/i",$_SERVER['HTTP_REFERER'])):
	        $this->return_url=$menu->page->home->url;
	        break;
	      default:
	        $this->return_url=$_SERVER['HTTP_REFERER'];
	    }
        if ($this->action == "new") fn_url_redirect($menu->page->myaccount);
        $this->auto_login();
		if (!$this->login) $this->error=$this->verify();
        if ($this->login) $this->login();
        if ( ($this->debug) && ($this->error) ) $forms->message[]=$this->error;
		$this->html();
    }
    function auto_login() {
    global $database, $forms, $menu;
		if ( ($_SERVER['REQUEST_METHOD'] != "GET") || (!strlen($this->email)) || (!$this->login_timestamp) ) return;
        $query=array("select * from user");
        $query[]="where email=" . fn_escape($this->email);
        $query[]="and auto_login=1";
        $query[]="and login_timestamp=" . fn_escape($this->login_timestamp);
        $query[]="and status='Active'";
		$database->user->query($query);
		$database->user->fetch(TRUE);
        if ($database->user->meta->rows) $this->login=TRUE;
    }
    function verify() {
    global $database, $forms, $menu;
		if ($_SERVER['REQUEST_METHOD'] != "POST") return;
		if (!strlen($this->email)) {
        	$forms->error('email');
			return "Email missing";
		}
        $query_where=array();
        $query_where[]=new query_where("email","=",$this->email);
        $query=array("select");
        $query[]="password(" . fn_escape($this->password) . ") as 'login_password',";
        $query[]="user.* from user";
        $query[]=$database->where($query_where);
        $database->user->query($query);
        $database->user->fetch(TRUE);
        $database->user->free_result();
	    switch (TRUE) {
	      case (!$database->user->meta->rows):
	        $forms->error('email','',"Invalid Email Address (#F1L)");
	        return "Unknown email";
          case (preg_match("/reject|forgotten|cron/i",$database->user->data->status)):
	        $forms->error('email','',"Invalid Email Address (#F1R)");
	        return "Status: " . $database->user->data->status;
	      case ($this->action == "forgot"):
	        $mail=new scsmail_class("forgot",$database->user->data);
	        $mail->user_id=$database->user->data->id;
	        $mail->recipient($database->user->data->email,$database->user->data->name);
	        $message="<p class='standard'>Your unlock code is: <b>" . dechex($database->user->data->login_timestamp) . "</b></p>";

	        $mail->html($message);
	        $forms->message[]=$mail->send(FALSE);
	        $this->action="unlock";
	        return "Unlock code sent";
	      case ($this->action == "unlock"):
	      	$this->unlock_code=trim($_POST['unlock_code']);
	        $this->unlock_date=hexdec($_POST['unlock_code']);
	        if ($this->unlock_date != $database->user->data->login_timestamp) {
            	$forms->error('unlock_code','',"Invalid unlock code (#U1L)");
                return "Invalid unlock code";
	        }
            $this->login=TRUE;
			return;

          case (!strlen($this->password)):
          	$forms->error('password');
          	return "Missing password";
	      case ($database->user->data->disabled);
	      	$forms->error('email','a',"Login has been disabled due to excess unsuccessful login attempts (#L" . $database->user->data->login_attempt . "D)");
	        return "Disabled account";
          case ( ($database->user->data->login_delay) && ($database->user->data->login_delay > strtotime("now")) ):
	      	$forms->error('email','a',"Login has been disabled due to excess unsuccessful login attempts (#L" . $database->user->data->login_attempt . "W)");
	        return "Disabled account";
		  case ($database->user->fetch['password'] != $database->user->fetch['login_password']):
			$text=array("L",$database->user->data->login_attempt);
            switch (TRUE) {
              case (!isset($database->user->data->login_delay)):
              case (!$database->registry->login->delay):
              	$text[]="L";
	            $database->user->data->login_attempt++;
	            if ($database->user->data->login_attempt >= $database->registry->login->max) $database->user->data->disabled=1;
                break;
              case ($database->user->data->login_attempt >= $database->registry->login->max):
              	$text[]="S";
				$database->user->data->login_delay=strtotime("+" . $database->registry->login->delay . " minutes");
              	break;
              default:
              	$text[]="L";
	            $database->user->data->login_attempt++;
			}
	      	$forms->error('email','',"Invalid email/password (#" . implode("",$text) . ")");
	        $database->user->update(TRUE);
	        if ($database->user->data->disabled) $database->log->update("User: Disabled","",$database->user->data->id);
	        return "Invalid password";
          default:
          	$this->login=TRUE;
		}
	}
    function login() {
    global $database, $forms, $menu;
    	switch (TRUE) {
//          case (fn_development_server()):
          case (!isset($database->user->data->ip_address)):
          case ( (is_array($database->user->data->ip_address)) && (!sizeof($database->user->data->ip_address)) ):
          case ( (is_array($database->user->data->ip_address)) && (in_array($_SERVER['REMOTE_ADDR'],$database->user->data->ip_address)) ):
          	break;
          default:
          	$this->error="IP address";
            $this->login=FALSE;
            $forms->error('null',"","Invalid email/password (#L01IP)");
            return;
		}
	    $database->user->data->login_attempt=0;
	    $database->user->data->disabled=0;
	    $database->user->data->login_delay=0;
	    $database->user->data->login_timestamp=strtotime("now");
	    $database->user->update(TRUE);
	    $database->user->session($this->remember_me);
	    if (method_exists($database->user,"dashboard")) $database->user->dashboard();
	    switch (TRUE) {
	      case ($this->unlock_code):
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
    	if (method_exists($database->user,"login")) $options=array_merge($options,$database->user->login());
	    switch (TRUE) {
	      case ($this->password == $database->user->constant->password):
	        $options['password']="default";
	        break;
	      case ( ($database->registry->password->reset_date) && ($database->user->data->password_date < $database->registry->password->reset_date) ):
	        $options['password']='reset';
	        break;
	      case ( ($database->registry->password->expiry) && (strtotime(date("Y/m/d",$database->user->data->password_date) . " + " . $database->registry->password->expiry . " days") < strtotime("now")) ):
	        $options['password']='expiry';
	        break;
	      case ($this->action=="unlock"):
	        $options['password']='unlock';
            break;
	    }
	    if (sizeof($options)) {
	        if (isset($database->user->data->role)) {
	            $_SESSION['user']->role="";
	          } else {
	            $_SESSION['user']->type="";
	        }
	        $_SESSION['myaccount']=$options;
	    }
    	switch (TRUE) {
	  	  case (sizeof($options)):
	      case ($this->action == "unlock"):
	        $url=$menu->page->myaccount->page;
	        break;
	      case ($this->popup):
	        $url="";
	        break;
	      case ((($database->registry->login->success == "return_url") && $this->return_url)):
	        $url=$this->return_url;
	        break;
	      case ( (!$database->registry->login->success) && (isset($menu->page->dashboard)) ):
	        $url=$menu->page->dashboard->page;
	        break;
	      default:
	        $url=$menu->page->home->page;
	    }
    	switch (TRUE) {
	  	  case (isset($menu->page->login_verify->page)):
			fn_url_redirect($menu->page->login_verify->page,array("_login"=>base64_encode($database->user->data->id . "\t" . $database->user->data->login_timestamp . "\t" . $url)));
			break;
          case ($this->popup):
	    	print "<script type='text/javascript'>window.close();</script>";
			break;
       	  default:
			fn_url_redirect($url);
		}
    }
    function html() {
    global $database, $forms, $menu;
	    if ($this->popup) {
	        $menu->head(FALSE);
	      } else {
	        $menu->head();
	    }
	    $forms->message();
	    print $forms->open();
	    print $forms->hidden("popup", $this->popup);
	    print $forms->hidden("return_url", $this->return_url);
        if ( (!$this->action) && ($this->email) ) $this->action="current";

        $text=array("<b>Please enter your Email Address:</b>");
        $text[]=$forms->text("email",$this->email,40,60,"email");
	    print "<p>" . implode("<br>",$text) . "</p>";
        $text=array("<b>Do you already have an account?</b>");
	    print "<p>" . implode(" ",$text) . "</p>";
        $text=array($forms->radio("action","current",$this->action,array("onclick"=>"fn_action(this.value);")));
        $text[]="Yes, my password is: " . $forms->text("password",$this->password,12,12,"password",array("onfocus"=>"fn_current();"));
        $text[]="Remember me? " . $forms->checkbox("remember_me",1,$this->remember_me);
	    print "<p>" . implode(" ",$text) . "</p>";
        $text=array($forms->radio("action","forgot",$this->action,array("onclick"=>"fn_action(this.value);")));
        $text[]="Yes, but I forgot my password";
	    print "<p>" . implode(" ",$text) . "</p>";
		$text=array($forms->radio("action","unlock",$this->action,array("onclick"=>"fn_action(this.value);")));
        $text[]="Enter unlock code " . $forms->text("unlock_code",$this->unlock_code,8,8,"",array("onfocus"=>"fn_unlock();"));
	    print "<p>" . implode(" ",$text) . "</p>";
        $text=array($forms->radio("action","new",$this->action,array("onclick"=>"fn_action(this.value);")));
        $text[]="No, please sign me up as a new user";
	    print "<p>" . implode(" ",$text) . "</p>";
        print "<p>" . $forms->submit("Login",array("id"=>"login_button")) . "</p>";
	    print $forms->close();

	    $text=array();
	    $text[]="<script type='text/javascript'>";
	    $text[]="function fn_action(value) {";
	    $text[]="    switch (value) {";
	    $text[]="      case 'forgot':";
	    $text[]="       document.getElementById('login_button').innerHTML='Retrieve unlock code';";
	    $text[]="       break;";
	    $text[]="      case 'unlock':";
	    $text[]="       document.getElementById('login_button').innerHTML='Enter unlock code';";
	    $text[]="       break;";
	    $text[]="      case 'new':";
	    $text[]="       document.getElementById('login_button').innerHTML='Create new account';";
	    $text[]="       break;";
	    $text[]="     default:";
	    $text[]="       document.getElementById('login_button').innerHTML='Login';";
	    $text[]="    }";
	    $text[]="}";
	    $text[]="function fn_current() {";
	    $text[]="    document.getElementById('action_current').checked=true;";
	    $text[]="    fn_action('current');";
	    $text[]="}";
	    $text[]="function fn_unlock() {";
	    $text[]="    document.getElementById('action_unlock').checked=true;";
	    $text[]="    fn_action('unlock');";
	    $text[]="}";
		$text[]="fn_action(" . fn_escape($this->action) . ");";
	    $text[]="</script>";
        print implode("\n",$text) . "\n";
        $menu->copyright();
    }
}
?>