<?php
require_once "scs_header.php";
$database->user->access("Administrator");
$forms->title("Apache Log BOTS");
$forms->report['action']=$_POST['action'];
$forms->report['status']=$_POST['status'];
$forms->report['seconds']=$_POST['seconds'];
switch ($forms->report['action']) {
  case "":
	break;
  default:
	if ($forms->report['status']) $forms->message[]="Status: " . $forms->report['status'];
	set_time_limit($forms->report['seconds']);
    $forms->constant->end_time=strtotime("+" . ($forms->report['seconds'] - 5) . " seconds");
	$forms->constant->ip=array();
	$forms->constant->bot=array();
	$forms->constant->ip=array();
	$upload=new file_upload_class("log");
    $upload->load();
	if ( (!$upload->size) || ($upload->error) ) {
		$forms->error('log',$upload->error);
        break;
    }
    $forms->message[]="Upload file: " . $upload->name;
    $upload->save('/scs.tmp');
	$fh=gzopen("scs.tmp","r");
	while (!feof($fh)) {
		if (strtotime("now") > $forms->constant->end_time) {
        	$forms->constant->timeout=TRUE;
        	break;
		}
		$line=gzgets($fh);
	    if (!preg_match('/^(\S+) \S+ \S+ \[(.*?)\] "(\S+).*?" \d+ \d+ "(.*?)" "(.*?)"/i', $line, $item)) continue;
        $forms->report['thru_timestamp']=$item[2];
        if (!array_key_exists("from_timestamp",$forms->report)) $forms->report['from_timestamp']=$item[2];
	    if ( (!preg_match("/robots\.txt/",$line)) || (array_key_exists($item[1],$forms->constant->ip)) ) continue;
	    $ip=new stdClass();
	    $ip->name=$item[5];
        $bot=$database->bot->match_bot($item[1]);
        switch (TRUE) {
          case (!$forms->report['status']):
          case ($forms->report['status'] == $bot->status):
          case ( ($forms->report['status']=="Undefined") && (!$bot->id) ):
	        $ip->dns=$bot->dns;
	        $ip->bot_id=$bot->id;
	        $forms->constant->ip[$item[1]]=$ip;
		}
	}
	gzclose($fh);
    unlink("scs.tmp");
    if (array_key_exists("from_timestamp",$forms->report)) $forms->message[]="From " . date("m/d/Y g:iA",strtotime($forms->report['from_timestamp'])) . " Thru " . date("m/d/Y g:iA",strtotime($forms->report['thru_timestamp']));
    if (isset($forms->constant->timeout)) $forms->message[]="Timeout after " . $forms->report['seconds'] . " seconds.";
    if (!sizeof($forms->constant->ip)) {
    	$forms->error('null','','No records found');
	  } else {
		ksort($forms->constant->ip);
	}
}
$menu->head();
print $forms->message();
print $forms->open(array("data"=>TRUE));
switch (TRUE) {
  case ( ($forms->report['action']) && (!sizeof($forms->error)) ):
	print $forms->hidden("status",$forms->report['status']);
	print $forms->hidden("seconds",$forms->report['seconds']);
  	print "<table class='small border tablesorter'>\n";
    print "<thead>\n";
    print "<tr>\n";
    print "<th>BOT Name</th>\n";
    print "<th>IP Address</th>\n";
    print "<th>DNS Name</th>\n";
    print "<th>BOT Match</th>\n";
    if (!$forms->report['status']) print "<th>Status</th>\n";
    print "</tr>\n";
    print "<tbody>\n";
    $count=0;
    foreach ($forms->constant->ip as $ip => $data) {
    	switch (TRUE) {
          case (!$data->bot_id):
        	$bg=$forms->background->yellow;
            break;
          case ($database->bot->constant->bot[$data->bot_id]->status == "Active"):
        	$bg=$forms->background->limegreen;
			break;
		  default:
        	$bg=$forms->background->tomato;
        }
    	print "<tr $bg>\n";
        print "<td>" . wordwrap($data->name,80,"<br>") . "</td>\n";
        print "<td>" . $ip . "</td>\n";
        print "<td>" . $data->dns . "</td>\n";
        if ($data->bot_id) {
			$text=$database->bot->constant->bot[$data->bot_id]->code;
		  } else {
			$text="&nbsp;";
        }
        print "<td>$text</td>\n";
		if (!$forms->report['status']) print "<td>" . (($data->bot_id) ? $database->bot->constant->bot[$data->bot_id]->status : "Undefined") . "</td>\n";
        print "</tr>\n";
    }
    print "</tbody>\n";
    print "</table>\n";
    $text=array();
    $text[]="Records found: " . number_format(sizeof($forms->constant->ip));
	$text[]=$forms->submit("Return");
	print "<p>" . implode("<br>",$text) . "</p>";
    break;
  default:
	print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<td width='30%'>WWW Log file</td>\n";
    $text=array();
    $text[]=$forms->file("log");
	if (array_key_exists("log",$forms->error_text)) $text[]="<b>". $forms->error_text['log'] . "</b>";
    print "<td width='70%'>" . implode("<br>",$text) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Status</td>\n";
    print "<td>" . $forms->select("status",array("Active","Denied","Undefined"),$forms->report['status']) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Run time</td>\n";
    print "<td>" .  $forms->select("seconds",array(30,60,90,120,240,600),$forms->report['seconds'],FALSE) . " seconds</td>";
    print "</tr>\n";
    print "</table>\n";
	print "<p>" . $forms->submit("Go!",array("name"=>"action")) . "</p>";
}
print $forms->close();
$menu->copyright();
?>