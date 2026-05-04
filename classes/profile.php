<?php
$database->profile=new profile_class();
class profile_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['12/06/2024']="Add SESSION:harcourt_profile";
        $results['08/23/2024']="Add generate";
        $results['08/14/2024']="Initial release";
        return $results;
    }
    function __construct() {
        $this->data=new profile_data_class();
        $this->constant=new profile_constant_class();
    }
    function load() {
        global $database, $menu;
        $this->data=new profile_data_class();
        switch (TRUE) {
          case ( func_num_args() == 1) && (is_object(func_get_arg(0))):
            $obj=func_get_arg(0);
            break;
          case (isset($_SESSION['harcourt_profile'])):
            $obj=$_SESSION['harcourt_profile'];
            break;
          case (isset($_COOKIE['harcourt_profile'])):
            $obj=$database->fetch_json($_COOKIE['harcourt_profile']);
            break;
          default:
            $obj=new stdClass();
        }
        $this->data->email=(strlen($obj->email)) ? $obj->email : $_COOKIE['harcourt'];
        $this->data->name_first=$obj->name_first;
        $this->data->name_last=$obj->name_last;
        $this->data->name="{$this->data->name_first} {$this->data->name_last}";
        $this->data->generate=intval($obj->generate);
        $database->cad->read($obj->cad_code, "code");
        if ($database->cad->meta->rows) {
            $this->data->cad_code=$obj->cad_code;
            $this->data->cad_name=$database->cad->data->name;
        }
        switch (TRUE) {
          case (strlen($obj->language_code)):
            $this->data->language_code=$obj->language_code;
            break;
          case ($_SESSION['user']->language_code):
            $this->data->language_code=$_SESSION['user']->language_code;
            break;
          default:
            $this->data->language_code="EN";
        }
        $this->data->consent=intval($obj->consent);
        if ($_SESSION['user']->id) {
            $this->data->company_name=$_SESSION['user']->company_name;
            $this->data->address=$_SESSION['user']->address;
            $this->data->city=$_SESSION['user']->city;
            $this->data->state=$_SESSION['user']->state;
            $this->data->zip=$_SESSION['user']->zip;
            $this->data->country_code=$_SESSION['user']->country_code;
          } else {
            $this->data->company_name=$obj->company_name;
            $this->data->address=$obj->address;
            $this->data->city=$obj->city;
            $this->data->state=$obj->state;
            $this->data->zip=$obj->zip;
            $this->data->country_code=$obj->country_code;
        }
        $this->cookie();
    }
    function cookie() {
        global $database, $menu;
        $_SESSION['harcourt_profile']=$this->data;
        $menu->cookie(json_encode($this->data), "harcourt_profile");
    }
    function forced($cad_code=FALSE) {
        global $database;
        foreach ($database->registry->address->profile as $field => $value) {
            if ( ($value == "required") && (!strlen($this->data->$field)) ) return FALSE;
        }
        if ( ($cad_code) && (!strlen($this->data->cad_code)) ) return FALSE;
        return TRUE;
    }
    function quick_cad_message() {
        switch (TRUE) {
          case (!isset($_SESSION['user'])):
          case ($_SESSION['user']->status != "Active"):
          case (!$_SESSION['user']->cad):
            return "";
          case (!strlen($this->data->cad_name)):
          case (!strlen($this->data->email)):
            return "Please complete profile to download CAD";
          default:
            $text=array();
            $text[]= "CAD Format: {$this->data->cad_name}.";
            $text[]=($this->data->generate) ? "CAD emailed to {$this->data->email} when complete." : "CAD downloaded when complete.";
            $text[]="Click PROFILE to change.";
            return implode(" ", $text);
        }
    }
    function quick_cad_access() {
        switch (TRUE) {
          case (!isset($_SESSION['user'])):
          case ($_SESSION['user']->status != "Active"):
          case (!$_SESSION['user']->cad):
            return FALSE;
        }
        return TRUE;
    }
    function enter($options=array()) {
        global $database, $forms, $menu;
        if (!array_key_exists("profile_tab", $options)) $options['profile_tab']="";
        if (!array_key_exists("success_url", $options)) $options['success_url']="";
        if (!array_key_exists("register", $options)) $options['register']=FALSE;
        if (!array_key_exists("notice", $options)) $options['notice']=FALSE;
        $this->address();
        $results=array();
        $results[]=$this->enter_css();
        $results[]=$this->enter_js();
        $results[]=$forms->hidden("success_url", $options['success_url'], "", "scscpq_input");
        $results[]=$forms->hidden("profile_tab", $options['profile_tab'], "", "scscpq_input");
        if (strlen($database->profile->data->company_name)) $results[]="<p class=profile_welcome>Welcome " . $database->profile->data->company_name . "!</p>";
        if (!$options['register']) $results[]="<div class=profile_box>";
        $results[]="<div id=profile_message></div>";
        $results[]="<table class=noborder>";
        if ($options['notice']) $results[]="<div>{$database->registry->local->profile_notice}</div>";
        $results[]=$this->address->input(array("class"=>"scscpq_input", "legend"=>FALSE));
        if (!$options['register']) {
            $results[]="<tr>";
            $text=array("Email CAD?");
            $text[]=$forms->checkbox("profile_generate", 1, $this->data->generate, array("class"=>"scscpq_input"));
            $results[]="<td>" . implode(" ", $text) . "</td>";
            $text=array();
            $text[]="Email CAD Checked:";
            $text[]="&bull; Continue browsing immediately, CAD will be emailed to you.";
            $text[]="&bull; Check your spam folder for messages from partserver@partcommunity.com";
            $text[]="&bull; Best used when you can wait for CAD.";
            $text[]="";
            $text[]="Email CAD Unchecked:";
            $text[]="&bull; CAD will be downloaded when complete.";
            $text[]="&bull; Pause browsing until generated.";
            $text[]="&bull; Best used when you need CAD <b>now</b>";
            $text[]="";
            $text[]="CAD generations takes around 30 seconds to over 2 minutes to complete.";
            $results[]="<td>" .  implode("<br>", $text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td><span id=cad_code_error>CAD Format</span></td>";
            $select_options=array("columns"=>2);
            $forms->options=array("class"=>"scscpq_input");
            if ($database->user->access("Administrator", FALSE)) $select_options['part2cad']=TRUE;
            $results[]="<td>" . $database->cad->picklist($database->profile->data->cad_code, $select_options, $forms->options) . "</td>";
            $results[]="</tr>";
        }
        $results[]=$this->address->legend();
        $results[]="</table>";
        if (!$options['register']) {
            $text=($_SESSION['user']->id) ? "Update Profile" : "Submit";
            $results[]="<p>" . $forms->button($text, array("class"=>"small_button","onclick"=>"profile_ajax('profile');")) . "</p>";
        }
        $results[]="<p>Note: This should be a one-time verification only. Your email address will be kept strictly confidential and will not be disclosed to a third party. We respect your privacy and trust in Harcourt - <b>we will not spam you.</b></p>";
        if (!$options['register']) $results[]="</div>";
        return implode("", $results);
    }
    function address() {
        global $database;
        if (isset($this->address)) return;
        $database->user->read(isset($_SESSION['user']) ? $_SESSION['user']->id : 0);
        $this->load();
        if ($this->data->name_first == $this->constant->name_first) $this->data->name_first="";
        if ($this->data->name_last == $this->constant->name_last) $this->data->name_last="";
        $this->address=new address_class("profile", $database->profile->data, array("order"=>array("email", "name")));
        $this->address->registry->email="required";
        $omit=array();
        $required=array();
        if (strlen($database->user->data->company_name)) {
            $omit[]="company_name";
          } else {
            $required[]="company_name";
        }
        if (!strlen($database->user->data->country_code)) $required[]="country_code";
        if ( ($this->address->registry->address == "required") && (!strlen($database->user->data->address)) ) $required[]="address";
        if ( ($this->address->registry->city == "required") && (!strlen($database->user->data->city)) ) $required[]="city";
        if ( ($this->address->registry->state == "required") && (!strlen($database->user->data->state)) ) $required[]="state";
        if ( ($this->address->registry->zip == "required") && (!strlen($database->user->data->zip)) ) $required[]="zip";
        if (!sizeof($required)) {
            $omit[]="address";
            $omit[]="city";
            $omit[]="state";
            $omit[]="zip";
            $omit[]="country_code";
        }
        $this->address->options(array("omit"=>$omit));
    }
    function enter_js() {
        return <<< EOT
<script>
function profile_ajax(action) {
    data=scscpq_class_data('scscpq_input');
    data.action=action;
	jQuery.ajax({
		url: "/scscpq_api.php",
        type: "post",
	    dataType: "json",
		data: data,
        error: jQuery("#profile_message").html("Internal Error Detected"),
	    success: function(response) {
            if (response['profile_tab'].length) jQuery('#products_jquery_div').tabs({ active: response['profile_tab'] });
            if (response['success_url'].length) window.location.href=response['success_url'];
            scscpq_fill(response['html']);
            error=response['error'];
            for (var field in error) {
                if (error[field] == 1) {
                    jQuery("#" + field).removeClass('profile_error').addClass('profile_error');
                } else {
                    jQuery("#" + field).removeClass('profile_error');
                }
            }
            jQuery("#profile_email").focus();
        }
    })
}
</script>
EOT;
    }
    function enter_css() {
        return <<< EOT
<style>
.profile_error {
    border: 1px solid red !important;
}
.profile_welcome {
    color: #000;
    font-family: 'Rajdhani';
    font-size: 22px;
    font-weight: 700;
    margin-top: 2em;
}
#profile_message {
    color: #000;
    font-size: 1.1em;
    font-weight: 700;
}
.profile_box {
    max-width: 1000px;
    border: 1px solid black;
    padding: 1em;
    margin: 1em;
}
.small_button {
    color: #FFF;
    cursor: pointer;
    border-radius: 4px;
    font-family: 'Rajdhani';
    font-size: 1.25rem;
    font-weight: 600;
    background: linear-gradient(0deg, rgba(115,14,28,1) 0%, rgba(229,28,56,1) 150%);
    border-radius: 4px;
    padding: 0 1em;
}
</style>
EOT;
    }
}
class profile_data_class {
    // Local
    var $email;
    var $name_first;
    var $name_last;
    var $name;
    var $cad_code;
    var $cad_name;
    var $language_code;
    var $generate=0;
    var $consent=0;
// Global
    var $company_name;
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country_code;
}
class profile_constant_class {
    var $name_first="?";
    var $name_last="?";
}
?>