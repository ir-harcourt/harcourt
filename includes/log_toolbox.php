<?php
require_once "map_rev3.php";
class log_toolbox_class {
	var $options;
	function scs_table_version($action="") {
		$results=array();
        switch ($action) {
          case "purge":
            $results['11/08/2024']="Bug correction";
        	$results['07/20/2021']="Based on logpurge_rev1";
            break;
          case "list":
            $results['08/27/2025']="New page break";
            $results['04/17/2025']="Standardize lookup";
        	$results['03/31/2023']="Improve page_break";
        	$results['07/20/2021']="Based on loglist_rev2";
            break;

        }
        return $results;
    }
	function __construct($function, $role, $options=array()) {
        global $database, $forms, $menu;
    	$this->function=$function;
        $this->input=new stdClass;
    	if (strlen($role)) $database->user->access($role);
        $this->options=new stdClass();
        $this->input=new stdClass();
        $this->input->action=$_POST['action'];
        if (method_exists($this,$function)) {
        	$this->{$function}();
		  } else {
          	die("Invalid function: {$function}");
        }
    }
// Activity log purge
    function purge() {
        global $database, $forms, $menu;
        $forms->title("Purge Event Log", key($this->scs_table_version(__FUNCTION__)));
        $this->purge_input();
        $menu->head();
	    $forms->message();
        print $this->purge_js();
	    print $forms->open();
		print $forms->hidden("action");
        switch (TRUE) {
          case (($this->input->action=="review") && (!sizeof($forms->error))):
            $items=array();
            print $forms->hidden("date",$this->input->date,"date:ymd");
            foreach ($this->input->selected as $code) {
                print $forms->hidden($code,1);
            }
            print "<table class='standard noborder'>";
            print "<tr>";
            print "<td width='20%'>Remove all activity thru date</td>";
            print "<td width='80%'>" . fn_date($this->input->date,"ymd") . "</td>";
            print "</tr>";
            foreach ($this->input->items as $type => $subtypes) {
                $text=array();
                foreach ($subtypes as $subtype => $count) {
                    $field=fn_base64(array("subtype",$type,$subtype));
                    $text[]="{$subtype} (" . number_format($count) . ")";
                }
                print "<tr>";
                print "<td>{$type}</td>";
                print "<td>" . implode("<br>",$text) . "</td>";
                print "</tr>";
                print "<tr><td colspan=2>&nbsp;</td></tr>";
            }
            print "</table>";
            $buttons=array();
            $buttons[]=$forms->button("<< Back",array("onclick"=>"toolbox_action('entry','');"));
            if (sizeof($this->input->items)) $buttons[]=$forms->button("Purge",array("onclick"=>"toolbox_action('update','Permantly remove selected items?');"));
            print "<p>" . implode(" ",$buttons) . "</p>";
            break;
        default:
            $this->purge_items();
            if (!sizeof($this->input->items)) {
                print "<p class=standard><b>Log is empty</b></p>";
              } else {
                print "<table class='standard noborder'>";
                print "<tr>";
                print "<td width='20%'>Remove all activity thru date</td>";
                $text=array($forms->text("date",$this->input->date,10,10,"date"));
                if ($forms->error_text['date']) $text[]=$forms->error_text['date'];
                print "<td width='80%'>" . implode(" ", $text) . "</td>";
                print "</tr>";
                foreach ($this->input->items as $type => $subtypes) {
                    $classname=fn_base64(array("type",$type));
                    $text=array();
                    foreach ($subtypes as $subtype => $count) {
                        $field=fn_base64(array("subtype",$type,$subtype));
                        $text[]=$forms->checkbox($field, 1, in_array($field, $this->input->selected), array("class"=>$classname)) . " {$subtype}";
                    }
                    print "<tr>";
                    $field=fn_base64(array("type",$type));
                    print "<td>" . $forms->checkbox($field,1,0,array("onclick"=>"jQuery('input.{$classname}').prop('checked',this.checked);")) . " {$type}</td>";
                    print "<td>" . implode("<br>",$text) . "</td>";
                    print "</tr>";
                    print "<tr><td colspan=2>&nbsp;</td></tr>";
                }
                print "</table>";
                print "<p>" . $forms->button("Next >>",array("onclick"=>"toolbox_action('review','Continue?');")) . "</p>";
            }
        }
	    print $forms->close();
	    $menu->copyright();
    }
    function purge_input() {
        global $database, $forms;
        $this->input->action=$_POST['action'];
        $this->input->items=array();
        $this->input->selected=array();
        if (!$this->input->action) {
            $this->input->date=date("Y/m/d",strtotime("-30 days"));
            return;
        }
        $this->input->date=fn_date($_POST['date'], "mdy");
        switch (TRUE) {
          case (!$this->input->date):
            $forms->error("date", "Cannot be blank");
            break;
          case ($this->input->date > date("Y/m/d")):
            $forms->error("date", "Future date not allowed");
            break;
        }
        foreach ($_POST as $key => $value) {
            $items=fn_base64($key,FALSE);
            if ($items[0]=="subtype") $this->input->selected[]=$key;
        }
        if (!sizeof($this->input->selected)) $forms->error("null","","No items selected");
        switch (TRUE) {
          case (sizeof($forms->error)):
            return;
          case ($this->input->action == "review"):
            $this->purge_items(TRUE);
            if (!$database->temp->meta->rows) $forms->error("date", "No records found in range");
            return;
          case ($this->input->action == "update"):
            $query=array("delete from log");
            $query[]=$this->purge_query();
            $database->temp->query($query);
			$purge_comment=number_format($database->affected_rows()) . " record(s) deleted from log";
            $forms->message[]=$purge_comment;
	        $items=array();
	        foreach ($this->input->selected as $code) {
	            list($field,$type,$subtype)=fn_base64($code,FALSE);
	            $items[]="{$type}:{$subtype}";
	        }
	        $comment=array($purge_comment);
		    $comment[]=implode("\n",$query);
	        $database->log->ipc("Administrator:Purge",$comment);
            return;
        }
    }
	function purge_items($update=FALSE) {
        global $database;
	    $query=array();
	    $query[]="select type,subtype,count(*) as count";
        $query[]="from log";
        if ($update) $query[]=$this->purge_query();
	    $query[]="group by type,subtype";
	    $query[]="order by type,subtype";
        $database->temp->query($query);
        $this->input->items=array();
        while ($database->temp->fetch=$database->temp->fetch_array()) {
            if (!array_key_exists($database->temp->fetch['type'],$this->input->items)) $this->input->items[$database->temp->fetch['type']]=array();
            $this->input->items[$database->temp->fetch['type']][$database->temp->fetch['subtype']]=$database->temp->fetch['count'];
        }
    }
    function purge_query() {
        global $database;
        $query_where=array();
	    $query_where[]=new query_where('date_time',"<",strtotime("{$this->input->date} +1 day") - 1);
	    $items=array();
	    foreach ($this->input->selected as $code) {
	        list($field,$type,$subtype)=fn_base64($code,FALSE);
	        $items[]="{$type}:{$subtype}";
	    }
        if (sizeof($items)) $query_where[]=new query_where("concat(type,':',subtype)","in",$items);
        return $database->where($query_where);
    }
    function purge_js() {
        return <<< EOT

<script>
function toolbox_action(action, text) {
    if ( (text) && (!confirm(text)) ) return;
    jQuery("#action").val(action);
    jQuery("#scs_form").submit();
}
</script>

EOT;
    }
    function list() {
        global $database, $forms, $menu;
        $forms->title("Event Log", key($this->scs_table_version(__FUNCTION__)));
        $this->list_input();
	    $menu->head();
	    $forms->message();
        print $this->list_js();
	    print $forms->open();
	    $this->tabs=new jquery_tab_class();
	    $this->tabs->item("Entry",$this->list_entry());
        $this->page_break=new page_break_class($this->input->action, $this->input->page, $this->input->item);
		switch (TRUE) {
          case (sizeof($forms->error)):
          case (!$this->input->action):
			break;
          case ($this->input->action == "item"):
		    $this->tabs->item("Item",$this->list_item());
            break;
           default:
		    $this->tabs->item("Summary",$this->list_summary());
		}
		$this->tabs->output();
	    print $forms->close();
	    $menu->copyright();
	}
    function list_input() {
        global $database, $forms;
	    $this->input->user_id=$_POST['user'];
	    $this->input->file_type=$_POST['file_type'];
	    $this->input->page=intval($_POST['scs_page']);
	    $this->input->item=intval($_POST['scs_item']);
	    switch (TRUE) {
	      case (!$this->input->action):
	        $this->input->from_date=date("Y/m/d",strtotime("-30 days"));
	        $this->input->thru_date=date("Y/m/d",strtotime("now"));
	        $this->input->descending="desc";
	        break;
	      default:
	        $this->input->type_subtype=$_POST['type_subtype'];
	        list($this->input->type,$this->input->subtype)=explode(":",base64_decode($_POST['type_subtype']));
	        $this->input->from_date=fn_date($_POST['from_date'],"mdy");
	        $this->input->thru_date=fn_date($_POST['thru_date'],"mdy");
            if ( ($this->input->from_date) && ($this->input->from_date > $this->input->thru_date) ) $forms->error("from_date","From Date > Thru Date");
	        $this->input->file_type=$_POST['file_type'];
	        $this->input->descending=$_POST['descending'];
			switch (TRUE) {
              case (!$this->input->descending):
	    		$this->input->timestamp=0;
              	break;
              case ($this->input->action == "list"):
	    		$this->input->timestamp=strtotime("now");
                break;
              default:
		    	$this->input->timestamp=intval($_POST['timestamp']);
			}
            $this->map=new map_class("log",$this->input);
            if (sizeof($forms->error)) return;
            if ($this->input->file_type) $this->map->export();
        }
    }
    function list_entry() {
        global $database, $forms, $menu;
	    print $forms->hidden("action");
	    print $forms->hidden("scs_page");
	    print $forms->hidden("scs_item");
	    print $forms->hidden("timestamp",$this->input->timestamp);
        $results[]="<table class='standard noborder'>";
        $results[]="<caption>&nbsp;</caption>";
        $results[]="<tr>";
        $results[]="<td width='20%'>User ID</td>";
        if ($this->input->user_id) {
            $database->user->read($this->input->user_id);
            $text=$database->user->data->name;
          } else {
            $text="";
        }
        $results[]="<td width='80%'>" . $forms->text("user",$this->input->user_id,8,0,'',array("class"=>"user")) . " <span id='user_name'>{$text}</span</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Record type</td>";
        $query=array();
        $query[]="select distinct(concat(type,':',subtype)) as code from log";
        $query[]="where type <> ''";
        $query[]="order by code";
        $database->temp->query($query);
        $items=array();
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
            list($type, $subtype)=explode(":",$database->temp->fetch['code']);
            $key=base64_encode($type . "\t" . "");
            if (!isset($items[$type])) $items[$type][base64_encode($type)]=$type . " (All)";
			if (strlen($subtype)) $items[$type][base64_encode($database->temp->fetch['code'])]=$subtype;
        }
        $database->temp->free_result();
        $results[]="<td>" . $forms->optgroup("type_subtype",$items,$this->input->type_subtype) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Date Range</td>";
        $text=array();
        $text[]=$forms->text("from_date",$this->input->from_date,10,10,"date");
        $text[]="-";
        $text[]=$forms->text("thru_date",$this->input->thru_date,10,10,"date");
        if ($forms->error_text['from_date']) $text[]=$forms->error_text['from_date'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Output</td>";
        $results[]="<td>" . $forms->select("file_type",array(".xlsx"=>"Excel (xlsx)",".txt"=>"Tab Delimited Text (txt)",".csv"=>"Comma Separated Values (csv)"),$this->input->file_type) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Descending?</td>";
        $results[]="<td>" . $forms->checkbox("descending","desc",$this->input->descending) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>&nbsp;</td>";
        $results[]="<td>" . $forms->button("Go!",array("onclick"=>"toolbox_page_item('list',0,0);")) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        return $results;
    }
    function list_summary() {
        global $database, $forms, $menu;
    	$this->tabs->landing_tab();
        $query=$database->log->export_query();
        $query[]=$this->page_break->limit();
        $database->log->query($query,TRUE);
    	$results=array();
        $results[]="<table class='scscpq_small border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Date/Time</th>";
        if (!$this->input->user_id) $results[]="<th>Company/User</th>";
        if (!$this->input->type) $results[]="<th>Type</th>";
        if (!$this->input->subtype) $results[]="<th>Subtype</th>";
        $results[]="<th>SKU</th>";
        $results[]="<th>Comment</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        $item=0;
        while ($database->log->fetch = $database->log->fetch_array() ) {
            $database->log->fetch();
            $results[]="<tr>";
            $results[]="<td class='center nobr'><a href=\"javascript:toolbox_page_item('item',{$this->input->page},{$item});\">" . date("m/d/Y h:i A",$database->log->data->date_time) . "</a></td>";
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
            if (!$this->input->user_id) $results[]="<td class='nobr'>" . implode("<br>",$text) . "</td>";
            if (!$this->input->type) $results[]="<td class='nobr'>" . $database->log->data->type . "</td>";
            if (!$this->input->subtype) $results[]="<td class='nobr'>" . $database->log->data->subtype . "</td>";
            $results[]="<td>" . $database->log->data->sku . "</td>";
            $results[]="<td>" . wordwrap(implode("<br>",$database->log->data->comment),100) . "</td>";
            $results[]="</tr>";
            $item++;
        }
        $results[]="</tbody>";
        $results[]="</table>";
        $database->log->free_result();
        $results[]=$this->page_break->output($database->log->meta->found_rows, array("url"=>"toolbox_page_item('list',%page%,0);"));
		return $results;
    }
    function list_item() {
        global $database, $forms, $menu;
    	$this->tabs->landing_tab();
        $query=$database->log->export_query();
        $query[]=$this->page_break->limit();
        $database->log->query($query,TRUE);
	    $database->log->fetch(TRUE);
	    $database->user->read($database->log->data->user_id);
    	$results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<caption>&nbsp;</caption>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Date/Time</td>";
        $results[]="<td width='80%'>" . date("l F j, Y h:i A T",$database->log->data->date_time) . "</td>";
        $results[]="</tr>";
        switch (TRUE) {
          case (!$database->log->data->user_id):
            $results[]="<tr>";
            $results[]="<td>User</td>";
            $results[]="<td>Visitor</td>";
            $results[]="</tr>";
            break;
          case (!$database->user->meta->rows):
            $results[]="<tr>";
            $results[]="<td>User</td>";
            $results[]="<td>Not on file</td>";
            $results[]="</tr>";
            break;
          default:
          	$address=new address_class("user",$database->user->data);
            $results[]=$address->output("table");
        }
        $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
        $results[]="<tr>";
        $results[]="<td>Record type</td>";
        $results[]="<td>" . $database->log->data->type;
        if ($database->log->data->subtype) $results[]=" (" . $database->log->data->subtype . ")";
        $results[]="</td>";
        $results[]="</tr>";
        if ($database->log->data->sku) {
            $results[]="<tr>";
            $results[]="<td>SKU</td>";
            $results[]="<td>" . $database->log->data->sku . "</td>";
            $results[]="<tr>";
        }
        if (sizeof($database->log->data->comment)) {
            $results[]="<tr>";
            $results[]="<td>Comment</td>";
            $results[]="<td>" . implode("<br>",$database->log->data->comment) . "</td>";
            $results[]="<tr>";
        }
        $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
        $results[]="<tr>";
        $results[]="<td>IP address</td>";
        if ($database->log->data->ip_address) {
            $text=array(long2ip($database->log->data->ip_address),"(" . gethostbyaddr(long2ip($database->log->data->ip_address)) . ")");
          } else {
            $text=array("console");
        }
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Agent</td>";
        $results[]="<td>" . $database->log->data->agent;
        if ( (ini_get("browscap")) && ($database->log->data->agent) ) {
            $agent=get_browser($database->log->data->agent);
            $results[]="<ul>";
            foreach ($agent as $key => $value) {
                if ( ($value) && (!preg_match("/^browser_name/i",$key)) ) {
                    $results[]="<li>$key: $value</li>";
                }
            }
            $results[]="</ul>";
        }
        $results[]="</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]=$this->page_break->output($database->log->meta->found_rows,array("return"=>TRUE));
        $page_break=new page_break_class("item", $database->log->meta->found_rows, $this->input->page, $this->input->item);
		return $results;
	}

    function list_js() {
        return <<< EOT

<script>
function toolbox_page_item(action,page,item) {
	jQuery('#action').val(action)
	jQuery('#scs_page').val(page)
	jQuery('#scs_item').val(item)
	jQuery('#scs_form').submit();
}
</script>

EOT;
    }

}
?>