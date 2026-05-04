<?php
require_once "scs_header.php";
$obj=new local_class();
class local_class {
    var $field=array("html"=>"HTML", "css"=>"CSS", "js"=>"JavaScript");
    function scs_table_version() {
        $results=array();
        $results['11/25/2025']="Initial Release";
        return $results;
    }
    function __construct() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
          } else {
            $this->html();
        }
    }
    function json() {
        $this->input=new stdClass;
        $this->response=new stdClass;
        $this->response->content_error="";
        $this->input->action=$_POST['action'];
        $this->input->record_id=intval($_POST['record_id']);
        $this->input->field=$_POST['field'];
        $this->input->content=trim($_POST['content']);
        $method="json_{$this->input->action}";
        if (method_exists($this, $method)) $this->$method();
        die(json_encode($this->response));
    }
    function json_initial() {
        $this->buttons(TRUE);
        $this->response->preview_content="";
    }
    function json_load() {
        global $database, $forms;
        $this->buttons(FALSE);
        if (!isset($_SESSION['maintain_hero'])) {
            $this->response->preview_content="Internal error detected";
            return;
        }
        $text=array();
        $text[]="<div id=content_error></div>";
        $text[]=$forms->textarea("content", $_SESSION['maintain_hero'][$this->input->field], 30, 150, array("class"=>"input"));
        $this->response->preview_content=implode("", $text);
    }
    function json_reload() {
        global $database;
        $database->hero->read($this->input->record_id);
        $_SESSION['maintain_hero'][$this->input->field]=$database->hero->data->{$this->input->field};
        $this->response->content=$database->hero->data->{$this->input->field};
    }
    function json_preview() {
        global $database;
        $options=array();
        $options['session']=TRUE;
        $options['preview']=TRUE;
        $this->response->preview_content=$database->hero->output($options);
    }
    function json_cancel() {
        $this->response->preview_content="Request Cancelled";
        $this->buttons(TRUE);
    }
    function json_save() {
        $this->response->content_error=$this->json_save_review();
    }
    function json_save_review() {
        switch ($this->input->field) {
          case "html":
            if (!strlen($this->input->content)) return "HTML cannot be blank";
            break;
          case "css":
            if (!strlen($this->input->content)) return;
            if (!preg_match("/^(" . preg_quote("<style>", "/") . ")/i", $this->input->content)) return "CSS must begin with: " . htmlentities("<style>");
            if (!preg_match("/(" . preg_quote("</style>", "/") . ")$/i", $this->input->content)) return "CSS must end with: " . htmlentities("</style>");
            if (preg_match_all("/(" . preg_quote("<style>", "/") . ")/i", $this->input->content) != 1) return "Multiple " . htmlentities("<style>") . " tags detected";
            if (preg_match_all("/(" . preg_quote("</style>", "/") . ")/i", $this->input->content) != 1) return "Multiple " . htmlentities("</style>") . " tags detected";
            break;
          case "js":
            if (!strlen($this->input->content)) break;
            if (!preg_match("/^(" . preg_quote("<script>", "/") . ")/i", $this->input->content)) return "JavaScript must begin with: " . htmlentities("<script>");
            if (!preg_match("/(" . preg_quote("</script>", "/") . ")$/i", $this->input->content)) return "JavaScript must end with: " . htmlentities("</script>");
            if (preg_match_all("/(" . preg_quote("<script>", "/") . ")/i", $this->input->content) != 1) return "Multiple " . htmlentities("<script>") . " tags detected";
            if (preg_match_all("/(" . preg_quote("</script>", "/") . ")/i", $this->input->content) != 1) return "Multiple " . htmlentities("</script>") . " tags detected";
            break;
        }
        $_SESSION['maintain_hero'][$this->input->field]=$this->input->content;
        $this->response->preview_content="";
        $this->buttons(TRUE);
        return "{$this->field[$this->input->field]} Updated";
    }
    function buttons($initial) {
        global $database, $forms;
        $buttons=array();
        if ($initial) {
            $buttons[]=$forms->button("Load {$this->field[$this->input->field]}", array("onclick"=>"fn_form('load');"));
            $buttons[]=$forms->button("Preview", array("onclick"=>"fn_form('preview');"));
          } else {
            $buttons[]=$forms->button("Save {$this->field[$this->input->field]}", array("onclick"=>"fn_form('save');"));
            $buttons[]=$forms->button("Reload {$this->field[$this->input->field]}", array("onclick"=>"fn_form('reload');"));
            $buttons[]=$forms->button("Cancel {$this->field[$this->input->field]}", array("onclick"=>"fn_form('cancel');"));
        }
        $this->response->preview_button=implode(" ", $buttons);
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass;
        $this->input->action=$_POST['action'];
        $this->input->record_id=$_POST['record_id'];
        $database->hero->read($this->input->record_id);
        $this->input->report_portal_id=$_POST['report_portal_id'];
        $this->input->report_industry_id=$_POST['report_industry_id'];
        $this->input->report_language_code=$_POST['report_language_code'];
        switch ($this->input->action) {
          case "":
            unset($_SESSION['maintain_hero']);
            return;
          case "maintain":
            if (!$this->input->record_id) {
                $database->hero->data->porder_id=$this->input->report_portal_id;
                $database->hero->data->industry_id=$this->input->report_industry_id;
                $database->hero->data->language_code=$this->input->report_language_code;
            }
            $_SESSION['maintain_hero']=array();
            foreach ($this->field as $field => $name) {
                $_SESSION['maintain_hero'][$field]=$database->hero->data->$field;
            }
            return;
          case "cancel":
            $forms->message[]="Request cancelled";
            return;
          case "delete":
            if (isset($_SESSION['maintain_hero'])) $database->hero->delete($this->input->record_id);
            $forms->message[]="Hero #{$this->input->record_id} deleted";
            unset($_SESSION['maintain_hero']);
            return;
          case "update":
            if (!isset($_SESSION['maintain_hero'])) {
                $this->input->action="";
                $form->message[]="Refresh not allowed";
            }
            $database->hero->data->effective_date=fn_date($_POST['effective_date'], "mdy");
            $database->hero->data->portal_id=$_POST['portal_id'];
            $database->hero->data->industry_id=$_POST['industry_id'];
            $database->hero->data->language_code=$_POST['language_code'];
            $database->hero->data->access=$_POST['access'];
            $database->hero->data->status=$_POST['status'];
            foreach ($this->field as $field => $name) {
                $database->hero->data->$field=$_SESSION['maintain_hero'][$field];
            }
            if (!strlen($database->hero->data->html)) $forms->error("field","","HTML cannot be blank");
            if (sizeof($forms->error)) return;
            $database->hero->update($this->input->record_id);
            if ($database->hero->meta->error) {
                $forms->error("key","Duplicate combination Effective Date / Portal / Industry / Language");
                return;
            }
            $forms->message[]="Record updated";
            unset($_SESSION['maintain_hero']);
        }
    }
    function html() {
        global $database, $forms, $menu;
        $database->user->access("Administrator");
        $forms->title("Hero Headline", key($this->scs_table_version()));
        $this->input();
        $menu->head();
        print $forms->message();
        print $this->js();
        print $this->css();
        print $forms->open();
        print $forms->hidden("action");
        print $forms->hidden("record_id", $this->input->record_id, "", "input");

        switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            $this->tabs=new jquery_tab_class();
            $this->tabs->item("Info", $this->html_info());
            $this->tabs->item("Content", $this->html_content());
            $this->tabs->output();
            $buttons=array();
            $buttons[]=$forms->button("Update", array("onclick"=>"fn_action('update',{$this->input->record_id},'');"));
            $buttons[]=$forms->button("Cancel", array("onclick"=>"fn_action('cancel',0,'');"));
            print "<p>" . implode(" ", $buttons) . "</p>";
            break;
          default:
            $this->html_list();
        }
        print $forms->close();
        $menu->copyright();
    }
    function html_list() {
        global $database, $forms, $menu;
        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='20%'>Portal</td>";
        print "<td widht='80%'>" . $database->portal->select($this->input->report_portal_id,array("field"=>"report_portal_id")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Industry</td>";
        print "<td widht='80%'>" . $database->industry->select($this->input->report_industry_id,array("field"=>"report_industry_id")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Language</td>";
        print "<td widht='80%'>" . $forms->select("report_language_code", array_keys($menu->language), $this->input->report_language_code, TRUE) . "</td>";
        print "</tr>";
        print "</table>";
        print "<p>" . $forms->button("List", array("onclick"=>"fn_action('list',0,0);")) . "</p>";
        print "<table class='border tablesorter'>";
        print "<thead>";
        print "<tr>";
        print "<th>ID</th>";
        print "<th>Effective Date</th>";
        print "<th>Portal</th>";
        print "<th>Industry</th>";
        print "<th>Language</th>";
        print "<th>Access</th>";
        print "<th>Status</th>";
        print "<th>Del?</th>";
        print "</tr>";
        print "</thead>";
        print "<tr>";
        print "<td colspan=8>" . fn_href("Add New","javascript:fn_action('maintain', 0, '');") .  "</td>";
        print "</tr>";
        if ($this->input->action == "list") {
            $query_where=array();
            if ($this->input->report_date) $query_where[]=new query_where("hero.effective_date", ">=", $this->input->report_date);
            if ($this->input->report_portal_id) $query_where[]=new query_where("hero.portal_id", "=", $this->input->report_portal_id);
            if ($this->input->report_industry_id) $query_where[]=new query_where("hero.industry_id", "=", $this->input->report_industry_id);
            if ($this->input->report_language_code) $query_where[]=new query_where("hero.language_code", "=", $this->input->report_language_code);
            $query=array("select");
            $query[]="ifnull(portal.name, 'All') as 'portal_name',";
            $query[]="ifnull(industry.name, 'All') as 'industry_name',";
            $query[]="language.name as 'language_name',";
            $query[]="hero.* from hero";
            $query[]="left join portal on portal.id=hero.portal_id";
            $query[]="left join industry on industry.id=hero.industry_id";
            $query[]="left join language on language.code=hero.language_code";
            $query[]=$database->where($query_where);
            $query[]="order by effective_date desc, id";
            $database->hero->query($query);
            print "<tbody>";
            while ($database->hero->fetch = $database->hero->fetch_array()) {
                $database->hero->fetch();
                switch ($database->hero->data->status) {
                  case "Inactive":
                    $bg=$forms->background->darksalmon;
                    break;
                  case "Pending":
                    $bg=$forms->background->cornsilk;
                    break;
                  default:
                    $bg="";
                }
                print "<tr {$bg}>";
                print "<td>" . fn_href($database->hero->data->id,"javascript:fn_action('maintain', {$database->hero->data->id}, '');") .  "</td>";
                $text=array();
                if ($database->hero->data->effective_date) {
                    $text[]=fn_date($database->hero->data->effective_date, "ymd");
                  } else {
                    $text[]="All";
                }
                print "<td>" . implode(" - ", $text) . "</td>";
                print "<td>{$database->hero->fetch['portal_name']}</td>";
                print "<td>{$database->hero->fetch['industry_name']}</td>";
                print "<td>{$database->hero->fetch['language_name']}</td>";
                print "<td>" . (($database->hero->data->access) ? $database->hero->data->access : "All") . "</td>";
                print "<td>{$database->hero->data->status}</td>";
                $text=($database->hero->data->status == "Inactive" ) ? $forms->checkbox_delete($database->hero->data->id, "") : "&nbsp;";
                print "<td class=center>{$text}</td>";
                print "</tr>";
            }
            print "</tbody>";
        }
        print "</table>";
    }
    function html_info() {
        global $database, $forms, $menu;
        $results=array();
        $results[]=$forms->hidden("report_portal_id",$this->input->report_portal_id);
        $results[]=$forms->hidden("report_industry_id",$this->input->report_industry_id);
        $results[]=$forms->hidden("report_language_code",$this->input->report_language_code);
        if ($forms->error_text['key']) $results[]="<p><b>{$forms->error_text['key']}</b></p>";
        $results[]="<table class=noborder>";
/*
        $results[]="<tr>";
        $results[]="<td width='20%'>Effective Date</td>";
        $text=array();
        $text[]=$forms->text("effective_date", $database->hero->data->effective_date, 10, 10, "date");
        if ($forms->error_text['effective_date']) $text[]=$forms->error_text['effective_date'];
        $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";
*/
        $results[]="<tr>";
        $results[]="<td width='20%'>Portal</td>";
        $results[]="<td width='80%'>" . $database->portal->select($database->hero->data->portal_id) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Industry</td>";
        $results[]="<td width='80%'>" . $database->industry->select($database->hero->data->industry_id) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Language</td>";
        $results[]="<td width='80%'>" . $forms->select("language_code", array_keys($menu->language), $database->hero->data->language_code, FALSE) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Minimum Access</td>";
        $results[]="<td width='80%'>" . $forms->select("access", $database->user->constant->type, $database->hero->data->access) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Status</td>";
        $results[]="<td width='80%'>" . $forms->select("status", $database->hero->constant->status, $database->hero->data->status) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        return $results;
    }
    function html_content() {
        global $database, $forms;
        $results=array();
        $text[]=$forms->select("field", $this->field, $this->input->field, FALSE, array("onchange"=>"fn_form('initial');", "class"=>"input"));
        $text[]="<span id=preview_button></span>";
        $results[]="<p>" . implode(" ", $text) . "</p>";
        $results[]="<div id=preview_content></div>";
        return $results;
    }
    function js() {
        return <<< EOT

<script>
jQuery(document).ready(function() {
	fn_form('initial');
});
function fn_action(action,record_id,name) {
    if (action=="delete") {
        jQuery("#delete" + record_id).prop("checked", false);
        if (!confirm("Delete: #" + record_id)) return;
    }
    jQuery("#action").val(action);
    jQuery("#record_id").val(record_id);
    jQuery("#scs_form").submit();
}

function fn_form(action) {
    data=scscpq_class_data('input');
    data['action']=action;
	jQuery.ajax({
        type: 'post',
	    dataType: 'json',
	    data: data,
	    dataType: 'json',
	    success:function(response) { scscpq_fill(response) }
    });
}
</script>

EOT;
    }
    function css() {
        return <<< EOT

<style>
#preview_content {
    border-top: 1px solid black;
    padding-top: 1em;
    padding-bottom: 1em;
}
#preview_content:empty  {
    border: none;
    padding: 0em;
}
.scscpq_hero_preview { border: 2px solid green; }
</style>

EOT;
    }
}
?>