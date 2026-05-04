<?php
/*
Enable less secure apps in gmail, https://myaccount.google.com/security
*/
class imap_class {
	var $meta;
    var $ch;
    var $constant;
    var $query=array();
    var $messages=array();
    var $content;
    var $content_html;
    var $content_text;
	function scs_table_version() {
		$results=array();
        $results['06/17/2021']="Add FT_PEEK option to read";
        $results['05/14/2020']="Add read";
        $results['07/12/2019']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($options=array()) {
        $this->meta=new imap_meta_class($options);
        $this->constant=new imap_constant_class();
    }
    function settings($options) {
		if ($this->meta->connected) return;
        $this->meta=new imap_meta_class($options);
    }
    function open($type="mailbox") {
		if ($this->meta->connected) return;
		$this->stopwatch=new stopwatch_class("IMAP");
    	$this->stopwatch->split("imap_open");
		switch (TRUE) {
          case (!strlen($this->meta->server)):
          case (!strlen($this->meta->email)):
          case (!strlen($this->meta->password)):
			$this->meta->error="Server/Email/Password missing";
            return;
		}
        $this->meta->connected=TRUE;
		$this->ch=@imap_open($this->stream($type),$this->meta->email,$this->meta->password, ( (strlen($type)) ? 0 : OP_HALFOPEN), 1);
        if (!$this->ch) {
        	$this->meta->connection_error=TRUE;
			$this->meta->error="imap_open error: " . imap_last_error();
            return;
        }
    	$this->stopwatch->split("imap_check");
		$this->mailbox=imap_check($this->ch);
        return TRUE;
    }
    function close() {
		if ( (!$this->meta->connected) || ($this->meta->connection_error) ) return;
    	$this->stopwatch->split("imap_close");
		imap_close($this->ch,CL_EXPUNGE);
	    $this->meta->connected=FALSE;
        $this->ch="";
    	$this->stopwatch->stop();
    }
    function read($msg, $flags=0) {
// https://www.php.net/manual/en/function.imap-fetchstructure.php
    	$this->content_html="";
    	$this->content_text="";
    	$structure=$this->fetchstructure($msg,$flags);
	    if (!$structure->parts) {
	        $this->read_part($msg,$structure,0,$flags);
	      } else {
	        foreach ($structure->parts as $partno0 => $part_structure) {
				$this->read_part($msg,$part_structure,$partno0+1,$flags);
			}
	    }
        $this->content=($this->content_html) ? $this->content_html : $this->content_text;
        return (strlen($this->content)) ? TRUE : FALSE;
    }
	function read_part($msg, $structure, $partno, $flags=0) {
		$data=($partno) ? imap_fetchbody($this->ch,$msg,$partno,$flags) : imap_body($this->ch,$msg,$flags);
        switch ($structure->encoding) {
          case 3:
        	$data=base64_decode($data);
            break;
           case 4:
        	$data=quoted_printable_decode($data);
            break;
		}
	    $parameters=array();
	    if ($structure->parameters) {
	        foreach ($structure->parameters as $item) {
	            $parameters[strtolower($item->attribute)] = $item->value;
	        }
	    }
	    if ($structure->dparameters) {
	        foreach ($structure->dparameters as $item) {
	            $parameters[strtolower($item->attribute)] = $item->value;
	        }
	    }
        switch (TRUE) {
          case (!strlen($data)):
          	break;
          case ($structure->type == 2):
          case ( ($structure->type == 0) && (strtolower($structure->subtype)=="plain") ):
	    	$this->content_text .= trim($data) ."\n\n";
            break;
          case ($structure->type == 0):
			$this->content_html .= $data ."<br><br>";
	        $this->charset=$parameters['charset'];
            break;
	    }
	    if ($structure->parts) {
	        foreach ($structure->parts as $partno0 => $part_structure)
	            $this->read_part($msg,$part_structure,$partno.".".($partno0+1),$flags);  // 1.2, 1.2.1, etc.
	    }
	}
    function stream($type) {
		$results=array();
        $results[]="{";
        $results[]=$this->meta->server;
        $results[]="}";
        if ( (strlen($type)) && (property_exists($this->meta,$type)) ) $results[]=$this->meta->$type;
        $this->meta->stream=implode("",$results);
        return implode("",$results);
    }
	function mailbox_list() {
	    if (!$this->meta->connected) $this->open("");
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_list");
        $this->mailboxes=imap_list($this->ch, $this->meta->stream, "*") or $this->meta->error=imap_last_error();
        if (!$this->meta->error) return TRUE;
	}
    function query($code,$text="") {
    	$results=new stdClass();
        $results->code=$code;
        $text=trim($text);
        $timestamp=strtotime($text);
        $results->type=$this->constant->search[$results->code]->type;
        $results->name=$this->constant->search[$results->code]->name;
        switch (TRUE) {
          case (!$results->code):
          case (!array_key_exists($results->code,$this->constant->search)):
          	$results->error="Invalid/Missing Code";
            break;
          case (!$results->type):
          	break;
          case ( ($results->type == "Date") && (!$timestamp) ):
          	$results->error="Date blank";
            break;
          case ($results->type == "Date"):
          	$results->text=date("j-M-Y",$timestamp);
            break;
          case ( ($this->constant->search[$results->code]->type == "String") && (!strlen($text)) ):
          	$results->error="String blank";
            break;
          default:
            $results->text=$text;
		}
        if (!$results->error) {
        	$results->query=$results->code;
            if ($this->constant->search[$results->code]->type) $results->query .= " \"" . $results->text  . "\"";
        }
        return $results;
    }
    function search($query) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_search");
		$this->query=array();
		foreach ($query as $item) {
        	if ($item->error) {
            	$this->meta->error=$item->error;
                return FALSE;
			}
            $this->query[]=$item->query;
        }
		$messages=imap_search($this->ch, implode(" ",$this->query),0);
        if (is_array($messages)) {
            $this->messages=$messages;
	        $this->meta->error="";
            return TRUE;
		  } else {
            $this->meta->error=imap_last_error();
			if (!$this->meta->error) $this->meta->error="No matches found";
            return FALSE;
		}
    }
    function body($msg) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_body","Message #{$msg}");
		return quoted_printable_decode(imap_body($this->ch,$msg,FT_PEEK) );
    }
    function fetchheader($msg) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_fetchheader","Message #{$msg}");
		return imap_fetchheader($this->ch,$msg,0);
    }
    function fetchstructure($msg) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_fetchstructure","Message #{$msg}");
		return imap_fetchstructure($this->ch,$msg,0);
    }
    function fetchbody($msg,$section) {
/* section
0 - Message header
1 - MULTIPART/ALTERNATIVE
1.1 - TEXT/PLAIN
1.2 - TEXT/HTML
2 - MESSAGE/RFC822 (entire attached message)
2.0 - Attached message header
2.1 - TEXT/PLAIN
2.2 - TEXT/HTML
2.3 - file.ext
*/
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_fetchbody","Message #{$msg}");
        $this->content=imap_fetchbody($this->ch,$msg,$section,FT_PEEK);
		$this->meta->error=imap_last_error();
        return ((!$this->meta->error) ? TRUE : FALSE);
    }
	function flags($msg_array, $flag_array, $set) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
        $msgs=( (is_array($msg_array)) ? $msg_array : array($msg_array) );
        $flag=( (is_array($flag_array)) ? $flag_array : array($flag_array) );
		$this->stopwatch->split("imap_setflag_full (" . sizeof($msgs) . " messages)");
        $flags=array();
        foreach ($flag as $i => $flag_code) {
        	if (array_key_exists($flag_code,$this->constant->flag)) $flags[]=$this->constant->flag[$flag_code];
        }
        switch (TRUE) {
		  case (!sizeof($msgs)):
          	$this->meta->error="No messages";
            break;
		  case (!sizeof($flags)):
          	$this->meta->error="No flag set";
            break;
           case ($set):
			if (!imap_setflag_full($this->ch, implode(",", $msgs), implode(" ", $flags))) {
	            $this->meta->error=imap_last_error();
            }
            break;
		  default:
			if (!imap_clearflag_full($this->ch, implode(",", $msgs), implode(" ", $flags))) {
	            $this->meta->error=imap_last_error();
            }
            break;
        }
        return (($this->meta->error) ? FALSE : TRUE);

	}
    function headerinfo($msg) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_headerinfo","Message #{$msg}");
		return imap_headerinfo($this->ch, $msg);
    }
    function overview($msg_array) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
        $msgs=( (is_array($msg_array)) ? $msg_array : array($msg_array) );
		$this->stopwatch->split("imap_fetch_overview",number_format(sizeof($msgs)) . " messages");
		return imap_fetch_overview($this->ch, implode(",", $msgs), 0);
    }
/* Mark a message for deletion, Phyiscal deleting occurs during expunge or close */
    function delete($msg_array) {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
        $msgs=( (is_array($msg_array)) ? $msg_array : array($msg_array) );
		$this->stopwatch->split("imap_delete", number_format(sizeof($msgs)) . " messages");
        foreach ($msgs as $msg) {
			imap_delete($this->ch,$msg);
        }
    }
/* Physically delete all messages marked for delete */
    function expunge() {
	    if (!$this->meta->connected) $this->open();
	    if ($this->meta->connection_error) return FALSE;
		$this->stopwatch->split("imap_expunge");
		imap_expunge($this->ch);
    }
    function append($envelope,$body) {
	    if (!$this->meta->connected) $this->open();
        switch (TRUE) {
          case ($this->meta->connection_error):
          	$this->meta->error="Connection error";
          	return FALSE;
          case (!strlen($this->meta->sent)):
          	$this->meta->error="Sent folder not defined";
          	return FALSE;
          case (!is_array($envelope)):
          case (!array_key_exists("to",$envelope)):
          case (!array_key_exists("subject",$envelope)):
          	$this->meta->error="Envelope error";
          	return FALSE;
          case (!is_array($body)):
          case (!sizeof($body)):
          	$this->meta->error="Body error";
          	return FALSE;
		}
		$this->meta->error="";
		$this->stopwatch->split("imap_append");
		if (!imap_append($this->ch, $this->stream("sent"), imap_mail_compose($envelope,$body))) {
    		$this->meta->error=imap_last_error();
        	return FALSE;
          } else {
            return TRUE;
		}
    }
/*
https://www.php.net/manual/en/function.imap-mail-compose.php
http://mailman13.u.washington.edu/pipermail/imap-protocol/2007-November/000729.html
*/
}
class imap_meta_class {
    var $server;
    var $mailbox;
    var $sent;
    var $email;
	var $password;
    var $stream;
    var $connected=FALSE;
    var $connection_error=FALSE;
    var $error;
    function __construct($options) {
    global $database;
		$this->server=(array_key_exists("server",$options)) ? $options['server'] : $database->registry->imap->server;
		$this->mailbox=(array_key_exists("mailbox",$options)) ? $options['mailbox'] : $database->registry->imap->mailbox;
		$this->sent=(array_key_exists("sent",$options)) ? $options['sent'] : $database->registry->imap->sent;
		$this->email=(array_key_exists("email",$options)) ? $options['email'] : $database->registry->imap->email;
		$this->password=(array_key_exists("password",$options)) ? $options['password'] : $database->registry->imap->password;
	}
}
class imap_constant_class {
	var $search=array();
    var $flag=array();
    function __construct() {
		$this->search['ON']=new imap_constant_search("Messages on date","Date");
		$this->search['BEFORE']=new imap_constant_search("Messages before date","Date");
		$this->search['SINCE']=new imap_constant_search("Messages since date","Date");

		$this->search['FROM']=new imap_constant_search("Message FROM","String");
		$this->search['TO']=new imap_constant_search("Message TO","String");
		$this->search['CC']=new imap_constant_search("Message CC","String");
		$this->search['BCC']=new imap_constant_search("Message BCC","String");
		$this->search['SUBJECT']=new imap_constant_search("Message subject","String");
		$this->search['BODY']=new imap_constant_search("Message body","String");
		$this->search['TEXT']=new imap_constant_search("Message text","String");
		$this->search['KEYWORD']=new imap_constant_search("Message keyword","String");
		$this->search['UNKEYWORD']=new imap_constant_search("Message without keyword","String");

		$this->search['SEEN']=new imap_constant_search("SEEN flag on");
		$this->search['UNSEEN']=new imap_constant_search("SEEN flag off");
		$this->search['ANSWERED']=new imap_constant_search("ANSWERED flag on");
		$this->search['UNANSWERED']=new imap_constant_search("ANSWERED flag off");
		$this->search['FLAGGED']=new imap_constant_search("FLAGGED flag on");
		$this->search['UNFLAGGED']=new imap_constant_search("FLAGGED flag off");
		$this->search['DELETED']=new imap_constant_search("DELETED flag on");
		$this->search['UNDELETED']=new imap_constant_search("DELETED flag off");
		$this->search['ALL']=new imap_constant_search("All messages");
		$this->search['NEW']=new imap_constant_search("New messages");
		$this->search['OLD']=new imap_constant_search("Old messages");
		$this->search['RECENT']=new imap_constant_search("Recent messages");

        $this->flag['seen']="\\Seen";
        $this->flag['flagged']="\\Flagged";
        $this->flag['answered']="\\Answered";
        $this->flag['draft']="\\Draft";
        $this->flag['deleted']="\\Deleted";
    }
    function search() {
        $results=array();
        $results["Date"]=array();
        $results["String"]=array();
        $results["Blank"]=array();
        foreach ($this->search as $field => $item) {
			$results[ (($item->type) ? $item->type : "Blank") ][$field]=$item->name . " ({$field})";
        }
        return $results;
    }
}
class imap_constant_search {
	var $name;
    var $type;
	function __construct($name="",$type="") {
		$this->name=$name;
        $this->type=$type;
	}
}
/*
Fetch section
0 - Message header
1 - MULTIPART/ALTERNATIVE
1.1 - TEXT/PLAIN
1.2 - TEXT/HTML
2 - MESSAGE/RFC822 (entire attached message)
2.0 - Attached message header
2.1 - TEXT/PLAIN
2.2 - TEXT/HTML
2.3 - file.ext

Internal options
FT_UID - The msg_number is a UID
FT_PEEK - Do not set the \Seen flag if not already set
FT_INTERNAL - The return string is in internal format, will not canonicalize to CRLF
FT_PREFETCHTEXT - The RFC822.TEXT should be pre-fetched at the same time. This avoids an extra RTT on an IMAP connection if a full message text is desired (e.g. in a "save to local file" operation)
*/
?>