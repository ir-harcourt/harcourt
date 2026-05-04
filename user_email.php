<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/export.php";
$database->user->access("Executive");
$obj=new local_class();

class local_class {
    function scs_table_version() {
    	$results=array();
        $results['12/03/2024']="Initial release";
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
        $this->response=new stdClass();
        die (json_encode($this->response));
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass();
        $this->input->map="email";
        $this->input->action=$_POST['action'];
        $this->input->source=$_POST['source'];
        $this->input->from_date=fn_date($_POST['from_date'], "mdy");
        $this->input->thru_date=fn_date($_POST['thru_date'], "mdy");
        $this->input->file_type=$_POST['file_type'];
        switch (TRUE) {
          case (!$this->input->action):
          case (!$this->input->source):
          case (!$this->input->thru_date):
            break;
          case ($this->input->from_date > $this->input->thru_date):
            $forms->error("date", "Invalid date range");
            break;
        }
        $this->map=new map_class("export", $this->input, "email");
        if ( (!$this->input->action) || (sizeof($forms->error)) ) return;
        $this->map->export($database->export->initialize("email"));
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Email Export", key($this->scs_table_version()));
        $this->input();
	    $menu->head();
	    $forms->message();
        print $this->js();
	    print $forms->open();
	    print $forms->hidden("action","export");
        print "<table class='noborder'>";
        print "<tr>";
        $text=array();
        $text['']="All Dates";
        $text['first']="First Date";
        $text['last']="Last Date";
        print "<td width='20%'>" . $forms->select("source", $text, $this->input->source, FALSE, array("onchange"=>"fn_date_range();")) . "</td>";
        $text=array();
        $text[]="From: " . $forms->text("from_date", $this->input->from_date, 10, 10, "date");
        $text[]="Thru: " . $forms->text("thru_date", $this->input->thru_date, 10, 10, "date");
        if ($forms->error_text['date']) $text[]="<b>{$forms->error_text['date']}</b>";
        print "<td width='80%' id=date_range>" . implode(" ", $text) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td width='20%'>Export file type</td>";
        print "<td>" . $forms->select("file_type", $this->map->file_type_list, $this->input->file_type, FALSE) . "</td>";
        print "</tr>";
        print "</table>";
        print "<p>" . $forms->submit("Go") . "</p>";
        print $forms->close();
        $menu->copyright();
    }
    function js() {
        return <<< EOT

<script>
jQuery(document).ready(function() {
	fn_date_range();
});
function fn_date_range() {
    if (jQuery("#source").val() == "") {
        jQuery("#date_range").hide();
      } else {
        jQuery("#date_range").show();
    }
}
</script>
EOT;
    }
}
?>