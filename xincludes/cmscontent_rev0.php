<?php
if (!$database->registry->cms->frame) fn_url_redirect($_SERVER['HTTP_REFERER']);
require_once "classes/cmscontent.php";
require_once "classes/cmspage.php";
$forms->title("CMS Content (Revised 06/22/2020)");
$html=new preg_replace_class(TRUE);
$forms->report['action']=$_REQUEST['action'];
$forms->report['record_id']=$_REQUEST['record_id'];
$forms->report['report_page']=$_POST['report_page'];
$forms->report['report_current']=$_POST['report_current'];
if ($forms->report['record_id']) {
	$database->cmscontent->read($forms->report['record_id']);
  } else {
	$database->cmscontent->data=new cmscontent_data_class();
}
$forms->report['report_date']=fn_date($_POST['report_date'],"mdy");
switch ($forms->report['action']) {
  case "":
	$forms->report['report_date']=date("Y/m/d");
    $forms->report['report_current']=1;
	break;
  case "cancel":
    $forms->message[]="Request cancelled";
    break;
  case "delete":
	$database->cmscontent->delete($forms->report['record_id']);
    $forms->message[]=("CMS Page #" . $forms->report['record_id'] . " deleted");
    break;
  case "maintain":
	if (!$forms->report['record_id']) {
		$database->cmscontent->data->page=$forms->report['report_page'];
	}
    break;
  case "update":
	$database->cmscontent->data->page=$_POST['page'];
    if (!$database->cmscontent->data->page) $forms->error('page');
	$database->cmscontent->data->frame=trim(strtolower($_POST['frame']));
    if (!$database->cmscontent->data->frame) $forms->error('frame');
	$database->cmscontent->data->from_date=fn_date($_POST['from_date'],"mdy");
	$database->cmscontent->data->thru_date=fn_date($_POST['thru_date'],"mdy");
    if (($database->cmscontent->data->thru_date) && ($database->cmscontent->data->from_date > $database->cmscontent->data->thru_date)) $forms->error('thru_date');
	$database->cmscontent->data->title=trim($_POST['title']);
	$database->cmscontent->data->html=$html->filter($_POST['html']);
    if (!$database->cmscontent->data->html) $forms->error('html');
    $css_array=explode("\n",trim($_POST['css']));
	$database->cmscontent->data->css=array();
    foreach ($css_array as $css) {
		$css=trim($css);
        switch (TRUE) {
          case (!$css):
          case (in_array($css,$database->cmscontent->data->css)):
          	break;
		  case (preg_match("/.css$/i",$css)):
           $database->cmscontent->data->css[]=$css;
           break;
          default:
			$forms->error("css");
		}
    }
	$database->cmscontent->data->status=$_POST['status'];
    if (sizeof($forms->error)) break;
	$database->cmscontent->update($forms->report['record_id']);
    if ($database->cmscontent->meta->error) {
    	$forms->error("page","","Page/frame/From Date must be unique");
	  } else {
		$forms->message[]="Record maintained";
	}
}

$menu->head();
$forms->message();
?>
<script language=javascript>
function fn_action(obj,action,id) {
	if (action=='delete') {
	    if (!confirm("Delete " + id + "?")) {
        	obj.checked=false;
            return;
	    }
    }
    document.scs_form.action.value=action;
    document.scs_form.record_id.value=id;
    document.scs_form.submit();
}
</script>
<?php
print $forms->open(array("data"=>TRUE));
print $forms->hidden("action");
print $forms->hidden("record_id");
switch (TRUE) {
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
  case ($forms->report['action'] == "maintain"):
	print $forms->hidden("report_page",$forms->report['report_page']);
	print $forms->hidden("report_date",$forms->report['report_date'],"date:ymd");
	print $forms->hidden("report_current",$forms->report['report_current']);
    print "<table class='standard noborder'>\n";
    if ($forms->report['record_id']) {
	    print "<tr>\n";
	    print "<td>ID</td>\n";
	    print "<td><b>" . $database->cmscontent->data->id . "</b></td>\n";
	    print "</tr>\n";
	}
    if ($database->cmscontent->data->revised) {
	    print "<tr>\n";
	    print "<td>Revised</td>\n";
	    print "<td>" . date("l F j, Y h:i A",$database->cmscontent->data->revised) . "</td>\n";
	    print "</tr>\n";
    }
    print "<tr>\n";
    print "<td width=20%>Page</td>\n";
	print "<td width=80%>" . $database->cmspage->select($database->cmscontent->data->page,"page") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Frame</td>\n";
    print "<td>";
    if ($database->registry->cms->frame == "numeric") {
		print $forms->select_number("frame",$database->cmscontent->data->frame,10,99);
	  } else {
		print $forms->text("frame",$database->cmscontent->data->frame,40,40);
	}
    print "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Date(s)</td>\n";
    print
    	"<td>" . $forms->text("from_date",$database->cmscontent->data->from_date,10,10,"date") . " - " .
    	$forms->text("thru_date",$database->cmscontent->data->thru_date,10,10,"date") . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Status</td>\n";
    print "<td>" . $forms->select("status",$database->cmscontent->constant->status,$database->cmscontent->data->status,FALSE) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Title</td>\n";
    print "<td>" . $forms->text("title",$database->cmscontent->data->title,40) . "</td>\n";
    print "</tr>\n";
    print "<tr>\n";
    print "<td>Filters</td>\n";
    print "<td>" . implode("<br>",$html->filter_html()) . "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<td>HTML Source</td>\n";
    print "<td>" . $forms->textarea("html",$database->cmscontent->data->html,40,120) . "</td>\n";
    print "</tr>\n";
	print "<tr>\n";
    print "<td>Additional .css (Optional)</td>\n";
    print "<td>" . $forms->textarea("css",$database->cmscontent->data->css,3,120) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print
    	"<p class=center>" . $forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($forms->report['record_id']) . ");")) . " " .
        $forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','0');")) . "</p>\n";
 	break;
  default:
    print "<table class='standard noborder'>\n";
    print "<tr>\n";
    print "<th>Page</th>\n";
    print "<th>Effective Date</th>\n";
    print "<th>Only Current?</th>\n";
    print "<th>&nbsp;</th>\n";
    print "</tr>\n";
    print "<tr>\n";
	print "<td class=center>" . $database->cmspage->select($forms->report['report_page'],"report_page") . "</td>\n";
    print "<td class=center>" . $forms->text("report_date",$forms->report['report_date'],10,10,"date:ymd",array("class"=>"datepicker")) . "</td>\n";
    print "<td class=center>" . $forms->checkbox("report_current",1,$forms->report['report_current']) . "</td>\n";
    print "<td class=center>" . $forms->button("Go!",array("onclick"=>"fn_action(this,'list','0');")) . "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    print "<table class='standard border'>\n";
	print $forms->caption();
    print "<tr>\n";
	print "<th>ID</th>\n";
	if (!$forms->report['report_page']) print "<th>Page</th>\n";
	print "<th>Frame</th>\n";
	print "<th>Date(s)</th>\n";
	print "<th>Title</th>\n";
	print "<th>Revised</th>\n";
	print "<th>Del?</th>\n";
    print "</tr>\n";
    print "<tr><td colspan=7>" . fn_href("Add new","javascript:fn_action(this,'maintain','0');") . "</td></tr>\n";
    if ($forms->report['action']=="list") {
		$query_where=array();
        if ($forms->report['report_page']) $query_where['page']=new query_where("cmscontent.page","=",$forms->report['report_page']);
        if ($forms->report['report_date']) $query_where['from_date']=new query_where("cmscontent.from_date","<=",$forms->report['report_date']);
        if ($forms->report['report_current']) {
        	$query_where['thru_date'][]=new query_where("cmscontent.thru_date",">=",$forms->report['report_date']);
        	$query_where['thru_date'][]=new query_where("cmscontent.thru_date","=","0000/00/00","or");
        	$query_where['status']=new query_where("cmscontent.status","=","Active");
		}
		$query =
        	"select  \n" .
            "cmspage.name as 'cmspage_name', \n" .
            "cmscontent.* from cmscontent \n" .
            "left join cmspage on cmspage.page=cmscontent.page \n" .
			$database->where($query_where) .
            "order by cmscontent.page, cmscontent.frame, cmscontent.from_date desc";
		$database->cmscontent->query($query);
		$results=array();
	    while ($database->cmscontent->fetch = $database->cmscontent->fetch_array()) {
	        $database->cmscontent->fetch();
	        $database->cmscontent->data->cmspage_name=$database->cmscontent->fetch['cmspage_name'];
			$key=$database->cmscontent->data->page . "\t" . $database->cmscontent->data->frame;
            switch (TRUE) {
              case (!$forms->report['report_current']):
              case (!isset($results[$key][0])):
				$results[$key][]=$database->cmscontent->data;
			}
		}
	    $database->cmscontent->free_result();
		foreach ($results as $pages) {
			foreach ($pages as $item) {
            	switch ($item->status) {
                  case "Inactive":
                  	$bg=$forms->background->yellow;
                    break;
                  case "Pending":
                  	$bg=$forms->background->silver;
                    break;
                  default:
                  	$bg=$forms->background->darkseagreen;
				}
	            print "<tr $bg>\n";
	            print "<td class=center>" . fn_href($item->id,"javascript:fn_action(this,'maintain'," . fn_escape($item->id) . ");") . "</td>\n";
				if (!$forms->report['report_page']) print "<td>" . $item->cmspage_name . "</td>\n";
	            print "<td>" . $item->frame . "</td>\n";
	            print "<td class=center>";
				switch (TRUE) {
                  case ((!$item->from_date) && (!$item->thru_date)):
                  	print "All Dates";
                    break;
                  case (!$item->from_date):
                  	print "Thru " . fn_date($item->thru_date,"ymd");
                    break;
                   case (!$item->thru_date):
                  	print fn_date($item->from_date,"ymd") . "-Present";
                    break;
				  default:
                  	print fn_date($item->from_date,"ymd") . "-" . fn_date($item->thru_date,"ymd");
                }
                print "</td>\n";
	            print "<td>" . $item->title . "</td>\n";
	            print "<td class=center>" . date("m/d/Y h:i A",$item->revised) . "</td>\n";
	            print "<td class=center>";
            	switch ($item->status) {
                  case "Inactive":
                  case "Pending":
					print $forms->radio("delete",1,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($item->id) . ");"));
                    break;
                  default:
                  	print "&nbsp;";
				}
				print "</td>\n";
	            print "</tr>\n";
			}
		}
    }
    print "</table>\n";
}
print $forms->close();
$menu->copyright();
?>