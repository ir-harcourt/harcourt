<?php
$loglist=new loglist_class();
class loglist_class {
    var $field=array();
    var $query;
    var $output_file;
	function scs_table_version() {
		$results=array();
        $results['01/21/2017']="Identify IP address 0.0.0.0 as console";
        $results['04/14/2016']="Include user_id in hidden fields";
        $results['01/01/2015']="Minor revisions";
        $results['06/25/2014']="Add ajax support";
        $results['05/22/2014']="Fix descending order has issues";
        $results['02/27/2014']="Improve layout";
        $results['11/11/2013']="Initial release";
        return $results;
    }
    function __construct() {
    global $database;
		if (!class_exists("map_item_class")) return;
		$this->output=array(".xls"=>"Excel (xls)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)");
        if (!class_exists("xls_class")) unset ($this->output['.xls']);

	    $this->field['date']=new map_item_class("date");
	    $this->field['time']=new map_item_class("time");
	    $this->field['type']=new map_item_class("type");
	    $this->field['subtype']=new map_item_class("subtype");
 	    $this->field['user_id']=new map_item_class("user_id");
	    $this->field['user_name']=new map_item_class("user_name");
	    $this->field['company_name']=new map_item_class("company_name");
	    $this->field['city']=new map_item_class("city");
	    $this->field['state']=new map_item_class("type");
	    $this->field['zip']=new map_item_class("subtype");
 	    $this->field['country']=new map_item_class("country");
 	    $this->field['comment']=new map_item_class("comment");
	    $this->field['ip_address']=new map_item_class("ip_address");
	    $this->field['agent']=new map_item_class("agent");

	    $this->field['date']->xls("Date","log","date_time",10,"date");
	    $this->field['time']->xls("Time","log","date_time",10,"time");
	    $this->field['type']->xls("Type","log","type",20);
	    $this->field['subtype']->xls("Subtype","log","subtype",20);
 	    $this->field['user_id']->xls("User#","log","user_id",10,"integer");
	    $this->field['user_name']->xls("User Name","user","name",30);
	    $this->field['company_name']->xls("Company Name","user","company_name",30);
	    $this->field['city']->xls("City","user","city",30);
	    $this->field['state']->xls("State","user","state",20);
	    $this->field['zip']->xls("Zip","user","zip",10);
 	    $this->field['country']->xls("Country","user","country_code",10);
 	    $this->field['comment']->xls("Comment","log","comment",100);
	    $this->field['ip_address']->xls("IP Address","log","ip_address",15);
	    $this->field['agent']->xls("Agent","log","agent",80);

    }
    function process() {
    global $database,$forms;
	    $forms->title("SCS Access Log");
	    $forms->message[]="Revised " . key( $this->scs_table_version() );
	    $forms->report['action']=$_POST['action'];
	    $forms->report['user_id']=$_POST['user_id'];
	    $forms->report['output']=$_POST['output'];
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
	        $forms->report['output']=$_POST['output'];
	        $forms->report['descending']=$_POST['descending'];
			switch (TRUE) {
              case (!$forms->report['descending']):
              case (!$forms->report['timestamp']):
              case ( (!$forms->report['page']) && (!$forms->report['item']) ):
				$forms->report['timestamp']=strtotime("now");
			}
	        $query_where=array();
			if ($forms->report['user_id']) $query_where[]=new query_where("log.user_id","=",$forms->report['user_id']);
	        if ($forms->report['type']) $query_where[]=new query_where("log.type","=",$forms->report['type']);
	        if ($forms->report['subtype']) $query_where[]=new query_where("log.subtype","=",$forms->report['subtype']);
	        if ($forms->report['descending']) $query_where[]=new query_where("log.date_time","<=",$forms->report['timestamp']);
	        if ($forms->report['from_date']) $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')",">=",$forms->report['from_date']);
	        if ($forms->report['thru_date']) $query_where[]=new query_where("date_format(from_unixtime(log.date_time),'%Y/%m/%d')","<=",$forms->report['thru_date']);
            if (sizeof($forms->error)) break;
	        $this->query="select \n";
			if (!class_exists("map_item_class")) {
            	$this->query .=
                    "user.name as user_name, \n" .
                    "user.company_name as user_company_name, \n" .
                    "user.city as user_city, \n" .
                    "user.state as user_state, \n" .
                    "user.zip as user_zip, \n" .
                    "user.country_code as user_country_code, \n";
				} else {
	              foreach ($this->field as $item) {
	              	if ($item->xls->table != "log") $this->query .= "{$item->xls->table}.{$item->xls->field} as {$item->xls->table}_{$item->xls->field},\n";
	        	}
			}
	        $this->query .=
	            "log.* from log \n" .
	            "left join user on user.id=log.user_id \n" .
	            $database->where($query_where) .
	            "order by id " . $forms->report['descending'];
			if ($forms->report['output']) {
				$database->log->query($this->query);
                if (!$database->log->meta->rows) $forms->error('mysql','','No records found in range');
            }
		}
        switch (TRUE) {
          case ((!sizeof($forms->error)) && ($forms->report['output'])):
			$this->output_file="log" . $forms->report['output'];
			$results=array();
            $results[]="File: " . $this->output_file;
			$results[]="Rows: " . number_format($database->log->meta->rows);
	        if ($forms->report['output']==".xls") {
	            $this->output_xls();
	          } else {
	            $this->output_txt();
			}
			$database->log->free_result();
			if (method_exists($database->log,"ipc")) $database->log->ipc("Export Table: log",$results);
            die;
          default:
			$this->html();
		}
    }
    function html() {
	global $forms,$menu,$database;
	    $menu->head();
	    $forms->message();
	    print $forms->open();
	    print $forms->hidden("action");
	    print $forms->hidden("page");
	    print $forms->hidden("item");
	    print $forms->hidden("timestamp",$forms->report['timestamp']);
	    switch (TRUE) {
	      case ($forms->report['action'] == "item"):
	        print $forms->hidden("output",$forms->report['output']);
	        print $forms->hidden("user_id",$forms->report['user_id']);
	        print $forms->hidden("type_subtype",$forms->report['type_subtype']);
	        print $forms->hidden("from_date",$forms->report['from_date'],"date:ymd");
	        print $forms->hidden("thru_date",$forms->report['thru_date'],"date:ymd");
	        print $forms->hidden("descending",$forms->report['descending']);
	        $query=$this->query . $database->item_limit($forms->report['item']);
	        $database->log->query($query,TRUE);
	        $database->log->fetch(TRUE);
	        $database->log->free_result();
	        $database->user->read($database->log->data->user_id);
	        print "<table class='standard noborder'>\n";
	        print "<caption>&nbsp;</caption>\n";
	        print "<tr>\n";
	        print "<td width='30%'>Date/Time</td>\n";
	        print "<td width='70%'>" . date("l F j, Y h:i A",$database->log->data->date_time) . "</td>\n";
	        print "</tr>\n";
	        switch (TRUE) {
	          case (!$database->log->data->user_id):
	            print "<tr>\n";
	            print "<td>User</td>\n";
	            print "<td>Visitor</td>\n";
	            print "</tr>\n";
	            break;
	          case (!$database->user->meta->rows):
	            print "<tr>\n";
	            print "<td>User</td>\n";
	            print "<td>Not on file</td>\n";
	            print "</tr>\n";
	            break;
	          default:
	            if ($database->user->data->username) {
	                print "<tr>\n";
	                print "<td>Username</td>\n";
	                print "<td>" . $database->user->data->username . "</td>\n";
	                print "</tr>\n";
	            }
	            print address_table("user",$database->user->data);
	            print "<td>Email</td>\n";
	            print "<td>" . $database->user->data->email . "</td>\n";
	            print "</tr>\n";
	            print "<tr>\n";
	        }
	        print "<tr><td colspan=2>&nbsp;</td></tr>\n";
	        print "<tr>\n";
	        print "<td>Record type</td>\n";
	        print "<td>" . $database->log->data->type;
	        if ($database->log->data->subtype) print " (" . $database->log->data->subtype . ")";
	        print "</td>\n";
	        print "</tr>\n";
	        if ($database->log->data->sku) {
	            print "<tr>\n";
	            print "<td>SKU</td>\n";
	            print "<td>" . $database->log->data->sku . "</td>\n";
	            print "<tr>\n";
	        }
	        if (sizeof($database->log->data->comment)) {
	            print "<tr>\n";
	            print "<td>Comment</td>\n";
	            print "<td>" . implode("<br>",$database->log->data->comment) . "</td>\n";
	            print "<tr>\n";
	        }
	        print "<tr><td colspan=2>&nbsp;</td></tr>\n";
	        print "<tr>\n";
	        print "<td>IP address</td>\n";
            if ($database->log->data->ip_address) {
            	$text=array(long2ip($database->log->data->ip_address),"(" . gethostbyaddr(long2ip($database->log->data->ip_address)) . ")");
			  } else {
              	$text=array("console");
            }
	        print "<td>" . implode(" ",$text) . "</td>\n";
	        print "</tr>\n";
	        print "<tr>\n";
	        print "<td>Agent</td>\n";
	        print "<td>" . $database->log->data->agent;
            if ( (ini_get("browscap")) && ($database->log->data->agent) ) {
				$agent=get_browser($database->log->data->agent);
				print "<ul>\n";
                foreach ($agent as $key => $value) {
					if ( ($value) && (!preg_match("/^browser_name/i",$key)) ) {
						print "<li>$key: $value</li>\n";
                    }
                }
				print "</ul>\n";
			}
            print "</td>\n";
	        print "</tr>\n";
	        print "</table>";
	        $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('item',0,%item%);");
	        $page_break->return_url("Return","fn_page_item('page'," . (floor($forms->report['item'] / $database->query_limit) + 1) . ",0);");
	        print $page_break->item($forms->report['item']);
	        break;
	      default:
	        print "<table class='standard noborder'>\n";
	        print "<caption>&nbsp;</caption>\n";
	        print "<tr>\n";
	        print "<td>User ID</td>\n";
			if ($forms->report['user_id']) {
            	$database->user->read($forms->report['user_id']);
                $text=$database->user->data->name;
			  } else {
              	$text="";
            }
	        print "<td>" . $forms->text("user_id",$forms->report['user_id'],8,0,'',array("id"=>"ajax_user_id")) . " <span id='ajax_user_name'>$text</span</td>\n";
	        print "</tr>\n";

	        print "<tr>\n";
	        print "<td>Record type</td>\n";
	        $query=
	            "select * from log \n" .
	            "where type<>'' \n" .
	            "group by type,subtype\n" .
	            "order by type,subtype";
	        $database->log->query($query);
	        $type_list=array();
	        while ($database->log->fetch = $database->log->fetch_array() ) {
	            $database->log->fetch();
	            $key=base64_encode($database->log->data->type . "\t" . "");
	            if (!isset($type_list[$database->log->data->type])) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . "")]=$database->log->data->type . " (All)";
	            if ($database->log->data->subtype) $type_list[$database->log->data->type][base64_encode($database->log->data->type . "\t" . $database->log->data->subtype)]=$database->log->data->subtype;
	        }
	        $database->log->free_result();
	        print "<td>" . $forms->optgroup("type_subtype",$type_list,$forms->report['type_subtype']) . "</td>\n";
	        print "</tr>\n";
	        print "<tr>\n";
	        print "<td width='30%'>From date</td>\n";
	        print "<td width='70%'>" . $forms->text("from_date",$forms->report['from_date'],10,10,"date") . "</td>\n";
	        print "</tr>\n";
	        print "<tr>\n";
	        print "<td>Thru date</td>\n";
	        print "<td>" . $forms->text("thru_date",$forms->report['thru_date'],10,10,"date") . "</td>\n";
	        print "</tr>\n";
	        if (isset($this->output)) {
	            print "<tr>\n";
	            print "<td>Output</td>\n";
	            print "<td>" . $forms->select("output",$this->output,$forms->report['output']) . "</td>\n";
	            print "</tr>\n";
	        }
	        print "<tr>\n";
	        print "<td>Descending?</td>\n";
	        print "<td>" . $forms->checkbox("descending","desc",$forms->report['descending']) . "</td>\n";
	        print "</tr>\n";
	        print "<tr>\n";
	        print "<td>&nbsp;</td>\n";
	        print "<td>" . $forms->button("Go!",array("onclick"=>"fn_page_item('page',0,0);")) . "</td>\n";
	        print "</tr>\n";
	        print "</table>\n";
	        if ((!$forms->report['action']) || (sizeof($forms->error))) break;
	        $query =$this->query . $database->page_limit($forms->report['page']);
	        $database->log->query($query,TRUE);
	        print "<table class='small border tablesorter'>\n";
	        print "<thead>\n";
	        print "<tr>\n";
	        print "<th>Date/Time</th>\n";
	        if (!$forms->report['user_id']) print "<th>User Name</th>\n";
	        if (!$forms->report['type']) print "<th>Type</th>\n";
	        if (!$forms->report['subtype']) print "<th>Subtype</th>\n";
	        print "<th>SKU</th>\n";
	        print "<th>Comment</th>\n";
	        print "</tr>\n";
	        print "</thead>\n";
	        print "<tbody>\n";
	        if (!$forms->report['page']) {
	            $item=1;
	          } else {
	            $item=((($forms->report['page'] - 1) * $database->query_limit) + 1);
	        }
	        while ($database->log->fetch = $database->log->fetch_array() ) {
	            $database->log->fetch();
	            print "<td class='center nobr'><a href=\"javascript:fn_page_item('item',0,'$item');\">" . date("m/d/Y H:i",$database->log->data->date_time) . "</a></td>\n";
	            $results=array();
	            switch (TRUE) {
	              case (!$database->log->data->user_id):
	                $results[]="Visitor";
	                break;
	              case (!$database->log->fetch['user_name']):
	                $results[]="Not on file";
	                break;
	              default:
	                if ($database->log->fetch['user_company_name']) $results[]=$database->log->fetch['user_company_name'];
	                $results[]=$database->log->fetch['user_name'];
	            }
				if (!$forms->report['user_id']) print "<td class='nobr'>" . implode("<br>",$results) . "</td>\n";
	            if (!$forms->report['type']) print "<td class='nobr'>" . $database->log->data->type . "</td>\n";
	            if (!$forms->report['subtype']) print "<td class='nobr'>" . $database->log->data->subtype . "</td>\n";
	            print "<td>" . $database->log->data->sku . "</td>\n";
	            print "<td>" . wordwrap(implode("<br>",$database->log->data->comment),100) . "</td>\n";
	            $item++;
	            print "</tr>\n";
	        }
	        print "</tbody>\n";
	        print "</table>\n";
	        $database->log->free_result();
	        $page_break=new page_item_break($database->log->meta->found_rows,"fn_page_item('page',%page%,0);");
	        print $page_break->page($forms->report['page']);
	    }
	    print $forms->close();
	    $menu->copyright();
    }
    function output_data($xls=FALSE) {
    global $database;
		$database->log->fetch();
		$results=array();
        foreach ($this->field as $field => $item) {
        	switch (TRUE) {
              case ($item->xls->table != "log"):
				$text=$database->log->fetch["{$item->xls->table}_{$item->xls->field}"];
				break;
              case ($field== "ip_address"):
              	$text=long2ip($database->log->data->ip_address);
              	break;
              case (($field == "comment") && (!$xls)):
              	$text=implode(",",$database->log->data->comment);
	            break;
              case (($field == "date") && (!$xls)):
              	$text=date("m/d/Y",$database->log->data->date_time);
              	break;
              case (($field == "time") && (!$xls)):
				$text=date("H:i",$database->log->data->date_time);
              	break;
              default:
              	$text=$database->log->data->{$item->xls->field};
			}
            $results[$field]=$text;
        }
        return $results;
    }
    function output_txt() {
    global $database,$forms;
		if ($forms->report['output'] == ".txt") {
        	$delimiter="\t";
		  } else {
          	$delimiter=",";
		}
	    header("Content-type: text/plain");
	    header("Content-Disposition: attachment; filename=" . $this->output_file);
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    $fh = fopen("php://output", "w");
        $results=array();
        foreach ($this->field as $field => $item) {
			$results[$field]=$item->xls->name;
        }
		fputcsv($fh,$results,$delimiter,"\"");
	    while ($database->log->fetch=$database->log->fetch_array()) {
			fputcsv($fh,$this->output_data(),$delimiter,"\"");
	    }
	    fclose($fh);
    }
    function output_xls() {
    global $database,$forms;
		$xls=new xls_class($this->output_file,"Export Table: log");
        $xls->worksheet("Data",$this->field);
	    while ($database->log->fetch=$database->log->fetch_array()) {
	        $xls->row($this->output_data(TRUE));
	    }
		$xls->write();
    }
}
?>