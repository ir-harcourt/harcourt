<?php
require_once "classes/gwtscs.php";
require_once "classes/gwtlog.php";
require_once "classes/gwttrack.php";

require_once "chart_rev1.php";

$gwt=new gwtscs_class();
$forms->title("Google Webmaster Toolbox (" .  key($gwt->scs_table_version()) . ")");
$forms->html->script("js",array("/js/scs_chart.js"));
$forms->report['action']=$_REQUEST['action'];
$forms->report['action2']=$_REQUEST['action2'];

switch ($forms->report['action']) {
  case "json":
  	switch (TRUE) {
      case (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
      case (!preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])):
	  case (!preg_match("/^(http|https)" . preg_quote("://" . $_SERVER['HTTP_HOST'],"/") . "/i",$_SERVER['HTTP_REFERER'])):
        die("Direct request");
	}
    list($type,$code)=preg_split("/\t/",base64_decode($_REQUEST['code']));
    switch (TRUE) {
	  case (!array_key_exists($type,$gwt->type)):
      case (!strlen($code)):
		die("Type: $type Code: $code");
    }
	$database->gwttrack->read($database->registry->gwt->domain,$type,$code);
    $results=array();
    $results['type']=$type;
    $results['code']=$code;
    switch (TRUE) {
	  case (!$database->gwttrack->meta->rows):
		$results['status']="add";
        break;
	  case (!$database->gwttrack->data->active):
		$results['status']="inactive";
        break;
	  default:
		$results['status']="active";
        break;
    }
	if ($forms->report['action2']) $results['update']=$forms->report['action2'];
    switch (TRUE) {
	  case (!$forms->report['action2']):
		break;
	  case ( (!$database->gwttrack->meta->rows) && ($forms->report['action2']=="add") ) :
		$database->gwttrack->data->domain=$database->registry->gwt->domain;
		$database->gwttrack->data->type=$type;
		$database->gwttrack->data->code=$code;
		$database->gwttrack->data->comment="Added by " . $_SESSION['user']->name . " @ " . date("m/d/Y h:i A");
		$database->gwttrack->update(FALSE);
		break;
	  case (!$database->gwttrack->meta->rows):
		break;
	  case ($forms->report['action2']=="active"):
		$database->gwttrack->data->comment="Activated by " . $_SESSION['user']->name . " @ " . date("m/d/Y h:i A");
        $database->gwttrack->data->active=1;
		$database->gwttrack->update(TRUE);
		break;
	  case ($forms->report['action2']=="inactive"):
		$database->gwttrack->data->comment="Deactivated by " . $_SESSION['user']->name . " @ " . date("m/d/Y h:i A");
        $database->gwttrack->data->active=0;
		$database->gwttrack->update(TRUE);
		break;
	  case ($forms->report['action2']=="delete"):
		$database->gwttrack->delete($database->gwttrack->data->id);
		break;
	}
    print json_encode($results);
	die;
  case "":
    $forms->report['live_from_date']=date("Y/m/d",strtotime("-2 days"));
    $forms->report['live_thru_date']=date("Y/m/d",strtotime("-2 days"));
    $forms->report['live_track']=3;
	$forms->report['update_from_date']=date("Y/m/d",strtotime("-2 days"));
	$forms->report['update_thru_date']=date("Y/m/d",strtotime("-2 days"));
	$forms->report['update_timeout']=30;
    $forms->report['history_from_date']=date("Y/m/d",strtotime("-32 days"));
    $forms->report['history_thru_date']=date("Y/m/d",strtotime("-2 days"));
    $forms->report['history_active']=TRUE;
	break;
  default:
	$forms->report['live_from_date']=fn_date($_POST['live_from_date'],"mdy");
	$forms->report['live_thru_date']=fn_date($_POST['live_thru_date'],"mdy");
	$forms->report['live_track']=$_POST['live_track'];
	$forms->report['live_order']=$_POST['live_order'];
	$forms->report['live_output']=$_POST['live_output'];

	$forms->report['update_from_date']=fn_date($_POST['update_from_date'],"mdy");
	$forms->report['update_thru_date']=fn_date($_POST['update_thru_date'],"mdy");
	$forms->report['update_timeout']=$_POST['update_timeout'];

	$forms->report['history_from_date']=fn_date($_POST['history_from_date'],"mdy");
	$forms->report['history_thru_date']=fn_date($_POST['history_thru_date'],"mdy");
    $forms->report['history_active']=$_POST['history_active'];
	$forms->report['history_order']=$_POST['history_order'];
}
switch ($forms->report['action']) {
  case "":
	break;
  case "live":
    switch ($forms->report['action2']) {
      case "":
         break;
      case "build":
        switch (TRUE) {
          case (!$forms->report['live_from_date']):
          case ($forms->report['live_from_date'] > date("Y/m/d",strtotime("-2 days"))):
			$forms->error('live_from_date');
		}
        switch (TRUE) {
          case (!$forms->report['live_thru_date']):
          case ($forms->report['live_thru_date'] < $forms->report['live_from_date']):
          case ($forms->report['live_thru_date'] > date("Y/m/d",strtotime("-2 days"))):
			$forms->error('live_thru_date');
		}
        if (sizeof($forms->error)) break;
		$gwt->login();
		if (!$gwt->login) {
	        $forms->error("null","","Invalid User Name/Password");
	        break;
		}
		$gwt->download($forms->report['live_from_date'],$forms->report['live_thru_date']);
        switch (TRUE) {
          case (!$gwt->download):
          	$forms->error('null',"","Connection error");
            break;
          case (!$gwt->count):
          	$forms->error('null',"","No records found");
            break;
		}
    }
	break;
  case "update":
	$gwt->login();
    switch (TRUE) {
      case (!$gwt->login):
		$forms->message[]="Invalid User Name/Password";
        $forms->report['action']="";
    	break;
      case (!$forms->report['action2']):
        break;
	  default:
        switch (TRUE) {
          case (!$forms->report['update_from_date']):
          case ($forms->report['update_from_date'] > date("Y/m/d",strtotime("-2 days"))):
			$forms->error('update_from_date');
		}
        switch (TRUE) {
          case (!$forms->report['update_thru_date']):
          case ($forms->report['update_thru_date'] < $forms->report['update_from_date']):
          case ($forms->report['update_thru_date'] > date("Y/m/d",strtotime("-2 days"))):
			$forms->error('update_thru_date');
		}
        if (sizeof($forms->error)) break;
		$gwt->login();
		if (!$gwt->login) {
	        $forms->error("null","","Invalid User Name/Password");
	        break;
		}
		set_time_limit($forms->report['update_timeout']);
        $eof=FALSE;
        $date=$forms->report['update_from_date'];
        while (!$eof) {
			$gwt->download($date,$date);
            $query=
				"insert into gwtlog \n" .
                "select \n" .
                fn_escape($database->registry->gwt->domain) . ",\n" .
                "gwttemp.type, \n" .
                fn_escape($date,TRUE) . ",\n" .
				"gwttemp.code, \n" .
                "gwttemp.impressions, \n" .
                "gwttemp.clicks, \n" .
                "gwttemp.position \n" .
                "from gwttemp \n" .
                "left join gwttrack on gwttrack.domain=" . fn_escape($database->registry->gwt->domain) . " and gwttrack.type=gwttemp.type and gwttrack.code=gwttemp.code \n" .
                "where not isnull(gwttrack.id)";
			$database->temp->query($query);
			$date=date("Y/m/d",strtotime($date . " +1 day"));
			if ($date > $forms->report['update_thru_date']) $eof=TRUE;
		}
		$forms->message[]="Processing Complete";
	}
	break;
}
$menu->head();
$forms->message();
$js=array();

//ui-dialog-buttonpane

$js[]="<script language=javascript>";
$js[]="function fn_action(action,action2) {";
$js[]="    document.scs_form.action.value=action;";
$js[]="    document.scs_form.action2.value=action2;";
$js[]="    document.scs_form.submit();";
$js[]="}";
$js[]="";
$js[]="function gwttrack_update(obj) {";
$js[]="  obj.checked=false;";
$js[]="  data=gwttrack_json('',obj.id);";
$js[]="  if (!data['status']) return;";


$js[]="  myButtons=[];";

$js[]="  if (data['status']=='add') {";
$js[]="    myButtons[myButtons.length]={";
$js[]="      text: 'Add',";
$js[]="      icons: { primary: 'ui-icon-circle-plus' },";
$js[]="      click: function() { gwttrack_json('add',obj.id); }";
$js[]="    };";
$js[]="  }";

$js[]="  if (data['status']=='active') {";
$js[]="    myButtons[myButtons.length]={";
$js[]="      text: 'Diable',";
$js[]="      icons: { primary: ' ui-icon-circle-minus' },";
$js[]="      click: function() { gwttrack_json('inactive',obj.id); }";
$js[]="    };";
$js[]="  }";

$js[]="  if (data['status']=='inactive') {";
$js[]="    myButtons[myButtons.length]={";
$js[]="      text: 'Enable',";
$js[]="      icons: { primary: 'ui-icon-circle-plus' },";
$js[]="      click: function() { gwttrack_json('active',obj.id); }";
$js[]="    };";
$js[]="  }";
$js[]="  if (data['status'] != 'add') {";
$js[]="    myButtons[myButtons.length]={";
$js[]="      text: 'Delete',";
$js[]="      icons: { primary: 'ui-icon-trash' },";
$js[]="      click: function() { gwttrack_json('delete',obj.id); }";
$js[]="    };";
$js[]="  }";

$js[]="    $( '#gtwtrack_dialog_code' ).html( data['code'] );";
$js[]="    $( '#gtwtrack_dialog' ).dialog( 'option', 'buttons' ,myButtons);";
$js[]="    $( '#gtwtrack_dialog' ).dialog( 'open' );";
$js[]="}";

$js[]="function gwttrack_json(action2,code) {";
$js[]="  if ( $( '#gtwtrack_dialog' ).dialog( 'isOpen' ) ) $( '#gtwtrack_dialog' ).dialog( 'close' );";
$js[]="  var response=Array();";
$js[]="  $.ajax({";
$js[]="      url: '" . $_SERVER['PHP_SELF'] . "',";
$js[]="      dataType: 'json',";
$js[]="      async: false,";
$js[]="      data: {action: 'json', action2: action2, code: code },";
$js[]="      success:function(data) { response = data; }";
$js[]="  });";
$js[]="  obj=document.getElementById('r_' + code);";
$js[]="  switch (true) {";
$js[]="    case (!action2):";
$js[]="    case (!response['update']):";
$js[]="    case (!obj):";
$js[]="      bg=''";
$js[]="      break;";
$js[]="    case (response['update'] == 'inactive'):";
$js[]="      bg='" . $database->registry->gwt->color['inactive'] . "'";
$js[]="      break;";
$js[]="    case (response['update'] == 'delete'):";
$js[]="      bg='" . $database->registry->gwt->color['default'] . "'";
$js[]="      break;";
$js[]="    default:";
$js[]="      bg='" . $database->registry->gwt->color['active'] . "'";
$js[]="      break;";
$js[]="  }";
$js[]="  if (bg) obj.style.backgroundColor=bg;";
$js[]="  return response;";
$js[]="}";

$js[]="$(function() {";
$js[]="  $('#gtwtrack_dialog').dialog({";
$js[]="    autoOpen: false,";
$js[]="    resizable: false,";
$js[]="    width: 300,";
$js[]="    height: 'auto',";
$js[]="    maxHeight: 600,";
$js[]="    modal: true";
$js[]="  });";
$js[]="});";
$js[]="</script>";

$js[]="<div id='gtwtrack_dialog' title='GWT Tracking'><span id=gtwtrack_dialog_code></span></div>";
print implode("\n",$js);

print $forms->open();
print $forms->hidden('action');
print $forms->hidden('action2');

$forms->report['live_totals']=array();
$forms->report['history_totals']=array();
$forms->report['history_detail']=array();
$tabs=new jquery_tab_class("standard");
$tabs->item("Overview",gwt_overview());
$tabs->item("Live: Load",gwt_live());
$tabs->item("Live: Summary",gwt_live_summary());
foreach ($gwt->type as $type_code => $type_name) {
	if ($forms->report['live_totals'][$type_code]) $tabs->item("Live: $type_name (" . number_format($forms->report['live_totals'][$type_code]) . ")",gwt_live_detail($type_code));
}
$tabs->item("History: Generate",gwt_history());
foreach ($gwt->type as $type_code => $type_name) {
	if ($forms->report['history_totals'][$type_code]) $tabs->item("History: $type_name (" . number_format(sizeof($forms->report['history_totals'][$type_code])) . ")",gwt_history_summary($type_code));
}
$tabs->item("History: Detail",gwt_history_detail());
gwt_history_graph();
$tabs->item("Update",gwt_update());
$tabs->output();
print $forms->close();
$menu->copyright();

function gwt_overview() {
global $forms, $database, $gwt, $tabs;
	$results=array();
	$results[]="<p><b>Google Webmaster Tools Overview</b></p>";
	$results[]="<p><b>Live View:</b><br>";
    $results[]="Request statistics from your site directly from the Google servers. ";
    $results[]="You can select items to be tracked by selecting the track button. ";
    $results[]="Daily history will only be stored for tracked items.";
    $results[]="</p>";
	$results[]="<p><b>Update:</b><br>";
    $results[]="Update your history from statistics directly from the Google servers. ";
    $results[]="Only tracked items are saved.";
    $results[]="</p>";
	$results[]="<p><b>History:</b><br>";
    $results[]="View history for tracked items.";
    $results[]="</p>";
    return $results;
}

function gwt_history() {
global $forms, $database, $gwt, $tabs;
    if ($forms->report['action'] == "history") $tabs->landing_tab();
	$results=array();
	$results[]="<table class='standard noborder'>";
	$results[]="<tr>";
	$results[]="<td width='30%'>Domain</td>";
	$results[]="<td width='70%'><b>" . $database->registry->gwt->domain . "</b></td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>From Date</td>";
	$results[]="<td>" . $forms->text("history_from_date",$forms->report['history_from_date'],10,10,"date:ymd") . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Thru Date</td>";
	$results[]="<td>" . $forms->text("history_thru_date",$forms->report['history_thru_date'],10,10,"date:ymd") . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Order</td>";
    $results[]="<td>" . $forms->select("history_order",$gwt->order(TRUE),$forms->report['history_order'],FALSE) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Only Active?</td>";
	$results[]="<td>" . $forms->checkbox("history_active",1,$forms->report['history_active']) . "</td>";
	$results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->button("View History",array("onclick"=>"fn_action('history','list');")) . "</p>";
    switch (TRUE) {
	  case ($forms->report['action'] != "history"):
      case (sizeof($forms->error)):
		break;
	  default:
		$query_where=array();
		$query_where[]=new query_where("gwttrack.domain","=",$database->registry->gwt->domain);
		$query_where[]=new query_where("gwtlog.date",">=",$forms->report['history_from_date']);
		$query_where[]=new query_where("gwtlog.date","<=",$forms->report['history_thru_date']);
        if ($forms->report['history_active']) $query_where[]=new query_where("gwttrack.active","=",1);
        $query_order=array();
        $query_order["type"]=FALSE;
        switch ($forms->report['history_order']) {
		  case "impressions":
			$query_order["impressions"]=TRUE;
            break;
		  case "clicks":
			$query_order["clicks"]=TRUE;
			$query_order["impressions"]=TRUE;
            break;
		  case "ctr":
			$query_order["ctr"]=TRUE;
			$query_order["impressions"]=TRUE;
            break;
		  case "position":
			$query_order["min_position"]=FALSE;
			$query_order["impressions"]=TRUE;
            break;
        }
		$query_order["code"]=FALSE;
        $query_group=array();
        $query_group["gwttrack.type"]=TRUE;
        $query_group["gwttrack.code"]=FALSE;
		$query=
	        "select \n" .
	        "gwttrack.type as 'type', \n" .
	        "gwttrack.code as 'code', \n" .
	        "gwttrack.comment as 'comment', \n" .
	        "case \n" .
            "when isnull(gwttrack.active) then 'Missing' \n" .
            "when gwttrack.active=1 then 'Active' \n" .
            "else 'Inactive' \n" .
            "end as 'status', \n" .
            "min(gwtlog.date) as 'min_date', \n" .
            "max(gwtlog.date) as 'max_date', \n" .
			"sum(gwtlog.clicks) as 'clicks', \n" .
			"round(((sum(gwtlog.clicks) / sum(gwtlog.impressions)) * 100), 2) as 'ctr', \n" .
			"sum(gwtlog.impressions) as 'impressions', \n" .
			"sum(gwtlog.impressions * gwtlog.position) as 'position_sum', \n" .
            "min(gwtlog.position) as 'min_position', \n" .
            "max(gwtlog.position) as 'max_position' \n" .
	        "from gwttrack \n" .
			"left join gwtlog on gwtlog.domain=gwttrack.domain and gwtlog.type=gwttrack.type and gwtlog.code=gwttrack.code \n" .
            $database->where($query_where) .
			$database->order($query_group,TRUE) .
			$database->order($query_order);
		$database->temp->query($query);
	    while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$item=new stdClass();
            $item->code=$database->temp->fetch['code'];
            $item->comment=$database->temp->fetch['comment'];
            $item->status=$database->temp->fetch['status'];
            $item->clicks=intval($database->temp->fetch['clicks']);
            $item->ctr=$database->temp->fetch['ctr'];
            $item->impressions=intval($database->temp->fetch['impressions']);
            $item->position_sum=round($database->temp->fetch['position_sum'],1);
            $item->min_position=round($database->temp->fetch['min_position'],1);
            $item->max_position=round($database->temp->fetch['max_position'],1);
            if ($item->impressions) $item->position=round($item->position_sum / $item->impressions,1);
            $item->min_date=fn_date($database->temp->fetch['min_date'],"mysql");
            $item->max_date=fn_date($database->temp->fetch['max_date'],"mysql");
	        $forms->report['history_totals'][ $database->temp->fetch['type'] ][ $database->temp->fetch['code'] ]=$item;
	    }
		if (!sizeof($forms->report['history_totals'])) $results[]="<p><b>Not records found</p>";
	}
    return $results;
}
function gwt_history_summary($type_code) {
global $forms, $database, $gwt, $tabs;
	if (!sizeof($forms->report['history_totals'][$type_code])) return;
	$results=array();
	$results[]="<table class='standard border'>";
    $results[]="<thead>";
    $results[]="<tr>";
    $results[]="<th rowspan=2>" . $gwt->type[$type_code] . "</th>\n";
    $results[]="<th rowspan=2>Impressions</th>\n";
    $results[]="<th rowspan=2>Clicks</th>\n";
    $results[]="<th rowspan=2>CTR</th>\n";
    $results[]="<th colspan=3>Position</th>\n";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<th>Average</th>";
    $results[]="<th>Best</th>";
    $results[]="<th>Worst</th>";
    $results[]="</tr>";
    $results[]="</thead>";
    $results[]="<tbody>";
    foreach ($forms->report['history_totals'][$type_code] as $item) {
		if (array_key_exists(strtolower($item->status),$database->registry->gwt->color)) {
          	$bg=$forms->background->{$database->registry->gwt->color[strtolower($item->status)]};
		  } else {
          	$bg=$forms->background->{$database->registry->gwt->color['default']};
        }
		$results[]="<tr $bg>";
        $results[]="<td>" . fn_href($item->code,"javascript:fn_action('history'," . fn_escape(base64_encode($type_code . "\t" . $item->code)) . ");") . "</td>\n";
        if (!$item->impressions) {
        	$results[]="<td colspan=8 class=center>No impressions</td>";
		  } else {
          	$results[]="<td class=right>" . number_format($item->impressions) . "</td>";
          	$results[]="<td class=right>" . number_format($item->clicks) . "</td>";
          	$results[]="<td class=right>" . number_format($item->ctr,2) . "%</td>";
          	$results[]="<td class=right>" . number_format($item->position,1) . "</td>";
          	$results[]="<td class=right>" . number_format($item->min_position,1) . "</td>";
          	$results[]="<td class=right>" . number_format($item->max_position,1) . "</td>";
		}
		$results[]="</tr>";
    }
    $results[]="</tbody>";
    $results[]="</table>";
    return $results;
}
function gwt_history_detail() {
global $forms, $database, $gwt, $tabs;
    if ( ($forms->report['action'] != "history") || (!$forms->report['action2']) ) return;
    list($type,$code)=preg_split("/\t/",base64_decode($forms->report['action2']));
    $query_where=array();
	$query_where[]=new query_where("gwtlog.domain","=",$database->registry->gwt->domain);
	$query_where[]=new query_where("gwtlog.type","=",$type);
	$query_where[]=new query_where("gwtlog.code","=",$code);
	if ($forms->report['history_from_date']) $query_where[]=new query_where("gwtlog.date",">=",$forms->report['history_from_date']);
	if ($forms->report['history_thru_date']) $query_where[]=new query_where("gwtlog.date","<=",$forms->report['history_thru_date']);
    $query=
    	"select * from gwtlog \n" .
        $database->where($query_where) .
        "order by date";
	$database->gwtlog->query($query);
    if (!$database->gwtlog->meta->rows) return;
    $tabs->landing_tab();
	$results=array();
	$results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='40%'>";
    $data=array();
    $data[]="Domain: <b>" . $database->registry->gwt->domain . "</b>";
    $data[]=$gwt->type[$type] . ": <b>" . $code  . "</b>";
	$data[]="From: <b>" . date("l F j, Y",strtotime($forms->report['history_totals'][$type][$code]->min_date)) . "</b>";
	$data[]="Thru: <b>" . date("l F j, Y",strtotime($forms->report['history_totals'][$type][$code]->max_date)) . "</b>";
	$data[]="Status: <b>" . $forms->report['history_totals'][$type][$code]->status . "</b>";
    if ($forms->report['history_totals'][$type][$code]->comment) $data[]="Comment: <b>" . $forms->report['history_totals'][$type][$code]->comment . "</b>";
	$data[]="# Records: <b>" . number_format($database->gwtlog->meta->rows) . "</b>";
    $results[]=implode("<br>\n",$data);
    $results[]="</td>";
	$results[]="<td width='60%'>";
	$results[]="<table class='standard border'>";
    $results[]="<thead>";
    $results[]="<tr>";
    $results[]="<th>Date</th>";
    $results[]="<th>Impressions</th>";
    $results[]="<th>Clicks</th>";
    $results[]="<th>CTR</th>";
    $results[]="<th>Position</th>";
    $results[]="</tr>";
    $results[]="</thead>";
    $results[]="<tbody>";
    while ($database->gwtlog->fetch = $database->gwtlog->fetch_array() ) {
		$database->gwtlog->fetch();
        $forms->report['history_detail'][$database->gwtlog->data->date]=$database->gwtlog->data;
	    $results[]="<tr>";
	    $results[]="<td class=center>" . fn_date($database->gwtlog->data->date,"ymd") . "</td>";
	    $results[]="<td class=right>" . number_format($database->gwtlog->data->impressions) . "</td>";
	    $results[]="<td class=right>" . number_format($database->gwtlog->data->clicks) . "</td>";
	    $results[]="<td class=right>" . number_format($database->gwtlog->data->ctr,2) . "%</td>";
	    $results[]="<td class=right>" . number_format($database->gwtlog->data->position,1) . "</td>";
	    $results[]="</tr>";
    }
    $results[]="</tbody>";
    if ($database->gwtlog->meta->rows > 1) {
    	$results[]="<tfoot>";
	    $results[]="<tr>";
        $results[]="<td class=center><b>Totals</b></td>";
        $results[]="<td class=right><b>" . number_format($forms->report['history_totals'][$type][$code]->impressions) . "</b></td>";
        $results[]="<td class=right><b>" . number_format($forms->report['history_totals'][$type][$code]->clicks) . "</b></td>";
        $results[]="<td class=right><b>" . number_format($forms->report['history_totals'][$type][$code]->ctr,2) . "%</b></td>";
        $results[]="<td class=right><b>" . number_format($forms->report['history_totals'][$type][$code]->position,1) . "</b></td>";
	    $results[]="</tr>";
    	$results[]="</tfoot>";
	}
    $results[]="</table>";
    $results[]="</td>";
    $results[]="</tr>";
    $results[]="</table>";
    return $results;
}
function gwt_history_graph() {
global $forms, $database, $gwt, $tabs;
    list($forms->report['history_type'],$forms->report['history_code'])=preg_split("/\t/",base64_decode($forms->report['action2']));
	if (sizeof($forms->report['history_detail']) < 2) return;
    $content=$forms->report['history_totals'][ $forms->report['history_type'] ][ $forms->report['history_code'] ];
    $label=array();
    $impressions=array();
    $clicks=array();
    $position=array();
    $ctr=array();
    $date=$content->min_date;
	$eof=FALSE;
    while ($date <= $content->max_date) {
		$label[]=fn_date($date,"ymd");
        $impressions[]=floatval($forms->report['history_detail'][$date]->impressions);
        $clicks[]=floatval($forms->report['history_detail'][$date]->clicks);
        $position[]=floatval($forms->report['history_detail'][$date]->position);
        $ctr[]=floatval($forms->report['history_detail'][$date]->ctr);
        $date=date("Y/m/d",strtotime($date . " +1 day"));
    }
    $chart=new scs_chart_class("impressions",$label);
    $chart->load("Impressions",$impressions);
	$tabs->item("Graph: Impressions",$chart->output());
    $chart=new scs_chart_class("clicks",$label);
    $chart->load("Clicks",$clicks);
	$tabs->item("Graph: Clicks",$chart->output());
    $chart=new scs_chart_class("ctr",$label);
    $chart->load("CTR",$ctr);
	$tabs->item("Graph: CTR",$chart->output());
    $chart=new scs_chart_class("position",$label);
    $chart->load("Position",$position);
	$tabs->item("Graph: Position",$chart->output());

/*



/*
    $results=array();
$id="xxx";
	$results=array();
	$results[]="<canvas id='{$id}_canvas' width=1500 height=600></canvas>";
	$results[]="<script language=javascript>";
	$results[]="var {$id}_data = {";
	$results[]="labels : ['" . implode("', '",$label) . "'],";
	$results[]="datasets : [{";
	$results[]="label: 'name 1',";
	$results[]="fillColor : 'rgba(220,220,220,0.2)',";
    $results[]="";
	$results[]="strokeColor : 'rgba(220,220,220,1)',";
	$results[]="pointColor : 'rgba(220,220,220,1)',";
	$results[]="pointStrokeColor : '#fff',";
	$results[]="pointHighlightFill : '#fff',";
	$results[]="pointHighlightStroke : 'rgba(220,220,220,1)',";
	$results[]="data : [" . implode(",",$ctr) . "]";
    $results[]="},";
    $results[]="{";
    $results[]="label: 'name 2',";
    $results[]="fillColor : 'rgba(220,220,220,0.2)',";
    $results[]="strokeColor : 'rgba(220,110,110,1)',";
    $results[]="data : [" . implode(",", $ctr) . "]";
    $results[]="}]";
    $results[]="}";
    $results[]="var ctx = {$id}_canvas.getContext('2d');";
    $results[]="window.myLine = new Chart(ctx).Line({$id}_data, {responsive: false, bezierCurve: false, pointDot: false, showTooltips: false, scaleBeginAtZero: true, scaleShowVerticalLines: false});";
    $results[]="</script>";


	return $results;
*/
}
function gwt_live() {
global $forms, $database, $gwt, $tabs;
    if ($forms->report['action'] == "live") $tabs->landing_tab();
	$results=array();
    $results[]="<p class='standard'>Retrieve live statistics directly from the Google API</p>";
	$results[]="<table class='standard noborder'>";
	$results[]="<tr>";
	$results[]="<td width='30%'>Domain</td>";
	$results[]="<td width='70%'><b>" . $database->registry->gwt->domain . "</b></td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>From Date</td>";
	$results[]="<td>" . $forms->text("live_from_date",$forms->report['live_from_date'],10,10,"date:ymd",array("onchange"=>"document.scs_form.live_thru_date.value=this.value;")) . "</td>";
	$results[]="</tr>";
	$results[]="<tr>";
	$results[]="<td>Thru Date</td>";
	$results[]="<td>" . $forms->text("live_thru_date",$forms->report['live_thru_date'],10,10,"date:ymd") . " (Results delayed by 2 days)</td>";
	$results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->button("Build",array("onclick"=>"fn_action('live','build');")) . "</p>";
    return $results;
}
function gwt_live_heading($rows=0) {
global $forms, $database, $gwt, $tabs;
    $results=array();
    $results[]="Domain: <b>" . $database->registry->gwt->domain . "</b>";
    $results[]="From Date: <b>" . date("l F j, Y",strtotime($forms->report['live_from_date'])) . "</b>";
    $results[]="Thru Date: <b>" . date("l F j, Y",strtotime($forms->report['live_thru_date'])) . "</b>";
    if ($rows) $results[]="<br>Records found: <b>" . number_format($rows) . "</b>";
    return $results;
}
function gwt_live_summary() {
global $forms, $database, $gwt, $tabs;
	$results=array();
	switch (TRUE) {
	  case ($forms->report['action'] != "live"):
      case (!$forms->report['action2']);
      case ( ($forms->report['action2'] == "build") && (sizeof($forms->error)) ):
		return;
	}
    $query=
	    "select \n" .
        "gwttemp.type as type, \n" .
	    "case \n" .
        " when isnull(gwttrack.active) then 2 \n" .
        " when gwttrack.active=1 then 0 \n" .
        " else 1 \n" .
        "end as sort_key, \n" .
        "count(*) as count \n" .
        "from gwttemp \n" .
        "left join gwttrack on gwttrack.domain=" .fn_escape($database->registry->gwt->domain) . " and gwttrack.type=gwttemp.type and gwttrack.code=gwttemp.code \n" .
        "group by gwttemp.type,sort_key \n" .
        "order by gwttemp.type,sort_key";
	$database->temp->query($query);
    $live_count=array();
    while ($database->temp->fetch = $database->temp->fetch_array() ) {
		if (!array_key_exists($database->temp->fetch['type'],$live_count)) $live_count[ $database->temp->fetch['type'] ]=array();
		$live_count[ $database->temp->fetch['type'] ][ $database->temp->fetch['sort_key'] ]=$database->temp->fetch['count'];
    }
	if (!sizeof($live_count)) return "No records found";
	$tabs->landing_tab();
	foreach ($gwt->type as $type_code => $type_name) {
		switch ($forms->report['live_track']) {
		  case 3:
			$forms->report['live_totals'][$type_code] = $live_count[$type_code][0];
            break;
		  case 2:
			$forms->report['live_totals'][$type_code] = $live_count[$type_code][0] + $live_count[$type_code][1];
            break;
		  default:
			$forms->report['live_totals'][$type_code] = $live_count[$type_code][0] + $live_count[$type_code][1] + $live_count[$type_code][2];
		}
	}
    $results[]="<table class='standard border'>";
    $results[]="<tr>";
    $results[]="<th>Parameters</th>";
    $results[]="<th>Type</th>";
    $results[]="<th>Tracked</th>";
    $results[]="<th>Inactive</th>";
    $results[]="<th>Total</th>";
    $results[]="</tr>";

	$count=0;
    foreach ($live_count as $type_code => $item) {
		$results[]="<tr>";
        if (!$count) $results[]="<td rowspan=" . sizeof($live_count) . " width='50%'>" . implode("<br>",gwt_live_heading()) . "</td>";
        $results[]="<td>" . $gwt->type[$type_code] . "</td>";
        $results[]="<td class=right>" . number_format($item[0]) . "</td>";
        $results[]="<td class=right>" . number_format($item[1]) . "</td>";
        $results[]="<td class=right>" . number_format(array_sum($item)) . "</td>";
		$results[]="</tr>";
        $count++;
    }
    $results[]="</table>";
	$results[]="<table class='standard noborder'>";
    $results[]=$forms->caption();
    $results[]="<tr>";
    $results[]="<td width='30%'>Tracked Items</td>";
    $results[]="<td width='70%'>" . $forms->select("live_track",array("1"=>"All","2"=>"Only Tracked","3"=>"Only Active/Tracked"),$forms->report['live_track'],FALSE) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Order</td>";
    $results[]="<td>" . $forms->select("live_order",$gwt->order(FALSE),$forms->report['live_order'],FALSE) . "</td>";
    $results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->button("View",array("onclick"=>"fn_action('live','detail');")) . "</p>";
    return $results;
}
function gwt_live_detail($type_code) {
global $forms, $database, $gwt, $tabs;
	switch (TRUE) {
	  case ($forms->report['action'] != "live"):
      case (!$forms->report['action2']);
      case ($forms->report['action2'] != "detail"):
		return;
	}
    $results=array();
    $query_order=array();
    switch ($forms->report['live_order']) {
	  case "impressions":
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "change_impressions":
		$query_order["gwttemp.change_impressions"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "clicks":
		$query_order["gwttemp.clicks"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "change_clicks":
		$query_order["gwttemp.change_clicks"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "ctr":
		$query_order["gwttemp.ctr"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "position":
		$query_order["gwttemp.position"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
	  case "change_position":
		$query_order["gwttemp.change_position"]=TRUE;
		$query_order["gwttemp.impressions"]=TRUE;
        break;
    }
    $query_order["gwttemp.code"]=FALSE;
    $query_where=array();
    $query_where[]=new query_where("gwttemp.type","=",$type_code);
    if (in_array($forms->report['live_track'],array(2,3))) $query_where[]=new query_where("not isnull(gwttrack.id)","","");
    if (in_array($forms->report['live_track'],array(3)))$query_where[]=new query_where("gwttrack.active","=","1");
    $query=
    	"select \n" .
        "gwttrack.id as 'track_id', \n" .
        "gwttrack.active as 'track_active', \n" .
        "gwttemp.* from gwttemp \n" .
        "left join gwttrack on gwttrack.domain=" . fn_escape($database->registry->gwt->domain) . " and gwttrack.type=gwttemp.type and gwttrack.code=gwttemp.code \n" .
        $database->where($query_where) .
        $database->order($query_order);
	$database->temp->query($query);
    if (!$database->temp->meta->rows) return;
	$total_count=0;
	$total_impressions=0;
	$total_clicks=0;
    $total_position=0;
    $results[]="<table class='standard noborder'>";
    $results[]="<tr>";
    $results[]="<td width='50%'><p class=standard>" . implode("<br>\n",gwt_live_heading($database->temp->meta->rows)) . "</p></td>";
    $results[]="<td width='50%'>";
    $results[]="<p class='standard'>GWT results contain following fields that are not always accurately populated:</p>";
    $results[]="<ul class='standard'>";
    $results[]="<li>Impressions change</li>";
    $results[]="<li>Clicks change</li>";
    $results[]="<li>CTR change</li>";
    $results[]="<li>Position change</li>";
    $results[]="</ul>";
    $results[]="</td>";
    $results[]="</tr>";
    $results[]="</table>";

    $results[]="<table class='standard border'>";
    $results[]=$forms->caption();
    $results[]="<thead>";
    $results[]="<tr>";
    $results[]="<th rowspan=2>Track?</th>";
    $results[]="<th rowspan=2>" . $gwt->type[$type_code] . "</th>";
    $results[]="<th colspan=2>Impressions</th>";
    $results[]="<th colspan=2>Clicks</th>";
    $results[]="<th colspan=2>CTR</th>";
    $results[]="<th colspan=2>Position</th>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<th>Count</th>";
    $results[]="<th>Change</th>";
    $results[]="<th>Count</th>";
    $results[]="<th>Change</th>";
    $results[]="<th>Rate</th>";
    $results[]="<th>Change</th>";
    $results[]="<th>Average</th>";
    $results[]="<th>Change</th>";
    $results[]="</tr>";
    $results[]="</thead>";
    $results[]="<tbody>";
    while ($database->temp->fetch = $database->temp->fetch_array() ) {
		$total_count++;
        $total_impressions += $database->temp->fetch['impressions'];
        $total_clicks += $database->temp->fetch['clicks'];
        $total_position += ($database->temp->fetch['impressions'] * $database->temp->fetch['position']);
        switch (TRUE) {
		  case ( ($database->temp->fetch['track_id']) && ($database->temp->fetch['track_active']) ):
          	$bg=$forms->background->{$database->registry->gwt->color['active']};
            break;
		  case ($database->temp->fetch['track_id']):
          	$bg=$forms->background->{$database->registry->gwt->color['inactive']};
            break;
		  default:
          	$bg="";
        }
        $id=base64_encode($type_code . "\t" . $database->temp->fetch['code']);
		$results[]="<tr id='r_{$id}' $bg>";
        $results[]="<td class=center>" .  $forms->radio("live_gwttrack",$database->temp->fetch['code'],"",array("onclick"=>"gwttrack_update(this);","id"=>$id)) . "</td>";
        $results[]="<td>" . $database->temp->fetch['code'] . "</td>";
        $results[]="<td class=right>" .  number_format($database->temp->fetch['impressions']) . "</td>";
		$results[]="<td class=right>" .  number_format(($database->temp->fetch['change_impressions'] * 100),2) . "%</td>";
        $results[]="<td class=right>" .  number_format($database->temp->fetch['clicks']) . "</td>";
		$results[]="<td class=right>" .  number_format(($database->temp->fetch['change_clicks'] * 100),2) . "%</td>";
        $results[]="<td class=right>" .  number_format(($database->temp->fetch['ctr'] * 100),2) . "%</td>";
		$results[]="<td class=right>" .  number_format($database->temp->fetch['change_ctr'],2) . "</td>";
        $results[]="<td class=right>" .  number_format($database->temp->fetch['position'],1) . "</td>";
		$results[]="<td class=right>" .  number_format($database->temp->fetch['change_position'],1) . "</td>";
		$results[]="</tr>";
    }
    $results[]="</tbody>";
    if ($total_count > 1) {
		$results[]="<tfoot>";
    	$results[]="<tr>";
        $results[]="<td colspan=2><b>" . $gwt->type[$type_code] . " Total (" . number_format($total_count) . ")</b></td>";
        $results[]="<td class=right><b>" . number_format($total_impressions) . "</b></td>";
		$results[]="<td class=center><b>N/A</b></td>";
        $results[]="<td class=right><b>" . number_format($total_clicks) . "</b></td>";
		$results[]="<td class=center><b>N/A</b></td>";
        $results[]="<td class=right><b>" . number_format((($total_clicks / $total_impressions) * 100),2) . "%</b></td>";
		$results[]="<td class=center><b>N/A</b></td>";
        $results[]="<td class=right><b>" . number_format(($total_position / $total_impressions),1) . "</b></td>";
		$results[]="<td class=center><b>N/A</b></td>";
        $results[]="</tr>";
		$results[]="</tfoot>";
    }
	$results[]="</table>";
    return $results;
}
function gwt_update() {
global $forms, $database, $gwt, $tabs;
    if ($forms->report['action'] == "update") $tabs->landing_tab();
	$query=
		"select \n" .
        "type as 'type', \n" .
        "min(date) as 'min_date', \n" .
        "max(date) as 'max_date', \n" .
        "count(*) as 'count'" .
        "from gwtlog \n" .
        "where domain=" . fn_escape($database->registry->gwt->domain) . "\n" .
        "group by type \n" .
        "order by type";
	$database->temp->query($query);
	$totals=array();
    foreach ($gwt->type as $type_code => $type_name) {
		$totals[$type_code]=new stdClass();
	}

    while ($database->temp->fetch = $database->temp->fetch_array() ) {
		$items=new stdClass();
        $items->min_date=fn_date($database->temp->fetch['min_date'],"mysql");
        $items->max_date=fn_date($database->temp->fetch['max_date'],"mysql");
        $items->count=$database->temp->fetch['count'];
		$totals[ $database->temp->fetch['type'] ]=$items;
    }
    $results=array();
    $results[]="<table class='standard border'>";
    $results[]="<tr>";
    $results[]="<th>Domain</th>";
    $results[]="<th>Type</th>";
    $results[]="<th>Count</th>";
    $results[]="<th>First Date</th>";
    $results[]="<th>Last Date</th>";
    $results[]="</tr>";
    $first_pass=TRUE;
    foreach ($gwt->type as $type_code => $type_name) {
		$item=$totals[$type_code];
        $results[]="<tr>";
        if ($first_pass) {
        	$first_pass=FALSE;
        	$results[]="<td rowspan=" . sizeof($gwt->type) . ">" . $database->registry->gwt->domain . "</td>";
		}
        $results[]="<td>$type_name</td>";
        if (!$item->count) {
			$results[]="<td colspan=3>No records found</td>";
		  } else {
			$results[]="<td class=right>" . number_format($item->count) . "</td>";
			$results[]="<td>" . date("l F j, Y",strtotime($item->min_date)) . "</td>";
			$results[]="<td>" . date("l F j, Y",strtotime($item->max_date)) . "</td>";
        }
        $results[]="</tr>";
    }
    $results[]="</table>";
	$results[]="<table class='standard noborder'>";
    $results[]=$forms->caption();
    $results[]="<tr>";
    $results[]="<td width='30%'>From Date</td>";
    $results[]="<td width='70%'>" . $forms->text("update_from_date",$forms->report['update_from_date'],10,10,"date:ymd",array("onchange"=>"document.scs_form.update_thru_date.value=this.value;")) . "</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Thru Date</td>";
    $results[]="<td>" . $forms->text("update_thru_date",$forms->report['update_thru_date'],10,10,"date:ymd") . " (Results delayed by 2 days)</td>";
    $results[]="</tr>";
    $results[]="<tr>";
    $results[]="<td>Update Timeout</td>";
    $results[]="<td>" . $forms->select("update_timeout",array(30,60,90,120,150,180), $forms->report['update_timeout'],FALSE) . " Seconds</td>";
    $results[]="</tr>";
	$results[]="</table>";
	$results[]="<p>" . $forms->button("Update Database",array("onclick"=>"fn_action('update','update');")) . "</p>";
    return $results;
}
class gwt_report_class {
	var $live_from_date;
	var $live_thru_date;
    var $live_track=2;

	var $update_from_date;
	var $update_thru_date;
    var $update_timeout=30;

	var $history_from_date;
	var $history_thru_date;
    var $history_active=TRUE;
    function __construct() {
    }
    function initial() {
    global $gwt;
		$this->live_from_date=date("Y/m/d",strtotime("-2 days"));
		$this->live_thru_date=date("Y/m/d",strtotime("-2 days"));
		$this->update_from_date=date("Y/m/d",strtotime("-2 days"));
		$this->update_thru_date=date("Y/m/d",strtotime("-2 days"));
	}
}
?>