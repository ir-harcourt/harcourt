<?php
/*
Initial action::
<blank>		None
remote		Remote access allowed
register    New user
cookie		Cookie not found

Ajax action:
register	Initial login (new remote is matching domman, otherwise new user
remote      Enter remote token
token       Verify remote token
*/
require_once "scs_header.php";
require_once "classes/scsmail_rev1.php";
require_once "recaptcha.php";
$obj=new remote_access_class();
class remote_access_class {
	var $action;
    var $access;
	var $results=array();
    var $error=array();
    var $meta;
    var $trace=array();
    function scs_table_version() {
    	$results=array();
        $results['10/01/2025']="Add harcourt_remote_addr()";
        $results['05/06/2025']="Update harcourt_profile";
        $results['08/23/2024']="Live site invoked with HTTP_X_REQUESTED_WITH";
        $results['08/16/2024']="Add full address";
        $results['08/31/2023']="Ajax email lookup";
        $results['04/05/2023']="Extend expiry";
        $results['10/26/2020']="Initial release";
        return $results;
    }
    function __construct() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
	    $this->access=$database->remote->access($_COOKIE['harcourt'],$_COOKIE['remote']);
	    $this->action=$database->remote->data->access;
        switch (TRUE) {
            case ( (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) && ($_SERVER['REQUEST_METHOD'] == "POST") ):
               $this->json();
               break;
             default:
               $this->html();
           }
    }
    function json() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $this->response=new stdClass();
        $this->response->error=new stdClass();
        $this->response->menu=new stdClass();
        $this->response->html=new stdClass();
    	$this->response->html->scscpq_input_status="";
		switch ($_POST['action']) {
          case "register":
          	$this->json_register();
          	break;
          case "remote":
          	$this->json_verify();
          	break;
		  case "token":
          	$this->json_email($database->profile->data->email);
            $this->response->html->unlock_code_error="";
            $this->response->html->unlock_code_message="Access token has been sent";
          	break;
		}
		die(json_encode($this->response));
    }
    function json_register() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
		$this->recaptcha=new recaptcha_class("newuser",1,array("local"=>TRUE));
		$this->recaptcha->score($_POST['g-recaptcha-response']);

        $address=new address_class("profile", $database->profile->data, array("order"=>array("email", "name")));
        $address->registry->email="required";
        $address->verify();
        $database->remote->access($address->data->email);
        if (strlen($database->remote->data->access)) {
            $menu->cookie($address->data->email);
            $this->json_email($address->data->email);
            $this->response->html->scscpq_content=$this->html_remote(TRUE);
            return;
        }
        foreach ($address->input as $field => $value) {
            $this->response->error->$field=(array_key_exists($field, $forms->error)) ? 1 : 0;
        }
        if (sizeof($forms->error)) {
            $this->response->html->profile_message="Correct " . sizeof($forms->error) . " error(s)";
          } else {
            $this->response->html->profile_register=$this->json_register_update();
        }
    }
    function json_register_update() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $database->user->data=new user_data_class();
        $database->user->data->ip=harcourt_remote_addr();
        $database->user->data->company_name=$database->profile->data->company_name;
        $database->user->data->address=$database->profile->data->address;
        $database->user->data->city=$database->profile->data->city;
        $database->user->data->state=$database->profile->data->state;
        $database->user->data->zip=$database->profile->data->zip;
        $database->user->data->country_code=$database->profile->data->country_code;
        $database->user->data->last_login=strtotime("now");
        $database->user->data->recaptcha_score=$this->recaptcha->score;
        $database->user->update(FALSE);
        if ($database->user->meta->error) return "Interal error detected";
        $database->user->data->ip=harcourt_remote_addr();
        $menu->cookie($database->profile->data->email);
        $_SESSION['user']=$database->user->data;
        $database->profile->cookie();

        $address=new address_class("profile", $database->profile->data, array("order"=>"email"));

        $options=array();
        $options['user_id']=$database->user->data->id;
        $comment=$address->output("html:array");
        $comment[]="reCAPTCHA Score: " . $this->recaptcha->score;
        $options['comment']=implode("<br>", $comment);
	    $database->log->update("user:signup",$options);
	    $mail=new scsmail_class("register",$database->user->data);

        $comment[]="";
        $comment[]="Browser: " . $_SERVER['HTTP_USER_AGENT'];
        $comment[]="IP Address: " . harcourt_remote_addr();
        $comment[]="DNS Name: " . gethostbyaddr(harcourt_remote_addr());
        $comment[]="Date/Time: " . date('m/d/Y h:i A');
        $mail->html("<p class='standard'>" . implode("<br>",$comment) . "</p>");
        $mail->send();

        return "<h2 class='page-subtitle editable h2-login'>Congratulations, you now have access!<h2>";
    }
    function json_verify() {
    	global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
		if (!isset($_SESSION['remote'])) $_SESSION['remote']=0;
		$timestamp=hexdec($database->remote->data->unlock_code);
		$this->expiry=date("r",$timestamp);
        $this->unlock_code=trim($_POST['unlock_code']);
        switch (TRUE) {
          case (!strlen($this->unlock_code)):
          	$error="Cannot be blank";
          	break;
          case ($timestamp < strtotime("now")):
          	$error="Your access token has expired. A new token must be resent";
          	break;
          case ($_SESSION['remote'] > 1):
          	$error="Excess failures. A new token must be resent";
          	break;
          case ($this->unlock_code != $database->remote->data->unlock_code):
          	$_SESSION['remote']++;
          	$error="Invalid access token (" . $_SESSION['remote'] . " of 3)";
            break;
		}

		$this->count=$_SESSION['remote'];
        if ($error) {
        	$this->response->html->unlock_code_message="";
        	$this->response->html->unlock_code_error=$error;
		  } else {
	        $database->remote->expiry();
	        $database->remote->token();
	        $database->remote->unlock_code();
            $database->remote->data->unlock_code=dechex(strtotime("now"));
    		$database->remote->update(TRUE);
          	$menu->cookie($database->remote->data->token, "remote");
            $menu->login_update(1);
            $this->response->html->scscpq_content=$this->json_remote_message();
            $this->response->menu->scscpq_newuser=0;
            if ($_SESSION['user']->catalog) $this->response->menu->scscpq_catalog=1;
		}
    }
    function json_remote_message() {
    	global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
    	$results=array();
	    $results[]="<h2 class='page-subtitle editable h2-login'>Congratulations, you now have access!<h2>";
		switch (TRUE) {
          case (fn_development_server()):
          	$url="/catalog.php";
            break;
	      case ($_SESSION['user']->language_code == "FR"):
          	$url="/catalogue/?lang=fr";
			break;
		  default:
			$url="/catalog";
		}
        if ($_SESSION['user']->catalog) $results[]="<p>" . $forms->button("Click Here To Enter Our Catalog" ,array("class"=>"red-button","onclick"=>"window.location.replace('{$url}');")) . "</p>";
        return implode("",$results);
    }
    function json_email($email) {
    	global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
		$_SESSION['remote']=0;
        $database->remote->read($email);
        $database->remote->data->email=$email;
	    $database->remote->expiry();
	    $database->remote->token();
	    $database->remote->unlock_code();
    	$database->remote->update($database->remote->meta->rows);
	    $mail=new scsmail_class("remote",$database->user->data);
	    $mail->recipient($email, "");
	    $html=array();
        $html[]="Your access code is: <b>" . $database->remote->data->unlock_code . "</b>";
	    $mail->html("<p class='standard'>" . implode("<br>",$html) . "</p>");
        $mail->send();
        return "Your access token has been emailed to: <b>{$email}</b>";
    }
    function html() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
		$this->recaptcha=new recaptcha_class("newuser",1,array("local"=>TRUE));
    	if ($this->access) $menu->login_update(1);
        switch (TRUE) {
          case ($this->action):
		  case (isset($_SESSION['user']->bot_id)):
  		  case ( ($_SESSION['user']->id) && ($_SESSION['user']->status != "Pending") ):
          	break;
  		  case (!$_SESSION['user']->id):
			$this->action="register";
	    	if ($_SESSION['impersonate']) {
	        	$database->user->data=$_SESSION['user'];
	      	  } else {
				$database->user->data=new user_data_class();
			}
            break;
  		  case ($_SESSION['user']->status == "Pending"):
			$this->action="pending";
            break;
		}
        if (fn_development_server()) $menu->head();
	    unset($forms->message[0]);
        print $this->css();
		print $this->js();
        print $forms->open(array(), array("onsubmit"=>"return false;"));
		print "<div id=scscpq_content>";
		print "<div id=scscpq_input_status></div>";;
        switch ($this->action) {
          case "register":
          	print $this->html_register();
          	break;
  		  case "remote":
  		  case "token":
            if ($this->action == "token") $this->json_email($database->profile->data->email);
          	print $this->html_remote(TRUE);
          	break;
          case "pending":
			print $this->html_pending();
          	break;
		}
	    print $forms->close();
        print "</div>";
        if (fn_development_server()) $menu->copyright();
    }
    function error($field, $text) {
    	$this->results["{$field}"]=$text;
        if (strlen($text)) $this->error[$field]=$text;
    }
    function html_register() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $database->profile->load();
        $results=array();
		$results[]=$this->recaptcha->js();
        $text=array();
	    if ($_SESSION['impersonate']) $text[]="User impersonating / no update allowed.";
	    $results[]="<h2 class='page-subtitle editable h2-login'>" . implode(" ",$text) . "</h2>";
        $results[]="<div id=profile_register>";
        $text=array();
        $text[]="Please complete the fields below and click \"Submit\" to become an approved Harcourt &reg; user. Once you have completed the form, our team will be in touch to confirm your access.";
        $text[]="Only email address is required if your company is already registered and you are working remotely.";
        $text[]="Thank you!";
	    $results[]="<p class='company_registered'>" . implode("<br>", $text) . "</p>";
        $results[]=$database->profile->enter(array("register"=>TRUE));
        $results[]=$this->recaptcha->button("Submit",array("class"=>"red-button"));
        $results[]="<div id=recaptcha_status></div>";
        $results[]="</div>";
        return implode("",$results);
    }

    function html_pending() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
    	$results=array();
        $results[]="<p>You access request is pending approval. Your request will be approved as soon as possible.</p>";
        return implode("",$results);
    }
    function html_remote($verbose=TRUE) {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
    	$results=array();
	    $results[]="<h2 class='page-subtitle editable h2-login'>Welcome " . $database->user->data->company_name . " Employee</h2>";
        $results[]="<p>Access token has been mailed from " . $database->registry->email->webmaster . ". Please check your inbox or spam folder.</p>";
        $text=array();
        switch (TRUE) {
          case (!$verbose):
            break;
           case ($this->remote == "expiry"):
           case ($this->remote == "token"):
	        $text[]="Your remote access token has expired.";
	        $text[]="A new access code must be resent.";
	        break;
		}
        $results[]="<div id=scscpq_remote>" . implode(" ",$text)  . "</div>";
        $text=array("Enter Access Token:");
		$text[]=$forms->text("unlock_code","",10,10,"",array("class"=>"scscpq_input"));
		$text[]=$forms->button("Submit",array("onclick"=>"fn_newuser('remote');","class"=>"red-button"));
        $results[]="<div style='font-size: 20px;'>" . implode(" ",$text) . "</div>";
        $results[]="<div id=unlock_code_error></div>";
        $text=array();
        $text[]=$forms->button("Resend Access Token",array("onclick"=>"fn_newuser('token');","class"=>"red-button"));
        $text[]="<span id=unlock_code_message></span>";
        $results[]="<div>" . implode(" ",$text) . "</div>";
        return implode("",$results);
	}
    function css() {
        $this->trace[]=__FUNCTION__;
        return <<< EOT
<style>
#scscpq_content {
    color: #000;
	background-color: #fff;
	font-family: "Open Sans",sans-serif;
    width: 100%;
    padding: 0px;
    margin: 0px;
}
#scs_form {display: flex;flex-wrap:wrap; background: transparent;}
form h2{flex-basis:100%; margin-top: 0; }

.scscpq_label {font-weight: 700; padding: 10px !important;}
.scscpq_input {padding: 10px;}

table.scscpq_table { width: 100%; border: 0px; }
table.scscpq_table th { padding: 2px; vertical-align: bottom; text-align: center; }
table.scscpq_table td { padding: 2px; vertical-align: top; }

.newuser_ok {border: 1px solid black; }
.newuser_error { border: 2px solid red; }

// .red-button { width: 220px !important; border-radius: 4px; text-align: center; text-transform: uppercase; padding: 20px 5px; display: inline-block; position: relative; z-index: 1; margin-top: 3.125rem; font-family: 'Rajdhani'; font-size: 1.25rem !important; font-weight: 600; border: none; background: linear-gradient(0deg, rgba(115,14,28,1) 0%, rgba(229,28,56,1) 100%);color:#fff; cursor: pointer}
.red-button { border-radius: 4px; text-align: center; text-transform: uppercase; padding: 20px 30px; display: inline-block; position: relative; z-index: 1; margin-top: 3.125rem; font-family: 'Rajdhani'; font-size: 1.25rem !important; font-weight: 600; border: none; background: linear-gradient(0deg, rgba(115,14,28,1) 0%, rgba(229,28,56,1) 100%);color:#fff; cursor: pointer}

</style>

EOT;
    }
	function js() {
        $this->trace[]=__FUNCTION__;
        return <<< EOT
<script>
jQuery(document).keypress(function(event) {
    if(event.key == "Enter") alert("The ENTER key is not available on this page. Please click the SUBMIT button.");
})
function recaptcha_script(token) {
	jQuery("#recaptcha_button").hide();
	jQuery("#recaptcha_status").html("Working ...");
	jQuery("#recaptcha_status").show();
	fn_newuser('register');
}

function fn_newuser(action) {
	var data=new Object();
	var obj=jQuery("#scs_form :input");
    obj.each(function() {
        if (this.name.length) data[this.name] = jQuery(this).val();
    });
    data['action']=action;
	jQuery.ajax({
        url: "/newuser.php",
        type: "post",
	    dataType: "json",
		data: data ,
	    success: function(response) {
            fields=response['html'];
            for (var field in fields) {
                jQuery("#" + field).html(fields[field]);
            }
            fields=response['error'];
            for (var field in fields) {
                if (fields[field] == 1) {
                    jQuery("#" + field).removeClass('profile_error').addClass('profile_error');
                } else {
                    jQuery("#" + field).removeClass('profile_error');
                }
            }
            fields=response['menu'];
            for (var field in fields) {
                if (fields[field] == 1) {
                    jQuery("." + field).show();
                } else {
                    jQuery("." + field).hide();
                }
            }
    	    jQuery("#recaptcha_button").show();
	        jQuery("#recaptcha_status").hide();
        },
        error: function(xhr, response) {
        	console.log(xhr);
			jQuery("#scscpq_input_status").html("Internal Error");
        }
	});

}
</script>

EOT;
	}
}
?>
