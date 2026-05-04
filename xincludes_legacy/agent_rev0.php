<?php
require_once "classes/scsbrowser.php";
$forms->title("Access Log Agents");
$forms->report['action']=$_POST['action'];
$forms->report['browscap']=$_POST['browscap'];
$forms->report['page']=$_POST['page'];
$forms->report['output']=$_POST['output'];
$query=
    "select * from log \n" .
    "where type <> '' \n" .
    "group by type\n" .
    "order by type";
$database->log->query($query);
$type_list=array();
$type_select=array();
while ($database->log->fetch = $database->log->fetch_array()) {
    $database->log->fetch();
    $type_list[$database->log->data->type]="";
}
$database->log->free_result();
$output_list=array("Summary","Detail");

switch ($forms->report['action']) {
  case "":
	$forms->report['from_date']=date("Y/m/d",strtotime("-1 year"));
	$forms->report['thru_date']=date("Y/m/d",strtotime("now"));
	break;
  default:
	$forms->report['from_date']=fn_date($_POST['from_date'],"mdy");
	if (!$date_ok) $forms->error('from_date');
	$forms->report['thru_date']=fn_date($_POST['thru_date'],"mdy");
	switch (TRUE) {
	  case (!$date_ok):
	  case (($forms->report['thru_date']) && ($forms->report['from_date'] > $forms->report['thru_date'])):
	    $forms->error('thru_date');
	}
    foreach ($_POST as $key => $value) {
		list($type,$field)=explode("\t",base64_decode($key));
		if ($field=="type") $type_select[$value]=$value;
    }
    if (!sizeof($type_select)) $forms->error("null","","No record types selected");
    if (sizeof($forms->error)) break;

	$query_where=array();
	$query_where['type'] = new query_where("type","in",$type_select);
	$query_where['agent'] = new query_where("agent","<>","");
    if ($forms->report['from_date']) $query_where['from_date'] = new query_where("date_format(from_unixtime(date_time),'%Y/%m/%d')",">=",$forms->report['from_date']);
    if ($forms->report['thru_date']) $query_where['thru_date'] = new query_where("date_format(from_unixtime(date_time),'%Y/%m/%d')","<=",$forms->report['thru_date']);
	$forms->report['query'] =
		"select \n" .
        "agent as 'agent', \n" .
        "min(date_time) as 'first_date',\n" .
        "max(date_time) as 'last_date',\n" .
        "count(*) as 'count' \n" .
        "from log \n" .
        $database->where($query_where) .
        "group by agent " .
        "order by agent";
}
$menu->head();
$forms->message();

print $forms->open();
print $forms->hidden("action","list");
print $forms->hidden("record_id");
print $forms->hidden("page");

print "<table class='standard noborder'>\n";
print "<tr>\n";
print "<td width='30%'>Range of dates</td>\n";
print "<td width='70%'>From: " . $forms->text("from_date",$forms->report['from_date'],10,10,"date:ymd");
print " Thru: " . $forms->text("thru_date",$forms->report['thru_date'],10,10,"date:ymd") . "</td>\n";
print "</tr>\n";
$text="";
foreach ($type_list as $key => $checked) {
	if ($text) $text .= "<br>";
    $text .= $forms->checkbox(base64_encode($key . "\t" . "type"),$key,$type_select[$key]) . " $key";
}
if ($database->scsbrowser->constant->browscap) {
	print "<tr>\n";
	print "<td>PHP Browscap?</td>\n";
	print "<td>" . $forms->checkbox("browscap",1,$forms->report['browscap']) . "</td>\n";
	print "</tr>\n";
}
print "<tr>\n";
print "<td>Output</td>\n";
print "<td>" . $forms->select("output",$output_list,$forms->report['output'],FALSE) . "</td>\n";
print "</tr>\n";
print "<tr>\n";
print "<td>Record types</td>\n";
print "<td>$text</td>\n";
print "</tr>\n";
print "<table>\n";
print "<p>" . $forms->submit("List") . "</p>\n";
switch (TRUE) {
  case (sizeof($forms->error)):
	break;
  case ($forms->report['output']=="Detail"):
	$start=microtime(TRUE);
    $query =
		$forms->report['query'] .
		$database->page_limit($forms->report['page']);
	$database->log->query($query,TRUE);
	print "<table class='standard border'>\n";
	print "<tr>\n";
    print "<th>Agent</th>\n";
	print "<th>Browser/OS</th>\n";
    print "<th>Features</th>\n";
    print "<th>Stats</th>\n";
    print "</tr>\n";
	while ($database->log->fetch=$database->log->fetch_array()) {
    	$database->log->fetch();
		$database->scsbrowser->agent($database->log->data->agent,$forms->report['browscap']);
        print "<tr>\n";
        print "<td>" . $database->log->data->agent . "</td>\n";
        print "<td class=nobr>" . $database->scsbrowser->data->comment;
        print "<br>" . $database->scsbrowser->data->platform . "</td>\n";
        print "<td class=nobr>" . implode("<br>",$database->scsbrowser->data->_options) . "</td>\n";
        print "<td class=nobr>First:" . date("m/d/Y",$database->log->fetch['first_date']);
        print "<br>Last: " . date("m/d/Y",$database->log->fetch['last_date']);
        print "<br># pages: " . number_format($database->log->fetch['count']);
        print "</td>\n";
        print "</tr>\n";
    }
    $database->log->free_result();
    print "</table>\n";
	$stop=microtime(TRUE);
    $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('list','%page%','');");
    print $page_break->page($forms->report['page'],"Elapsed time: " . number_format((floatval($stop) - floatval($start)),2) . " seconds");
	break;
  case ($forms->report['output']=="Summary"):
	$start=microtime(TRUE);
	$results=array();
	$database->log->query($forms->report['query']);
	while ($database->log->fetch=$database->log->fetch_array()) {
    	$database->log->fetch();
		$database->scsbrowser->agent($database->log->data->agent,$forms->report['browscap']);
        switch (TRUE) {
		  case ($database->scsbrowser->data->crawler):
	        $results['crawler_count'] += $database->log->fetch['count'];
			$results['crawler'][$database->scsbrowser->data->browser][$database->scsbrowser->data->version] += $database->log->fetch['count'];
			break;
		  case ($database->scsbrowser->data->syndicationreader):
	        $results['syndicationreader_count'] += $database->log->fetch['count'];
			$results['syndicationreader'][$database->scsbrowser->data->browser][$database->scsbrowser->data->version] += $database->log->fetch['count'];
			break;
		  default:
	        $results['browser_count'] += $database->log->fetch['count'];
	        $results['browser'][$database->scsbrowser->data->browser][$database->scsbrowser->data->version] += $database->log->fetch['count'];
	        $results['os'][$database->scsbrowser->data->platform] += $database->log->fetch['count'];
	        $results['css'][$database->scsbrowser->data->cssversion] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->frames) $results['features']['Frames'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->iframes) $results['features']['Iframes'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->tables) $results['features']['Tables'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->cookies) $results['features']['Cookies'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->backgroundsounds) $results['features']['Background Sounds'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->javascript) $results['features']['Javascript'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->vbscript) $results['features']['VBscript'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->javaapplets) $results['features']['Java Applets'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->activexcontrols) $results['features']['ActiveX Controls'] += $database->log->fetch['count'];
	        if ($database->scsbrowser->data->ismobiledevice) $results['features']['Mobile Device'] += $database->log->fetch['count'];
		}
	}
    $database->log->free_result();
	$stop=microtime(TRUE);
    print "<p>" . number_format($results['browser_count']) . " total page views in " . number_format((floatval($stop) - floatval($start)),2) . " seconds</p>\n";
    if (sizeof($results['browser'])) {
	    $browsers=$results['browser'];
        ksort($browsers);
	    print "<table class='standard border'>\n";
	    print "<caption><br>&nbsp;</caption>\n";
        print "<tr>\n";
        print "<th>Broswer</th>\n";
        print "<th>Page views</th>\n";
        print "<th>% of total</th>\n";
        print "<th>Version</th>\n";
        print "<th>Page views</th>\n";
        print "<th>% browser</th>\n";
        print "</tr>\n";
        foreach ($browsers as $browser => $versions) {
            $browser_count=array_sum($versions);
			ksort($versions);
			$i=0;
            foreach ($versions as $version => $count) {
	            print "<tr>\n";
                if (!$i) {
	                print "<td rowspan=" . sizeof($versions) . ">$browser</td>\n";
	                print "<td rowspan=" . sizeof($versions) . " class=right>" . number_format($browser_count) . "</td>\n";
	                print "<td rowspan=" . sizeof($versions) . " class=right>" . number_format((($browser_count / $results['browser_count']) * 100),2) . "%</td>\n";
				}
                print "<td>$version</td>\n";
                print "<td class=right>" . number_format($count) . "</td>\n";
                print "<td class=right>" . number_format((($count / $browser_count) * 100),2) . "%</td>\n";
	            print "</tr>\n";
                $i++;
			}
        }
	    print "</table>\n";
    }
    if (sizeof($results['os'])) {
	    $os=$results['os'];
        ksort($os);
	    print "<table class='standard border'>\n";
	    print "<caption><br>&nbsp;</caption>\n";
        print "<tr>\n";
        print "<th>Operating system</th>\n";
        print "<th>Page views</th>\n";
        print "<th>% of total</th>\n";
        print "</tr>\n";
        foreach ($os as $key => $value) {
	        print "<tr>\n";
	        print "<td>$key</td>\n";
	        print "<td class=right>" . number_format($value) . "</td>\n";
	        print "<td class=right>" . number_format((($value / $results['browser_count']) * 100),2) . "%</td>\n";
	        print "</tr>\n";
        }
        print "</table>\n";
	}
    if (sizeof($results['features'])) {
	    print "<table class='standard border'>\n";
	    print "<caption><br>&nbsp;</caption>\n";
        print "<tr>\n";
        print "<th>Feature</th>\n";
        print "<th>Page views</th>\n";
        print "<th>% of total</th>\n";
        print "</tr>\n";
        foreach ($results['features'] as $key => $value) {
	        print "<tr>\n";
	        print "<td>$key</td>\n";
	        print "<td class=right>" . number_format($value) . "</td>\n";
	        print "<td class=right>" . number_format((($value / $results['browser_count']) * 100),2) . "%</td>\n";
	        print "</tr>\n";
		}
        print "</table>\n";
    }
 	break;
}
print $forms->close();
$menu->copyright();
?>