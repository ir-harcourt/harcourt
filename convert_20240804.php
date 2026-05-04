<?php
require_once "scs_header.php";
$database->user->access("Super User");

$obj=new local_class();
class local_class {
    var $ip=array();
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
        global $database, $forms, $menu;
        set_time_limit(90);
        $this->stopwatch=new stopwatch_class("Merge Users");
        $this->response=new stdClass();
        $this->json_update();
        $this->response->ip=$this->ip;
        $this->response->count_id=number_format(sizeof($this->ip));
        if (!sizeof($this->ip)) $this->json_alter();
        $this->response->stopwatch=implode("", $this->stopwatch->results(TRUE));
        die(json_encode($this->response));
    }
    function json_update() {
        global $database;
        $this->ip=array();
        $this->query();
        $this->stopwatch->split("Load IP", $database->temp->meta->query);
        $this->stopwatch->split_comment("Found:" . number_format($database->temp->meta->rows));
        while ($database->temp->fetch = $database->temp->fetch_array()) {
            $this->ip[ $database->temp->fetch['ip'] ]=array();
        }
        if (!sizeof($this->ip)) return;
        $query_where=array();
        $query_where[]=new query_where("ip", "in", array_keys($this->ip));
        $query=array("select ip, id, domain from user");
        $query[]=$database->where($query_where);
        $query[]="order by domain desc, id";
        $database->user->query($query);

        $this->stopwatch->split("Load IP Users", $query);
        $this->stopwatch->split_comment("Found:" . number_format($database->user->meta->rows));
        $this->response->meta=$database->user->meta;
        while ($database->user->fetch = $database->user->fetch_array()) {
            $this->ip[ $database->user->fetch['ip'] ][]=$database->user->fetch['id'];
        }
        $this->stopwatch->split("Process Users");
        foreach ($this->ip as $ip => $items) {
            $id=array_shift($items);
            $this->response->id=$id;
      //      $database->transaction("start");
            $query_where=array();
            $query_where[]=new query_where("user_id", "in", $items);
            $query=array("update log set user_id={$id}");
            $query[]=$database->where($query_where);
            if (!isset($first_pass)) $this->stopwatch->split_comment($query);
            $database->temp->query($query);
            $query_where=array();
            $query_where[]=new query_where("id", "in", $items);
            $query=array("delete from user");
            $query[]=$database->where($query_where);
            if (!isset($first_pass)) $this->stopwatch->split_comment($query);
            $database->temp->query($query);
  //          $database->transaction("commit");
            $first_pass=1;
            unset($this->ip[$ip]);
        }
    }
    function json_alter() {
        global $database;
        $query=array("ALTER TABLE `user`");
        $query[]="DROP COLUMN `name`,";
        $query[]="DROP COLUMN `last_name`,";
        $query[]="DROP COLUMN `first_name`,";
        $query[]="DROP COLUMN `email`,";
        $query[]="DROP INDEX `ip_email_key`;";
        $database->temp->query($query);
        $this->stopwatch->split("Alter Table", $query);
    }
    function html() {
        global $database, $forms, $menu;
        $database->temp->query("show create table user");
        $database->temp->fetch(TRUE);
        $found=(preg_match("/first_name/", $database->temp->fetch['Create Table']));

        $forms->title("User Conversion (08/04/2024)");
        if (!$found) $forms->message[]="Table already converted";
        $menu->head();
        print $forms->message();
        print $this->js();
        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='20%'>Remaining records</td>";
        print "<td width='80%' id=count_id>" . number_format($this->query()) . "</td>";
        print "</tr>";
        print "</table>";
        $text=array();
        $text[]=$forms->button("Go!", array("id"=>"button_id", "onclick"=>"fn_process();"));
        $text[]="<span id=span_id style='display:none;'>Working ...</span>";
        if ($found) print "<p>" . implode("", $text) . "</p>";
        print "<div id=stopwatch></div>";
        print $forms->close();
        $menu->copyright();
    }
    function query() {
        global $database;
        $query=array();
        $query[]="select ip, count(*) as count from user";
        $query[]="group by ip";
        $query[]="having count > 1";
        $database->temp->query($query);
        return $database->temp->meta->rows;
    }
    function js() {
        return <<< EOT
<script>
function fn_process() {
    jQuery("#button_id").hide();
    jQuery("#span_id").show();
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: 'update' },
	    success: function(response) {
            scscpq_fill(response)
            jQuery("#button_id").show();
            jQuery("#span_id").hide();
        },
        error: function (request, status, error) { console.log(request) }
    });
}
</script>
EOT;
    }
}
?>