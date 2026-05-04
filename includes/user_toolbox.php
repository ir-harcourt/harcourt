<?php
require_once "classes/scsmail_rev1.php";
require_once "recaptcha.php";
require_once "map_rev3.php";
/* Mandatory fields
id
email
status
disabled
login_attempt
login_delay
login_timestamp
auto_login
*/
class user_toolbox_class {
	var $function;
	var $options;
	var $action;
    var $cpqdomain;
    var $email;
    var $password;
    var $password_reset=FALSE;
    var $unlock_code;
    var $login_timestamp=0;
    var $remember_me=TRUE;
	var $referer=FALSE;
    var $login=FALSE;
    var $error;
    var $tab_name;
    var $tab_count=0;
	var $recaptcha;
    var $message;
    var $mailform_code;
    var $response=array();
    var $trace=array();
	function scs_table_version($function="") {
		$results=array();
        switch ($function) {
          case "global":
            $results['08/01/2025']="Add local:js, local:tabs";
            $results['03/24/2025']="Add debug";
            $results['01/28/2025']="Add cpqdomain";
            $results['11/04/2024']="Add global scripts";
            $results['02/21/2024']="Add global js";
        	$results['07/03/2023']="Add discount";
        	$results['03/26/2023']="Create tabs:info if missing, add template";
        	$results['07/12/2021']="Initial release";
          	break;
          case "login":
            $results['08/21/2025']="Improve flow";
            $results['03/19/2025']="Autologin bug";
            $results['01/28/2025']="Add confirm";
        	$results['03/30/2023']="Add recaptcha score to event log";
          	$results['10/07/2022']="Registry forgot";
        	$results['08/02/2022']="New recaptcha";
        	$results['03/04/2022']="Log disabling";
        	$results['09/22/2021']="Initial release";
            break;
          case "initial_timestamp":
        	$results['07/19/2021']="Initial release";
            break;
          case "purge":
        	$results['07/19/2021']="Initial release";
            break;
          case "unlock":
        	$results['08/08/2025']="Add dashboard link";
        	$results['07/11/2021']="Initial release based on user_unlock_rev0 09/13/2015";
            break;
          case "export":
        	$results['09/16/2025']="Add local";
        	$results['07/12/2021']="Initial release";
            break;
          case "approve":
            $results['02/24/2025']="Add cpqdomain add";
            $results['01/28/2025']="Add cpqdomain";
        	$results['06/11/2024']="Add mysql_duplicate";
        	$results['10/07/2022']="Add approval message";
        	$results['04/06/2022']="Initial release";
            break;
          case "maintain":
            $results['07/31/2025']="Add list/lookup button";
            $results['02/28/2025']="Add options:cad_code";
        	$results['06/11/2024']="Add mysql_duplicate";
        	$results['03/31/2023']="Add page_break_class";
        	$results['06/02/2022']="Add api_key";
        	$results['05/05/2022']="Initial release";
            break;
          case "myaccount":
            $results['08/21/2025']="Add myaccount_return";
            $results['02/28/2025']="Add options:cad_code";
        	$results['01/28/2025']="Integrate cpqdomain";
        	$results['12/15/2024']="Add status_approve";
        	$results['03/30/2023']="Add recaptcha score to event log";
            $results['10/07/2022']="Add discount_pct";
        	$results['08/12/2022']="Add new user email";
        	$results['08/02/2022']="Add recaptcha";
        	$results['05/05/2022']="Initial release";
            break;
          case "impersonate":
        	$results['01/31/2024']="Home page landing w/stop impersonate";
        	$results['11/13/2023']="Add tabs";
        	$results['03/28/2023']="Add verify";
        	$results['09/29/2022']="Show current user_id";
        	$results['06/21/2022']="Initial release";
            break;
        }
        return $results;
    }
	function __construct($function, $role, $options=array()) {
        global $database, $forms, $menu;
    	$this->function=$function;
        $this->role=$role;
        $this->cpqdomain=property_exists($database->registry, "cpqdomain");
        if ( (is_array($database->registry->cpqvariable->document)) && (array_key_exists("user", $database->registry->cpqvariable->document)) ) $this->cpqvariable=new cpqvariable_input_class("user");
        $this->input=new stdClass();
        if ( ($function == "impersonate") & (isset($_SESSION['impersonate'])) ) {
	        $database->user->read($_SESSION['impersonate']);
	        unset($_SESSION['impersonate']);
	        $database->user->session();
            fn_url_redirect($menu->page->home);
		}
    	if (strlen($role)) $database->user->access($role);
		if (!method_exists($this,$function)) die("Invalid function: {$function}");
        $this->options=new stdClass();
        foreach ($options as $key => $value) {
            switch ($key) {
              case "scripts":
                foreach ($value as $type => $items) {
                    $forms->html->script($type, $items);
                }
                break;
              default:
        	    $this->options->$key=$value;
            }
        }
        $local="{$function}_local";
        $ajax_action=$_POST['action'];
        $ajax_global="{$function}_{$ajax_action}_ajax";
        $this->local=(class_exists($local)) ? new $local() : new stdClass;
        if (method_exists($this->local,"js")) $this->options->js=$this->local->js();

        switch (TRUE) {
          case (property_exists($this->options,"tabs")):
            break;
          case (method_exists($this->local,"tabs")):
            $this->options->tabs=$this->local->tabs();
            break;
          default:
            $this->options->tabs=array();
            break;
        }
        if ($this->options->debug) $this->options->tabs['debug']="Debug";
        $forms->report['action']=$_REQUEST['action'];
        if ( (method_exists($this->local,"info")) && (!array_key_exists("info", $this->options->tabs)) ) $this->options->tabs['info']="Info";
        $this->template=new user_data_class();
        switch (TRUE) {
          case (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
        	$this->{$function}();
			break;
          case (method_exists($this,$ajax_global)):
			die(json_encode($this->$ajax_global()));
          case ($_POST['action'] == "rolodex"):
			die($database->shipto->rolodex_ajax());
          case (method_exists($this->local,"ajax")):
          	die( json_encode($this->local->ajax()) );
		  case ( (strlen($ajax_action)) && (method_exists($this,$ajax_action)) ):
          	die( json_encode($this->$ajax_action()) );
          default:
          	die();
        }
    }
    function forms_title($title) {
        global $forms;
        $forms->title($title, key($this->scs_table_version($this->function)));
    }
	function tabs($action) {
        global $database;
        $this->trace[]=__FUNCTION__;
        foreach ($this->options->tabs as $field => $name) {
        	$this->tab_name=$name;
        	$results=array();
            $options=array();
            $options['id']=$field;
            switch (TRUE) {
              case ( ($action == "input") && ($field == "info") ):
              	break;
              case (method_exists($this->local,$field)):
                $results=$this->local->$field($action);
                $this->tab_count=(is_array($this->local->tabs_count)) ? intval($this->local->tabs_count[$field]) : 0;
                break;
              case (method_exists($this,$field)):
                $this->tab_count=0;
                $results=$this->$field($action);
                break;
            }
            if ( ($action == "input") && (is_array($results)) && (sizeof($results)) ) $this->tabs->item($this->tab_name . ( ($this->tab_count) ? " (" . $this->tab_count . ")" : ""), $results, $options);
        }
    }
    function debug($action) {
        if ($action != "input") return;
        $results=array();
        $results[]="<p>Global Class: " . get_class() . "</p>";
        $results[]="<ul>Global Trace:";
        $results[]="<li>" . implode("</li><li>", $this->trace) . "</li>";
        $results[]="</ul>";
        $results[]="<ul>Global Methods:";
        $results[]="<li>" . implode("</li><li>", get_class_methods($this)) . "</li>";
        $results[]="</ul>";
        if (property_exists($this->local, "trace")) {
            $results[]="<p>Local Class: " . get_class($this->local) . "</p>";
            $results[]="<ul>Local Trace:";
            $results[]="<li>" . implode("</li><li>", $this->local->trace) . "</li>";
            $results[]="</ul>";
            $results[]="<ul>Local Methods:";
            $results[]="<li>" . implode("</li><li>", get_class_methods($this->local)) . "</li>";
            $results[]="</ul>";
        }
        return $results;
    }
    function mailform($code) {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
    	$this->mailform_code="";
    	switch (TRUE) {
          case (!strlen($code)):
          case (!property_exists($database->registry->login,"mailform")):
          case (!array_key_exists($code, $database->registry->login->mailform)):
          	return;
		}
        $database->mailform->read($database->registry->login->mailform[$code],"code");
		if ( ($database->mailform->meta->rows) && ($database->mailform->data->active) ) {
        	$this->mailform_code=$database->mailform->data->code;
            return TRUE;
		}
	}
    function discount_pct($options=array()) {
        $this->trace[]=__FUNCTION__;
        global $database, $forms;
        if (!property_exists($this->template,"discount_pct")) return;
        $field=$options['prefix'] . "discount_pct";;
        $results[]="<tr" . (($options['tr']) ? " class=" . $options['tr'] : "") . ">";
        $results[]="<td>Discount</td>";

        $text=array();
        if (method_exists($database->user, "discount_pct")) {
        	$text[]=$forms->select($field,$database->user->discount_pct(),number_format($database->user->data->discount_pct * 100,2),FALSE);
		  } else {
        	$text[]=$forms->text($field,$database->user->data->discount_pct,5,5,"percent:2") . "%";
        }
        if ($forms->error_text[$field]) $text[]=$forms->error_text[$field];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        return implode("",$results);
	}
/* Standard Login */
    function login() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
	    switch (TRUE) {
	      case (!$_SESSION['user']->id):
	        break;
	      case (isset($menu->page->dashboard)):
	        fn_url_redirect($menu->page->dashboard);
	      default:
	        fn_url_redirect($menu->page->home);
	    }
        $forms->error_class="scscpq_login_text_error";
		$this->forms_title("Account Login");
        if ($this->options->debug) $forms->message[]="Debugging on";
		$this->recaptcha=new recaptcha_class("login",$database->registry->recaptcha->login);
        $url=parse_url($_SERVER['HTTP_REFERER']);
        $host=array($url['host']);
        if (strlen($url['port'])) $host[]=$url['port'];
        switch (TRUE) {
          case (isset($_SESSION['local_return'])):
            $_SESSION['return_url']=$_SESSION['local_return'];
            break;
           case (isset($_SESSION['return_url'])):
            break;
           case ($_SERVER['HTTP_HOST'] == implode(":", $host)):
             $_SESSION['return_url']=$_SERVER['HTTP_REFERER'];
            break;
          default:
             $_SESSION['return_url']=$menu->page->home->url;
        }
        if ($_SERVER['REQUEST_METHOD']=="POST") {
        	$this->recaptcha->score();
			switch (TRUE) {
              case ($this->recaptcha->ignore):
	            $database->user->data=new user_data_class();
	            $databsae->user->data->role="";
	            $databsae->user->data->status="Reject";
	            $database->user->session(FALSE);
            	fn_url_redirect($menu->page->home);
                break;
              case ($this->recaptcha->error):
				$this->error=TRUE;
                $forms->message[]="Internal error, please resubmit";
                break;
			  default:
	            $this->action=$_POST['action'];
	            $this->email=trim($_POST['email']);
	            $this->password=trim($_POST['password']);
	            if ($this->action == "unlock") $this->unlock_code=fn_text($_POST['unlock_code'],TRUE);
	            $this->remember_me=$_POST['remember_me'];
	            if ($this->action == "new") fn_url_redirect($menu->page->myaccount);
	            $this->error=$this->login_verify();
			}
		  } else {
          	unset($_SESSION['login_forgot']);
	        $this->remember_me=TRUE;
	        $this->referer=TRUE;
            if ( (strlen($database->registry->login->cookie)) && ($_COOKIE[$database->registry->login->cookie]) ) {
	            list($this->email,$this->login_timestamp)=explode("\t",$_COOKIE[$database->registry->login->cookie]);
	            if (!preg_match("/@/",$this->email)) $this->email="";
            	$this->login_auto();
			}
	    }
		if ($this->login) $this->login_success();
        if ( ($this->options->debug) && ($this->error) ) $forms->message[]=$this->error;
		$this->login_html();
    }
    function login_auto() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		if ( (!strlen($this->email)) || (!$this->login_timestamp) ) return;
	    $query=array("select * from user");
	    $query[]="where email=" . fn_escape($this->email);
	    $query[]="and auto_login=1";
	    $query[]="and login_timestamp=" . fn_escape($this->login_timestamp);
	    $query[]="and status='Active'";
	    $database->user->query($query);
	    $database->user->fetch(TRUE);
        if ($database->user->meta->rows) {
            $this->auto_login=TRUE;
            $this->error=$this->login_verify(TRUE);
        }
    }
    function login_manual() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        $query=array("select");
		$query[]=(($database->registry->password->encoding) ? "passcode" : "password") . " as 'user_passcode',";
        $query[]=$database->user_password_field($this->password) . " as 'login_passcode',";
        $query[]="user.* from user";
		$query[]="where email=" . fn_escape($this->email);
        $database->user->query($query);
        $database->user->fetch(TRUE);
        $database->user->free_result();
    }
    function login_verify($auto_login=FALSE) {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        switch (TRUE) {
          case (!strlen($this->email)):
        	$forms->error("email","Cannot be blank");
			return "Blank email";
          case ( ($this->action == "current") && (!strlen($this->password)) && (!$auto_login) ):
        	$forms->error("password","Cannot be blank");
			return "Blank password";
          case ( ($this->action == "unlock") && (!strlen($this->unlock_code)) ):
          	$forms->error('unlock_code',"Cannot be blank");
            return "Blank unlock code";
		}
        if (!$auto_login) $this->login_manual();
	    switch (TRUE) {
	      case (!$database->user->meta->rows):
	        $forms->error("email","Invalid Email Address (#F1L)");
	        return "Unknown email";
          case (preg_match("/reject|forgotten|cron/i",$database->user->data->status)):
	        $forms->error("email","Invalid Email Address (#F1R)");
	        return "Status: " . $database->user->data->status;
	      case ($this->action == "forgot"):
          	$session=new stdClass();
            $session->count=0;
            $session->email=$this->email;
            $session->unlock_code=($database->registry->password->encoding) ? strtoupper(bin2hex(openssl_random_pseudo_bytes(6))) : $this->unlock_code();
            $session->timestamp=strtotime("now");
            if ($database->registry->password->encoding) $session->expiry=strtotime("+30 minutes");
            if ($this->mailform("forgot")) {
				$_SESSION['login_forgot']=$session;
	            $mail=new scsmail_class($this->mailform_code,$database->user->data);
	            $mail->user_id=$database->user->data->id;
	            $mail->recipient($database->user->data->email,$database->user->data->name);
	            $text=array("Your unlock code is: <b>" . $session->unlock_code . "</b>");
	            if ($database->registry->password->encoding) $text[]="Unlock code expires: " . date("m/d/Y h:i A",$session->expiry);
	            $message=array();
	            $message[]="<p>Email sent: " . date("m/d/Y h:i A",$session->timestamp);
	            $message[]="<p>" . implode("<br>",$text) . "</p>";
	            $mail->html($message);
	            $forms->message[]=$mail->send(FALSE);
	        	return "Unlock code sent";
			  } else {
              	unset($_SESSION['login_forgot']);
              	return "Unlock function not implemented";
			}
	      case ($this->action == "unlock"):
	      	$this->unlock_code=fn_text($_POST['unlock_code'],TRUE);
            switch (TRUE) {
              case (!isset($_SESSION['login_forgot'])):
            	$forms->error('forgot',"Unlock code must be resent (#U3L)");
            	return "Access denied";
              case ($this->email != $_SESSION['login_forgot']->email):
            	unset($_SESSION['login_forgot']);
            	$this->action="forgot";
            	$forms->error('forgot',"Unlock code must be resent (#U4L)");
            	return "Access denied";
              case ($this->unlock_code == $_SESSION['login_forgot']->unlock_code):
            	$this->login=TRUE;
                break;
              case ($_SESSION['login_forgot']->count >= 4):
              case (strtotime("now") > $_SESSION['login_forgot']->expiry):
            	unset($_SESSION['login_forgot']);
            	$forms->error('forgot',"Unlock code expired (#U2L)");
                return "Unlock code expiry";
              default:
            	$forms->error('unlock_code',"Invalid unlock code (#U1L)");
                $_SESSION['login_forgot']->count++;
                return "Invalid unlock code";
	        }
	      case ($database->user->data->disabled);
          	$this->password_reset=TRUE;
	      	$forms->error("forgot","Login has been disabled due to excess unsuccessful login attempts (#L" . $database->user->data->login_attempt . "D)");
	        return "Disabled account";
          case ( ($database->user->data->login_delay) && ($database->user->data->login_delay > strtotime("now")) ):
          	$this->password_reset=TRUE;
	      	$forms->error("email","Login has been disabled due to excess unsuccessful login attempts (#L" . $database->user->data->login_attempt . "W)");
	        return "Disabled account";
		  case ( (!$auto_login) && ($database->user->fetch[password_encoding()] != $database->user->fetch['login_passcode']) ):
			$text=array("L",$database->user->data->login_attempt);
            switch (TRUE) {
              case (!isset($database->user->data->login_delay)):
              case (!$database->registry->login->delay):
              	$text[]="L";
	            $database->user->data->login_attempt++;
	            if ($database->user->data->login_attempt >= $database->registry->login->max) {
                	$database->user->data->disabled=1;
                    $database->log->update("User:Disabled","Disabaled after " . $database->registry->login->max . " attempts", $database->user->data->id);
				}
                break;
              case ($database->user->data->login_attempt >= $database->registry->login->max):
              	$text[]="S";
				$database->user->data->login_delay=strtotime("+" . $database->registry->login->delay . " minutes");
              	break;
              default:
              	$text[]="L";
	            $database->user->data->login_attempt++;
			}
	      	$forms->error("email","Invalid email/password (#" . implode("",$text) . ")");
	        $database->user->update(TRUE);
	        if ($database->user->data->disabled) $database->log->update("User: Disabled","",$database->user->data->id);
	        return "Invalid password";
		  case (!strlen($database->user->fetch[password_encoding()])):
          	$this->password_reset=TRUE;
			$forms->error('forgot',"Password must be reset");
            return "Password expired";
          default:
			$this->login=TRUE;
		}
	}
    function login_success() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		unset($_SESSION['login_forgot']);
	    $database->user->data->login_attempt=0;
	    $database->user->data->disabled=0;
	    $database->user->data->login_delay=0;
	    $database->user->data->login_timestamp=strtotime("now");
	    $database->user->update(TRUE);
        if ($database->user->data->status != "Active") $database->user->data->role="Customer";
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
	    $database->log->update("User:{$action}",$this->recaptcha->comment());
	    $options=array();
	    if (isset($database->registry->address->user)) {
	        foreach ($database->registry->address->user as $field => $required) {
	            if (($required == "required") && (!$database->user->data->{$field})) $options[$field]="Field cannot be blank";
	        }
	    }
    	if (method_exists($database->user,"login")) $options=array_merge($options,$database->user->login());
	    switch (TRUE) {
          case (property_exists($this, "auto_login")):
            break;
	      case ($this->password == $database->user->constant->password):
	      case (!strlen($database->user->fetch[password_encoding()])):
		  case ( ($database->registry->password->encoding == "") && (strlen($database->user->fetch[password_encoding()]) != 41) ):
		  case ( ($database->registry->password->encoding == "Legacy") && (strlen($database->user->fetch[password_encoding()]) != 41) ):
		  case ( ($database->registry->password->encoding == "SHA1") && (strlen($database->user->fetch[password_encoding()]) != 40) ):
		  case ( ($database->registry->password->encoding == "SHA2:256") && (strlen($database->user->fetch[password_encoding()]) != 64) ):
		  case ( ($database->registry->password->encoding == "SHA2:512") && (strlen($database->user->fetch[password_encoding()]) != 128) ):
	        $options['password']="Access limited until password is reset";
	        break;
	    }
	    if (sizeof($options)) {
	    	$_SESSION['user']->role="";
	        $_SESSION['myaccount']=$options;
	    }
    	switch (TRUE) {
	  	  case (sizeof($options)):
	      case ($this->action == "unlock"):
	        $url=$menu->page->myaccount->page;
	        break;
	      case ($database->registry->login->success == "return_url"):
	        $url=$_SESSION['return_url'];
	        break;
	      case ( (!$database->registry->login->success) && (property_exists($menu->page, "dashboard")) ):
	        $url=$menu->page->dashboard->page;
	        break;
        }
        if (!strlen($url)) $url=$menu->page->home->page;
        $return_url=$_SESSION['return_url'];

//die("return url: {$_SESSION['return_url']}");

        unset($_SESSION['local_return'], $_SESSION['return_url']);
    	switch (TRUE) {
          case ($database->user->data->status == "Pending"):
            $_SESSION['return_url']=$return_url;
            fn_url_redirect($menu->page->myaccount->page);
            break;
	  	  case (isset($menu->page->login_verify->page)):
            $_SESSION['return_url']=$return_url;
			fn_url_redirect($menu->page->login_verify->page,array("_login"=>base64_encode($database->user->data->id . "\t" . $database->user->data->login_timestamp . "\t" . $url)));
			break;
       	  default:
			fn_url_redirect($url);
		}
    }
    function login_html() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        print $this->recaptcha->js();
        switch (TRUE) {
          case ($this->action == "forgot"):
          	$this->action="unlock";
          	break;
          case ( ($this->action == "unlock") && (!isset($_SESSION['login_forgot'])) ):
          case ($this->password_reset):
          	$this->action="forgot";
            break;
          case (!$this->action):
          	$this->action="current";
            break;
		}
	    $menu->head();
	    $forms->message();
        print $this->login_css();
	    print $forms->open();
        if ( (!$this->action) && ($this->email) ) $this->action="current";
	    print $forms->hidden("action", $this->action);
        print "<table class=scscpq_login>";
        print "<tr>";
        $text=array("<b>Please enter your Email Address:</b>");
        $text[]=$forms->div($forms->text("email",$this->email,40,60,"email"));
        if ($forms->error_text['email']) $text[]=$forms->div($forms->error_text['email'],array("placeholder"=>"Mandatory","class"=>"scscpq_login_error"));
        print "<td colspan=2>" . implode("",$text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td colspan=2><b>Do you already have an account?</b></td>";
        print "</tr>";
        print "<tr>";
        print "<td class=scscpq_login_left>" . $forms->radio("action","current",$this->action,array("onclick"=>"login_action(this.value);")) . "</td>";
        $content=array();
        $content[]="Yes, my password is: " . $forms->text("password",$this->password,12,12,"password",array("onclick"=>"login_checkbox('current');"));
        $content[]="Remember me? " . $forms->checkbox("remember_me",1,$this->remember_me);
        $text=array(implode("",$content));
        if ($forms->error_text['password']) $text[]=$forms->div($forms->error_text['password'],array("class"=>"scscpq_login_error"));
        print "<td class=scscpq_login_right>" . implode("",$text) . "</td>";
        print "</tr>";
        if ($this->mailform("forgot")) {
	        print "<tr>";
	        print "<td class=scscpq_login_left>" . $forms->radio("action","forgot",$this->action,array("onclick"=>"login_action(this.value);")) . "</td>";
	        $text=array();
	        switch (TRUE) {
	          case ($database->user->data->disabled):
	            $text[]="Email unlock code";
	            break;
	          case ($this->password_reset):
	            $text[]="Email unlock code";
	            break;
	          default:
	            $text[]="Yes, but I forgot my password";
	        }
	        if ($forms->error_text['forgot']) $text[]=$forms->div($forms->error_text['forgot'],array("class"=>"scscpq_login_error"));
	        print "<td class=scscpq_login_right>" . implode("",$text) . "</td>";
	        print "</tr>";
		}
        if (isset($_SESSION['login_forgot'])) {
	        print "<tr>";
	        print "<td class=scscpq_login_left>" . $forms->radio("action","unlock",$this->action,array("onclick"=>"login_action(this.value);")) . "</td>";
	        $text=array("Enter unlock code " . $forms->text("unlock_code",$this->unlock_code,16,16,"",array("onfocus"=>"login_checkbox('unlock');")));
            $text[]=$forms->div("Emailed to " . $_SESSION['login_forgot']->email . " at " . date("m/d/Y h:i A",$_SESSION['login_forgot']->timestamp));
            if ($forms->error_text['unlock_code']) $text[]=$forms->div($forms->error_text['unlock_code'],array("class"=>"scscpq_login_error"));
	        print "<td class=scscpq_login_right>" . implode("",$text) . "</td>";
		}
        print "<tr>";
        print "<td class=scscpq_login_left>" . $forms->radio("action","new",$this->action,array("onclick"=>"login_action(this.value);")) . "</td>";
        $text=array("No, please sign me up as a new user");
        print "<td class=scscpq_login_right>" . implode("",$text) . "</td>";
        print "</tr>";
        print "</table>";
	    print "<p>" . $this->recaptcha->button("Login",array("id"=>"recaptcha_button")) . "</p>";
	    print $forms->close();
        print $this->login_js();
        if ($this->options->debug) print "<p>" . implode("<br>",$this->trace) . "</p>";
        $menu->copyright();
    }
    function login_css() {
        return <<< EOT

<style>
table.scscpq_login { border-collapse: separate !important; width: 100% !important; font-size: 100% !important; }
table.scscpq_login td { padding-top: .5em !important; padding-bottom: .5em !important; }
.scscpq_login_left { width: 2em !important; vertical-align: top !important; }
.scscpq_login_right { width: 99% !important; vertical-align: top !important; }
.scscpq_login_error { font-weight: bold !important; }
.scscpq_login_text_error { border: 1px solid red !important; }
</style>
EOT;
    }
    function login_js() {
        return <<< EOT

<script>
jQuery(document).ready(function() {
    login_action(jQuery('#action').val());
})
function login_action(value) {
    switch (value) {
      case 'forgot':
       jQuery('#recaptcha_button').val('Email unlock code');
       break;
      case 'unlock':
       jQuery('#recaptcha_button').val('Enter unlock code');
       break;
      case 'new':
       jQuery('#recaptcha_button').val('Create new account');
       break;
     default:
       jQuery('#recaptcha_button').val('Login');
    }
}
function login_checkbox(action) {
    jQuery('#action_' + action).prop('checked',true);
    login_action(action);
}
</script>
EOT;
    }
/*
This utility generates initial_timestamp from log
*/
    function initial_timestamp() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
		$this->forms_title("User Initial Timestamp");
        switch ($forms->report['action']) {
          case "":
			$forms->report['table']="log";
            break;
          default:
			$forms->report['table']=$_POST['table'];
			$users=array();
            $query=array("select id from user");
            $query[]="where initial_timestamp=0";
            $query[]="order by id";
            $database->temp->query($query);
            if (!$database->temp->meta->rows) {
                $forms->message[]="No records processed";
            	break;
            }
            while ($database->temp->fetch = $database->temp->fetch_array() ) {
            	$users[]=$database->temp->fetch['id'];
            }
          	$database->temp->meta->error_abort=FALSE;
			$database->temp2=new database_temp_class();
            $query_where=array();
            $query_where[]=new query_where("user_id","in",$users);
			$query=array("select");
            $query[]="if (concat(type,':',subtype)='user:new account',0,1) as 'sort_key',user_id,date_time";
            $query[]="from " . $forms->report['table'];
            $query[]=$database->where($query_where);
            $query[]="group by user_id";
            $query[]="order by user_id,sort_key,date_time";
            $database->temp->query($query);
            if ($database->temp->meta->error) {
            	$forms->message[]=$database->temp->meta->error;
            	break;
            }
			while ($database->temp->fetch = $database->temp->fetch_array()) {
            	$query=array("update user");
                $query[]="set initial_timestamp=" . fn_escape($database->temp->fetch['date_time']);
                $query[]="where id=" . fn_escape($database->temp->fetch['user_id']);
				$database->temp2->query($query);
            }
            $forms->message[]=number_format(sizeof($users)) . " Users w/o Initial Timestamp";
            $forms->message[]=number_format($database->temp->meta->rows) . " Users Updated";
		}
	    $menu->head();
	    $forms->message();
        print $this->js();
		print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action","update");
		$text=array("<b>Table</b>");
        $text[]=$forms->radio("table","log",$forms->report['table']) . " log";
        $text[]=$forms->radio("table","legacy_log",$forms->report['table']) . " legacy_log";
		$text[]=$forms->button("Go",array("onclick"=>"toolbox_continue('Generate initial timestamp');"));
        print "<p>" . implode("<br>",$text) . "</p>";
	    print $forms->close();
	    $menu->copyright();
    }
    function myaccount() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		$this->recaptcha=new recaptcha_class("myaccount",($_SESSION['user']->id) ? 0 : $database->registry->recaptcha->myaccount);

        switch (TRUE) {
          case (isset($_SESSION['local_return'])):
            $_SESSION['return_url']=$_SESSION['local_return'];
            break;
           case (isset($_SESSION['return_url'])):
            break;
          default:
             $_SESSION['return_url']="";
        }
	    if ($_SESSION['user']->id) {
	        $this->forms_title("My Account");
	      } else {
	        $this->forms_title("New Account");
	        $forms->html_meta("You need to create an to access the full site");
	    }
	    $forms->report['action']=$_POST['action'];
	    if ($_SESSION['user']->id) {
	        $database->user->read($_SESSION['user']->id);
	      } else {
	        $database->user->data=new user_data_class();
	    }
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        break;
	      default:
        	$address=new address_class("user",$database->user->data);
	        $address->verify();
            if (property_exists($this, "cpqvariable")) {
                $this->cpqvariable->load($database->user->data->variable);
                $this->cpqvariable->load($_POST);
                $this->cpqvariable->verify(FALSE);
                $this->cpqvariable->json($database->user->data->variable);
            }
            $database->user->data->cad_code=$_POST['cad_code'];
            $this->tabs("verify");
	        if (sizeof($forms->error)) break;
			$this->recaptcha->score();
            if ($this->recaptcha->error) {
            	$forms->error('null',"","Internal error, please try again");
                break;
            }
            $database->user->data->recaptcha_score=$this->recaptcha->score;
            if ($this->options->cpqdomain) $database->user->data->cpqdomain_id=$database->cpqdomain->locate($database->user->data->email);
	        $database->user->update($_SESSION['user']->id);
	        if ($database->user->meta->error) {
	            $forms->error('email',$database->user->meta->error);
                break;
			}
	        if (!$_SESSION['user']->id) {
            	$comment=array("Email: " . $database->user->data->email);
                if ($this->recaptcha->recaptcha) $comment[]=$this->recaptcha->comment();
            	$database->log->update("User:New Account", $comment, $database->user->data->id);
                if ($this->mailform("new")) {
                	$options=array();
                    $options['user_id']=$database->user->data->id;
	                $mail=new scsmail_class($this->mailform_code, $database->user->data, $options);
	                $html=array();
	                $html[]="<table class='noborder'>";
                    if ($this->recaptcha->recaptcha) {
	                    $text=array(number_format($database->user->data->recaptcha_score,1));
	                    if ($database->user->data->recaptcha_score < $database->registry->recaptcha->score_spam) $text[]="(Possible spam)";
	                    $html[]="<tr><td>reCAPTCHA score</td><td>" . implode(" ",$text) . "</td></tr>";
					}
	                $html[]=$address->output("table");
	                $html[]="</table>";
	                $mail->html($html);
	                $mail->send();
            	}
                if ($this->cpqdomain) $this->confirm=$database->cpqdomain->confirm($database->user->data);
			}
            $this->tabs("update");
            if ($database->user->data->status != "Active") $database->user->data->role="Customer";
            $database->user->session(TRUE);
            $return_url=$_SESSION['return_url'];
            unset($_SESSION['return_url'], $_SESSION['local_return']);
            switch (TRUE) {
              case ($this->confirm):
                break;
              case (strlen($return_url)):
                fn_url_redirect($return_url);
                break;
              case (property_exists($menu->page, "dashboard")):
                fn_url_redirect($menu->page->dashboard);
                break;
              default:
                fn_url_redirect($menu->page->home);
            }
		}
	    $menu->head();
	    print $forms->message();
		print $this->js();
        print $this->recaptcha->js();
	    print $forms->open();
	    print $forms->hidden('action','update');
        switch (TRUE) {
          case ($this->confirm):
            break;
          case (array_key_exists("info",$this->options->tabs)):
            $this->tabs=new jquery_tab_class();
            $this->tabs->item("Info",$this->myaccount_info());
            $this->tabs("input");
            $this->tabs->output();
            break;
          default:
        	print $this->myaccount_info();
        }
        switch (TRUE) {
          case ($this->confirm):
            $items=array();
            $items[]="Welcome <b>{$database->cpqdomain->data->company_name}</b> employee.";
            $items[]="";
            $items[]=$this->confirm;
            $items[]="";
            $items[]=fn_href("Resend the confirmation email and follow the instructions.","javascript:myaccount_confirm();");
            $items[]="<span id=confirm_response></span>";
            $text=implode("<br>", $items);
            break;
          case (isset($_SESSION['impersonate'])):
	        $text="Cannot update while impersonating another user";
            break;
          default:
	        $text=$this->recaptcha->button(($_SESSION['user']->id) ? "Update My Account" : "Create Account");
	    }
	    print "<p class='standard'>{$text}</p>";
	    print $forms->close();
	    $menu->copyright();
	}
    function myaccount_info() {
        global $database, $forms, $menu;
	    $results=array();
	    $results[]="<table class='standard noborder'>";
        if ($database->user->data->cpqdomain_id) {
            $database->cpqdomain->read($database->user->data->cpqdomain_id);
            $results[]="<tr>";
            $results[]="<td>Domain name</td>";
            $results[]="<td><b>{$database->cpqdomain->data->company_name}</b></td>";
            $results[]="</tr>";
        }
        $text=array();
        switch (TRUE) {
          case (!$database->user->data->id):
          case ($database->user->data->status != "Pending"):
            break;
           case ($database->user->data->cpqdomain_id):
            $text[]="Your email address has not been confirmed.";
            $text[]="An email was been sent from <b>{$database->registry->email->webmaster}</b> with instructions to complete your registration.";
            $text[]=fn_href("Resend the confirmation email.","javascript:myaccount_confirm();");
            $text[]="<b><span id=confirm_response></span></b>";
            break;
          case (strlen($database->registry->login->pend_html)):
            $text[]=$database->registry->login->pend_html;
            break;
          default:
            $text[]="Your account status is pending. Site access will be limited until administrator approval.";

        }
        if (sizeof($text)) {
            $text[]="";
            $results[]="<tr>";
            $results[]="<td>Access pending</td>";
            $results[]="<td>" . implode("<br>", $text) . "</td>";
            $results[]="</tr>";
        }
	    $address=new address_class("user",$database->user->data);
	    $results[]=$address->input(array("legend"=>FALSE));
        if ( (property_exists($this->template,"discount_pct")) && ($database->user->data->discount_pct) ) {
        	$results[]="<tr><td>Discount Percent</td><td><b>" . number_format($database->user->data->discount_pct * 100,2) . "%</b></td></tr>";
        }
        if (property_exists($this, "cpqvariable")) {
            $this->cpqvariable->load($database->user->data->variable);
            $results[]=$this->cpqvariable->input(FALSE);
        }
        if ($database->registry->cad->user_format) {
            $results[]="<tr>";
            $results[]="<td>CAD Format</td>";
            $text=($database->registry->cad->user_format  == "picklist") ? $database->cad->picklist($database->user->data->cad_code,array("field"=>"{$prefix}cad_code")) : $database->cad->select($database->user->data->cad_code);
            $results[]="<td class=small>{$text}</td>";
            $results[]="</tr>";
        }
		if (method_exists($this->local,"info")) $results[]=implode("",$this->local->info("input"));
        $results[]=$address->legend();
	    $results[]="</table>";
	    return implode("",$results);
    }
	function myaccount_cad($action) {
	    global $database, $forms;
		if ( (!$_SESSION['user']->id) || (!$database->registry->cad->link_hours) ) return;
        switch ($action) {
          case "input":
			$this->tab_name="{$database->registry->cad->link_hours} Hour CAD Download";
	        $query_where=array();
	        $query_where[]=new query_where("crmlog.user_id","=",$_SESSION['user']->id);
	        $query_where[]=new query_where("crmlog.status","=","Update");
	        $query_where[]=new query_where("crmlog.timestamp",">=",strtotime("- {$database->registry->cad->link_hours} hours"));
	        $query=array("select");
	        $query[]="cad.name as 'cad_name',";
	        $query[]="crmlog.* from crmlog";
	        $query[]="left join cad on cad.code=crmlog.cad_code";
	        $query[]=$database->where($query_where);
	        $query[]="order by crmlog.timestamp desc";
	        $database->crmlog->query($query);
	        if (!$database->crmlog->meta->rows) return;
            $this->tab_count=$database->crmlog->meta->rows;
	        $results=array();
            $js=array("");
            $js[]="<script>";
	        $js[]="function myaccount_cad(obj, sku, transaction_id) {";
	        $js[]="obj.checked=false;";
	        $js[]="if (!confirm('CAD Download: ' + sku)) return;";
	        $js[]="window.open(scscpq_api_url + '?action=cad_download&transaction_id=' + transaction_id, '_blank');";
	        $js[]="}";
            $js[]="</script>";
            $js[]="";
            $results[]=implode("\n",$js);
	        $results[]="<table class='standard border'>";
	        $results[]="<tr>";
	        $results[]="<th width='10%'>Download?</th>";
	        $results[]="<th width='40%'>SKU</th>";
	        $results[]="<th width='30%'>CAD Format</th>";
	        $results[]="<th width='20%'>Expires</th>";
	        $results[]="</tr>";
	        while ($database->crmlog->fetch = $database->crmlog->fetch_array()) {
	            $database->crmlog->fetch();
	            $results[]="<tr>";
	            $results[]="<td class=center>" . $forms->radio("download",$database->crmlog->data->id,0,array("onclick"=>"myaccount_cad(this," . fn_escape($database->crmlog->data->sku) . "," . fn_escape($database->crmlog->data->transaction_id) . ");")) .  "</td>";
	            $results[]="<td>" . $database->crmlog->data->sku . "</td>";
	            $results[]="<td>" . $database->crmlog->fetch['cad_name'] . "</td>";
	            $results[]="<td class=center>" . date("m/d/Y h:i A",expiry($database->crmlog->data->timestamp, $database->registry->cad->link_hours,"hours")) . "</td>";
	            $results[]="</tr>";
	        }
	        $results[]="</table>";
	        return $results;
          	break;
		}
    }
    function myaccount_rolodex($action) {
	    global $database, $forms;
	    if (!$_SESSION['user']->id) return;
        switch ($action) {
          case "input":
	        $results=array();
            $results[]=$database->shipto->js();
	        $results[]="<table class='standard noborder'>";
	        $results[]="<tr>";
	        $results[]="<td width='50%' id='scscpq_rolodex_items'>" . $database->shipto->rolodex(array("self"=>FALSE,"select"=>FALSE)) . "</td>";
	        $results[]="<td width='50%'>";
	        $results[]="<div id=scscpq_rolodex_status></div>";
	        $results[]="<div id=scscpq_rolodex_input></div>";
	        $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="</table>";
	        return $results;
		  case "verify":
          	break;
          case "update":
			$database->shipto->self($database->user->data);
          	break;
		}
	}
    function myaccount_confirm_ajax() {
        global $database;
        $this->trace[]=__FUNCTION__;
        $response=new stdClass();
        $response->confirm_response=$database->cpqdomain->confirm($_SESSION['user']);
        return $response;
    }
    function myaccount_js() {
        return <<< EOT
function myaccount_confirm() {
    jQuery("#confirm_response").html("Working ...");
        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            data: { action: "confirm" },
            success:function(response) { scscpq_fill(response) }
    });
}
EOT;
    }
    function maintain() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        if (!$database->user->access("Super User", FALSE)) {
            $key=array_search("CRON", $database->user->constant->status);
            if (strlen($key)) unset($database->user->constant->status[$key]);
        }
		if (!property_exists($this->options,"update")) $this->options->update=$this->role;
        $this->options->api_key=in_array("CRON",$database->user->constant->status);
		$this->forms_title("User Maintain");
	    $forms->report['action']=$_POST['action'];
	    $forms->report['record_id']=$_POST['record_id'];
	    $forms->report['user']=intval($_POST['user']);
	    $forms->report['report_status']=$_POST['report_status'];
	    $forms->report['report_role']=$_POST['report_role'];
	    $forms->report['report_source']=$_POST['report_source'];
	    $forms->report['report_value']=fn_text($_POST['report_value']);
	    $forms->report['page']=intval($_POST['scs_page']);
	    if ($forms->report['record_id']) {
	        $database->user->read($forms->report['record_id']);
	      } else {
	        $database->user->data=new user_data_class();
	    }
        $this->page_break=new page_break_class($forms->report['action'], $forms->report['page'], 0);
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        break;
	      case ($forms->report['action']=="cancel"):
	        $forms->message[]="Request cancelled";
	        break;
	      case ($forms->report['action']=="maintain"):
	        break;
	      case (!$database->user->access($this->options->update,FALSE)):
	        $forms->message[]=$this->options->update . " access required to update";
	        break;
	      case ($forms->report['action']=="delete"):
	        $database->user->delete($forms->report['record_id']);
			$this->tabs("delete");
	        $forms->message[]="Deleted: " . $database->user->data->email;
	        break;
	      case ($forms->report['action']=="update"):
	        $address=new address_class("user",$database->user->data);
	        $address->verify(array("password"=>$database->user->meta->rows));
        	if (property_exists($this->template,"discount_pct")) {
	            $database->user->data->discount_pct=fn_number($_POST['discount_pct'] . "%",2);
	            if ( ($database->user->data->discount_pct < 0) || ($database->user->data->discount_pct > 1) ) $forms->error("discount_pct");
			}
	        $database->user->data->status=$_POST['status'];
	        if ($database->user->data->status == "Reject") {
	            $database->user->data->role=current($database->user->constant->role);
	          } else {
	            $database->user->data->role=$_POST['role'];
	        }
			if ($database->user->data->status == "CRON") {
            	$database->user->data->api_key=trim($_POST['api_key']);
                if (!strlen($database->user->data->api_key)) $forms->error("api_key","Cannot be blank");
			  } else {
            	$database->user->data->api_key="";
            }
	        $database->user->data->login_attempt=$_POST['login_attempt'];
	        $database->user->data->disabled=intval($_POST['disabled']);
            $database->user->data->cad_code=$_POST['cad_code'];
            if (property_exists($this->template,"comment")) $database->user->data->comment=fn_text($_POST['comment']);
            if (property_exists($this, "cpqvariable")) {
                $this->cpqvariable->load($_POST);
                $this->cpqvariable->verify(TRUE, "info");
                $this->cpqvariable->json($database->user->data->variable);
            }
			$this->tabs("verify");
	        if (sizeof($forms->error)) break;
	        $database->user->update($database->user->meta->rows);
	        if ($database->user->meta->error) {
                switch (TRUE) {
                  case ( (method_exists($database->user, "mysql_duplicate")) && ($database->user->mysql_duplicate()) ):
                    break;
                  default:
                    $forms->error('user_email',$database->user->meta->error);
                }
                break;
			}
			$this->tabs("update");
	        $forms->message[]="Record maintained";
	    }
	    $menu->head();
	    $forms->message();
		print $this->js();
	    print $forms->open();
	    print $forms->hidden('action');
        if ($GLOBALS['fn_page_item']) {
	        print $forms->hidden('scs_page');
        } else {
	        print $forms->hidden('page');
        }
	    print $forms->hidden('record_id');
	    switch (TRUE) {
	      case ($forms->report['action'] == "maintain"):
	      case (($forms->report['action'] == "update") && (sizeof($forms->error))):
            print $forms->hidden("report_status",$forms->report['report_status']);
            print $forms->hidden("report_role",$forms->report['report_role']);
            print $forms->hidden("report_source",$forms->report['report_source']);
            print $forms->hidden("report_value",$forms->report['report_value']);
	        if (array_key_exists("info",$this->options->tabs)) {
	            $this->tabs=new jquery_tab_class();
	            $this->tabs->item("Info",$this->maintain_info());
				$this->tabs("input");
	            $this->tabs->output();
			  } else {
              	print $this->maintain_info();
	        }
	        $buttons=array();
	        if ($database->user->access($this->options->update,FALSE)) $buttons[]=$forms->button("Update",array("onclick"=>"toolbox_action('update'," . fn_escape($forms->report['record_id']) . ",'');"));
	        $buttons[]=$forms->button("Cancel",array("onclick"=>"toolbox_action('cancel','','');"));
	        print "<p>" . implode(" ",$buttons) . "</p>";
			break;
           default:
           	print $this->maintain_list();
		}
	    print $forms->close();
	    $menu->copyright();
	}
    function maintain_js() {
        return <<< EOT
jQuery(document).ready(function() {
    maintain_status();
})
function maintain_status() {
   if (jQuery('#status').val() == 'CRON') {
       jQuery('#api_key_tr').show();
     } else {
       jQuery('#api_key_tr').hide();
    }
}
function maintain_api_key() {
    jQuery.ajax({
      type: 'post',
      dataType: 'json',
      data: { action: 'maintain_api_key' },
      success: function(response) { jQuery('#api_key').val(response['api_key']) }
})};
EOT;
    }
    function maintain_api_key() {
	    $results=array();
	    $results['api_key']=bin2hex(openssl_random_pseudo_bytes(20));
        return $results;
    }
    function maintain_list() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		$results=array();
	    $results[]="<table class='standard border'>";
        $results[]="<tr>";
        $results[]="<th colspan=2>Parameter List</th>";
        $results[]="<th colspan=2>User Lookup</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='15%'>Status</td>";
        $results[]="<td width='35%'>" . $forms->select("report_status",$database->user->constant->status,$forms->report['report_status'],TRUE) . "</td>";
        $results[]="<td width='15%'>User#</td>";
        $results[]="<td width='35%'>" . $forms->text("user",0,12,0,"decimal:0",array("class"=>"toolbox")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Role</td>";
        $results[]="<td>" . $forms->select("report_role",$database->user->constant->role,$forms->report['report_role'],TRUE) . "</td>";
        $results[]="<td>Name</td>";
        $results[]="<td id=user_name></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Source: " . $forms->select("report_source",array("Name","Company Name","Email","Domain"),$forms->report['report_source'],FALSE) . " </td>";
	    $results[]="<td>" . $forms->text("report_value",$forms->report['report_value'],20) . "</td>";
        $results[]="<td>Company Name</td>";
        $results[]="<td id=user_company_name></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td colspan=2>&nbsp;</td>";
        $results[]="<td>Email</td>";
        $results[]="<td id=user_email></td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td colspan=2>" . $forms->button("List",array("onclick"=>"toolbox_action('list','','');")) . "</td>";
        $results[]="<td colspan=2>" . $forms->button("Lookup",array("onclick"=>"toolbox_action('lookup','','');")) . "</td>";
        $results[]="</tr>";
	    $results[]="</table>";

	    $results[]="<table class='border tablesorter'>";
	    $results[]="<thead>";
	    $results[]="<tr>";
	    $results[]="<th>Email</th>";
	    $results[]="<th>Name</th>";
	    $results[]="<th>Company name</th>";
	    $results[]="<th>Status</th>";
	    $results[]="<th>Role</th>";
	    $results[]="<th>Del?</th>";
	    $results[]="</tr>";
	    $results[]="</thead>";
	    if ($database->user->access($this->options->update,FALSE)) {
	        $results[]="<tr>";
	        $results[]="<td colspan=6>" . fn_href("Add new","javascript:toolbox_action('maintain','0','');") . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="<tbody>";
        $this->page_break->visible=FALSE;
	    switch (TRUE) {
	      case (sizeof($forms->error)):
	        break;
	      case ($forms->report['action'] == "lookup"):
	      case ($forms->report['action'] == "list"):
	      case ($forms->report['action'] == "page"):
	        $this->page_break->visible=TRUE;
	        $query_where=array();
	        $query_having=array();
            if (!$database->user->access("Super User", FALSE)) $query_where[]=new query_where("status","<>","cron");
            if ($forms->report['action'] == "lookup") {
            	$query_where[]=new query_where("id","=",$forms->report['user']);
			  } else {
            	if ($forms->report['report_status']) $query_where[]=new query_where("status","=",$forms->report['report_status']);
                if ($forms->report['report_role']) $query_where[]=new query_where("role","=",$forms->report['report_role']);
                switch (TRUE) {
                  case ( (!strlen($forms->report['report_source'])) || (!strlen($forms->report['report_value'])) ):
                	break;
                  case ($forms->report['report_source'] == "Name"):
                  	$query_where[]=new query_where("name","like","%" . $forms->report['report_value'] . "%");
                    break;
                  case ($forms->report['report_source'] == "Company Name"):
                  	$query_where[]=new query_where("company_name","like","%" . $forms->report['report_value'] . "%");
                    break;
                  case ($forms->report['report_source'] == "Email"):
                  	$query_where[]=new query_where("email","like","%" . $forms->report['report_value'] . "%");
                    break;
                  case ($forms->report['report_source'] == "Domain"):
                  	$query_having[]=new query_where("email_domain","=",$forms->report['report_value']);
                    break;
                }
			}
            switch ($forms->report['report_source']) {
              case "Company Name":
              	$query_order="company_name,name,id";
                break;
              case "Email":
              	$query_order="email,name,id";
                break;
              case "Domain":
              	$query_order="email_domain,email_name,id";
                break;
              default:
		        $query_order="name,id";
            }
            $query_where[]=new query_where("role","in",$database->user->role_list());
            $query=array("select");
            $query[]="substring(email, 1, locate('@',email) -1) as email_name,";
            $query[]="substring(email, locate('@',email) +1) as email_domain,";
            $query[]="user.* from user";
            $query[]=$database->where($query_where);
            $query[]=$database->where($query_having,"having");
            $query[]="order by {$query_order}";
            $query[]=$this->page_break->limit();
            $database->user->query($query, TRUE);
			while ($database->user->fetch = $database->user->fetch_array() ) {
            	$database->user->fetch();
                $results[]="<tr>";
                $results[]="<td>" . fn_href($database->user->data->email,"javascript:toolbox_action('maintain'," . fn_escape($database->user->data->id) . ",'');") . "</td>";
                $results[]="<td>" . $database->user->data->name . "</td>";
                $results[]="<td>" . $database->user->data->company_name . "</td>";
                $results[]="<td>" . $database->user->data->status . "</td>";
                $results[]="<td>" . $database->user->data->role . "</td>";
                switch (TRUE) {
                  case ($database->user->data->status == "Active"):
                  case (!$database->user->access($this->options->update,FALSE)):
                  	$text="&nbsp;";
                  	break;
                  default:
                    $text=$forms->checkbox_delete($database->user->data->id, $database->user->data->email, array(), "toolbox_action");
				}
	            $results[]="<td class=center>{$text}</td>";
                $results[]="</tr>";
            }
		}
	    $results[]="</tbody>";
	    $results[]="</table>";
		$results[]=$this->page_break->output($database->user->meta->found_rows,array("url"=>"toolbox_page_item('list',%page%,0);"));
		return implode("",$results);
    }
    function maintain_info() {
    global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		$results=array();
	    $results[]="<table class='standard noborder'>";
	    $address=new address_class("user",$database->user->data,array("legend"=>FALSE,"password"=>$database->user->meta->rows));
	    $results[]=$address->input();
	    $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
	    $results[]="<tr>";
	    $results[]="<td>Role</td>";
	    $results[]="<td>" . $forms->select("role",$database->user->role_list(),$database->user->data->role,FALSE) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Status</td>";
	    $results[]="<td>" . $forms->select("status",$database->user->constant->status,$database->user->data->status,FALSE,array("onchange"=>"maintain_status();")) . "</td>";
	    $results[]="</tr>";
		if ($this->options->api_key) {
		    $results[]="<tr id=api_key_tr>";
	        $text=array("API Key");
	        $text[]=fn_href("(Create New)","javascript:maintain_api_key();");
	        $results[]="<td>" . implode(" ",$text) . "</td>";
	        $text=array($forms->text("api_key",$database->user->data->api_key,40,40,"",array("readonly"=>TRUE,"placeholder"=>"Must be generated")));
	        if ($forms->error_text['api_key']) $text[]=$forms->error_text['api_key'];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
        }
        $results[]=$this->discount_pct();
	    $results[]="<tr>";
	    $results[]="<td>Disabled?</td>";
	    $results[]="<td>" . $forms->checkbox("disabled",1,$database->user->data->disabled) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Unsuccessful login</td>";
	    $results[]="<td>" . $forms->select_number("login_attempt",$database->user->data->login_attempt,0,99) . "</td>";
	    $results[]="</tr>";
        if ($database->registry->cad->user_format) {
            $results[]="<tr>";
            $results[]="<td>CAD Format</td>";
            $results[]="<td>" . $database->cad->select($database->user->data->cad_code) . "</td>";
            $results[]="</tr>";
        }
        if (property_exists($this->template,"comment")) {
	        $results[]="<tr>";
	        $results[]="<td>Comment</td>";
	        $results[]="<td>" . $forms->textarea("comment",$database->user->data->comment,3,60) . "</td>";
	        $results[]="</tr>";
		}
        if (property_exists($this, "cpqvariable")) {
            $this->cpqvariable->load($database->user->data->variable);
            $results[]=$this->cpqvariable->input(TRUE);
        }
		if (method_exists($this->local,"info")) $results[]=implode("",$this->local->info("input"));
	    if ($forms->report['record_id']) {
	        if ($database->user->data->initial_timestamp) {
	            $results[]="<tr>";
	            $results[]="<td>Initial Date</td>";
	            $results[]="<td><b>" . date("m/d/Y h:i A", $database->user->data->initial_timestamp) . "</b></td>";
	            $results[]="</tr>";
	        }
	        if ($database->user->data->login_timestamp) {
	            $results[]="<tr>";
	            $results[]="<td>Last login</td>";
	            $results[]="<td><b>" . date("m/d/Y h:i A", $database->user->data->login_timestamp) . "</b></td>";
	            $results[]="</tr>";
	        }
		}
	    $results[]=$address->legend();
	    $results[]="</table>";
		return implode("",$results);
	}
    function approve() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $key=array_search("CRON", $database->user->constant->status);
        if (strlen($key)) unset($database->user->constant->status[$key]);
        $this->forms_title("User Approval");
        $this->approve_input();
        $menu->head();
        $forms->message();
        print $this->js();
        print $this->approve_css();
        print $forms->open();
        print $forms->hidden('action');
        print $forms->hidden('record_id', $this->input->record_id);
        print $forms->hidden('domain', $this->input->domain, '', 'cpqdomain');
        print $forms->hidden('cpqdomain_id', $this->input->cpqdomain_id, '', 'cpqdomain');
        switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            if (array_key_exists("info",$this->options->tabs)) {
                $this->tabs=new jquery_tab_class();
                $this->tabs->item("Info",$this->approve_info());
                $this->tabs("input");
                $this->tabs->output();
              } else {
                  print implode("",$this->approve_info());
            }
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"toolbox_action('update'," . fn_escape($database->user->data->id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"toolbox_action('cancel','','');"));
            print "<p>" . implode(" ",$buttons) . "</p>";
            break;
          default:
            print "<table class='border tablesorter'>";
            print "<thead>";
            print "<tr>";
            print "<th>Email</th>";
            print "<th>Company name</th>";
            print "<th>Name</th>";
            print "<th>Date</th>";
            print "<th>Del?</th>";
            print "</tr>";
            print "</thead>";
            print "<tbody>";
            $query_where=array();
            $query_where[]=new query_where("status", "=", "Pending");
            if ($this->cpqdomain) $query_where[]=new query_where("cpqdomain_id", "=", 0);
            $query=array("select * from user");
            $query[]=$database->where($query_where);
            $query[]="order by email";
            $database->user->query($query);
            while ($database->user->fetch = $database->user->fetch_array()) {
                $database->user->fetch();
                print "<tr>";
                print "<td>" . fn_href($database->user->data->email,"javascript:toolbox_action('maintain'," . fn_escape($database->user->data->id) . ",'');") .  "</td>";
                print "<td>{$database->user->data->company_name}</td>";
                print "<td>{$database->user->data->name}</td>";
                print "<td class=center>" . date("m/d/Y",$database->user->data->initial_timestamp) . "</td>";
                print "<td class=center>" . $forms->checkbox_delete($database->user->data->id, $database->user->data->email, array(), "toolbox_action") . "</td>";
                print "</tr>";
            }
            print "</tbody>";
            print "</table>";
            $database->user->free_result();
        }
        print $forms->close();
        $menu->copyright();
    }
    function approve_input() {
        global $database, $forms;
        $this->input->action=$_REQUEST['action'];
        $this->input->record_id=$_REQUEST['record_id'];
        if ($this->input->record_id) $database->user->read($this->input->record_id);
        switch ($this->input->action) {
          case "":
            break;
          case "cancel":
            $forms->message[]="Request cancelled";
            break;
          case "delete":
            $database->user->delete($this->input->record_id);
            $forms->message[]="Delete: " . $database->user->data->email;
            break;
          case "maintain":
            if ($this->cpqdomain) {
                $email=new email_class($database->user->data->email);
                $this->input->domain=$email->domain;
                $database->cpqdomain->read($this->input->domain, "domain");
                $this->input->cpqdomain_id=$database->cpqdomain->data->id;
                if ( ($this->input->cpqdomain_id) && (!$database->user->data->cpqdomain_id) ) {
                    $database->user->data->cpqdomain_id=$this->input->cpqdomain_id;
                    $database->user->data->role=$database->cpqdomain->data->role;
                    $database->user->data->discount_pct=$database->cpqdomain->data->discount_pct;
                }
            }
            break;
          case "update":
            $this->approve_update();
            break;
          }
    }
    function approve_update() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        if ($this->cpqdomain) {
            $this->input->domain=$_POST['domain'];
            $database->cpqdomain->read($this->input->domain, "domain");
            $database->user->data->cpqdomain_id=$database->cpqdomain->data->id;
            $this->input->cpqdomain_id=$database->cpqdomain->data->id;
        }
        $database->user->data->status=$_POST['status'];
        if ($database->user->data->status == "Active") {
            $database->user->data->role=$_POST['role'];
            if (property_exists($this->template,"discount_pct")) {
                $database->user->data->discount_pct=fn_number($_POST['discount_pct'] . "%",2);
                if ( ($database->user->data->discount_pct < 0) || ($database->user->data->discount_pct >= 1) ) $forms->error("discount_pct","Allowed values: 0.00-99.99%");
            }
            } else {
            $database->user->data->role=current($database->user->constant->role);
            if (property_exists($this->template,"discount_pct")) {
                $database->user->data->discount_pct=0;
            }
        }
        $this->input->active_comment=fn_text($_POST['active_comment']);
        $this->input->reject_comment=fn_text($_POST['reject_comment']);
        switch ($database->user->data->status) {
          case "Pending":
            $forms->error('status',"Cannot be pending");
            break;
          case "Reject":
            $database->user->data->comment=fn_text($this->input->reject_comment);
            if (!strlen($this->input->reject_comment)) $forms->error("reject_comment","Reject reason required");
            break;
          default:
            if (property_exists($this, "cpqvariable")) {
                $this->cpqvariable->load($_POST);
                $this->cpqvariable->verify(TRUE, "info");
                $this->cpqvariable->json($database->user->data->variable);
            }
        }
        $this->tabs("verify");
        if (sizeof($forms->error)) return;
        $database->user->data->disabled=0;
        $database->user->data->login_attempt=0;
        $database->user->update(TRUE);
        if ($database->user->meta->error) {
            switch (TRUE) {
              case ( (method_exists($database->user, "mysql_duplicate")) && ($database->user->mysql_duplicate()) ):
                break;
              default:
                $forms->error('user_email',$database->user->meta->error);
            }
            return;
        }
        $this->tabs("update");
        if ($database->user->data->status == "Active") {
            $forms->message[]="Approved: {$database->user->data->email}";
            $text=array("Email: " . $database->user->data->email);
            if (strlen($this->input->active_comment)) $text[]=$this->input->active_comment;
            $database->log->ipc(array("User","Approved"),$text,$database->user->data->id);
            if ($this->mailform("approve")) {
                $options=array();
                $options['user_id']=$database->user->data->id;
                $mail=new scsmail_class($this->mailform_code, $database->user->data, $options);
                $mail->user_id=$database->user->data->id;
                $mail->recipient($database->user->data->email,$database->user->data->name);
                $message=array();
                if (strlen($this->input->active_comment)) {
                    $message[]="<p>" . str_replace("\n","<br>",$this->input->active_comment) . "</p>";
                }
                $mail->html($message);
                $forms->message[]=$mail->send(FALSE);
            }
            } else {
            $forms->message[]="Rejected: {$database->user->data->email}";
            $database->log->ipc(array("User","Rejected"),$this->input->reject_comment,$database->user->data->id);
        }
    }
    function approve_info() {
        global $database, $forms;
        $results=array();
        $address=new address_class("user", $database->user->data);
        $results[]="<table class='standard noborder'>";
        $results[]=$address->output("table");
        $results[]="<tr>";
        $results[]="<td>Initial Timestamp</td>";
        $results[]="<td>" . date("m/d/Y h:i A",$database->user->data->initial_timestamp) . "</td>";
        $results[]="</tr>";

        switch (TRUE) {
          case (!$this->cpqdomain):
          case (in_array($this->input->domain, $database->registry->cpqdomain->blacklist)):
            $text="";
            break;
          case ($this->input->cpqdomain_id):
            $text=$database->cpqdomain->data->company_name;
            break;
          case (!$database->user->access("Administrator", FALSE)):
          case (!$database->registry->cpqdomain->approve_create):
            $text="";
            break;
          default:
            $text=fn_href("Add Domain: {$this->input->domain}", "javascript:user_approve_ajax('maintain');");
        }
        if (strlen($text)) {
            $results[]="<tr>";
            $results[]="<td>Domain</td>";
            $items=array();
            $items[]="<div id=cpqdomain_td>{$text}</div>";
            $items[]="<div id=cpqdomain_div></div>";
            $results[]="<td>" . implode("", $items) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td>Status</td>";
        $text=array($forms->select("status", $database->user->constant->status, $database->user->data->status, FALSE, array("onchange"=>"user_approve_status();")));
        if ($forms->error_text['status']) $text[]=$forms->error_text['status'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr class=active_tr>";
        $results[]="<td>Role</td>";
        $results[]="<td>" . $forms->select("role",$database->user->constant->role,$database->user->data->role,FALSE) . "</td>";
        $results[]="</tr>";
        $results[]=$this->discount_pct(array("tr"=>"active_tr"));
        if (property_exists($this, "cpqvariable")) {
            $this->cpqvariable->load($database->user->data->variable);
            $results[]=$this->cpqvariable->input(TRUE, array("tr_class"=>"active_tr"));
        }
        if ($this->mailform("approve")) {
            if ($database->mailform->data->header_html) {
                $results[]="<tr class=active_tr>";
                $results[]="<td>Standard message</td>";
                $results[]="<td>" . $database->mailform->data->header_html . "</td>";
                $results[]="</tr>";
            }
            $results[]="<tr class=active_tr>";
            $results[]="<td>Personal message</td>";
            $results[]="<td>" . $forms->textarea("active_comment",$this->input->active_comment,3,60) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr class=reject_tr>";
        $results[]="<td>Reject reason</td>";
        $text=array();
        if ($forms->error_text['reject_comment']) $text[]=$forms->error_text['reject_comment'];
        $text[]=$forms->textarea("reject_comment",$this->input->reject_comment,3,60);
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        if (method_exists($this->local,"info")) $results[]=implode("",$this->local->info("input"));
        $results[]="</table>";
        return $results;
    }
    function approve_cpqdomain_ajax() {
        global $database, $forms;
        $database->cpqdomain->read($_POST['domain'], "domain");
        $database->user->read($_POST['user_id']);
        $this->response=new stdClass();
        switch (TRUE) {
          case ($database->cpqdomain->meta->rows):
            $this->response->cpqdomain_div="{$_POST['domain']} alread defined";
            break;
          case ($_POST['api_action'] == "external"):
            if (true_false($_POST['external'])) $this->response->cpqdomain_role="External";
            break;
          case ($_POST['api_action'] == "token"):
            $this->response->token=token(20);
            break;
          case ($_POST['api_action'] == "cancel"):
            $this->response->cpqdomain_div="";
            break;
          case ($_POST['api_action'] == "maintain"):
            $this->approve_cpqdomain_maintain();
            break;
          case ($_POST['api_action'] == "update"):
            $this->approve_cpqdomain_update();
            break;
        }
        die(json_encode($this->response));
    }
    function approve_cpqdomain_maintain() {
        global $database, $forms;
        $this->address=new address_class("cpqdomain", $database->user->data);
        $results=array();
        $results[]="<div id=cpqdomain_status></div>";
        $results[]="<table class=noborder>";
        $results[]="<tr>";
        $results[]="<td width='20%'><b>Add Domain</b></td>";
        $results[]="<td width='80%'><b>{$_POST['domain']}</b></td>";
        $results[]="</tr>";
        $results[]=$this->address->input(array("legend"=>FALSE, "class"=>"cpqdomain"));
        $results[]="<tr>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>External group?</td>";
        $results[]="<td>" . $forms->checkbox("external", 1, 0, array("class"=>"cpqdomain", "onclick"=>"user_approve_ajax('external');")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Role</td>";
        $text=array($forms->select("cpqdomain_role", $database->user->role_list(), "", FALSE, array("class"=>"cpqdomain")));
        $results[]="<td>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Discount</td>";
        $text=array($forms->text("cpqdomain_discount_pct",0, 5, 5, "decimal:2",array("class"=>"cpqdomain")));
        $results[]="<td>" . implode(" ", $text) . "%</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $text=array("Token");
        $text[]=fn_href("(Create New)","javascript:user_approve_ajax('token');");
        $results[]="<td>" . $forms->font("red","*",implode(" ",$text)) . "</td>";
        $text=array($forms->text("token",$database->cpqdomain->data->token,40,40,"",array("class"=>"cpqdomain", "readonly"=>TRUE,"placeholder"=>"Token must be generated")));
        if ($forms->error_text['token']) $text[]=$forms->error_text['token'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";

        $results[]=$this->address->legend();
        $results[]="</table>";
        $buttons=array();
        $buttons[]=$forms->button("Domain: Add", array("onclick"=>"user_approve_ajax('update');"));
        $buttons[]=$forms->button("Domain: Cancel", array("onclick"=>"user_approve_ajax('cancel');"));
        $results[]="<p>" . implode(" ", $buttons) . "</p>";
        $this->response->cpqdomain_div=implode("", $results);
    }
    function approve_cpqdomain_update() {
        global $database, $forms;
        $database->cpqdomain->data=new cpqdomain_data_class();
        $database->cpqdomain->data->domain=$_POST['domain'];
        $this->address=new address_class("cpqdomain", $database->cpqdomain->data);
        $this->address->verify();
        $database->cpqdomain->data->external=true_false($_POST['external']);
        $database->cpqdomain->data->role=fn_text($_POST['cpqdomain_role']);
        if ( ($database->cpqdomain->data->external) && ($database->cpqdomain->data->role != "External") ) $forms->error("role", "Role: Must be external");
        $database->cpqdomain->data->token=fn_text($_POST['token']);
        if (!strlen($database->cpqdomain->data->token)) $forms->error("token", "Token: Cannot be blank");
        $database->cpqdomain->data->discount_pct=fn_number($_POST['cpqdomain_discount_pct'] . "%",2);
        if ( ($database->cpqdomain->data->discount_pct < 0) || ($database->cpqdomain->data->discount_pct > 1) ) $forms->error("discount_pct","Discount percent: Must be <=0 and < 100");
        $database->cpqdomain->data->active=1;
        if (!sizeof($forms->error)) {
            $database->cpqdomain->update(FALSE);
            switch (TRUE) {
              case (preg_match("/domain_UNIQUE/i", $database->cpqdomain->meta->error)):
                $forms->error("domain", "Domain: Duplicate domain");
                break;
              case (preg_match("/token_UNIQUE/i", $database->cpqdomain->meta->error)):
                $forms->error("token", "Token: Duplicate value");
                break;
              case (strlen($database->cpqdomain->meta->error)):
                $forms->error("mysql", "Internal error");
                break;
            }
        }
        if (sizeof($forms->error)) {
            array_unshift($forms->error_text, "<b>Correct " . sizeof($forms->error) . " error(s)</b>");
            $this->response->cpqdomain_status="<p>" . implode("<br>", $forms->error_text) . "</p>";
            return;
        }
        $this->response->cpqdomain_td="{$database->cpqdomain->data->company_name}";
        $this->response->cpqdomain_id=$database->cpqdomain->data->id;
        $this->response->role=$database->cpqdomain->data->role;
        $this->response->discount_pct=number_format($database->cpqdomain->data->discount_pct * 100, 2);
        $this->response->cpqdomain_div="";
    }
    function approve_css() {
        return <<< EOT

<style>
#cpqdomain_div {
    border: 1px solid;
    padding: 1em;
}
#cpqdomain_div:empty {
    border: 0px;
    padding: 0em;
</style>
EOT;
    }
    function approve_js() {
        return <<< EOT
jQuery(document).ready(function() {
    user_approve_status();
});
function user_approve_status() {
    if ( jQuery('#status').val() == 'Active') {
        jQuery('.active_tr').show();
        } else {
        jQuery('.active_tr').hide();
    }
    if ( jQuery('#status').val() == 'Reject') {
        jQuery('.reject_tr').show();
        } else {
        jQuery('.reject_tr').hide();
    }
}
function user_approve_ajax(api_action) {
    if ( (api_action == "update") && (!confirm("Add domain: " + jQuery("#domain").val() + "?")) ) return;
    request=scscpq_class_data("cpqdomain");
    request['action']="cpqdomain";
    request['api_action']=api_action;
    request['user_id']=jQuery("#record_id").val();
    jQuery.ajax({
        url: window.location.pathname,
        dataType: 'json',
        method: 'post',
        data: request,
        success:function(response) { scscpq_fill(response) }
    });
}
EOT;
    }
    function purge() {
        global $database, $forms, $menu;
        $this->forms_title("Purge Users");
        switch ($forms->report['action']) {
          case "":
            $forms->report['date']=date("Y/m/d",strtotime("-1 year"));
            break;
          default:
            $forms->report['date']=fn_date($_POST['date'],"mdy");
            switch (TRUE) {
              case (!$forms->date_ok):
                $forms->error('date',"","Invalid date format");
                break;
              case ( strtotime($forms->report['date']) > strtotime("-1 year") ):
                $forms->error('date',"","Must be at least 1 year from today");
                break;
            }
            if (sizeof($forms->error)) break;
            $query_where=array();
            $query_where[]=new query_where("login_timestamp","<=",strtotime($forms->report['date']));
            $query=array("delete from user");
            $query[]=$database->where($query_where);
            $database->temp->query($query);
            $count=$database->temp->affected_rows();
            $comment=number_format($count) . " User record(s) purged thru " . fn_date($forms->report['date'],"ymd");
            $forms->message[]=$comment;
            $database->log->ipc("User:Purge",$comment);
            break;
        }
	    $menu->head();
	    $forms->message();
        print $this->js();
	    print $forms->open();
	    print $forms->hidden("action","update");
	    $text=array();
	    $text[]="Purge if last login on or before:";
	    $text[]=$forms->text("date",$forms->report['date'],10,10,"date");
	    $text[]=$forms->button("Purge",array("onclick"=>"toolbox_continue('Purge Users?');"));
	    print "<p> " . implode(" ", $text)  . "</p>";
	    print $forms->close();
	    $menu->copyright();
	}
    function unlock() {
        global $database, $forms, $menu;
        $this->forms_title("Reset Locked Accounts");
		if ($forms->report['action'] == "update") {
        	$items=array();
        	foreach ($_POST as $key => $value) {
            	list($field,$id)=fn_base64($key,FALSE);
                if ($field==__FUNCTION__) $items[]=$id;
            }
            if (sizeof($items)) {
	            $query_where=array();
	            $query_where[]=new query_where("id","in",$items);
	            $query=array("update user");
	            $query[]="set disabled=0,login_attempt=0";
	            $query[]=$database->where($query_where);
				$database->user->query($query);
            	$forms->message[]=number_format(sizeof($items)) . " Users Unlocked";
			  } else {
              	$forms->message[]="No users selected";
            }
        }
	    $menu->head();
	    print $forms->message();
	    print $forms->open();
	    print $forms->hidden("action","update");
		$query_where=array();
        $query_where[]=new query_where("disabled","=",1);
        $query_where[]=new query_where("status","<>","reject");
		$query_order=array();
		if (isset($database->registry->address->user['company_name'])) $query_order[]="company_name";
	    if (isset($database->user->data->name_first)) {
	        $query_order[]="name_last";
	        $query_order[]="name_first";
	      } else {
	        $query_order[]="name";
	    }
        $query=array("select * from user");
        $query[]=$database->where($query_where);
        $query[]="order by " . implode(", ",$query_order);
		$database->user->query($query);
	    print "<table class='standard border tablesorter'>";
        print "<thead>";
	    print "<tr>";
	    print "<th>Unlock?</th>";
	    print "<th>Email</th>";
	    print "<th>Name</th>";
		print "<th>Company Name</th>";
		print "<th>Status</th>";
        if (!$database->registry->password->encoding) print "<th>Unlock Code</th>";
	    print "</tr>";
        print "</thead>";
        print "<tbody>";
        while ($database->user->fetch = $database->user->fetch_array()) {
        	$database->user->fetch();
	        print "<tr>";
	        print "<td class=center>" . $forms->checkbox(fn_base64(array(__FUNCTION__, $database->user->data->id)),$database->user->data->id,0) . "</td>";
	        print "<td>{$database->user->data->email}</td>";
	        print "<td>{$database->user->data->name}</td>";
	        print "<td>{$database->user->data->company_name}</td>";
	        print "<td>{$database->user->data->status}</td>";
	        if (!$database->registry->password->encoding) print "<td class=center>" . $this->unlock_code() . "</td>";
	        print "</tr>";
        }
        print "</tbody>";
	    print "</table>";
        print "<p>" . (($database->user->meta->rows) ? $forms->submit("Update") : "There are no locked accounts") . "</p>";
	    print $forms->close();
	    $menu->copyright();
    }
    function export() {
        global $database, $forms, $menu;
		$this->forms_title("User Export", key($this->scs_table_version(__FUNCTION__)));
	    $forms->report['file_type']=$_POST['file_type'];
	    $forms->report['role']=array();
	    $this->map=new map_class("user",$forms->report);
	    switch ($forms->report['action']) {
	      case "":
	        $forms->report['active']=TRUE;
	        foreach ($database->user->constant->role as $value) {
	            $forms->report['role'][$value]=1;
	        }
	        break;
	      default:
	        $forms->report['from_date']=fn_date($_POST['from_date'],"mdy");
	        $forms->report['thru_date']=fn_date($_POST['thru_date'],"mdy");
	        if (($forms->report['thru_date']) && ($forms->report['from_date'] > $forms->report['thru_date'])) $forms->error('date',"End of range before beginning");
	        $forms->report['initial_date']=fn_date($_POST['initial_date'],"mdy");
	        foreach ($_POST as $key => $value) {
	            list($field,$role)=explode("\t",base64_decode($key));
	            if ($field == "role") $forms->report['role'][$role]=1;
	        }
	        if (!sizeof($forms->report['role'])) $forms->error('role','No user roles selected');
	        $forms->report['active']=$_POST['active'];
	        if (sizeof($forms->error)) break;
	        $query_where=array();
	        if ($forms->report['active']) $query_where[]=new query_where("status","=","Active");
	        if ($forms->report['from_date']) $query_where[]=new query_where($database->unixtime("login_timestamp"),">=",$forms->report['from_date']);
	        if ($forms->report['thru_date']) $query_where[]=new query_where($database->unixtime("login_timestamp"),">=",$forms->report['thru_date']);
	        if ($forms->report['initial_date']) $query_where[]=new query_where($database->unixtime("initial_timestamp"),">=",$forms->report['initial_date']);
	        $query_where[]=new query_where("role","in",$role);
	        $query=array("select * from user");
	        $query[]=$database->where($query_where);
	        $query[]="order by email";
	        $this->map->export($query);
	    }
	    $menu->head();
	    print $forms->message();
	    print $forms->open();
		print $this->js();
	    print $forms->hidden("action","update");
	    print "<table class='noborder'>";
	    print "<tr>";
	    print "<td width='20%'><span " . (($forms->error_text['role']) ? $forms->error_style : "") . ">User Roles</span></td>";
	    $text=array();
	    $text[]=$forms->checkbox("role_all",1,1,array("onclick"=>"toolbox_role(this);")) . " All?";
	    foreach ($database->user->constant->role as $value) {
	        $text[]=$forms->checkbox(base64_encode("role" . "\t" . $value),1,$forms->report['role'][$value],array("class"=>"role_class")) . " $value";
	    }
	    print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	    print "</tr>";
	    print "<tr>";
	    print "<td><span " . (($forms->error_text['date']) ? $forms->error_style : "") . ">Login Date Range</span></td>";
        $text=array();
        $text[]=$forms->text("from_date",$forms->report['from_date'],10,10,"date",array("placeholder"=>"From Date"));
        $text[]="-";
        $text[]=$forms->text("thru_date",$forms->report['thru_date'],10,10,"date",array("placeholder"=>"Thru Date"));
        if ($forms->error_text['date']) $text[]=$forms->error_text['date'];
	    print "<td>" . implode(" ",$text) . "</td>";
	    print "</tr>";
	    print "<tr>";
	    print "<td>Initial Date</td>";
	    print "<td>" . $forms->text("initial_date",$forms->report['initial_date'],10,10,"date",array("placeholder"=>"Initial Date")) . "</td>";
	    print "</tr>";
	    print "<tr>";
	    print "<td>Only active?</td>";
	    print "<td>" . $forms->checkbox("active",1,$forms->report['active']) . "</td>";
	    print "</tr>";
	    print "<tr>";
	    print "<td>Export file type</td>";
	    print "<td>" . $forms->select("file_type",$this->map->file_type_list,$forms->report['file_type'],FALSE) . "</td>";
	    print "</tr>";
	    print "</table>";
	    print "<p>" . $forms->submit("Go!") . "</p>";
	    print $forms->close();
	    $menu->copyright();
    }
    function unlock_code() {
    global $database;
		return strtoupper(dechex($database->user->data->login_timestamp));
    }
    function impersonate() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
		if ( ($_POST['action']) && ($this->impersonate_verify()) ) {
			$_SESSION['impersonate']=$_SESSION['user']->id;
			if (!$_POST['user_role']) {
            	unset ($_SESSION['user']);
			  } else {
				$_SESSION['user']=$database->user->data;
			}
			fn_url_redirect($menu->page->home);
        }
        if (!$_POST['action']) $database->user->read($_SESSION['user']->id);
		$this->forms_title("User Impersonate");
        if ($this->message) $forms->message[]=$this->message;
	    $menu->head();
	    print $forms->message();
        print $this->js();
	    print $forms->open();
		print $forms->hidden("action","update");
		print $forms->hidden("impersonate_user_id",$_SESSION['user']->id);

        if (array_key_exists("info",$this->options->tabs)) {
            $this->tabs=new jquery_tab_class();
            $this->tabs->item("Info",$this->impersonate_info());
            $this->tabs("input");
            $this->tabs->output();
          } else {
            print implode("",$this->impersonate_info());
        }
		print "<p>" . $forms->submit("Go!") . "</p>";
	    print $forms->close();
	    $menu->copyright();
	}
    function impersonate_info() {
        global $database, $forms, $menu;
    	$this->trace[]=__FUNCTION__;
        $results=array();
	    $results[]="<table class='standard noborder'>";
	    $results[]="<tr>";
	    $results[]="<td width='20%'>Impersonate User</td>";
	    $results[]="<td width='80%'>" . $forms->text("user", $database->user->data->id, 12, 0, "", array("class"=>"toolbox") ) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr><td>Email:</td><td><b><span id=user_email>{$database->user->data->email}</span></b></td></tr>";
	    $results[]="<tr><td>Name:</td><td><b><span id=user_name>{$database->user->data->name}</span></b></td></tr>";
	    $results[]="<tr><td>Company Name:</td><td><b><span id=user_company_name>{$database->user->data->company_name}</span></b></td></tr>";
	    $results[]="<tr>";
	    $results[]="<td>User Role</td>";
	    $results[]="<td>" . $forms->select("user_role",$database->user->constant->role,$database->user->data->role) . "</td>";
	    $results[]="</tr>";
	    $results[]="<tr>";
	    $results[]="<td>Status</td>";
	    $results[]="<td>" . $forms->select("user_status",$database->user->constant->status,$database->user->data->status) . "</td>";
	    $results[]="</tr>";
        $results[]=$this->discount_pct(array("tr"=>"user_discount_pct_tr","prefix"=>"user_"));
		if (method_exists($this->local,"info")) $results[]=implode("",$this->local->info("input"));
	    $results[]="</table>";
        return $results;
    }
    function impersonate_verify() {
        global $database, $forms;
    	if (!$_POST['user_role']) return TRUE;
        $database->user->read($_POST['user']);
        if (!$database->user->meta->rows) $database->user->read($_SESSION['user']->id);
        $database->user->data->role=$_POST['user_role'];
        $database->user->data->status=$_POST['user_status'];
        if (property_exists($this->template,"discount_pct")) {
            $database->user->data->discount_pct=fn_number($_POST['user_discount_pct'] . "%",2);
            if ( ($database->user->data->discount_pct < 0) || ($database->user->data->discount_pct > 1) ) $database->user->data->discount_pct=0;
        }
        $this->tabs("verify");
        if (!sizeof($forms->error)) return TRUE;
    }
    function js() {
		$results=array();
        $results[]="<script>";
        $results[]=<<<EOT
function toolbox_action(action, record_id, text) {
	if (text.length > 0) {
        jQuery("#delete" + record_id).prop("checked", false);
	    if (!confirm(text)) return;
	}
	jQuery('#action').val(action);
	jQuery('#record_id').val(record_id);
	jQuery('#scs_form').submit();
}
function toolbox_continue(text) {
	if (!confirm(text)) return;
	jQuery('#scs_form').submit();
}
function toolbox_role(obj) {
	jQuery('.role_class').prop('checked',obj.checked);
}
function toolbox_page_item(action,page,item) {
	jQuery('#action').val(action)
	jQuery('#scs_page').val(page)
	jQuery('#scs_item').val(item)
	jQuery('#scs_form').submit();
}

jQuery(function() {
    jQuery('.toolbox').autocomplete({
        source: '/scs_ajax.php?table=user',
        minLength: 2,
        html: true,
        open: function(event, ui) { jQuery('.ui-autocomplete').css('z-index', 1000) },
        select: function(event, ui) { toolbox_fill(ui.item, this.id + "_") }
    });
});
// Based on scs.js 08/12/2024
function toolbox_data(classname) {
    if (typeof scscpq_class_data !== "undefined") return scscpq_class_data(classname);
	data=new Object();
    jQuery("." + classname).each(
	    function() {
	        switch (true) {
	          case (!this.id):
              case ( (this.id).substring(0,9) == "jquerytab"):
	            break;
	          case (jQuery("#" + this.id).attr('type') == "checkbox"):
	            data[this.id]=jQuery("#" + this.id).prop("checked")
	            break;
              case (jQuery("#" + this.id).attr('type') == "radio"):
                if (jQuery("#" + this.id).prop("checked")) data[this.name]=this.value;
                break;
	          default:
	            data[this.id]=jQuery("#" + this.id).val();
	        }
		}
    );
    return data;
}
// Based on scs.js 08/12/2024
function toolbox_fill(data, prefix="") {
    if (typeof scscpq_fill !== "undefined") return scscpq_fill(data, prefix);
    for (var field in data) {
		value=data[field];
        field="#" + prefix + field;
        switch ( jQuery(field).prop('type') ) {
          case "undefined":
            break;
          case "text":
          case "textarea":
          case "select-one":
          case "hidden":
            jQuery(field).val(value);
            break;
          case "button":
            jQuery(field).prop("value", value);
          	break;
          case "radio":
          case "checkbox":
            jQuery(field).prop("checked", (value) ? true : false);
            break;
          default:
            jQuery(field).html(value);
        }
	}
}
EOT;

        $function_js=$this->function . "_js";
        if (method_exists($this,$function_js)) $results[]=$this->$function_js();
		switch (TRUE) {
          case (!isset($this->options->js)):
          	break;
          case (is_array($this->options->js)):
          	$results[]=implode("\n",$this->options->js);
            break;
          default:
          	$results[]=$this->options->js;
            break;
		}
		$results[]="</script>";
        $results[]="";
        return implode("\n",$results);
    }
}
?>