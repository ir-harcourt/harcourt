<?php
require_once "classes/scsmail_rev1.php";
$database->mailform2 = new mailform_class();
class email_toolbox {
	function scs_table_version() {
		$results=array();
        $results['04/19/2021']="New mailform format";
        $results['12/17/2020']="Additional tracking";
        $results['06/30/2020']="Add attachments";
        $results['03/09/2020']="Initial release";
        return $results;
    }
	function __construct($access) {
    global $database, $menu, $forms;
		$database->user->access($access);
        $this->input=new stdClass();
		$forms->title("Email Toolbox");
        $forms->message[]="Email Engine: " . key($this->scs_table_version());
        if (fn_development_server()) $forms->message[]="AVG Firewall must be disabled";
		$this->input->action=$_POST['action'];
	    $this->input->mailform=$_POST['mailform'];
        $database->mailform->read($this->input->mailform,"code");
	    switch ($this->input->action) {
	      case "":
	        $this->input->action="enter";
	        $this->input->recipient=$_SESSION['user']->email;
	        $this->input->base_href=$database->registry->email->base_href;
	        $this->input->css=$database->registry->email->css;
	        $this->input->webmaster=$database->registry->email->webmaster;
	        $this->input->webmaster_name=$database->registry->email->webmaster_name;
	        $this->input->host=$database->registry->email->host;
	        $this->input->secure=$database->registry->email->secure;
	        $this->input->smtp_port=$database->registry->email->smtp_port;
	        $this->input->authentication=$database->registry->email->authentication;
	        $this->input->username=$database->registry->email->username;
	        $this->input->password=$database->registry->email->password;
	        break;
	      default:
	        if (!$this->input->mailform) $forms->error('mailform');
            if ($this->input->action == "mailform") {
	        	$this->input->default_recipient=( ($database->mailform->data->recipient) ? 1 : 0);
	        	$this->input->default_bcc=( (sizeof($database->mailform->data->bcc)) ? 1 : 0);
			  } else {
	        	$this->input->default_recipient=intval($_POST['default_recipient']);
	        	$this->input->default_bcc=intval($_POST['default_bcc']);
			}
	        $this->input->recipient=$_POST['recipient'];
	        $email=new email_class($this->input->recipient);
            if ($email->error) $forms->error('recipient',$email->error);
	        $email=new email_class($_POST['cc_email']);
	        $this->input->cc_email=$email->address;
	        if ($email->error) $forms->error('cc_email',$email->error);
	        $this->input->cc_name=trim($_POST['cc_name']);
	        $email=new email_class($_POST['bcc']);
	        $this->input->bcc=$email->address;
	        if ($email->error) $forms->error('bcc',$email->error);
	        $this->input->bcc_name=trim($_POST['bcc_name']);
	        $this->input->message=trim($_POST['message']);
	        if (($this->input->action != "mailform") && (!$this->input->message)) $forms->error('message');
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
			$this->input->attachment=$_POST['attachment'];
            if ( ($this->input->attachment) && (!$this->input->attachment_file) ) $forms->error("upload","No file specified");
	        $this->input->base_href=trim($_POST['base_href']);
	        $this->input->css=trim($_POST['css']);
	        $email=new email_class($_POST['webmaster'],TRUE);
	        $this->input->webmaster=$email->address;
	        if ($email->error) $forms->error('webmaster',$email->error);
	        $this->input->webmaster_name=trim($_POST['webmaster_name']);
	        $this->input->host=trim($_POST['host']);
	        $this->input->secure=$_POST['secure'];
	        $this->input->smtp_port=intval($_POST['smtp_port']);
	        $this->input->authentication=intval($_POST['authentication']);
	        $this->input->username=trim($_POST['username']);
	        $this->input->password=trim($_POST['password']);
	        switch (TRUE) {
	          case (sizeof($forms->error)):
	            break;
	          case ($this->input->action=="review"):
	            $forms->message[]="Review settings";
	            break;
	          case ($this->input->action=="send"):
	            $forms->message[]="Sending message";
	            break;
	        }
	    }
	    $menu->head();
	    $forms->message();
?>
<script>
function fn_action(action,obj) {
	if (action=='send') obj.style.display ='none';
    document.scs_form.action.value=action;
    document.scs_form.submit();
}
</script>
<?php
	    print $forms->open(array("data"=>TRUE));
	    print $forms->hidden("action");

	    switch (TRUE) {
	      case ($this->input->action == "enter"):
	      case ($this->input->action == "mailform"):
	      case (sizeof($forms->error)):
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>Document</td>";
	        print "<td width='70%'>" . $database->mailform2->select($this->input->mailform,array(),array("onchange"=>"fn_action('mailform',this);")) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Recipient</td>";
            $text=array();
            if ($database->mailform->data->recipient) $text[]=$forms->checkbox("default_recipient",1,$this->input->default_recipient) . " Use Default: <b>" . $database->mailform->data->recipient . "</b>";
            $text[]=$forms->text("recipient",$this->input->recipient,60);
	        if ($forms->error_text['recipient']) $text[]=$forms->error_text['recipient'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>CC</td>";
            $text=array($forms->text("cc",$this->input->cc,60));
	        if ($forms->error_text['cc']) $text[]=$forms->error_text['cc'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        print "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>BCC</td>";
            $text=array();
            if (sizeof($database->mailform->data->bcc)) $text[]=$forms->checkbox("default_bcc",1,$this->input->default_bcc) . " Use Default: " . implode(", ",$database->mailform->data->bcc);
            $text[]=$forms->text("bcc",$this->input->bcc,60);
	        if ($forms->error_text['bcc']) $text[]=$forms->error_text['bcc'];
	        print "<td>" . implode("<br>",$text) . "</td>";
	        if ($database->mailform->meta->rows) {
	            print "<tr>";
	            print "<td>Subject</td>";
	            print "<td><b>" . $database->mailform->data->subject . "</b></td>";
	            print "</tr>";
	        }
	        print "</tr>";
	        print "<tr>";
	        print "<td>Message</td>";
	        print "<td>" . $forms->textarea("message",$this->input->message,20,60) . "</td>";
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
            $text=array( $forms->checkbox("email_setting_check",1,0,array("onclick"=>"div_show('email_setting_div',this.checked);")) );
            $text[]="<b>Email Settings</b>";
	        print "<div>" . implode(" ",$text) . "</div>";
	        print "<div id='email_setting_div' style='display:none;'>";
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>Base href</td>";
	        print "<td width='70%'>" . $forms->text("base_href",$this->input->base_href,60,60) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>CSS</td>";
	        print "<td>" . $forms->text("css",$this->input->css,60,60) . "</td>";
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
	        print "<td width='30%'>Webmaster email</td>";
	        print "<td width='70%'>" . $forms->text("webmaster",$this->input->webmaster,60,60) . " " . $forms->error_text["webmaster"] . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Webmaster name</td>";
	        print "<td>" . $forms->text("webmaster_name",$this->input->webmaster_name,60,60) . "</td>";
	        print "</tr>";
	        print "</tr>";
	        print "<tr><td colspan=2><br><b>PHPMailer Settings</b></td></tr>";
	        print "<tr>";
	        print "<td>Host</td>";
	        print "<td>" . $forms->text("host",$this->input->host,60) . " " . $forms->error_text["host"] . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Secure?</td>";
	        print "<td>" . $forms->select("secure",array("tls","ssl"),$this->input->secure) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>SMTP Port#</td>";
	        print "<td>" . $forms->text("smtp_port",$this->input->smtp_port,5,5) . " (Default: 25)</td>";
	        print "</tr>";
	        print "<tr><td colspan=2><br>&nbsp;</td></tr>";
	        print "<tr>";
	        print "<td>SMTP Authentication?</td>";
	        print "<td>" . $forms->checkbox("authentication",1,$this->input->authentication) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Username</td>";
	        print "<td>" . $forms->text("username",$this->input->username,60) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Password</td>";
	        print "<td>" . $forms->text("password",$this->input->password,60) . "</td>";
	        print "</tr>";
	        print "</table>";
	        print "</div>";
	        print "<p class=left>" . $forms->button("Review >>",array("onclick"=>"fn_action('review',this);")) . "</p>";
	        break;
	      default:
	        $options=array();
	        $options['debug']=2;
	        $options['base_href']=$this->input->base_href;
	        $options['css']=$this->input->css;
            $options['bcc_load']=$this->input->default_bcc;
	        $mail=new scsmail_class($this->input->mailform,$_SESSION['user'],$options);
	        if (!$this->input->default_recipient) $mail->recipient($this->input->recipient,"");
	        if ($this->input->cc_email) $mail->recipient($this->input->cc_email,$email_list[$this->input->cc_name],"cc");
	        if ($this->input->bcc) $mail->recipient($this->input->bcc,"","bcc");
            if ($this->input->attachment) $mail->attachment($this->input->attachment_file);
	        $mail->host=$this->input->host;
	        $mail->secure=$this->input->secure;
	        $mail->port=$forms->report['port'];
	        $mail->authentication=$this->input->authentication;
	        $mail->username=$this->input->username;
	        $mail->password=$this->input->password;
	        $mail->html($this->input->message);
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>Document code</td>";
	        print "<td width='70%'>" . $database->mailform->data->code . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Document name</td>";
	        print "<td>" . $database->mailform->data->name . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Sender</td>";
	        print "<td>" . $this->input->webmaster . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Recipient</td>";
	        print "<td>" . ( ($this->input->default_recipient) ? $database->mailform->data->recipient : $this->input->recipient) . "</td>";
	        print "</tr>";
	        print "<td><td colspan=2>&nbsp;</td></tr>";
	        foreach ($mail->addresses as $type => $type_list) {
	            $text="";
	            foreach ($type_list as $email => $email_name) {
	                if ($text) $text .= "<br>";
	                $text .= $email;
	                if ($email_name) $text .= " (" . $email_name . ")";
	            }
	            if ($text) {
	                print "<tr>";
	                print "<td>$type</td>";
	                print "<td>$text</td>";
	                print "</tr>";
	            }
	        }
	        if ($this->input->attachment) {
	            print "<tr>";
	            print "<td>Attached file:</td>";
	            print "<td>" . $this->input->attachment_file . "</td>";
	            print "</tr>";
	        }
	        print "<tr>";
	        print "<td>CSS</td>";
	        print "<td>" . $this->input->css . "</td>";
	        print "</tr>";
	        print "</table>";
	        if ($this->input->action=="send") {
	            print "<p>Sending message ...<br>";
	            $results=$mail->send();
	            print "</p>";
	            print "<p>Results: " . $results . "</p>";
	          } else {
	            print "<p>Results: <b>" . $mail->review() . "</b></p>";
	        }
            $buttons=array();
	        $buttons[]=$forms->button("<< Back",array("onclick"=>"fn_action('enter',this);"));
	        $buttons[]=$forms->button("Send >>",array("onclick"=>"fn_action('send',this);"));
	        print "<p>" . implode(" ",$buttons) . "</p>";
	        break;
	    }
        print $forms->hidden_fields($this->input);
	    print $forms->close();
	    $menu->copyright();
    }
}
?>