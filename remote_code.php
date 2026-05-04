<?php
require_once "scs_header.php";
$database->user->access("Administrator");
$obj=new local_class();

class local_class {
    function scs_table_version() {
        $results=array();
        $results['05/07/2025']="Initial release";
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
        global $database, $forms;
        $this->response=new stdClass;
        $this->email=fn_text($_POST['email']);
        $this->response->response_api_status="";
        $database->remote->read($this->email,"email");
        switch (TRUE) {
          case (!$database->remote->meta->rows):
            $this->response->remote_api_status="Invalid email address: {$this->email}";
            break;
          case ($_POST['action'] == "reset"):
            $this->response->remote_api_status=$this->json_reset();
            break;
        }
        die(json_encode($this->response));
    }
    function json_reset() {
        global $database, $forms;
        $database->remote->expiry();
        $database->remote->unlock_code($_POST['expiry']);
        $database->remote->update(TRUE);
        foreach ($database->remote->ajax_fill(FALSE) as $key => $value) {
            $field="remote_{$key}";
            $this->response->$field=$value;
        }
        return "Token updated";
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Remote Access Code", key($this->scs_table_version()));
        $menu->head();
        print $forms->message();
        print $this->js();
        print "<table class=border>";
        print "<tr>";
        print "<td width='20%'>Email Address</td>";
        print "<td width='80%'>" . $forms->text("email","",50,0, "", array("class"=>"remote input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Domain</td>";
        print "<td id=remote_domain></td>";
        print "</tr>";
        print "<tr>";
        print "<td>Company Name</td>";
        print "<td id=remote_company_name></td>";
        print "</tr>";
        print "<tr>";
        print "<td>Token Expiry</td>";
        print "<td id=remote_expiry></td>";
        print "</tr>";
        print "<tr>";
        print "<td>Unlock Code: <b><span id=remote_unlock_code></span></b></td>";
        print "<td id=remote_unlock_status></td>";
        print "</tr>";
        print "<tr>";
        print "<td>Reset Unlock Code?</td>";
        $text=array();
        $text[]=$forms->select("expiry", array(15, 30, 45, 60), 0, FALSE, array("class"=>"input")) . " Minutes";
        $text[]=$forms->button("Reset Unlock Code", array("onclick"=>"fn_reset('reset');"));
        print "<td>" . implode(" ", $text) . "<div id=remote_api_status></div></td>";
        print "</tr>";
        print "</table>";

        print $forms->close();
        $menu->copyright();
    }
    function js() {
        return <<< EOT

<script>
function fn_reset(action) {
    if (!confirm("Continue?")) return;
    data=scscpq_class_data('input');
    data['action']=action;
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: data,
	    success: function(response) { scscpq_fill(response) },
        error: function(xhr, response) {  }
	});

}
</script>
EOT;
    }
}
?>