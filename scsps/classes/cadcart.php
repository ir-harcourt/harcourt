<?php
require_once "classes/cadorder_update.php";
class cadcart_data_class {
	var $revised;
    var $pending=0;
    var $file=0;
    var $retrieve=0;
    var $count=0;
    function __construct() {
    global $database;
		$this->revised=date("m/d/Y g:i:s A");
        if (!isset($_SESSION['user']->id)) return;
        $query=
            "select \n" .
            "count(*) as 'count', \n" .
            "status as 'status' \n" .
            "from cadorder \n" .
            "where user_id=" . fn_escape($_SESSION['user']->id) . "\n" .
            "group by status";
        $database->temp=new database_temp_class();
        $database->temp->query($query);
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
            $status=strtolower($database->temp->fetch['status']);
            $count=intval($database->temp->fetch['count']);
            if (isset($this->$status)) {
                $this->$status=$count;
                $this->count += $count;
            }
        }
        $database->temp->free_result();
    }
}
?>