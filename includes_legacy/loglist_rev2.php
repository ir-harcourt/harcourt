<?php
require_once "map_rev3.php";
$loglist=new loglist_class();
$loglist->process();
class loglist_class {
    var $field=array();
    var $query;
    var $output_file;
	function scs_table_version() {
		$results=array();
        $results['07/19/2021']="Standardize query";
        $results['06/19/2019']="Improve export";
        $results['05/08/2018']="Improve query";
        $results['01/19/2018']="Initial release";
        return $results;
    }
    function __construct() {
    }
    function process() {
    global $database,$forms;
	    $forms->title("SCS Access Log");
	    $forms->message[]="Revised " . key( $this->scs_table_version() );
	    $forms->report['action']=$_POST['action'];
	    $forms->report['user_id']=$_POST['user_id'];
	    $forms->report['file_type']=$_POST['file_type'];
	    $forms->report['page']=intval($_POST['page']);
	    $forms->report['item']=intval($_POST['item']);
	    $forms->report['timestamp']=intval($_POST['timestamp']);
	    switch (TRUE) {
	      case (!$forms->report['action']):
	        $forms->report['from_date']=date("Y/m/d",strtotime("-30 days"));
	        $forms->report['thru_date']=date("Y/m/d",strtotime("now"));
	        $forms->report['descending']="desc";
	        break;
	      default:
	        $forms->report['type_subtype']=$_POST['type_subtype'];
	        list($forms->report['type'],$forms->report['subtype'])=preg_split("/\t/",base64_decode($_POST['type_subtype']));
	        $forms->report['from_date']=fn_date($_POST['from_date'],"mdy");

	        if (!$forms->date_ok) $forms->error('from_date');
	        $forms->report['thru_date']=fn_date($_POST['thru_date'],"mdy");
	        switch (TRUE) {
	          case (!$forms->date_ok):
	          case (($forms->report['thru_date']) && ($forms->report['from_date'] > $forms->report['thru_date'])):
	            $forms->error('thru_date');
	        }
	        $forms->report['file_type']=$_POST['file_type'];
	        $forms->report['descending']=$_POST['descending'];
			switch (TRUE) {
              case (!$forms->report['descending']):
              case (!$forms->report['timestamp']):
              case ( (!$forms->report['page']) && (!$forms->report['item']) ):
				$forms->report['timestamp']=strtotime("now");
			}
            if (sizeof($forms->error)) break;
			$this->map=new map_class("log",$forms->report);
			$this->query=$database->log->export_query();
		}
        switch (TRUE) {
          case ((!sizeof($forms->error)) && ($forms->report['file_type'])):
	        $this->map->export();
	        die();
          default:
			$this->html();
		}
    }

    function html() {
	global $database, $forms, $menu;
	    $menu->head();
	    $forms->message();
	    print $forms->open();
	    print $forms->hidden("action");
	    print $forms->hidden("page");
	    print $forms->hidden("item");
	    print $forms->hidden("timestamp",$forms->report['timestamp']);
	    switch (TRUE) {
	      case ($forms->report['action'] == "item"):
	        print $forms->hidden("file_type",$forms->report['file_type']);
	        print $forms->hidden("user_id",$forms->report['user_id']);
	        print $forms->hidden("type_subtype",$forms->report['type_subtype']);
	        print $forms->hidden("from_date",$forms->report['from_date'],"date:ymd");
	        print $forms->hidden("thru_date",$forms->report['thru_date'],"date:ymd");
	        print $forms->hidden("descending",$forms->report['descending']);

	        $this->query[]=$database->item_limit($forms->report['item']);
	        $database->log->query($this->query,TRUE);
	        $database->log->fetch(TRUE);
	        $database->log->free_result();
	        $database->user->read($database->log->data->user_id);
	        print "<table class='standard noborder'>";
	        print "<caption>&nbsp;</caption>";
	        print "<tr>";
	        print "<td width='30%'>Date/Time</td>";
	        print "<td width='70%'>" . date("l F j, Y h:i A",$database->log->data->date_time) . "</td>";
	        print "</tr>";
	        switch (TRUE) {
	          case (!$database->log->data->user_id):
	            print "<tr>";
	            print "<td>User</td>";
	            print "<td>Visitor</td>";
	            print "</tr>";
	            break;
	          case (!$database->user->meta->rows):
	            print "<tr>";
	            print "<td>User</td>";
	            print "<td>Not on file</td>";
	            print "</tr>";
	            break;
	          default:
	            print address_table("user",$database->user->data);
	            print "<td>Email</td>";
	            print "<td>" . $database->user->data->email . "</td>";
	            print "</tr>";
	            print "<tr>";
	        }
	        print "<tr><td colspan=2>&nbsp;</td></tr>";
	        print "<tr>";
	        print "<td>Record type</td>";
	        print "<td>" . $database->log->data->type;
	        if ($database->log->data->subtype) print " (" . $database->log->data->subtype . ")";
	        print "</td>";
	        print "</tr>";
	        if ($database->log->data->sku) {
	            print "<tr>";
	            print "<td>SKU</td>";
	            print "<td>" . $database->log->data->sku . "</td>";
	            print "<tr>";
	        }
	        if (sizeof($database->log->data->comment)) {
	            print "<tr>";
	            print "<td>Comment</td>";
	            print "<td>" . implode("<br>",$database->log->data->comment) . "</td>";
	            print "<tr>";
	        }
	        print "<tr><td colspan=2>&nbsp;</td></tr>";
	        print "<tr>";
	        print "<td>IP address</td>";
            if ($database->log->data->ip_address) {
            	$text=array(long2ip($database->log->data->ip_address),"(" . gethostbyaddr(long2ip($database->log->data->ip_address)) . ")");
			  } else {
              	$text=array("console");
            }
	        print "<td>" . implode(" ",$text) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Agent</td>";
	        print "<td>" . $database->log->data->agent;
            if ( (ini_get("browscap")) && ($database->log->data->agent) ) {
				$agent=get_browser($database->log->data->agent);
				print "<ul>";
                foreach ($agent as $key => $value) {
					if ( ($value) && (!preg_match("/^browser_name/i",$key)) ) {
						print "<li>$key: $value</li>";
                    }
                }
				print "</ul>";
			}
            print "</td>";
	        print "</tr>";
	        print "</table>";
	        $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('item',0,%item%);");
	        $page_break->return_url("Return","fn_page_item('page'," . (floor($forms->report['item'] / $database->query_limit) + 1) . ",0);");
	        print $page_break->item($forms->report['item']);
	        break;
	      default:
	        print "<table class='standard noborder'>";
	        print "<caption>&nbsp;</caption>";
	        print "<tr>";
	        print "<td>User ID</td>";
			if ($forms->report['user_id']) {
            	$database->user->read($forms->report['user_id']);
                $text=$database->user->data->name;
			  } else {
              	$text="";
            }
	        print "<td>" . $forms->text("user_id",$forms->report['user_id'],8,0,'',array("id"=>"ajax_user_id")) . " <span id='ajax_user_name'>$text</span</td>";
	        print "</tr>";

	        print "<tr>";
	        print "<td>Record type</td>";
            $query=array();
	        $query[]="select * from log";
	        $query[]="where type <> ''";
	        $query[]="group by type,subtype";
	        $query[]="order by type,subtype";
	        $database->log->query($query);
	        $type_list=array();
	        while ($database->log->fetch = $database->log->fetch_array() ) {
	            $database->log->fetch();
	            $key=base64_encode($database->log->data->type . "\t" . "");
	            if (!isset($type_list[$database->log->data->type])) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . "")]=$database->log->data->type . " (All)";
	            if ($database->log->data->subtype) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . $database->log->data->subtype)]=$database->log->data->subtype;
	        }
	        $database->log->free_result();
	        print "<td>" . $forms->optgroup("type_subtype",$type_list,$forms->report['type_subtype']) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td width='30%'>From date</td>";
	        print "<td width='70%'>" . $forms->text("from_date",$forms->report['from_date'],10,10,"date") . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Thru date</td>";
	        print "<td>" . $forms->text("thru_date",$forms->report['thru_date'],10,10,"date") . "</td>";
	        print "</tr>";
            print "<tr>";
            print "<td>Output</td>";
            print "<td>" . $forms->select("file_type",array(".xlsx"=>"Excel (xlsx)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)"),$forms->report['file_type']) . "</td>";
            print "</tr>";
	        print "<tr>";
	        print "<td>Descending?</td>";
	        print "<td>" . $forms->checkbox("descending","desc",$forms->report['descending']) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>&nbsp;</td>";
	        print "<td>" . $forms->button("Go!",array("onclick"=>"fn_page_item('page',0,0);")) . "</td>";
	        print "</tr>";
	        print "</table>";
	        if ((!$forms->report['action']) || (sizeof($forms->error))) break;
            $this->query[]=$database->page_limit($forms->report['page']);
	        $database->log->query($this->query,TRUE);
	        print "<table class='scscpq_small border tablesorter'>";
	        print "<thead>";
	        print "<tr>";
	        print "<th>Date/Time</th>";
	        if (!$forms->report['user_id']) print "<th>Company/User</th>";
	        if (!$forms->report['type']) print "<th>Type</th>";
	        if (!$forms->report['subtype']) print "<th>Subtype</th>";
	        print "<th>SKU</th>";
	        print "<th>Comment</th>";
	        print "</tr>";
	        print "</thead>";
	        print "<tbody>";
	        if (!$forms->report['page']) {
	            $item=1;
	          } else {
	            $item=((($forms->report['page'] - 1) * $database->query_limit) + 1);
	        }
	        while ($database->log->fetch = $database->log->fetch_array() ) {
	            $database->log->fetch();
	            print "<td class='center nobr'><a href=\"javascript:fn_page_item('item',0,'$item');\">" . date("m/d/Y H:i",$database->log->data->date_time) . "</a></td>";
	            $text=array();
	            switch (TRUE) {
	              case (!$database->log->data->user_id):
	                $text[]="Visitor";
	                break;
	              case (!$database->log->fetch['user_name']):
	                $text[]="Not on file";
	                break;
	              default:
	                if ($database->log->fetch['user_company_name']) $text[]=$database->log->fetch['user_company_name'];
	                $text[]=$database->log->fetch['user_name'];
	            }
				if (!$forms->report['user_id']) print "<td class='nobr'>" . implode("<br>",$text) . "</td>";
	            if (!$forms->report['type']) print "<td class='nobr'>" . $database->log->data->type . "</td>";
	            if (!$forms->report['subtype']) print "<td class='nobr'>" . $database->log->data->subtype . "</td>";
	            print "<td>" . $database->log->data->sku . "</td>";
	            print "<td>" . wordwrap(implode("<br>",$database->log->data->comment),100) . "</td>";
	            $item++;
	            print "</tr>";
	        }
	        print "</tbody>";
	        print "</table>";
	        $database->log->free_result();
	        $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('page',%page%,0);");
	        print $page_break->page($forms->report['page']);
	    }
	    print $forms->close();
	    $menu->copyright();
    }
}
?>