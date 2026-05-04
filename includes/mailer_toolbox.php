<?php
require_once "classes/mailform.php";
require_once "classes/scsmail_rev1.php";
class mailer_toolbox {
    var $function;
    var $role;
    var $options=array();
	var $input;
	function scs_table_version() {
		$results=array();
        switch ($this->function) {
          case "entry":
            $results['08/06/2025']="Initial release based on mailform_rev1.php: 03/26/2023";
            break;
          case "toolbox":
            $results['08/07/2025']="Initial release based on mailform_toolbox_rev0.php: 04/19/2021";
            break;
          default:
            $results['08/07/2025']="Initial release";
        }
        return $results;
    }
	function __construct($function, $role, $options=array()) {
        global $database, $forms;
        $this->function=$function;
        $this->role=$role;
        $this->options=$options;
        $this->input=new stdClass;
    	if (strlen($role)) $database->user->access($role);
        if (!property_exists($database->registry, "email")) die("Registry: Email not set up");
		if (!method_exists($this,$function)) die("Invalid function: {$function}");
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->$function();
          } else {
            $this->response=new stdClass;
            $action="ajax_{$this->function}_{$_POST['action']}";
            if (method_exists($this, $action)) $this->$action();
            die(json_encode($this->response));
        }
    }
    function ajax_output_mailform() {
        global $database;
        $database->mailform->read($_POST['mailform'],"code");
        if (!$database->mailform->meta->rows) return;
        $this->response->recipient=($database->mailform->data->recipient) ? $database->mailform->data->recipient : $_SESSION['user']->email;
        $this->response->subject=$database->mailform->data->subject;
        $this->response->bcc=implode("\n", $database->mailform->data->bcc);
    }
    function output() {
        global $database, $forms, $menu;
        $this->debug=$database->user->access("Super User", FALSE);
	    $forms->title("Email Output Tester", key($this->scs_table_version()));
        if (fn_development_server()) $forms->message[]="AVG Firewall must be disabled";
        $this->output_input();
	    $menu->head();
	    $forms->message();
        print $this->output_js();
	    print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action");
	    switch (TRUE) {
	      case (in_array($this->input->action, array("", "enter", "mailform"))):
	      case (sizeof($forms->error)):
            $this->output_enter();
            break;
          default:
            $this->output_review();
            break;
        }
        print $forms->hidden_fields($this->input, array("class"=>"input"));
        print $forms->hidden_fields($this->secure, array("class"=>"input"));
	    print $forms->close();
	    $menu->copyright();
    }
    function output_input() {
        global $database, $forms;
		$this->input->action=$_POST['action'];
        if ( (!$this->debug) && (!strlen($_POST['mailform'])) ) {
            $query=array("select * from mailform");
            $query[]="where recipient=''";
            $query[]="order by id";
            $query[]="limit 1";
            $database->mailform->query($query);
            $database->mailform->fetch(TRUE);
            $this->input->mailform=$database->mailform->data->code;
          } else {
	        $this->input->mailform=$_POST['mailform'];
            $database->mailform->read($this->input->mailform,"code");
        }
        $this->secure=new stdClass;
        if ( (!$this->input->action) || (!$this->debug) ) {
	        $this->secure->base_href=$database->registry->email->base_href;
	        $this->secure->css=$database->registry->email->css;
	        $this->secure->webmaster=$database->registry->email->webmaster;
	        $this->secure->webmaster_name=$database->registry->email->webmaster_name;
	        $this->secure->host=$database->registry->email->host;
	        $this->secure->secure=$database->registry->email->secure;
	        $this->secure->smtp_port=$database->registry->email->smtp_port;
	        $this->secure->authentication=$database->registry->email->authentication;
	        $this->secure->username=$database->registry->email->username;
	        $this->secure->password=$database->registry->email->password;
          } else {
	        $this->secure->base_href=trim($_POST['base_href']);
	        $this->secure->css=trim($_POST['css']);
	        $email=new email_class($_POST['webmaster'],TRUE);
	        $this->secure->webmaster=$email->address;
	        if ($email->error) $forms->error('webmaster',$email->error);
	        $this->secure->webmaster_name=trim($_POST['webmaster_name']);
	        $this->secure->host=trim($_POST['host']);
	        $this->secure->secure=$_POST['secure'];
	        $this->secure->smtp_port=intval($_POST['smtp_port']);
	        $this->secure->authentication=intval($_POST['authentication']);
	        $this->secure->username=trim($_POST['username']);
	        $this->secure->password=trim($_POST['password']);
        }

        if (!$this->input->action) {
            $this->input->recipient=$_SESSION['user']->email;
            return;
        }
        $this->input->recipient=$_POST['recipient'];
        $email=new email_class($this->input->recipient);
        if ($email->error) $forms->error('recipient',$email->error);
        $this->input->cc=array();
        $items=explode("\n", $_POST['cc']);
        $errors=array();
        foreach ($items as $item) {
            $item=trim($item);
            if (!strlen($item)) continue;
            $email=new email_class($item);
            if (in_array($email->address, $this->input->cc)) {
                $errors[]="{$item}: Duplicate entry";
                continue;
            }
            if ($email->error) $errors[]="{$item}: {$email->error}";
            $this->input->cc[]=$email->address;
        }
        if (sizeof($errors)) $forms->error("cc", implode("<br>", $errors));
        $this->input->bcc=array();
        $items=explode("\n", $_POST['bcc']);
        $errors=array();
        foreach ($items as $item) {
            $item=trim($item);
            if (!strlen($item)) continue;
            $email=new email_class($item);
            if (in_array($email->address, $this->input->bcc)) {
                $errors[]="{$item}: Duplicate entry";
                continue;
            }
            if ($email->error) $errors[]="{$item}: {$email->error}";
            $this->input->bcc[]=$email->address;
        }
        if (sizeof($errors)) $forms->error("bcc", implode("<br>", $errors));
        $this->input->message=trim($_POST['message']);
        if (!strlen($this->input->message)) $forms->error('message', "Cannot be blank");
//tomhayes
        $this->input->attachment_file=$_POST['attachment_file'];
        switch (TRUE) {
          case (!sizeof($_FILES)):
          case (!strlen($_FILES['upload']['name'])):
            break;
          case (intval($_FILES['upload']['error'])):
            $forms->error("upload","Upload error# " . $_FILES['upload']['error']);
            break;
          case (!intval($_FILES['upload']['size'])):
            $forms->error("upload","Empty file");
            break;
          default:
            if ( ($this->input->attachment_file) && (file_exists($this->input->attachment_file)) )  unlink($this->input->attachment_file);
            $filename=$_FILES['upload']['name'];
            if (!move_uploaded_file($_FILES['upload']['tmp_name'],$filename)) {
                $forms->error("upload","Cannot access temp folder: " . sys_get_temp_dir());
              } else {
                $this->input->attachment_file=$filename;
            }
        }
        $this->input->attachment=intval($_POST['attachment']);
        if ( ($this->input->attachment) && (!$this->input->attachment_file) ) $forms->error("upload","No file specified");
    }
    function output_enter() {
        global $database, $forms;
        print "<table class='standard noborder'>";
        print "<tr>";
        print "<td width='20%'>Document</td>";
        if ($this->debug) {
            $database->mailform2=new mailform_class();
            $text=$database->mailform2->select($this->input->mailform,array(),array("class"=>"input","onchange"=>"fn_ajax('mailform');"));
          } else {
            $text="<b>{$database->mailform->data->name}</b>";
            print $forms->hidden("mailform", $database->mailform->data->code);
        }
        print "<td width='80%'>{$text}</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Subject</td>";
        print "<td><b><span id=subject>{$database->mailform->data->subject}</span></b></td>";
        print "</tr>";
        print "<tr>";
        print "<td>Recipient</td>";
        $text=array();
        if ($this->debug) {
            $text[]=$forms->text("recipient",$this->input->recipient,60);
            if ($forms->error_text['recipient']) $text[]=$forms->error_text['recipient'];
          } else {
            print $forms->hidden("recipient", $this->input->recipient);
            $text[]="<b>{$this->input->recipient}</b>";
        }
        print "<td>" . implode("<br>",$text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>CC</td>";
        $text=array();
        $text[]=$forms->textarea("cc", $this->input->cc, 3, 60);
        if ($forms->error_text['cc']) $text[]=$forms->error_text['cc'];
        print "<td>" . implode("<br>",$text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>BCC</td>";
        $text=array();
        $text[]=$forms->textarea("bcc", $this->input->bcc, 3, 60);
        if ($forms->error_text['bcc']) $text[]=$forms->error_text['bcc'];
        print "<td>" . implode("<br>",$text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Message</td>";
        $text=array();
        if ($forms->error_text['message']) $text[]=$forms->error_text['message'];
        $text[]=$forms->textarea("message",$this->input->message,20,60);
        print "<td>" . implode("<br>", $text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Attach file? " . $forms->checkbox("attachment",1,$this->input->attachment) . "</td>";
        $text=array();
        if ($this->input->attachment_file) $text[]=$this->input->attachment_file;
        $text[]= $forms->file("upload",40);
        if ($forms->error_text['upload']) $text[]=$forms->error_text['upload'];
        print "<td>" . implode("<br>",$text) . "</td>";
        print "</tr>";
        print "</table>";
        if ($this->debug) {
            $text=array( $forms->checkbox("email_setting_check",1,0,array("onclick"=>"div_show('email_setting_div',this.checked);")) );
            $text[]="<b>Email Settings</b>";
            print "<div>" . implode(" ",$text) . "</div>";
            print "<div id='email_setting_div' style='display:none;'>";
            print "<table class='standard noborder'>";
            print "<tr>";
            print "<td>Base href</td>";
            print "<td>" . $forms->text("base_href",$this->secure->base_href,60,60) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>CSS</td>";
            print "<td>" . $forms->text("css",$this->secure->css,60,60) . "</td>";
            print "</tr>";
            print "</table>";
            print "</div>";
            $text=array( $forms->checkbox("email_server_check",1,0,array("onclick"=>"div_show('email_server_div',this.checked);")) );
            $text[]="<b>Email Server Settings</b>";
            print "<div>" . implode(" ",$text) . "</div>";
            print "<div id='email_server_div' style='display:none;'>";
            print "<table class='standard noborder'>";
            print "<tr><td colspan=2><br><b>Webmaster Settings</b></td></tr>";
            print "<tr>";
            print "<td>Webmaster email</td>";
            print "<td>" . $forms->text("webmaster",$this->secure->webmaster,60,60) . " " . $forms->error_text["webmaster"] . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>Webmaster name</td>";
            print "<td>" . $forms->text("webmaster_name",$this->secure->webmaster_name,60,60) . "</td>";
            print "</tr>";
            print "</tr>";
            print "<tr><td colspan=2><br><b>PHPMailer Settings</b></td></tr>";
            print "<tr>";
            print "<td>Host</td>";
            print "<td>" . $forms->text("host",$this->secure->host,60) . " " . $forms->error_text["host"] . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>Secure?</td>";
            print "<td>" . $forms->select("secure",array("tls","ssl"),$this->secure->secure) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>SMTP Port#</td>";
            print "<td>" . $forms->text("smtp_port",$this->secure->smtp_port,5,5) . " (Default: 25)</td>";
            print "</tr>";
            print "<tr><td colspan=2><br>&nbsp;</td></tr>";
            print "<tr>";
            print "<td>SMTP Authentication?</td>";
            print "<td>" . $forms->checkbox("authentication",1,$this->secure->authentication) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>Username</td>";
            print "<td>" . $forms->text("username",$this->secure->username,60) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>Password</td>";
            print "<td>" . $forms->text("password",$this->secure->password,60) . "</td>";
            print "</tr>";
            print "</table>";
            print "</div>";
        }
        print "<p class=left>" . $forms->button("Review >>",array("onclick"=>"fn_action('review',this);")) . "</p>";
    }
    function output_review() {
    global $database, $forms;
        $options=array();
        $options['debug']=2;
        $options['base_href']=$this->secure->base_href;
        $options['css']=$this->secure->css;
        $options['bcc_load']=$this->secure->default_bcc;
        $mail=new scsmail_class($this->input->mailform,$_SESSION['user'],$options);
        $mail->recipient($this->input->recipient,"");
        foreach ($this->input->cc as $email) {
            $mail->recipient($email,"","cc");
        }
        foreach ($this->input->bcc as $email) {
            $mail->recipient($email,"","bcc");
        }
        if ($this->input->attachment) $mail->attachment($this->input->attachment_file);
        $mail->host=$this->secure->host;
        $mail->secure=$this->secure->secure;
        $mail->port=$this->secure->smtp_port;
        $mail->authentication=$this->secure->authentication;
        $mail->username=$this->secure->username;
        $mail->password=$this->secure->password;
        $mail->html($this->input->message);
        print "<table class='standard noborder'>";
        print "<tr>";
        print "<td width='20%'>Document code</td>";
        print "<td width='80%'>{$database->mailform->data->code}</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Document name</td>";
        print "<td>{$database->mailform->data->name}</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Subject</td>";
        print "<td>{$database->mailform->data->subject}</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Sender</td>";
        print "<td>{$this->secure->webmaster}</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Recipient</td>";
        print "<td>{$this->input->recipient}</td>";
        print "</tr>";
        if (sizeof($this->input->cc)) {
            print "<tr>";
            print "<td>CC</td>";
            print "<td>" . implode("<br>", $this->input->cc) . "</td>";
            print "</tr>";
        }
        if (sizeof($this->input->bcc)) {
            print "<tr>";
            print "<td>BCC</td>";
            print "<td>" . implode("<br>", $this->input->bcc) . "</td>";
            print "</tr>";
        }
        if ($this->input->attachment) {
            print "<tr>";
            print "<td>Attached file:</td>";
            print "<td>{$this->input->attachment_file}</td>";
            print "</tr>";
        }
        print "</table>";
        if ($this->input->action == "send") {
            print "<p>Sending message ...<br>";
            $results=$mail->send();
            print "</p>";
            print "<p>Results: {$results}</p>";
            } else {
            print "<p>Results: <b>" . $mail->review() . "</b></p>";
        }
        $buttons=array();
        $buttons[]=$forms->button("<< Back",array("onclick"=>"fn_action('enter',this);"));
        $buttons[]=$forms->button("Send >>",array("onclick"=>"fn_action('send',this);"));
        print "<p>" . implode(" ",$buttons) . "</p>";
    }
    function output_js() {
        return <<< EOT

<script>
function fn_action(action, obj) {
	if (action=='send') obj.style.display ='none';
    jQuery("#action").val(action);
    jQuery("#scs_form").submit();
}
function fn_ajax(action) {
    request=scscpq_class_data("input");
    request['action']=action;
    jQuery.ajax({
        dataType: 'json',
        method: 'post',
        data: request,
        success:function(response) { scscpq_fill(response) }
    });
}
</script>
EOT;
    }
    function maintain() {
        global $database, $forms, $menu;
	    $forms->title("Email Documents", key($this->scs_table_version()));
        $this->maintain_input();
	    $menu->head();
	    $forms->message();
        print $this->maintain_js();
	    print $forms->open();
	    print $forms->hidden("action");
	    print $forms->hidden("record_id");
	    switch (TRUE) {
	      case (($this->input->action == "update") && (sizeof($forms->error))):
	      case ($this->input->action == "maintain"):
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='20%'>Code</td>";
            $text=array($forms->text("code",$database->mailform->data->code,20,20));
            if ($forms->error_text['code']) $text[]=$forms->error_text['code'];
	        print "<td width='80%'>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Name</td>";
            $text=array($forms->text("name",$database->mailform->data->name,60,60));
            if ($forms->error_text['name']) $text[]=$forms->error_text['name'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Subject</td>";
	        print "<td>" . $forms->text("subject",$database->mailform->data->subject,60,60);
	        print "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Recipient</td>";
            $text=array( $forms->text("recipient",$database->mailform->data->recipient,80) );
            if ($forms->error_text['recipient']) $text[]=$forms->error_text['recipient'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>BCC</td>";
            $text=array( $forms->textarea("bcc",$database->mailform->data->bcc,3,80) );
            if ($forms->error_text['bcc']) $text[]=$forms->error_text['bcc'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>CSS (optional)</td>";
            $text=array($forms->text("css",$database->mailform->data->css,60));
	        if ($forms->error_text['css']) $text[]=$forms->error_text['css'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Header HTML</td>";
	        print "<td>" . $forms->textarea("header_html",$database->mailform->data->header_html,20,80) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Footer HTML</td>";
	        print "<td>" . $forms->textarea("footer_html",$database->mailform->data->footer_html,20,80) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Active</td>";
	        print "<td>" . $forms->checkbox("active",1,$database->mailform->data->active) . "</td>";
	        print "</tr>";
	        print "</table>";
	        $buttons=array();
	        $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update'," . fn_escape($this->input->record_id) . ",'');"));
	        $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel','0','');"));
	        print "<p>" . implode(" ",$buttons) . "</p>";
	        break;
	      default:
	        print "<table class='standard border tablesorter'>";
	        print "<thead>";
	        print "<tr>";
	        print "<th>Code</th>";
	        print "<th>Name</th>";
	        print "<th>Subject</th>";
	        print "<th>Recipient</th>";
	        print "<th>BCC</th>";
	        print "<th width='5%'>Del?</th>";
	        print "</tr>";
	        print "</thead>";
	        print "<tr><td colspan=6>" . fn_href("Add new","javascript:fn_action('maintain','0','');") . "</td></tr>";
	        print "<tbody>";
	        $query=array("select * from mailform");
	        $query[]="order by name";
	        $database->mailform->query($query);
	        while ($database->mailform->fetch = $database->mailform->fetch_array()) {
	            $database->mailform->fetch();
	            print "<tr " . (($database->mailform->data->active) ? "" : $forms->background->yellow) . ">";
	            print "<td>" . fn_href($database->mailform->data->code,"javascript:fn_action('maintain'," . fn_escape($database->mailform->data->id) . ",'');") . "</td>";
	            print "<td>" . $database->mailform->data->name . "</td>";
	            print "<td>" . $database->mailform->data->subject . "</td>";
	            print "<td>" . ( ($database->mailform->data->recipient) ? $database->mailform->data->recipient : "Visitor") . "</td>";
	            print "<td>" . implode("<br>",$database->mailform->data->bcc) . "</td>";
                $text=($database->mailform->data->active) ? "&nbsp" :  $forms->checkbox_delete($database->mailform->data->id, $database->mailform->data->code, array(), "fn_action");
                print "<td class=center>{$text}</td>";
	            print "</tr>";
	        }
	        print "</tbody>";
	        print "</table>";
	    }
	    print $forms->close();
	    $menu->copyright();
    }
    function maintain_input() {
        global $database, $forms;
	    $this->input->action=$_REQUEST['action'];
	    $this->input->record_id=$_REQUEST['record_id'];
        $database->mailform->read($this->input->record_id);
	    switch ($this->input->action) {
          case "":
            return;
	      case "cancel":
	        $forms->message[]="Request cancelled";
	        return;
	      case "delete":
	        $database->mailform->delete($this->input->record_id);
	        $forms->message[]=("Document deleted: <b>{$database->mailform->data->code} </b>");
	        return;
	      case "maintain":
	        return;
	      case "update":
	        $database->mailform->data->code=trim(strtolower($_POST['code']));
	        if (!$database->mailform->data->code) $forms->error('code',"Cannot be blank");
	        $database->mailform->data->name=trim($_POST['name']);
	        if (!$database->mailform->data->name) $forms->error('name',"Cannot be blank");
	        $database->mailform->data->subject=trim($_POST['subject']);
	        if (!strlen($database->mailform->data->subject)) $forms->error('subject');
            $email=new email_class($_POST['recipient'],FALSE);
	        $database->mailform->data->recipient=$email->address;
            if ($email->error) $forms->error("recipient",$email->error);
            $database->mailform->data->bcc=array();
            $items=explode("\n",$_POST['bcc']);
            $errors=array();
            foreach ($items as $item) {
            	$email=new email_class($item,FALSE);
                if ( (!strlen($email->address)) || (in_array($email->address, $database->mailform->data->bcc)) ) continue;
	            $database->mailform->data->bcc[]=$email->address;
				switch (TRUE) {
                  case ($email->error):
                  	$errors[]=$email->address . ": " . $email->error;
                    break;
                  case ($email->address == $database->mailform->data->recipient):
                  	$errors[]=$email->address . ": Recipient";
                    break;
				}
            }
            if (sizeof($errors)) $forms->error("bcc",implode("<br>",$errors));
	        $database->mailform->data->css=trim($_POST['css']);
	        $css_error=verify_file($database->mailform->data->css,"css");
	        if ($css_error) $forms->error('css',$css_error);
	        $database->mailform->data->header_html=trim($_POST['header_html']);
	        $database->mailform->data->footer_html=trim($_POST['footer_html']);
	        $database->mailform->data->active=$_POST['active'];
            if (sizeof($forms->error)) return;
	        $database->mailform->update($this->input->record_id);
            switch (TRUE) {
              case (!$database->mailform->meta->error):
	            $forms->message[]="Record maintained";
                return;
              case (preg_match("/code_key/i",$database->mailform->meta->error)):
              	$forms->error("code","Duplicate code");
              	return;
			  default:
              	$forms->error("name","Duplicate name");
			}
        }
    }
    function maintain_js() {
        return <<< EOT

<script>
function fn_action(action, record_id, text) {
	if (text.length > 0) {
        jQuery("#delete" + record_id).prop("checked", false);
	    if (!confirm(text)) return;
	}
	jQuery('#action').val(action);
	jQuery('#record_id').val(record_id);
	jQuery('#scs_form').submit();
}
</script>
EOT;
    }

}
?>