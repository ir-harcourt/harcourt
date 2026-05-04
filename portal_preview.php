<?php
require_once "scs_header.php";
require_once "classes/portaloutput.php";
$database->user->access("Administrator");
$obj=new local_class();

class local_class {
    function scs_table_version() {
        $results=array();
        $results['11/18/2025']="Initial Release";
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
        global $database;
        $this->response=new stdClass;
        $this->input=new stdClass;
        $this->input->action=$_POST['action'];
        $this->input->category_id=$_POST['category_id'];
        $this->input->subcategory_id=$_POST['subcategory_id'];
        $this->input->portal_id=$_POST['portal_id'];
        $this->input->language_code=$_POST['language_code'];
        $method="json_{$this->input->action}";
        if (method_exists($this, $method)) {
            $this->response->preview_detail=$this->$method();
          } else {
            $this->response->preview_detail="Method not found: {$method}";
        }
        die(json_encode($this->response));
    }
    function json_content($record_type, $record_id) {
        global $database;
        $query_where=array();
	    $query_where[]=new query_where("record_type","=",$record_type);
	    $query_where[]=new query_where("record_id","=",$record_id);
	    $query_where[]=new query_where("record_item","=","");
	    $query_where[]=new query_where("language_code","in",array("EN",$this->input->language_code));
        $query=array("select");
        $query[]="if (language_code='EN',2,1) as sort_key,";
        $query[]="content.* from content";
        $query[]=$database->where($query_where);
        $query[]="order by sort_key";
        $query[]="limit 1";
        $database->content->query($query);
        $database->content->fetch(TRUE);
    }
    function json_subcategory() {
        global $database;
        $results=array();
        $database->category->read($this->input->category_id);
        $this->json_content("category",$this->input->category_id);
        $results[]="<p><b>Category: {$database->category->data->name} (#{$this->input->category_id})</b></p>";
        if ($database->content->meta->rows) {
            $results[]="<div>{$database->content->data->name}</div>";
            if ($database->content->data->description) $results[]="<div>{$database->content->data->description}</div>";
          } else {
            $results[]="<div>Not on file";
        }
        $database->subcategory->read($this->input->subcategory_id);
        $this->json_content("subcategory",$this->input->subcategory_id);
        $results[]="<p><b>Subcategory: {$database->subcategory->data->name} (#{$this->input->subcategory_id})</b></p>";
        if ($database->content->meta->rows) {
            $results[]="<div>{$database->content->data->name}</div>";
            if ($database->content->data->description) $results[]="<div>{$database->content->data->description}</div>";
          } else {
            $results[]="<div>Not on file";
        }
        return implode("", $results);
    }
    function json_portal() {
        global $database;
        $options=array();
        $options['portal_id']=$this->input->portal_id;
        $options['portalcategory_id']=$this->input->subcategory_id;
        $options['language_code']=$this->input->language_code;
        $options['preview']="fn_preview";
        $this->portal=new portaloutput_class($options);
        $content=$this->portal->detail($this->input->subcategory_id);
        $results=array();
        $text=array("<b>Portal: {$database->portal->data->name} (#{$this->input->portal_id})</b>");
        $text[]="<b>Portal Category: {$database->portalcategory->data->name} (#{$this->input->subcategory_id})</b>";
        $results[]="<p>" . implode("<br>", $text) . "</p>";
        $results[]=(strlen($content)) ? $content : "<p><b>No items found</b></p>";
        return implode("", $results);
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass;
        $this->input->action=$_POST['action'];
        $this->input->portal_id=$_POST['portal_id'];
        $this->input->language_code=$_POST['language_code'];
        $this->input->debug=intval($_POST['debug']);
        $options=array();
        $options['portal_id']=$this->input->portal_id;
        $options['language_code']=$this->input->language_code;
        $options['preview']="fn_preview";
        $this->portal=new portaloutput_class($options);
        if ( ($this->input->action) && ($this->portal->error) ) $forms->error("portal_id", $this->portal->error);
    }
    function html() {
        global $database, $forms, $menu;
        $forms->html->script("css", "/scs_scripts/catalog.css?v=" . $database->registry->local->filmstrip_timestamp);
        $forms->title("Portal Preview", key($this->scs_table_version()));
        $this->input();
        $menu->head();
        print $forms->message();
        print $this->css();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action", "load");
        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='20%'>Portal</td>";
        $text=array($database->portal->select($this->input->portal_id));
        if ($forms->error_text['portal_id']) $text[]=$forms->error_text['portal_id'];
        print "<td width='80%'>" . implode(" ", $text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Language</td>";
        print "<td>". $database->language->select($this->input->language_code,array("blank"=>FALSE)) . "</td>";
        print "</tr>";
        if ($database->user->access("Super User", FALSE)) {
            print "<tr>";
            print "<td>Debug?</td>";
            print "<td>" . $forms->checkbox("debug", 1, $this->input->debug) . "</td>";
            print "</tr>";
        }
        print "</table>";
        print "<p>" . $forms->submit("Go") . "</p>";

        if ( ($this->input->action) && (!$this->portal->error) ) {
            $content=$this->portal->image(array("class"=>"left"));
            $results=array("<div class=preview_content>");
            $results[]="<div><b>Logo/Image:</b> (" . sizeof($this->portal->image_list) . ")</div>";
            if (strlen($content)) $results[]=$content;
            $results[]="</div>";
            print implode("", $results);
            $results=array("<div class=preview_content>");
            $results[]="<b>Headline:</b> " . ((strlen($this->portal->header->description)) ? "" : "None");
            $results[]=$this->portal->headline();
            $results[]="</div>";
            print implode("", $results);
            $results=array("<div class=preview_content>");
            $results[]="<b>Summary:</b> (" . sizeof($this->portal->category_list) . ")";
            $results[]=$this->portal->summary();
            $results[]="</div>";
            $results[]="<div id=preview_detail class=preview_content></div>";
            print implode("", $results);
        }
        if ( ($this->input->action) && ($this->input->debug) ) {
            print "<div class=preview_content>";
            print "<b>Queries</b>";
            foreach ($this->portal->query as $name => $query) {
                array_unshift($query, "<b>{$name}</b>");
                print "<p>" . implode("<br>", str_replace("\n", "<br>", $query)) . "</p>";
            }
            print "</div>";
        }
        print $forms->close();
        $menu->copyright();
    }
    function css() {
        return <<< EOT

<style>
.preview_content {
    border-top: 1px solid black;
    margin-top: 5px;
    margin-bottom: 5px;
}
.preview_content:empty  {
    border: none;
    margin: 0px;
}
</style>

EOT;
    }
    function js() {
        return <<< EOT

<script>
function fn_preview(action, category_id, subcategory_id) {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: action, category_id: category_id, subcategory_id: subcategory_id, portal_id: jQuery("#portal_id").val(), language_code: jQuery("#language_code").val() },
	    success: function(response) { scscpq_fill(response) }
    });
}
</script>


EOT;
    }
}
?>