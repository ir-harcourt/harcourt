<?php
require_once "map_rev3.php";
require_once "classes/imap.php";
require_once "classes/crmlog.php";
require_once "classes/scsmail_rev1.php";
/*
notes: upload notes/kollmorgen_crmlog.php to root folder
cron: /usr/local/bin/ea-php73 kollmorgen_crmlog.php
*/
class crmlog_imap_class {
	var $options=array();
    var $update=array();
    var $log_id=0;
    var $log_comment=array();
    var $count=array();
    var $imap;
	function scs_table_version() {
		$results=array();
        $results['02/07/2022']="Add Authorization: Basic";
        $results['06/28/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role, $options=array()) {
    global $database, $forms, $menu;
		set_time_limit(600);
        $this->options=$options;
        if (!isset($this->options['title'])) $this->options['title']="CRM IMAP Import";
		if ($forms->cron) {
			$this->process();
		  } else {
          	$this->html();
        }
	}
    function html() {
    global $database, $forms, $menu;
		$database->user->access($role);
	    $forms->title($this->options['title']);
	    $forms->message[]="Revised " . key($this->scs_table_version());
	    $menu->head();
	    $forms->message();
        $script=array("");
        $script[]="<script>";
        $script[]="function fn_action() {";
        $script[]="if (!confirm('Continue?')) return;";
        $script[]="jQuery('#scs_form').submit();";
        $script[]="}";
        $script[]="</script>";
        $script[]="";
        print implode("\n",$script);
        print $forms->open();
        print $forms->hidden("action","update");
		$menu->tabs=new jquery_tab_class();
        $results=array();
        if ($_POST['action']) {
        	$results[]=$this->process();
		  } else {
          	$results[]="<p>" . $forms->button("Process unread email",array("onclick"=>"fn_action();")) . "</p>";
        }
        $menu->tabs->item("Input",$results);
        if ($_POST['action']) {
        	$results=array();
            $results[]=implode("\n",$this->stopwatch->results(TRUE));
            $results[]="<p><b>Map Trace:</b><br>" . implode("<br>", $this->map->trace) . "</p>";
        	$menu->tabs->item("Stop Watch",$results);
		}
        $menu->tabs->output();

        print $forms->close();
		$menu->copyright();
    }
    function process() {
    global $database;
        $this->count['email']=0;
        foreach ($database->crmlog->constant->count as $field) {
        	$this->count[$field]=0;
        }
        $comment=array();
        $comment[]="CRM IMAP Import";
        $comment[]="Initiator: " . ( ($forms->cron) ? "CRON"  : $_SERVER['REMOTE_ADDR']);
        $this->log($comment);
        $map_options=array();
	    $map_options['function']="upload";
	    $map_options['zipfile']=".csv";
	    $this->map=new map_class("crmlog",$map_options);
        $this->stopwatch=new stopwatch_class($this->options['title']);
        $this->imap();
		$database->crmlog->import_crm();
		$html=array();
        $html[]="<p><b>CRM IMAP Import Statistics</b><p>";
        $text=array("Run time: " . date("r"));
        if (!$this->count['email']) {
        	$text[]="No records processed";
		  } else {
          	foreach ($this->count as $field => $value) {
            	$text[]="{$field}: " . number_format($value);
            }
		}
        $html[]="<p>" . implode("<br>",$text) . "</p>";
	    $mail_options=array();
	    $this->mail=new scsmail_class("crm_imap",array(),$mail_options);
        $this->mail->html($html);
		$this->mail->send();
        return implode("\n",$html);
    }
    function log($comment) {
    global $database;
		$this->log_comment[]=( (is_array($comment)) ? implode("\n",$comment) : $comment);
		$log_id=$database->log->comment($this->log_id,"CRM Update:IMAP",$this->log_comment);
		if (!$this->log_id) $this->log_id=$log_id;
    }
    function imap() {
    global $database;
		if ( (!strlen($database->registry->crm->imap_code)) || (!strlen($database->registry->crm->imap_text)) ) {
        	$this->log("CRM IMAP Code/Text not set in registry");
        	return;
        }
   		$this->stopwatch->split("Retrieve IMAP Messages");
	    $this->imap=new imap_class();
	    $this->imap->open();
	    $query=array();
	    $query[]=$this->imap->query($database->registry->crm->imap_code,$database->registry->crm->imap_text); // soften this up
	    $query[]=$this->imap->query("UNSEEN");
	    if (!$this->imap->search($query)) {
        	$this->log("No unread email");
        	return;
		}
		$this->overview=$this->imap->overview($this->imap->messages);
        $this->count['email']=sizeof($this->overview);
	    foreach ($this->imap->messages as $i => $msg) {
			$this->update[$msg]=array();
	        if (!$this->imap->read($msg, FT_PEEK)) continue;
	        $dom=new DOMDocument();
	        @$dom->loadHTML($this->imap->content,LIBXML_NOERROR);
	        $links=$dom->getElementsByTagName('a');
	        foreach ($links as $link) {
	            $url=(string)$link->nodeValue;
	            if (!preg_match("/^" . preg_quote("https://config.partcommunity.com/","/") . "(.*)\.zip$"  . "/i",$url)) continue;
	            $item=new stdClass();
                $item->url=$url;
	            $item->i=$i;
	            $item->count=0;
	            $item->add=0;
	            $this->update[$msg][]=$item;
	        }
	    }
	    $this->curlfile=tempnam(sys_get_temp_dir(),"curl");
	    foreach ($this->update as $msg => $items) {
	        $this->stopwatch->split("curl (message {$msg})");
            foreach ($items as $item) {
            	$this->log("URL: " . $item->url);
				$this->upload_url($item);
        		if ($item->error) $this->log($item->error);
            }
		}
		@unlink($this->curlfile);
		$this->imap->flags($this->imap->messages,"seen",TRUE);
    }
    function upload_url($item) {
    global $database, $menu, $forms;
		$this->stopwatch->split_comment($item->url);
        $headers=array();
        $headers[]="Authorization: Basic " . base64_encode($database->registry->cad->statistics['login'] . ":" . $database->registry->cad->statistics['password']);
        $fh=fopen($this->curlfile, "w");
        $ch=curl_init($item->url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_exec($ch);
        $item->http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$item->curl_error=curl_error($ch);
		$item->curl_info=curl_getinfo($ch);
        curl_close($ch);
        fclose($fh);
        if ($item->http_code != "200") {
			$text=array($item->http_code);
            if (strlen($item->curl_error)) $text[]="(" . $item->curl_error . ")";
			$item->error=implode(" ",$text);
            return;
        }
	    $this->zip=new ZipArchive;
	    if (!$this->zip->open($this->curlfile)) {
	    	$item->error=$this->zip->getStatusString();
		  } else {
			$this->upload_zip($item);
		}
	    $this->zip->close();
    }
    function upload_zip($item) {
    global $database;
	    for ($i=0; $i < $this->zip->numFiles; $i++) {
			$this->stopwatch->split_comment("Zip file: " . $this->zip->getNameIndex($i));
            $comment=array("File: " . $this->zip->getNameIndex($i));
	        $this->zipfile=sys_get_temp_dir() ."/" . $this->zip->getNameIndex($i);
	        if (file_exists($this->zipfile)) unlink($this->zipfile);
	        if (!$this->zip->extractTo(sys_get_temp_dir(), $this->zip->getNameIndex($i))) {
	            $item->error="Zip extract error";
			  } else {
	            $options=array();
	            $options["external_file"]=$this->zipfile;
	            $options["update"]=TRUE;
	            $options["complete"]=TRUE;
	            $options["nolog"]=TRUE;
                $this->map->upload("",$options);
                $text=array();
        		foreach ($database->crmlog->constant->count as $field) {
                	if ($database->crmlog->map->meta->count[$field]) {
                    	$this->count[$field] += $database->crmlog->map->meta->count[$field];
						$text[]="{$field}: " . number_format($database->crmlog->map->meta->count[$field]);
					}
                }
                if (!sizeof($text)) $text[]="No records found";
                $comment[]="Records: " . implode(", ",$text);
                $this->log($comment);
	        }
	        unlink($this->zipfile);
        }
	}
}
?>