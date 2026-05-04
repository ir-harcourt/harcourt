<?php
require_once "classes/scsmail.php";
$forms->title("Email Toolbox 07/01/2013");
$forms->report['action']=$_POST['action'];
switch ($forms->report['action']) {
  case "":
	$forms->report['action']="enter";
	$forms->report['email']=$_SESSION['user']->email;
	$forms->report['name']=$_SESSION['user']->name;
	$forms->report['base_href']=$database->registry->email->base_href;
	$forms->report['css']=$database->registry->email->css;
	$forms->report['webmaster']=$database->registry->email->webmaster;
	$forms->report['webmaster_name']=$database->registry->email->webmaster_name;
	$forms->report['host']=$database->registry->email->host;
	$forms->report['secure']=$database->registry->email->secure;
	$forms->report['smtp_port']=$database->registry->email->smtp_port;
	$forms->report['authentication']=$database->registry->email->authentication;
	$forms->report['username']=$database->registry->email->username;
	$forms->report['password']=$database->registry->email->password;
	break;
  default:
	$forms->report['mailform']=$_POST['mailform'];
    if (!$forms->report['mailform']) $forms->error('mailform');
	$forms->report['email']=$_POST['email'];
    $email=new email_class($_POST['cc_email']);
    $forms->report['cc_email']=$email->address;
    if ($email->error) $forms->error('cc_email',$email->error);
	$forms->report['cc_name']=trim($_POST['cc_name']);
    $email=new email_class($_POST['bcc_email']);
    $forms->report['bcc_email']=$email->address;
    if ($email->error) $forms->error('bcc_email',$email->error);
	$forms->report['bcc_name']=trim($_POST['bcc_name']);
	$forms->report['message']=trim($_POST['message']);
    if (($forms->report['action'] != "mailform") && (!$forms->report['message'])) $forms->error('message');
	switch (TRUE) {
	  case ($forms->report['action'] != "review"):
	    $forms->report['filename']=$_POST['filename'];
	    $forms->report['file_url']=$_POST['file_url'];
        break;
	  case (!$_FILES['upload']['name']):
		break;
	  case (intval($_FILES['upload']['error'])):
		$forms->error('filename',"Upload error #" . $_FILES['upload']['error']);
		break;
      case (!preg_match("/(" . implode("|",$database->registry->upload->filetype) . ")$/i",$_FILES['upload']['name'])):
		$forms->error('filename',"File type cannot be uploaded");
		break;
      default:
		$forms->report['filename']=$_FILES['upload']['name'];
		$forms->report['file_url']=$database->registry->upload->folder . $forms->report['filename'];
		if (file_exists($forms->report['file_url'])) unlink($forms->report['file_url']);
		move_uploaded_file($_FILES['upload']['tmp_name'],$forms->report['file_url']);
	}
	$forms->report['base_href']=trim($_POST['base_href']);
	$forms->report['css']=trim($_POST['css']);
    $email=new email_class($_POST['webmaster'],TRUE);
    $forms->report['webmaster']=$email->address;
    if ($email->error) $forms->error('webmaster',$email->error);
	$forms->report['webmaster_name']=trim($_POST['webmaster_name']);
	$forms->report['host']=trim($_POST['host']);
	$forms->report['secure']=$_POST['secure'];
	$forms->report['smtp_port']=intval($_POST['smtp_port']);
	$forms->report['authentication']=intval($_POST['authentication']);
	$forms->report['username']=trim($_POST['username']);
	$forms->report['password']=trim($_POST['password']);
    switch (TRUE) {
	  case (sizeof($forms->error)):
      	break;
	  case ($forms->report['action']=="review"):
      	$forms->message[]="Review settings";
        break;
	  case ($forms->report['action']=="send"):
      	$forms->message[]="Sending message";
        break;
	}
}
if ($forms->report['mailform']) $database->mailform->read($forms->report['mailform'],'code');
$menu->head();
$forms->message();
?>
<script type='text/javascript'>
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
  case ($forms->report['action'] == "enter"):
  case (sizeof($forms->error)):
	break;
  default:
	foreach ($forms->report as $key => $value) {
		if ($key != "action") print $forms->hidden($key,$value);
    }
}
switch (TRUE) {
  case ($forms->report['action'] == "enter"):
  case ($forms->report['action'] == "mailform"):
  case (sizeof($forms->error)):
    print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>Form</td>\n";
    $database->temp=new mailform_class();
    print "<td width='70%'>" . $database->temp->select($forms->report['mailform'],'mailform',TRUE,array("onchange"=>"fn_action('mailform',this);")) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td width='30%'>Email</td>\n";
    print "<td width='70%'>" . $forms->text("email",$forms->report['email'],40);
    if ($forms->error_text['email']) print "<br>" . $forms->error_text['email'];
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>CC Email</td>";
    print "<td>" . $forms->text("cc_email",$forms->report['cc_email'],40);
    if ($forms->error_text['cc_email']) print "<br>" . $forms->error_text['cc_email'];
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>BCC Email</td>";
    print "<td>" . $forms->text("bcc_email",$forms->report['bcc_email'],40);
    if ($forms->error_text['bcc_email']) print "<br>" . $forms->error_text['bcc_email'];
    print "</td>\n";
    if ($database->mailform->meta->rows) {
	    print "<tr>\n";
	    print "<td>Subject</td>\n";
	    print "<td><b>" . $database->mailform->data->subject . "</b></td>\n";
	    print "</tr>\n";
	}
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Message</td>\n";
    print "<td>" . $forms->textarea("message",$forms->report['message'],20,60) . "</td>\n";
    print "</tr>\n";
	if (sizeof($database->registry->upload->filetype)) {
	    print "<tr>\n";
	    print "<td>Upload files</td>\n";
	    print "<td>" . $forms->file("upload",40);
        if ($forms->error_text['upload']) print "<br>" . $forms->error_text['filename'];
        print "<br>Allowed file types: " . implode(", ",$database->registry->upload->filetype);
        print "</td>\n";
	    print "</tr>\n";
    }
    print "</table>\n";
    print "<div>" . $forms->checkbox("email_setting_check",1,0,array("onclick"=>"div_show('email_setting_div',this.checked);"));
	print "<b>Email Settings</b></div>\n";
	print "<div id='email_setting_div' style='display:none;'>\n";
	print "<table class='standard noborder'>\n";
	print "<tr>\n";
	print "<td width='30%'>Base href</td>\n";
	print "<td width='70%'>" . $forms->text("base_href",$forms->report['base_href'],60,60) . "</td>\n";
	print "</tr>\n";
	print "<tr>\n";
	print "<td>CSS</td>\n";
	print "<td>" . $forms->text("css",$forms->report['css'],60,60) . "</td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</div>\n";
    print "<div>" . $forms->checkbox("email_server_check",1,0,array("onclick"=>"div_show('email_server_div',this.checked);"));
	print "<b>Email Server Settings</b></div>\n";
	print "<div id='email_server_div' style='display:none;'>\n";
	print "<table class='standard noborder'>\n";
    print "<tr><td colspan=2><br><b>Webmaster Settings</b></td></tr>\n";
    print "<tr>\n";
    print "<td width='30%'>Webmaster email</td>\n";
    print "<td width='70%'>" . $forms->text("webmaster",$forms->report['webmaster'],60,60) . " " . $forms->error_text["webmaster"] . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Webmaster name</td>\n";
    print "<td>" . $forms->text("webmaster_name",$forms->report['webmaster_name'],60,60) . "</td>\n";
    print "</tr>\n";
    print "</tr>\n";
    print "<tr><td colspan=2><br><b>PHPMailer Settings</b></td></tr>\n";
    print "<tr>\n";
    print "<td>Host</td>\n";
    print "<td>" . $forms->text("host",$forms->report['host'],60) . " " . $forms->error_text["host"] . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Secure?</td>\n";
    print "<td>" . $forms->select("secure",array("tls","ssl"),$forms->report['secure']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>SMTP Port#</td>\n";
    print "<td>" . $forms->text("smtp_port",$forms->report['smtp_port'],5,5) . " (Default: 25)</td>\n";
    print "</tr>\n";
	print "<tr><td colspan=2><br>&nbsp;</td></tr>\n";
    print "<tr>\n";
    print "<td>SMTP Authentication?</td>\n";
    print "<td>" . $forms->checkbox("authentication",1,$forms->report['authentication']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Username</td>\n";
    print "<td>" . $forms->text("username",$forms->report['username'],60) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Password</td>\n";
    print "<td>" . $forms->text("password",$forms->report['password'],60) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print "</div>\n";
    print "<p class=left>" . $forms->button("Review >>",array("onclick"=>"fn_action('review',this);")) . "</p>\n";
    break;
  default:
	$options=array();
    $options['debug']=2;
    $options['base_href']=$forms->report['base_href'];
    $options['css']=$forms->report['css'];
	$mail=new scsmail_class($forms->report['mailform'],array(),$options);
	$mail->recipient($forms->report['email'],"");
	if ($forms->report['cc_email']) $mail->recipient($forms->report['cc_email'],$email_list[$forms->report['cc_name']],"cc");
	if ($forms->report['bcc_email']) $mail->recipient($forms->report['bcc_email'],$email_list[$forms->report['bcc_name']],"bcc");
    if ($forms->report['file_url']) $mail->attachment("../" . $forms->report['file_url']);
	$mail->host=$forms->report['host'];
    $mail->secure=$forms->report['secure'];
    $mail->port=$forms->report['port'];
    $mail->authentication=$forms->report['authentication'];
    $mail->username=$forms->report['username'];
    $mail->password=$forms->report['password'];
	$mail->html($forms->report['message']);
    print "<table class='standard noborder'>\n";
    print "<tr>\n";
	print "<td width='30%'>Form code</td>\n";
    print "<td width='70%'>" . $database->mailform->data->code . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Form name</td>\n";
    print "<td>" . $database->mailform->data->name . "</td>\n";
    print "</tr>\n";
    if ($database->mailform->data->from_user) {
    	$text="From user";
	  } else {
      	$text="To user";
	}
    print "<tr>\n";
    print "<td>Direction</td>\n";
    print "<td>$text</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>CSS</td>\n";
    print "<td>" . $forms->report['css'] . "</td>\n";
    print "</tr>\n";
    print "<td><td colspan=2>&nbsp;</td></tr>\n";
	foreach ($mail->addresses as $type => $type_list) {
    	$text="";
        foreach ($type_list as $email => $email_name) {
        	if ($text) $text .= "<br>";
            $text .= $email;
            if ($email_name) $text .= " (" . $email_name . ")";
        }
        if ($text) {
	        print "<tr>\n";
	        print "<td>$type</td>\n";
	        print "<td>$text</td>\n";
	        print "</tr>\n";
		}
	}
    if ($forms->report['filename']) {
		print "<tr>\n";
        print "<td>Attached file:</td>\n";
        print "<td>" . $forms->report['filename'] . "</td>\n";
        print "</tr>\n";
    }
    print "</table>\n";
    if ($forms->report['action']=="send") {
	    print "<p>Sending message ...<br>\n";
	    $results=$mail->send();
	    print "</p>\n";
	    print "<p>Results: " . $results . "</p>\n";
	  } else {
	    print "<p>Results: <b>" . $mail->review() . "</b></p>\n";
    }
    print
  		"<p class=left>" .
    	$forms->button("<< Back",array("onclick"=>"fn_action('enter',this);")) . " " .
    	$forms->button("Send >>",array("onclick"=>"fn_action('send',this);")) .
        "</p>\n";
	break;
}
print $forms->close();
$menu->copyright();
?>