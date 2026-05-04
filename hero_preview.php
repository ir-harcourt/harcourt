<?php
require_once "scs_header.php";
$database->user->access("Administrator");
$obj=new local_class();
class local_class {
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
        global $database;
        $this->response=new stdClass;
        $this->response->preview_detail="";
        $this->response->preview_debug="";
        $this->response->preview_summary=$this->json_load();
        die(json_encode($this->response));
    }
    function json_load() {
        global $database;
        $this->debug=true_false($_POST['debug']);
        $options=array();
        $options['date']=fn_date($_POST['date'], "mdy");
        $options['portal_id']=intval($_POST['portal_id']);
        $options['industry_id']=intval($_POST['industry_id']);
        $options['language_code']=$_POST['language_code'];
        $options['role']=$_POST['role'];
        $options['pending']=true_false($_POST['pending']);
        $match=$database->hero->load($options);



        if ($this->debug) $this->response->preview_debug=str_replace("\n", "<br>", $database->hero->meta->query);
        if (!$match) return "No matches found";
        $results=array();
        $results[]="<table class='noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Hero ID</td>";
        $results[]="<td width='80%'>{$database->hero->data->id}</td>";
        $results[]="</tr>";
        if ($database->hero->data->effective_date) {
            $results[]="<tr>";
            $results[]="<td>Effective Date</td>";
            $results[]="<td>" . fn_date($database->hero->data->effective_date, "ymd") . "</td>";
            $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td>Portal</td>";
        $results[]="<td>{$database->hero->fetch['portal_name']}</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Industry</td>";
        $results[]="<td>{$database->hero->fetch['industry_name']}</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Language</td>";
        $results[]="<td>{$database->hero->fetch['language_name']}</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Access</td>";
        $results[]="<td>" . ( ($database->hero->data->access) ? $database->hero->data->access : "All") . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Status</td>";
        $results[]="<td>{$database->hero->data->status}</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $options=array();
        $options['preview']=TRUE;
        $this->response->preview_output=$database->hero->output($options);
        return implode("", $results);
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Hero Preview", key($this->scs_table_version()));
        $menu->head();
        print $forms->message();
        print $this->js();
        print $this->css();
        print $forms->open();
        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='50%'>";

        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='20%'>Date</td>";
        print "<td width='80%'>" . $forms->text("date", date("Y/m/d"), 10, 10, "date", array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Portal</td>";
        print "<td width='80%'>" . $database->portal->select(0, array(), array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Industry</td>";
        print "<td width='80%'>" . $database->industry->select(0, array(), array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Language</td>";
        print "<td width='80%'>" . $forms->select("language_code", array_keys($menu->language), "", FALSE, array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>User Role</td>";
        print "<td width='80%'>" . $forms->select("role", $database->user->constant->type, "", FALSE, array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Include pending?</td>";
        print "<td width='80%'>" . $forms->checkbox("pending", 1, 0, array("class"=>"input")) . "</td>";
        print "</tr>";
        if ($database->user->access("Super User", FALSE)) {
            print "<tr>";
            print "<td width='20%'>Debug Query?</td>";
            print "<td width='80%'>" . $forms->checkbox("debug", 1, 0, array("class"=>"input")) . "</td>";
            print "</tr>";
        }
        print "</table>";
        print "</td>";
        print "<td width='50%' id=preview_summary></td>";
        print "</table>";
        print "<p>" . $forms->button("Load", array("onclick"=>"fn_action('load');")) . "</p>";
        print $forms->close();
        print "<div id=preview_output class=preview_content></div>";
        print "<div id=preview_debug class=preview_content></div>";
        $menu->copyright();
    }
    function js() {
        return <<< EOT

<script>
function fn_action(action) {
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
.preview_content {
    border-top: 1px solid black;
    margin-top: 5px;
    margin-bottom: 5px;
}
.preview_content:empty  {
    border: none;
    margin: 0px;
}
.scscpq_hero_preview { border: 2px solid green; }
</style>

EOT;
    }
}
?>