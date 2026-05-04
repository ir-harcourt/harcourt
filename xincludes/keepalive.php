<?php
$obj=new keepalive_class();
class keepalive_class {
	function scs_table_version() {
		$results=array();
        $results['03/03/2024']="Initial Release";
        return $results;
    }
    function __construct() {
        if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
        } else {
            $this->html();
        }
    }
    function json() {
        session_start();
        $response=new stdClass();
        $response->revised=key($this->scs_table_version());
        $response->active=intval(isset($_SESSION['user']));
        $date_start=new DateTime();
        $date_start->setTimestamp(floor( $_POST['timestamp'] / 1000));
        $date_now=new DateTime();
        $interval=$date_now->diff($date_start);
        $response->elapsed=$interval->format('%h hours %i minutes %s seconds');
        die( json_encode($response) );
    }
    function html() {
        http_response_code(401);
        die();
    }
}
/*
60000 = 1 minute

window.onload = function() {
    sessionStorage.setItem('timestamp',Date.now());
    window.setInterval("keepalive()", 300000);
};
function scscpq_keepalive() {
    jQuery.ajax({
        url: "scs_keepalive.php",
        dataType: 'json',
		method: 'post',
        data: { timestamp: sessionStorage.getItem('timestamp') },
        success:function(response) {  }
    });
}
*/
?>