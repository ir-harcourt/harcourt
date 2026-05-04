<?php
require_once "classes/mailform.php";
class scsmail_class {
	var $host;
    var $secure;
    var $smtp_port;
    var $authentication;
    var $username;
    var $password;
    var $subject;
    var $form;
    var $base_href;
    var $css;
    var $user_id=0;
    var $bcc_user_id=0;
    var $encoding;
    var $attachments=array();
    var $map;
    var $debug=0;
    var $recipient;
    var $addresses=array("from"=>array(),"to"=>array(),"cc"=>array(),"bcc"=>array());
    var $output_addresses=array();
    var $mailform;
    var $results=array();
    var $error;
    var $html=array();
	function scs_version() {
	    $results=array();
	    $results['03/08/2020']="Add encoding";
	    $results['07/17/2019']="Add user_id (cron)";
	    $results['07/20/2017']="Override default bcc";
	    $results['08/24/2014']="Minor changes to html/preg_replace interface";
	    $results['06/16/2014']=array("Prevent email relaying","Omit object/array data mapping");
	    $results['03/18/2014']="Incorporate preg_replace_class";
	    $results['07/01/2013']=array("Add secure","Drop host function");
	    $results['06/03/2013']="Add user_id";
	    $results['05/02/2013']="Renamed to scsmail";
	    $results['04/06/2013']="Add attachments";
	    $results['03/26/2013']="Initial release";
		$results['file']= __FILE__;
	    return $results;
	}
    function __construct($form,$map_data=array(),$options=array()) {
    global $database;
		$this->form=$form;
		$database->mailform->read($this->form,"code");
        if (!$database->mailform->meta->rows) {
			$this->error="Missing mailform: $form";
            return;
        }
		$this->map=new preg_replace_class();
	    $this->map->load('/%scs_date%/i',date("F j, Y"));
	    $this->map->load('/%scs_time%/i',date("g:i A"));
	    $this->map->load('/%scs_datetime%/i',date("F j, Y g:i A"));
	    $this->map->load('/%scs_mdy%/i',date("m/d/Y"));
	    $this->map->load('/%scs_hhmm%/i',date("H:i"));
	    $this->map->load('/%scs_ip4%/i',$_SERVER['REMOTE_ADDR']);
	    $this->map->load('/%scs_host%/i',$_SERVER['HTTP_HOST']);
	    $this->map->load('/%scs_user_id%/i',$_SESSION['user']->id);
	    $this->map->load('/%scs_user_name%/i',$_SESSION['user']->name);
	    $this->map->load('/%scs_company_name%/i',$database->registry->company->name);
	    $this->map->load('/%scs_company_address%/i',$database->registry->company->address);
	    $this->map->load('/%scs_company_city_state_zip%/i',$database->registry->company->city_state_zip);
	    $this->map->load('/%scs_company_phone%/i',$database->registry->company->phone);
	    $this->map->load('/%scs_company_fax%/i',$database->registry->company->fax);
		foreach ($map_data as $field => $value) {
        	switch (TRUE) {
              case (is_object($value)):
              case (is_array($value)):
				break;
			  default:
				$this->map->load('/%' . $field . '%/i',$value);
			}
        }
        $this->mailform=$database->mailform->data;
        $this->subject=$this->map->filter( ((array_key_exists('subject',$options)) ? $options['subject'] : $this->mailform->subject ));
		$this->base_href=( (array_key_exists('base_href',$options)) ? $this->base_href=$options['base_href'] : $database->registry->email->base_href);
        $this->debug=( (array_key_exists('debug',$options)) ? $options['debug'] : FALSE);
        $this->user_id=( (array_key_exists('user_id',$options)) ? $options['user_id'] : 0);
        $this->bcc_user_id=( (array_key_exists('bcc_user_id',$options)) ? $options['bcc_user_id'] : $this->mailform->bcc_user_id);
        $this->encoding=( (array_key_exists('encoding',$options)) ? $options['encoding'] : "8bit");
        switch (TRUE) {
          case ($options['css']):
			$this->css=$options['css'];
            break;
          case ($this->mailform->css):
        	$this->css=$this->mailform->css;
			break;
          default:
	        $this->css=$database->registry->email->css;
		}
	    $email_address=$database->registry->email->webmaster;
	    $email_name=$database->registry->email->webmaster_name;
        switch (TRUE) {
          case (array_key_exists("email",$options)):
	        $email_address=$options['email'];
	        if (array_key_exists("email_name",$options)) $email_name=$options['email_name'];
			break;
		  case ($this->mailform->user_id):
			$temp=new database_temp_class();
        	$query="select * from user where id=" . fn_escape($this->mailform->user_id);
            $temp->query($query);
            $temp->fetch();
            $temp->free_result();
            if ($temp->meta->rows) {
				$email_address=$temp->fetch['email'];
				$email_name=$temp->fetch['name'];
            }
        }
	    list ($account,$domain) = preg_split("/@/",$email_address) + Array(NULL,NULL);
        switch (TRUE) {
	      case (!is_array($database->registry->email->domain)):
          case (in_array($domain,$database->registry->email->domain)):
			break;
          default:
	        $this->address("cc",$email_address,$email_name);
	        $email_address=$database->registry->email->webmaster;
	        $email_name=$database->registry->email->webmaster_name;
        }
        if  ($this->mailform->from_user) {
			$this->address("to",$email_address,$email_name);
		  } else {
			$this->address("from",$email_address,$email_name);
		}
        if ($this->bcc_user_id) {
            $temp=new database_temp_class();
            $query_where=array();
            $query="select * from user where id=" . fn_escape($this->bcc_user_id);
            $temp->query($query);
            $temp->fetch();
            $temp->free_result();
			if ($temp->meta->rows) $this->address("bcc",$temp->fetch['email'],$temp->fetch['name']);
		}
		$this->host=$database->registry->email->host;
		$this->secure=$database->registry->email->secure;
		$this->smtp_port=$database->registry->email->smtp_port;
		$this->authentication=$database->registry->email->authentication;
		$this->username=$database->registry->email->username;
		$this->password=$database->registry->email->password;
	}
    function recipient($email,$name,$field="") {
		switch (TRUE) {
		  case ($field=="cc"):
		  case ($field=="bcc"):
			$this->address($field,$email,$name);
            break;
          case ($field):
          	break;
		  case ($this->mailform->from_user):
          	$this->address("from",$email,$name);
			break;
		  default:
          	$this->address("to",$email,$name);
        }
    }
	private function address($field,$email,$name) {
    	$field=trim(strtolower($field));
		switch (TRUE) {
          case ( (!$field)|| (!array_key_exists($field,$this->addresses)) || (!$email) ):
          case (array_key_exists($email,$this->addresses)):
          case ( ($field != "from") && (in_array($email,$this->output_addresses)) ):
        	return;
          case ($field != "from"):
			$this->output_addresses[]=$email;
			break;
        }
		$this->addresses[$field][$email]=$name;
    }
    function html($html) {
	global $database;
        $this->html=array();
        $this->html[]="<html>";
        $this->html[]="<head>";
		if ($this->base_href) $this->html[]="<base href=" . fn_escape($this->base_href) . ">";
//			if ($this->css) $this->html[]="<link rel='stylesheet' type='text/css' href='" . $this->css . "'>";
		if ($this->css) {
	        $css=$_SERVER['DOCUMENT_ROOT'] . $this->css;
	        if (file_exists($css)) {
            	$this->html[]="<style>";
                $this->html[]=implode("\n",file($css));
            	$this->html[]="</style>";
            }
		}
        $this->html[]="</head>";
        $this->html[]="<body>";
		$this->html[]="<div id='content'>";
        if ($this->mailform->header_html) $this->html[]=$this->map->filter($this->mailform->header_html);
        if (is_array($html)) {
			$this->html[]=implode("",$html);
		  } else {
			$this->html[]=trim($html);
		}
        if ($this->mailform->footer_html) $this->html[]=$this->map->filter($this->mailform->footer_html);
        $this->html[]="</div>";
        $this->html[]="</body>";
        $this->html[]="</html>";         
    }
    function attachment($file) {
		if (($file) && (!in_array($file,$this->attachments))) $this->attachments[]=$file;
    }
    function send($success_message="") {
	require_once "PHPMailer_5.2.4/class.phpmailer.php";
		$this->error=$this->review(FALSE);
		$this->results[]="Form: " . $this->form  . " (" . $this->mailform->name . ")";
		$this->results[]="Subject: " . $this->subject;
	    $mail=new PHPMailer();
		$mail->IsSMTP();
        $mail->SMTPDebug=$this->debug;
	    $mail->Host=$this->host;
	    $mail->SMTPSecure=$this->secure;
	    $mail->Port=$this->smtp_port;
		$mail->SMTPAuth=$this->authentication;
		$mail->Username=$this->username;
		$mail->Password=$this->password;
		$mail->Subject=$this->subject;
        $mail->WordWrap=50;
	    $mail->AltBody="To view the message, please use an HTML compatible email viewer!";
	    $mail->MsgHTML(implode("\n",$this->html));
        $mail->IsHTML(TRUE);
        $address=array();
        foreach ($this->addresses as $type => $type_list) {
			if (sizeof($type_list)) {
	            foreach ($type_list as $email => $email_name) {
                	$this->results[]="$type: $email ($email_name)";
	                switch ($type) {
	                  case "from":
	                    $mail->SetFrom($email,$email_name);
	                    break;
	                  case "to":
	                    $mail->AddAddress($email,$email_name);
	                    break;
	                  case "cc":
	                    $mail->AddCC($email,$email_name);
	                    break;
	                  case "bcc":
	                    $mail->AddBCC($email,$email_name);
	                    break;
	                }
				}
            }
        }
        if (sizeof($this->attachments)) {
			foreach ($this->attachments as $file) {
                $this->results[]="Attachment: $file";
				$mail->AddAttachment($file);
			}
        }
        if ($this->error) {
			$this->log(FALSE,$this->error);
        	return "Email not sent due to errors detected";
		}
        if (!$mail->Send()) {
			$this->error=str_replace(array("<p>","</p>","<",">"),array("\n","","&lt;","&gt;"),$mail->ErrorInfo);
	        $this->log(FALSE,$this->error);
			return $this->error;
		  } else {
			$message="Email successfully sent to " . key($this->addresses['to']);
	        $this->log(TRUE,$message);
            if ($success_message) {
				return $success_message;
              } else {
				return $message;
			}
		}
    }
    function log($success,$message) {
    global $database;
		$this->results[]="Results: $message";
		if (method_exists($database->log,"ipc")) {
        	$action=$this->form;
			if (!$success) $this->action .= " Error";
			$database->log->ipc("Mail: $action",$this->results, $this->user_id);
		}
    }
    function review($prelist=TRUE) {
		switch (TRUE) {
		  case ($this->error):
			$results=$this->error;
          	break;
		  case (!sizeof($this->html)):
          	$results="Empty message";
            break;
          case (!sizeof($this->addresses['to'])):
          	$results="To address missing";
            break;
          case (!sizeof($this->addresses['from'])):
          	$results="From address missing";
            break;
          case (sizeof($this->addresses['from']) != 1):
          	$results="Multiple from addresses";
            break;
          case ($prelist):
			$results="Deliver email to " . key($this->addresses['to']);
        }
        return $results;
    }
}
?>