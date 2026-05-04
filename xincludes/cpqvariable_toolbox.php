<?php
require_once "scs_header.php";
class cpqvariable_toolbox_class {
    var $function;
	function scs_table_version() {
		$results=array();
        switch ($this->function) {
          case "maintain":
            $results['01/29/2025']="Initial release";
            break;
        }
        return $results;
    }
    function __construct($role, $function, $options=array()) {
        global $database;
        $database->user->access($role);
        $this->function=$function;
        switch (TRUE) {
          case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
            $this->json();
            break;
          default:
            $this->html();
        }
    }
    function json() {
        $this->response=new stdClass();
        $this->response->version=key($this->scs_table_version());
        die(json_encode($this->response));
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass();
        $this->input->action=$_POST['action'];
        $this->input->record_id=$_POST['record_id'];
        $this->input->report_document=$_POST['report_document'];
        $database->cpqvariable->read($this->input->record_id);
        switch ($this->input->action) {
          case "":
            break;
          case "cancel":
            $forms->message[]="Request cancelled";
            break;
          case "maintain":
            $database->cpqvariable->data->document[]=$this->input->report_document;
            break;
          case "delete":
            $database->cpqvariable->delete($this->input->record_id);
            $forms->message[]="{$database->cpqvariable->data->prompt} deleted";
            break;
          case  "update":
            $this->input_update();
            break;
        }
    }
    function input_update() {
        global $database, $forms;
        $database->cpqvariable->data->prompt=fn_text($_POST['prompt']);
        if (!strlen($database->cpqvariable->data->prompt)) $forms->error("prompt","Cannot be blank");
        $database->cpqvariable->data->rank=$_POST['rank'];
        $database->cpqvariable->data->type=$_POST['type'];
        $database->cpqvariable->data->document=array();
        foreach ($database->registry->cpqvariable->document as $code => $name) {
            $field=fn_base64(array("document", $code));
            if ($_POST[$field]) $database->cpqvariable->data->document[]=$code;
        }
        if (!sizeof($database->cpqvariable->data->document)) $forms->error("document", "No documents selected");
        $database->cpqvariable->data->mandatory=$_POST['mandatory'];
        $database->cpqvariable->data->output_pdf=$_POST['output_pdf'];
        $database->cpqvariable->data->list=array();
        if ($database->cpqvariable->data->type == "list") {
            $items=explode("\n", $_POST['list']);
            foreach ($items as $item) {
                $item=trim($item);
                if ( (strlen($item) && (!in_array($item, $database->cpqvariable->data->list)))) $database->cpqvariable->data->list[]=$item;

            }
            if (!sizeof($database->cpqvariable->data->list)) $fomrs->error("list", "Cannot be blank");
        }
        $database->cpqvariable->data->output=$_POST['output'];
        $database->cpqvariable->data->active=$_POST['active'];
        if ( ($database->cpqvariable->data->output == "hide") && ($database->cpqvariable->data->output_pdf) ) $forms->error("output_pdf", "Hidden fields cannot be included on PDF");
        if (sizeof($forms->error)) return;
        if ($database->cpqvariable->data->type == "checkbox") $database->cpqvariable->data->mandatory=1;
        $database->cpqvariable->update($database->cpqvariable->meta->rows);
        switch (TRUE) {
          case (!$database->cpqvariable->meta->errno):
            $forms->message[]="Record maintained";
            return;
          case (preg_match("/" . preg_quote("code_UNIQUE","/") . "/i",$database->cpqvariable->meta->error)):
            $forms->error("code", $database->cpqvariable->meta->error);
            break;
          case (preg_match("/" . preg_quote("prompt_UNIQUE","/") . "/i",$database->cpqvariable->meta->error)):
            $forms->error("prompt", $database->cpqvariable->meta->error);
            break;
          default:
            $forms->error("null", "", $database->cpqvariable->meta);
        }
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Maintain Entry Variable",  key($this->scs_table_version($this->function)));
        $this->input();
        $menu->head();
        $forms->message();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action");
        print $forms->hidden("record_id");
        switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            $this->tabs=new jquery_tab_class();
            $this->tabs->item("Entry", $this->html_entry());
            $this->tabs->output();
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update',{$this->input->record_id},'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel',0,'');"));
            print "<p>" . implode(" ", $buttons) . "</p>";
            break;
          default:
            print implode("", $this->html_list());
        }
        print $forms->close();
        $menu->copyright();
    }
    function html_list() {
        global $database, $forms;
        $results=array();
        $results[]="<table class='noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Only document</td>";
        $text=array();
        $text[]=$forms->select("report_document", $database->registry->cpqvariable->document, $this->input->report_document, "All");
        $text[]=$forms->button("List",array("onclick"=>"fn_action('list',0,'');"));
        $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
        $results[]="<tr>";
        $results[]="</table>";
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Prompt</th>";
        $results[]="<th>Display Order</th>";
        $results[]="<th>Document</th>";
        $results[]="<th>Type</th>";
        $results[]="<th>List</th>";
        $results[]="<th>Mandatory</th>";
        $results[]="<th>Customer Facing</th>";
        $results[]="<th>PDF?</th>";
        $results[]="<th>Del?</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tr><td colspan=8>" . fn_href("Add New", "javascript:fn_action('maintain',0,'');") . "</td>";
        $results[]="<tbody>";
        $query_where=array();
        $query=array("select * from cpqvariable");
        if ($this->input->report_document) $query_where[]=new query_where("document", "like", "%\"{$this->input->report_document}\"%");
        $query=array("select * from cpqvariable");
        $query[]=$database->where($query_where);
        $query[]="order by rank, prompt";
        $database->cpqvariable->query($query);
        while ($database->cpqvariable->fetch = $database->cpqvariable->fetch_array()) {
            $database->cpqvariable->fetch();
            $bg=(!$database->cpqvariable->data->active) ? $forms->background->yellow : "";
            $results[]="<tr {$bg}>";
            $results[]="<td>" . fn_href($database->cpqvariable->data->prompt, "javascript:fn_action('maintain',{$database->cpqvariable->data->id},'');") . "</td>";
            $results[]="<td class=center>{$database->cpqvariable->data->rank}</td>";
            $text=array();
            foreach ($database->cpqvariable->data->document as $code) {
                $text[]=$database->registry->cpqvariable->document[$code];
            }
            $results[]="<td>" . implode("<br>", $text) . "</td>";
            $results[]="<td>" . $database->cpqvariable->constant->type[$database->cpqvariable->data->type] . "</td>";
            $results[]="<td>" . implode("<br>", $database->cpqvariable->data->list) . "</td>";
            $results[]="<td>" . ( ($database->cpqvariable->data->mandatory) ? "Yes" : "No") . "</td>";
            $results[]="<td>{$database->cpqvariable->constant->output[$database->cpqvariable->data->output]}</td>";
            $results[]="<td>" . ( ($database->cpqvariable->data->output_pdf) ? "Yes" : "No") . "</td>";
            $results[]="<td class=center>" . ( ($database->cpqvariable->data->active) ? "&nbsp;" : $forms->checkbox_delete($database->cpqvariable->data->id, $database->cpqvariable->data->prompt)) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
        return $results;
    }
    function html_entry() {
        global $database, $forms;
        $results=array();
        $results[]=$forms->hidden("report_document", $this->input->report_document);
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Prompt</td>";
        $text=array($forms->text("prompt",$database->cpqvariable->data->prompt,60, 120));
        if ($forms->error_text['prompt']) $text[]=$forms->error_text['prompt'];
        $results[]="<td width='80%'>" . implode("<br>",$text)  . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Documents</td>";
        $text=array();
        if ($forms->error_text['document']) $text[]=$forms->error_text['document'];
        foreach ($database->registry->cpqvariable->document as $code => $name) {
            $text[]=$forms->checkbox(fn_base64(array("document", $code)), 1, in_array($code, $database->cpqvariable->data->document)) . " {$name}";
        }
        $results[]="<td>" . implode("<br>", $text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Display Order</td>";
        $results[]="<td>" . $forms->select_number("rank",$database->cpqvariable->data->rank,1,99) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Input Type</td>";
        $results[]="<td>" . $forms->select("type",$database->cpqvariable->constant->type,$database->cpqvariable->data->type,"", array("onchange"=>"fn_type();")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr id=list_tr>";
        $results[]="<td>List</td>";
        $text=array();
        if ($forms->error_text["list"]) $text[]=$forms->error_text["list"];
        $text[]=$forms->textarea("list", $database->cpqvariable->data->list, 12, 60);
        $results[]="<td>" . implode("", $text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Customer Facing</td>";
        $results[]="<td>" . $forms->select("output", $database->cpqvariable->constant->output, $database->cpqvariable->data->output, FALSE) . "</td>";
        $results[]="</tr>";

        $results[]="<tr id=mandatory_tr>";
        $results[]="<td>Mandatory?</td>";
        $results[]="<td>" . $forms->checkbox("mandatory",1,$database->cpqvariable->data->mandatory) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Include on PDF?</td>";
        $text=array($forms->checkbox("output_pdf",1,$database->cpqvariable->data->output_pdf));
        if ($forms->error_text['output_pdf']) $text[]=$forms->error_text['output_pdf'];
        $results[]="<td>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Active?</td>";
        $results[]="<td>" . $forms->checkbox("active",1,$database->cpqvariable->data->active) . "</td>";
        $results[]="</tr>";

        $results[]="</table>";
        return $results;
    }
    function js() {
        return <<< EOT

<script>
jQuery(document).ready(function() {
    fn_type();
});
function fn_action(action, record_id, name) {
    if (action=='delete') {
        jQuery("#delete" + record_id).prop("checked", false);
        if (!confirm("Delete: #" + record_id + " " + name)) return;
    }
    jQuery("#action").val(action);
    jQuery("#record_id").val(record_id);
    jQuery("#scs_form").submit();
}
function fn_type() {
    var type=jQuery("#type").val();
console.log(type);

    if (type == "list") {
        jQuery("#list_tr").show();
      } else {
        jQuery("#list_tr").hide();
    }
    if (type == "checkbox") {
        jQuery("#mandatory_tr").hide();
      } else {
        jQuery("#mandatory_tr").show();
    }
}
</script>

EOT;
    }
}
?>