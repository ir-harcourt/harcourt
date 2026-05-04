<?php
require_once "scs_header.php";
require_once "classes/portal.php";
require_once "classes/portalcategory.php";
require_once "classes/portalitem.php";
require_once "classes/portalcontent.php";
require_once "classes/subcategory.php";
$database->user->access("Administrator");

$obj=new local_class();

class local_class {
    var $description=array();
    var $subcategory=array();
    function scs_table_version() {
		$results=array();
        $results['03/28/2025']="Bug fix";
        $results['10/14/2024']="Rewrite";
        return $results;
    }
    function __construct() {
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
        die(json_encode($this->response));
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass();
        $this->input->action=$_POST['action'];
        $this->input->record_id=$_POST['record_id'];
        $this->input->subcategory=array();
        $database->portal->read($this->input->record_id);
        switch ($this->input->action) {
          case "":
            return;
          case "cancel":
            $forms->message[]="Request cancelled";
            return;
          case "maintain":
            $this->input_maintain();
            return;
          case "delete":
            $this->input_delete();
            return;
          case "update":
            $this->input_update();
            return;
        }
    }
    function input_maintain() {
        global $database, $forms;
        $query=array("select * from content");
        $query[]="where record_type='portal'";
        $query[]="and record_id=" . fn_escape($this->input->record_id);
	    $database->content->query($query);
        while ($database->content->fetch = $database->content->fetch_array() ) {
            $database->content->fetch();
            $this->input->description[$database->content->data->language_code]=$database->content->data->description;
        }
        $query=array("select id from subcategory");
        if ($this->input->record_id) $query[]="where portal like '%^{$this->input->record_id}^%'";
        $database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array()) {
            $this->input->subcategory[]=$database->temp->fetch['id'];
        }
    }
    function input_delete() {
        global $database, $forms;
    }
    function input_update() {
        global $database, $forms;
        $database->portal->data->name=fn_text($_POST['name']);
        if (!strlen($database->portal->data->name)) $forms->error("name", "Cannot be blank");
        $database->portal->data->active=intval($_POST['active']);
        foreach ($_POST as $key => $value) {
            list($field, $code)=(fn_base64($key, FALSE));
            switch ($field) {
              case "description":
                $this->input->description[$code]=fn_text($value);
                break;
              case "subcategory":
                $this->input->subcategory[]=$code;
                break;
            }
        }
        $this->input->subcategory=array();
        foreach ($_POST as $key => $value) {
            list($field, $code)=fn_base64($key, FALSE);
            if ($field == "subcategory") $this->input->subcategory[]=$code;
        }
//        if (!sizeof($this->input->subcategory)) $forms->error("subcategory_error", "No subcategories selected");
        if (sizeof($forms->error)) return;
        $database->portal->update($this->input->record_id);
        if ($database->portal->meta->error) {
            $forms->error("name","Duplicate name");
            return;
        }
        foreach ($this->input->description as $language => $description) {
            $query=array("select * from content");
            $query[]="where record_type='portal'";
            $query[]="and record_id=" . fn_escape($database->portal->data->id);
            $query[]="and language_code=" . fn_escape($language);
            $database->content->query($query);
            $database->content->fetch(TRUE);
            switch (TRUE) {
              case ( ($language == "EN") || (strlen($description)) ):
                $database->content->data->record_type="portal";
                $database->content->data->record_id=$database->portal->data->id;
                $database->content->data->language_code=$language;
                $database->content->data->name=$database->portal->data->name;
                $database->content->data->description=$description;
                $database->content->data->mime="";
                $database->content->data->revised=strtotime("now");
                $database->content->update($database->content->data->id);
                break;
              case ($database->content->data->id):
                $database->content->delete($database->content->data->id);
                break;
            }
        }
        $query=array("update subcategory");
        $query[]="set portal=replace(portal, '^{$database->portal->data->id}^', '^')";
        $query[]="where portal like '%^{$database->portal->data->id}^%'";
        $database->temp->query($query);
        if (sizeof($this->input->subcategory)) {
            $query_where=array();
            $query_where[]=new query_where("id", "in", $this->input->subcategory);
            $query=array("update subcategory");
            $query[]="set portal=concat(portal, '{$database->portal->data->id}^')";
            $query[]=$database->where($query_where);
            $database->temp->query($query);
        }
        $forms->message[]="Portal {$database->portal->data->name} updated";
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Customer Portal Manager", key($this->scs_table_version()));
        $this->input();
        $menu->head();
        $forms->message();
        print $this->js();
        print $forms->open(array("data"=>TRUE));
        print $forms->hidden("action");
        print $forms->hidden("record_id");
        switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            $this->tabs=new jquery_tab_class("standard");
            if (isset($_SESSION['scscpq_wp'])) $this->tabs->meta->options->lineheight="auto";
            foreach ($menu->language as $obj) {
                $this->tabs->item($obj->name,$this->html_language($obj->code));
            }
            $this->tabs->item("Subcategory",$this->html_subcategory());
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"fn_action('update'," . fn_escape($this->input->record_id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action('cancel','','');"));
            $this->tabs->output();
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
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th width='90%'>Name</th>";
        $results[]="<th width='10%'>Del?</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        $results[]="<tr><td colspan=2>" . fn_href("Add New","javascript:fn_action('maintain','0','0');") . "</td></tr>";
        $query=array("select * from portal");
        $query[]="order by name";
        $database->portal->query($query);
        while ($database->portal->fetch = $database->portal->fetch_array() ) {
            $database->portal->fetch();
            $results[]="<tr>";
            $results[]="<td>" . fn_href($database->portal->data->name,"javascript:fn_action('maintain'," . fn_escape($database->portal->data->id) . ",'');") . "</td>";
            $results[]="<td class=center>" . ( ($database->portal->data->active) ? "&nbsp;" : $forms->checkbox_delete($database->portal->data->id, $database->portal->data->name)) . "</td>";
            print "</tr>";
	    }
        $results[]="</tbody>";
        $results[]="</table>";
        return $results;
    }
    function html_language($language) {
        global $database, $forms;
        $results=array();
        $results[]="<table class='standard noborder'>";
        if ($language == "EN") {
            $results[]="<tr>";
            $results[]="<td width='20%'>Portal name</td>";
            $text=array($forms->text("name",$database->portal->data->name,60));
            if ($forms->error_text['name']) $text[]=$forms->error_text['name'];
            $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td width='20%'>Headline</td>";
        $results[]="<td width='80%'>" . $forms->textarea(fn_base64(array("description",$language)),$this->input->description[$language],4,60) . "</td>";
        $results[]="</tr>";
        if ($language == "EN") {
            $results[]="<tr>";
            $results[]="<tr>";
            $results[]="<td>Active?</td>";
            $results[]="<td>" . $forms->checkbox("active",1,$database->portal->data->active) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_subcategory() {
        global $database, $forms;
        $query_where=array();
        $query_where[]=new query_where("category.active","=",1);
        $query_where[]=new query_where("subcategory.status","<>","Inactive");
        $query_where[]=new query_where("category.name is not null","","");
        $query=array("select");
        $query[]="subcategory.id as 'subcategory.id',";
        $query[]="subcategory.name as 'subcategory.name',";
        $query[]="category.id as 'category.id',";
        $query[]="category.name as 'category.name'";
        $query[]="from subcategory";
        $query[]="left join category on category.id=subcategory.category_id";
        $query[]=trim($database->where($query_where));
        $query[]="order by category.sort_order,category.name,subcategory.sort_order,subcategory.name";
        $database->temp->query($query);
        $items=array();
        while ($database->temp->fetch = $database->temp->fetch_array()) {
            $category_id=$database->temp->fetch['category.id'];
            if (!array_key_exists($category_id, $items)) {
                $obj=new stdClass;
                $obj->name=$database->temp->fetch['category.name'];
                $obj->found=0;
                $obj->items=array();
                $items[$category_id]=$obj;
              } else {
                $obj=$items[$category_id];
            }
            $obj->items[ $database->temp->fetch['subcategory.id'] ]=$database->temp->fetch['subcategory.name'];
            if (in_array($database->temp->fetch['subcategory.id'], $this->input->subcategory)) $obj->found++;
        }
        $results=array();
        $results[]=$forms->hidden("subcategory_error");
        if ($forms->error_text['subcategory_error']) $results[]="<p>{$forms->error_text['subcategory_error']}</p>";
        $results[]="<table class='standard border'>";
        $results[]="<tr><th style='text-align: left;'>" . $forms->checkbox("all",1,0,array("onclick"=>"fn_subcategory(this);")) . " All subcategories</th></tr>";
        $results[]="<tr><td>&nbsp;</td></tr>";
        foreach ($items as $category_id => $category) {
            $results[]="<tr><th style='text-align: left;'>" . $forms->checkbox("category_{$category_id}",1,
            ($category->found == sizeof($category->items) ? 1 : 0),
            array("onclick"=>"fn_subcategory(this);","class"=>"subcategory")) . "{$category->name}</th></tr>";
            $text=array();
            foreach ($category->items as $subcategory_id => $subcategory_name) {
                $field=fn_base64(array("subcategory", $subcategory_id));
                $options=array();
                $options["onclick"]="fn_subcategory(this);";
                $options["class"]="subcategory category_{$category_id}";
                $text[]=$forms->checkbox($field,1,in_array($subcategory_id, $this->input->subcategory), $options) . " {$subcategory_name}";
            }
            $results[]="<tr><td>" . implode("<br>",$text) . "</td></tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function js() {
        return <<< EOT
<script>
function fn_action(action, record_id, name) {
    if (action=='delete') {
        jQuery("#delete" + record_id).prop("checked", false);
        if (!confirm("Delete: #" + record_id + " " + name)) return;
    }
    jQuery("#action").val(action);
    jQuery("#record_id").val(record_id);
    jQuery("#scs_form").submit();
}
function fn_subcategory(obj) {
    field=obj.id.split("_");
    switch (field[0]) {
        case "all":
        $('input.subcategory').prop('checked',obj.checked);
        break;
        case "category":
        $('input.' + obj.id).prop('checked',obj.checked);
        break;
        case "subcategory":
// reserved
        break;
    }
}
</script>

EOT;
    }
}
/* tomhayes

  case "delete":
    $database->portal->delete($this->input->record_id);
    $query=
		"delete from portalcategory \n" .
        "where portal_id=" . fn_escape($this->input->record_id);
	$database->temp->query($query);
    $query=
		"delete from portalitem \n" .
        "where portal_id=" . fn_escape($this->input->record_id);
	$database->temp->query($query);
     $query=
        "delete from portalcontent \n" .
        "where portal_id=" . fn_escape($this->input->record_id);
    $database->temp->query($query);
     $query=
        "delete from content \n" .
        "where record_type='portal' \n" .
        " and record_id=" . fn_escape($this->input->record_id);
    $database->temp->query($query);
    $database->subcategory->access_delete("portal",$database->portal->data->id);
    $forms->message[]="Portal " . $database->portal->data->name . " deleted";
    break;

*/
?>