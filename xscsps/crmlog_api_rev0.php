<?php
require_once "map_rev3.php";
require_once "classes/crmlog.php";
require_once "classes/scsmail_rev1.php";
/*
notes: upload notes/kollmorgen_crmlog_api.php to root folder
cron: /usr/local/bin/ea-php73 kollmorgen_crmlog_api.php
*/
class crmlog_api_class {
    var $date;
    var $today;
    var $yesterday;
    var $tomorrow;
    var $last_cron;
    var $dates=array();
	var $options=array();
    var $log_id=0;
    var $download=0;
    var $update=1;
    var $log_type="CRM Update:API";
    var $log_comment=array();
    var $count=array();
    var $filename;
    var $title;
    var $error;
	function scs_table_version() {
		$results=array();
        $results['02/28/2024']="PHP 8 compatible";
        $results['05/13/2022']="Import included multiple companies activity";
        $results['02/16/2022']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($role, $options=array()) {
        global $database, $forms, $menu;
		set_time_limit(600);
        $this->options=$options;
        if (array_key_exists("log_type", $options)) $this->log_type=$options['log_type'];
        $this->today=date("Y/m/d",strtotime("now"));
        $this->yesterday=date("Y/m/d",strtotime("yesterday"));
        $this->tomorrow=date("Y/m/d",strtotime("tomorrow"));
        switch (TRUE) {
          case (!$database->registry->crmlog->last_cron):
			$this->last_cron=$this->yesterday;
            break;
          case ($database->registry->crmlog->last_cron > $this->today):
			$this->last_cron=$this->today;
            break;
		  default:
			$this->last_cron=$database->registry->crmlog->last_cron;
		}
        $this->title=(array_key_exists("title", $options)) ? $this->options['title'] : "CRM API Import";
        switch (TRUE) {
          case ($forms->cron):
			$this->process();
            break;
          case $database->user->access($role):
          	$this->html($role);
            break;
        }
	}
    function html() {
    global $database, $forms, $menu;
	    $forms->title("{$this->title} (" . key($this->scs_table_version()) . ")");
        switch ($_POST['action']) {
          case "":
          	$this->date=$this->today;
            break;
          case "update":
        	$this->date=fn_date($_POST['date'],"mdy");
            $this->download=intval($_POST['download']);
            $this->update=intval($_POST['update']);
            switch (TRUE) {
              case ( (!$this->date) && (!$database->user->access("Super User",FALSE)) ):
				$forms->error("date","Cannot be 00/00/0000");
                break;
			}
            if (!$this->date) $this->download=0;
            if (sizeof($forms->error)) break;
	        $this->process();
            break;
        }
	    $menu->head();
	    $forms->message();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action","update");
		$menu->tabs=new jquery_tab_class();
        $results=array();
        switch (TRUE) {
          case ( ($_POST['action']) && (!sizeof($forms->error)) ):
        	$menu->tabs->item("Results",implode("<br>",$this->log_comment));
			break;
		  default:
			$results=array();
            $results[]="<table class='standard noborder'>";
            if ($database->registry->crmlog->last_cron) {
            	$results[]="<tr>";
            	$results[]="<td>Last CRON Import</td>";
                $results[]="<td>" . fn_date($database->registry->crmlog->last_cron,"ymd") . "</td>";
                $results[]="</tr>";
			}
            $results[]="<tr>";
            $results[]="<td width='20%'>Date</td>";
            $text=array($forms->text("date",$this->date,10,10,"date"));
            if ($forms->error_text['date']) $text[]=$forms->error_text['date'];
            $results[]="<td width='80%'>" . implode(" ",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Update?</td>";
            $results[]="<td>" . $forms->checkbox("update",1,$this->update) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Download?</td>";
            $results[]="<td>" . $forms->checkbox("download",1,$this->download) . "</td>";
            $results[]="</tr>";
            $results[]="</table>";
			$results[]=$forms->button("Continue",array("onclick"=>"fn_action();"));
        	$menu->tabs->item("Input",$results);
        }
        switch (TRUE) {
          case ( ($_POST['action']) && (!sizeof($forms->error)) ):
        	$results=array();
            $results[]=implode("\n",$this->stopwatch->results(TRUE));
        	$menu->tabs->item("Stop Watch",$results);
		}
        $menu->tabs->output();

        print $forms->close();
		$menu->copyright();
    }
	function process() {
    global $database, $forms, $menu;
		set_time_limit(600);
        $this->stopwatch=new stopwatch_class($this->options['title']);
        $this->filename=sys_get_temp_dir() . "/" . $database->registry->cad->catalog . ".csv";
    	switch (TRUE) {
          case ($this->date): // Manual entry
			$update_code="Manual";
			$this->dates[$this->date]=new stdClass();  // class reserved for future purposes
			break;
          case ($this->last_cron == $this->today):
			$update_code="Today";
			$this->dates[$this->yesterday]=new stdClass();
			$this->dates[$this->today]=new stdClass();
            break;
		  default:
			$update_code="Range";
			$date=$this->last_cron;
            while ($date < $this->tomorrow) {
				$this->dates[$date]=new stdClass();
				$date=date("Y/m/d",strtotime($date . " +1 days"));
            }
        }
		$text=array("{$this->title} ({$update_code})");
        $text[]=fn_date(key($this->dates),"ymd");
        if (sizeof($this->dates) > 1) {
        	$text[]="-";
            $text[]=fn_date(key(array_slice($this->dates, -1, 1, TRUE)), "ymd");
        }
        if (!$this->update) $text[]="(No CRM Update)";
		$this->log(implode(" ",$text));
        foreach ($this->dates as $date => $obj) {
        	if (!$this->process_date($date,TRUE)) break;
        }
        @unlink($this->filename);
        if (!$this->update) return;

		$text=array();
		$database->crmlog->import_crm();
		foreach ($this->dates as $date => $obj) {
        	switch (TRUE) {
              case ($obj->error):
				$text[]=fn_date($date,"ymd") . " Error: " . $obj->error;
                break;
              case ($obj->count->add):
              	$text[]=fn_date($date,"ymd") . " Added: " . number_format($obj->count->add);
                break;
            }
        }
        if ($forms->cron) $this->cron($text);
/*
		if ( ($forms->cron) && (sizeof($text)) && (strlen($database->registry->crmlog->notify)) ) {
	        $html=array();
	        $html[]="<p><b>{$this->title} Statistics</b><p>";
	        $text=array("Run time: " . date("r"));
	        $html[]="<p>" . implode("<br>",$this->log_comment) . "</p>";
	        $mail_options=array();
	        $this->mail=new scsmail_class($database->registry->crmlog->notify, array(), $mail_options);
	        $this->mail->html($html);
	        $this->mail->send();
		}
*/
    }
    function cron($text) {
        global $database, $forms;
        if (  (sizeof($text)) && (strlen($database->registry->crmlog->notify)) ) {
	        $html=array();
	        $html[]="<p><b>{$this->title} Statistics</b><p>";
	        $text=array("Run time: " . date("r"));
	        $html[]="<p>" . implode("<br>",$this->log_comment) . "</p>";
	        $mail_options=array();
	        $this->mail=new scsmail_class($database->registry->crmlog->notify, array(), $mail_options);
	        $this->mail->html($html);
	        $this->mail->send();
		}
        die("<p>" . implode("<br>",$this->log_comment) . "</p>");
    }
    function process_date($date) {
    global $database, $forms, $menu;
    	if (sizeof($this->dates) > 1) $this->stopwatch->split(fn_date($date,"ymd"));
        $map_options=array();
	    $map_options['function']="upload";
		$map_options['file_type']=".csv";
	    $this->map=new map_class("crmlog",$map_options);
        $headers=array();
        $headers[]="Authorization: Basic " . base64_encode($database->registry->crmlog->login . ":" . $database->registry->crmlog->password);
	    $options=array();
	    $options[]="templateName=" . $database->registry->crmlog->template;
	    $options[]="format=CSV";
	    $options[]="dt=". str_replace("/","-",$date) . "T00:00:00";
	    $options[]="comp=false";
	    $url=$database->registry->crmlog->url . "?" . implode("&",$options);
        $fh=fopen($this->filename, "w");
        $ch=curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_exec($ch);
        $curl_http=curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error=curl_error($ch);
		$curl_info=curl_getinfo($ch);
        curl_close($ch);
        fclose($fh);
        switch (TRUE) {
          case ($curl_http != "200"):
        	$text=array();
        	$text=array("CURL Error #{$curl_http}");
            if (strlen($curl_error)) $text[]="($curl_error)";
            $this->log(implode(" ",$text));
            return;
		  case ($this->download):
			$filename=$database->registry->cad->catalog . str_replace("/","-",$date) . ".csv";
	      	header("Content-type: text/plain");
	        header("Content-Disposition: attachment; filename=" . $filename);
	        header("Pragma: no-cache");
	        header("Expires: 0");
			die(file_get_contents($this->filename));
		}
        if (!$this->date) $database->registry->update("crmlog", "last_cron", $date);
        $comment=array();
        $options=array();
        $options["external_file"]=$this->filename;
        $options["update"]=TRUE;
        $options["complete"]=TRUE;
        $options["nolog"]=TRUE;
        if ($this->map->upload("",$options)) {
			$this->dates[$date]->count=(object) $database->crmlog->map->meta->count;
          	$text=array();
	    	if (!$this->date) $text[]=(fn_date($date,"ymd"));
            $text[]="Records Processed: " . array_sum($database->crmlog->map->meta->count);
            foreach ($database->crmlog->map->meta->count as $field => $count) {
            	if ($count) $text[]="{$field}: " . number_format($count);
            }
            $comment[]=implode(" ",$text);
		  } else {
			$this->dates[$date]->error=$this->map->upload_error;
        	$comment[]="Error(s) detected:<br>" . $this->map->upload_error;
        }
        $this->log(implode($comment));
		return TRUE;
    }
    function log($comment) {
    global $database;
		$this->log_comment[]=( (is_array($comment)) ? implode("\n",$comment) : $comment);
		$this->log_id=$database->log->comment($this->log_id, $this->log_type, $this->log_comment);
    }
    function js() {
        return <<< EOT
<script>
function fn_action() {
    if (!confirm('Continue?')) return;
    jQuery('#scs_form').submit();
}
</script>
EOT;
    }
}
?>