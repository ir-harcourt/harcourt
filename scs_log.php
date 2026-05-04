<?php
require_once "scs_header.php";
$obj=new local_class();

class local_class {
    var $trace=array();
    function scs_table_version() {
    	$results=array();
        $results['09/25/2025']="Release update";
        return $results;
    }
    function __construct() {
        $this->constant=new stdClass;
        $this->constant->option_output=array("txt","csv");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        	$this->json();
		  } else {
			$this->html();
		}
    }
    function json() {
         $this->response=new stdClass;
         die(json_encode($this->response));
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass;
        $this->input->action=$_REQUEST['action'];
        $this->input->page=intval($_POST['scscpq_page']);
        $this->input->item=intval($_POST['scscpq_item']);
        switch (TRUE) {
          case (!$this->input->action):
            $this->input->from_date=date("Y/m/d",strtotime("-7 days"));
            $this->input->thru_date=date("Y/m/d");
            break;
          case ($this->input->action=="login"):
            $this->input->user_id=$_REQUEST['user_id'];
            $this->input->from_date=date("Y/m/d",strtotime("-7 days"));
            $this->input->thru_date=date("Y/m/d");
            break;
          default:
            $this->input->from_date=fn_date($_POST['from_date'],"mdy");
            $this->input->thru_date=fn_date($_POST['thru_date'],"mdy");
            if (($this->input->thru_date) && ($this->input->from_date > $this->input->thru_date)) $forms->error('thru_date');
            $this->input->type_subtype=$_POST['type_subtype'];
            list ($this->input->type,$this->input->subtype)=fn_base64($this->input->type_subtype,FALSE);
            $this->input->user_id=fn_number($_POST['ajax_user_id'],0);
            if ($this->input->user_id) {
                $database->user->read($this->input->user_id);
                if (!$database->user->meta->rows) $forms->error('ajax_user_id');
            }
            $this->input->output=$_POST['output'];
        }
        if ((!$this->input->output) || (sizeof($forms->error))) return;
        $database->log->query($this->query(TRUE));
        if (!$database->log->meta->rows) {
            $forms->error('null',"","No records found");
            return;
        }
        $field_delimiter=($this->input->output=="txt") ? "\t" : ",";
        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename=" . "log.{$this->input->output}");
        header("Pragma: no-cache");
        header("Expires: 0");
        $fh=fopen("php://output", "w");
        $data=array();
        $data[]="IP Address";
        $data[]="Email";
        $data[]="Company Name";
        $data[]="Date/Time";
        $data[]="Type";
        $data[]="Subtype";
        $data[]="SKU";
        $data[]="Comment";
        fputcsv($fh,$data,$field_delimiter,"\"");
        while ($database->log->fetch = $database->log->fetch_array() ) {
            $database->log->fetch();
            $data=array();
            $data[]=$database->log->fetch['ip'];
            $data[]=$database->log->data->email;
            $data[]=$database->log->fetch['company_name'];
            $data[]=date("m/d/Y h:i:s A",$database->log->data->date_time);
            $data[]=$database->log->data->type;
            $data[]=$database->log->data->subtype;
            $data[]=$database->log->data->sku;
            $data[]=str_replace("\n"," ",$database->log->data->comment);
            fputcsv($fh,$data,$field_delimiter,"\"");
        }
        $database->log->free_result();
        fclose($fh);
        die();
    }
    function html() {
        global $database, $forms, $menu;
        $this->input();
        $forms->title("Event Log", key($this->scs_table_version()));
        $menu->head();
        print $forms->message();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action");
        print $forms->hidden("scscpq_page");
        print $forms->hidden("scscpq_item");
        $this->tabs=new jquery_tab_class();
        if (isset($_SESSION['scscpq_wp'])) $this->tabs->meta->options->lineheight="auto";
        $this->tabs->item("Input", $this->html_input());
        if ( $this->input->action && (!sizeof($forms->error)) ) $this->tabs->item("Results", $this->html_results());
        $this->tabs->output();
        print $forms->close();
        $menu->copyright();
    }
    function html_input() {
        global $database,$forms;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='30%'>From Date</td>";
        $results[]="<td width='70%'>" . $forms->text("from_date",$this->input->from_date,10,10,"date:ymd") . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Thru Date</td>";
        $results[]="<td>" . $forms->text("thru_date",$this->input->thru_date,10,10,"date:ymd") . "</td>";
        $results[]="</tr>";
        $text=array();
        foreach ($database->log->constant->type as $type => $subtypes) {
            $text[fn_base64(array($type,""))]="{$type} (All)";
            if (sizeof($subtypes)) {
                foreach ($subtypes as $subtype) {
                    $text[fn_base64(array($type,$subtype))]="&nbsp;" . $subtype;
                }
            }
        }
        $results[]="<tr>";
        $results[]="<td>Record type</td>";
        $results[]="<td>" . $forms->select("type_subtype",$text,$this->input->type_subtype) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>User ID</td>";
        $text=array();
        $text[]=$forms->text("ajax_user_id",$this->input->user_id,20);
        $text[]="<b><span id=ajax_user_label>{$database->user->data->company_name}</span></b>";
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Export?</td>";
        $results[]="<td>" . $forms->select("output",$this->constant->option_output,$this->input->output) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"toolbox_page_item('list',0,0);")) . "</p>";
        return $results;
    }
    function html_results() {
        global $database,$forms;
        $this->tabs->landing_tab();
        $database->log->query($this->query(),TRUE);
        if (!$database->log->meta->rows) return "<p>No records found</p>";
        $results=array();
        $results[]="<table class='small tablesorter border'>";
        $results[]="<caption>&nbsp;</caption>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Email</th>";
        $results[]="<th>Company Name</th>";
        $results[]="<th>Date/Time</th>";
        if (!$this->input->type) $results[]="<th>Type</th>";
        if (!$this->input->subtype)$results[]="<th>Subtype</th>";
        $results[]="<th>SKU</th>";
        $results[]="<th>Comment</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        while ($database->log->fetch = $database->log->fetch_array() ) {
            $database->log->fetch();
            $results[]="<tr>";
            $results[]="<td>{$database->log->data->email}</td>";
            $results[]="<td>{$database->log->fetch['company_name']}</td>";
            $results[]="<td class='center nobr'>" . date("m/d/Y h:i A",$database->log->data->date_time) . "</td>";
            if (!$this->input->type) $results[]="<td>" . $database->log->data->type . "</td>";
            if (!$this->input->subtype) $results[]="<td>{$database->log->data->subtype}</td>";
            $results[]="<td class=nobr>{$database->log->data->sku}</td>";
            $results[]="<td>" . str_replace("\n","<br>",$database->log->data->comment) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
        $database->log->free_result();
        $this->page_break=new page_break_class($this->input->action, $this->input->page, $this->input->item);
        $results[]=$this->page_break->output($database->log->meta->found_rows, array("url"=>"toolbox_page_item('list',%page%,0);"));
        return $results;
    }
    function query($file=FALSE) {
        global $database;
        $query_where=array();
        $query_where[]=new query_where("log.bot_id","=","");
        if ($this->input->from_date) $query_where[]=new query_where("log.date_time",">=",strtotime($this->input->from_date));
        if ($this->input->thru_date) $query_where[]=new query_where("log.date_time","<=",strtotime($this->input->thru_date . " + 1 day") - 1);
        if ($this->input->type) $query_where[]=new query_where("log.type","=",$this->input->type);
        if ($this->input->subtype) $query_where[]=new query_where("log.subtype","=",$this->input->subtype);
        if ($this->input->user_id) $query_where[]=new query_where("log.user_id","=",$this->input->user_id);
        $query=array();
        $query[]="select user.company_name as 'company_name',";
        $query[]="user.ip as 'ip_address',";
        $query[]="log.* from log";
        $query[]="left join user on user.id=log.user_id";
        $query[]=$database->where($query_where);
        $query[]="order by log.date_time desc";
        if (!$file) $query[]=$database->limit("page", $this->input->page);
        return $query;
    }
    function js() {
        return <<< EOT

<script>
function toolbox_page_item(action, page, item) {
	jQuery('#action').val(action)
	jQuery('#scscpq_page').val(page)
	jQuery('#scscpq_item').val(item)
	jQuery('#scs_form').submit();
}
</script>
EOT;
    }
}
?>