<?php
require_once "classes/scsmail_rev1.php";
require_once "recaptcha.php";
require_once "map_rev3.php";
class cpddomain_toolbox_class {
	var $function;
    var $json_action;
	var $options;
	var $action;
    var $trace=array();
	function scs_table_version($function="") {
		$results=array();
        switch ($function) {
          case "list":
            $results['12/14/2024']="Initial release";
            break;
          case "member":
            $results['12/20/2024']="Initial release";
          	break;
          case "maintain":
            $results['02/02/2025']="Add website";
            $results['01/26/2025']="Field now external";
            $results['01/08/2025']="Force external role";
            $results['12/27/2024']="Add variable";
            $results['12/15/2024']="Add image";
            $results['12/13/2024']="Initial release";
            break;
        }
        return $results;
    }
	function __construct($function, $role, $options=array()) {
        global $database, $forms, $menu;
    	$this->function=$function;
        $this->role=$role;
        $this->admin=(array_key_exists("admin", $options)) ? $options['admin'] : "";
        if ( (is_array($database->registry->cpqvariable->document)) && (array_key_exists("cpqdomain", $database->registry->cpqvariable->document)) ) $this->variable=new cpqvariable_input_class("cpqdomain");
        $this->response=new stdClass();
        $this->input=new stdClass();
        if (method_exists($database->user, "access_domain")) {
            $database->user->access_domain($this->role, $this->admin);
          } else {
            $database->user->access($this->role);
        }
		if (!method_exists($this,$function)) die("Invalid function: {$function}");
        $this->options=new stdClass();
        foreach ($options as $key => $value) {
            switch ($key) {
              case "scripts":
                foreach ($value as $type => $items) {
                    $forms->html->script($type, $items);
                }
                break;
              default:
        	    $this->options->$key=$value;
            }
        }
        if (!array_key_exists("HTTP_X_REQUESTED_WITH", $_SERVER)) {
            $this->{$function}();
          } else {
            $json_action=$_POST['action'];
            switch (TRUE) {
              case (method_exists($this, $json_action)):
                $this->{$json_action}();
                break;
              case (method_exists($this->local, $json_action)):
                $this->local->{$json_action}();
                break;
            }
            die( json_encode($this->response) );
        }
    }
    function maintain() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $title=(strlen($this->options->title)) ? $this->options->title : "Registered Domains";
        $forms->title($title, key($this->scs_table_version(__FUNCTION__)));
        $this->maintain_input();
        $menu->head();
        $forms->message();
		print $this->js();
	    print $forms->open(array("data"=>TRUE));
	    print $forms->hidden('action');
        print $forms->hidden('record_id', $this->input->record_id);
	    switch (TRUE) {
          case ($this->input->action == "maintain"):
          case (($this->input->action == "update") && (sizeof($forms->error))):
            $this->tabs=new jquery_tab_class();
            $this->tabs->item("Info", $this->maintain_info());
            $this->tabs->output();
            $buttons=array();
            $buttons[]=$forms->button("Update",array("onclick"=>"toolbox_action('update'," . fn_escape($this->input->record_id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"toolbox_action('cancel','','');"));
            print "<p>" . implode(" ",$buttons) . "</p>";
            break;
          default:
            print implode("", $this->maintain_list());
        }
        print $forms->close();
        $menu->copyright();
    }
    function maintain_input() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->input->action=$_POST['action'];
        $this->input->record_id=intval($_POST['record_id']);
        $database->cpqdomain->read($this->input->record_id);
        $this->logo=new maintain_logo_class($this->input->record_id);
        switch ($this->input->action) {
          case "":
          case "maintain":
            if (isset($this->variable)) $this->variable->load($database->cpqdomain->data->variable);
            @unlink($this->logo->type['upload']->url);
            return;
          case "cancel":
            $forms->message[]="Request Cancelled";
            return;
          case "delete":
            $database->cpqdomain->delete($database->cpqdomain->data->id);
            $forms->message[]="Domain {$database->cpqdomain->data->name} deleted";
            return;
          case "update":
            $this->maintain_update();
            return;
        }
    }
    function maintain_update() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $database->cpqdomain->data->domain=strtolower(trim($_POST['domain']));
        switch (TRUE) {
          case (!verify_domain($database->cpqdomain->data->domain)):
            $forms->error("domain", "Invalid domain format");
            break;
          case ($database->cpqdomain->blacklist($database->cpqdomain->data->domain)):
            $forms->error("domain", "Domain blacklisted");
            break;
        }
        $address=new address_class("cpqdomain", $database->cpqdomain->data);
        $results[]=$address->verify();
        $database->cpqdomain->data->tollfree=fn_text($_POST['tollfree']);
        $database->cpqdomain->data->external=intval($_POST['external']);
        $database->cpqdomain->data->website=fn_text($_POST['website']);

        $url=parse_url($database->cpqdomain->data->website);
        switch (TRUE) {
          case (!strlen($database->cpqdomain->data->website)):
            break;
          case (!in_array($url['scheme'], array("http", "https"))):
            $forms->error("website", "Scheme must be http or https");
            break;
          case (!strlen($url['host'])):
            $forms->error("website", "Host missing");
            break;
        }
        $database->cpqdomain->data->role=fn_text($_POST['role']);
        $database->cpqdomain->data->discount_pct=fn_number($_POST['discount_pct'] ."%", 2);
        $database->cpqdomain->data->token=fn_text($_POST['token']);
        if (!strlen($database->cpqdomain->data->token)) $forms->error("token", "Cannot be blank");
        $database->cpqdomain->data->html=fn_text($_POST['html']);
        $database->cpqdomain->data->active=intval($_POST['active']);
        if (isset($this->variable)) {
            $this->variable->load($_POST);
            $this->variable->verify(TRUE);
            $this->variable->json($database->cpqdomain->data->variable);
        }
        if ( ($database->cpqdomain->data->external) && ($database->cpqdomain->data->role != "External") ) $forms->error("role", "Must be external");
        if (sizeof($forms->error)) return;
        $database->cpqdomain->update($this->input->record_id);
        switch (TRUE) {
          case (!$database->cpqdomain->meta->error);
            $forms->message[]="Record Updated";
            break;
          case (preg_match("/domain_UNIQUE/i", $database->cpqdomain->meta->error)):
            $forms->error("domain", "Duplicate entry");
            break;
          case (preg_match("/token_UNIQUE/i", $database->cpqdomain->meta->error)):
            $forms->error("token", "Duplicate entry");
            break;
          default:
            $forms->error("null","","external Error");
        }
        switch (TRUE) {
          case (sizeof($forms->error)):
          case (!$this->logo->url("upload")):
            break;
          default:
            $database->cpqdomain->data->logo=$this->logo->save($database->cpqdomain->data->id);
            $database->cpqdomain->update(TRUE);
        }
    }
    function maintain_list() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $results=array();
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Name</th>";
        $results[]="<th>Domain</th>";
        $results[]="<th>Role</th>";
        $results[]="<th>Discount</th>";
        if (isset($this->variable)) $results[]="<th>Variables</th>";
        $results[]="<th>Delete?</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tr>";
        $colspan=(isset($this->variable)) ? 6 : 5;
        $results[]="<td colspan={$colspan}>" . fn_href("Add new","javascript:toolbox_action('maintain','0','');") . "</td>";
        $results[]="</tr>";
        $results[]="<tbody>";
        $query=array("select * from cpqdomain");
        $query[]="order by company_name";
        $database->cpqdomain->query($query);
        while ($database->cpqdomain->fetch = $database->cpqdomain->fetch_array()) {
            $database->cpqdomain->fetch();
            $results[]="<tr " . ( ($database->cpqdomain->data->active) ? "" : $forms->background->yellow) . ">";
            $results[]="<td>" . fn_href($database->cpqdomain->data->company_name,"javascript:toolbox_action('maintain','{$database->cpqdomain->data->id}','');") . "</td>";
            $results[]="<td>{$database->cpqdomain->data->domain}</td>";
            $results[]="<td>{$database->cpqdomain->data->role}</td>";
            $results[]="<td class=right>" . number_format($database->cpqdomain->data->discount_pct * 100, 2) . "%</td>";
            if (isset($this->variable)) $results[]="<td>" . $this->variable->list(TRUE, $database->cpqdomain->data->variable) . "</td>";
            if ($database->cpqdomain->data->active) {
                $text="&nbsp;";
              } else {
                $text=$forms->checkbox_delete($database->cpqdomain->data->id, $database->cpqdomain->data->domain, array(), "toolbox_action");
              }
              $results[]="<td class=center>{$text}</td>";
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
        return $results;
    }
    function maintain_info() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Domain</td>";
        $text=array($forms->text("domain", $database->cpqdomain->data->domain, 50, 50));
        if ($forms->error_text['domain']) $text[]=$forms->error_text['domain'];
        $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";
        $address=new address_class("cpqdomain", $database->cpqdomain->data);
        $results[]=$address->input(array("legend"=>FALSE));
        $results[]="<tr>";
        $results[]="<td>Toll Free</td>";
        $results[]="<td>" . $forms->text("tollfree", $database->cpqdomain->data->tollfree, 40, 40, "", array("placeholder"=>"Optional")) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Website</td>";
        $text=array($forms->text("website", $database->cpqdomain->data->website, 80, 120, "", array("placeholder"=>"Optional")));
        if ($forms->error_text['website']) $text[]=$forms->error_text['website'];
        $results[]="<td>" . implode("<br>", $text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>External group?</td>";
        $results[]="<td>" . $forms->checkbox("external", 1, $database->cpqdomain->data->external, array("onclick"=>"maintain_external();")) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Role</td>";
        $text=array($forms->select("role", $database->user->role_list(), $database->cpqdomain->data->role, FALSE));
        if ($forms->error_text['role']) $text[]=$forms->error_text['role'];
        $results[]="<td>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Discount</td>";
        $text=array($forms->text("discount_pct",$database->cpqdomain->data->discount_pct * 100, 5, 5, "decimal:2"));
        if ($forms->error_text['discount_pct']) $text[]=$forms->error_text['discount_pct'];
        $results[]="<td>" . implode(" ", $text) . "%</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td>Confirm Email HTML</td>";
        $results[]="<td>" . $forms->textarea("html", $database->cpqdomain->data->html, 6, 80) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $text=array("Token");
        $text[]=fn_href("(Create New)","javascript:maintain_token();");
        $results[]="<td>" . $forms->font("red","*",implode(" ",$text)) . "</td>";
        $text=array($forms->text("token",$database->cpqdomain->data->token,40,40,"",array("readonly"=>TRUE,"placeholder"=>"Token must be generated")));
        if ($forms->error_text['token']) $text[]=$forms->error_text['token'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Active?</td>";
        $results[]="<td>" . $forms->checkbox("active", 1, $database->cpqdomain->data->active) . "</td>";
        $results[]="</tr>";
        if ( (isset($this->variable)) && (sizeof($this->variable->fields)) ) {
            $results[]="<tr><td colspan=2><b>User Variables</td></tr>";
            $results[]=$this->variable->input(TRUE);
        }
        if ($database->registry->cpqdomain->folder) $results[]=$this->maintain_logo_html();
        $results[]=$address->legend();
        $results[]="</table>";
        return $results;
    }
    function maintain_logo_html() {
        global $database, $forms;
        $results=array();
        $results[]="<tr>";
        $results[]="<td>Logo (optional)</td>";
        $text=array();
        $text[]=$forms->file("upload",30,array("onchange"=>"maintain_upload();","accept"=>".png"));
        $text[]="<div id=upload_status></div>";
        $text[]="<div>max-width: 160px; max-height: 120px;</div>";
        $results[]="<td>" . implode("", $text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>&nbsp;</td>";
        $text=array();
        $text[]="<table class=border style='width: 600px;'>";
        $text[]="<tr>";
        $text[]="<th width='300px;'>New</th>";
        $text[]="<th width='300px;'>Original</th>";
        $text[]="</tr>";
        $text[]="<tr>";
        $text[]="<td id=upload_meta></td>";
        $text[]="<td id=image_meta></td>";
        $text[]="</tr>";
        $text[]="<tr>";
        $text[]="<td id=upload_image height='300px'></td>";
        $text[]="<td id=image_image height='300px'></td>";
        $text[]="</tr>";
        $text[]="</table>";
        $results[]="<td>" . implode("", $text) . "</td>";
        $results[]="</tr>";
        return implode("", $results);
    }
    function maintain_token() {
        $this->response->token=token(20);
    }
    function maintain_logo() {
        $this->id=$_POST['id'];
        $this->type=$_POST['type'];
        $this->logo=new maintain_logo_class($_POST['id']);
        switch ($_POST['option']) {
          case "initial":
            $this->maintain_logo_initial();
            break;
          case "upload":
            $this->maintain_logo_upload();
            break;
          case "delete":
            $this->maintain_logo_delete();
            break;
        }
        $this->response->logo=$this->logo;
    }
    function maintain_logo_display($type) {
        global $database, $forms;
        $field_meta="{$type}_meta";
        $field_image="{$type}_image";
        $this->response->$field_meta="&nbsp;";
        $this->response->$field_image="&nbsp;";
        switch (TRUE) {
          case (!array_key_exists($type, $this->logo->type)):
          case (!$this->logo->type[$type]->exists):
            return;
        }
        $info=getimagesize($this->logo->type[$type]->url);
        $text=array();
        $text[]="Delete? " . $forms->checkbox("logo_delete_{$type}", 1 , 0, array("onclick"=>"maintain_logo('delete'," . fn_escape($type) . ");"));
        $text[]=$info[3];
        $this->response->$field_meta=implode("<br>", $text);
        $this->response->$field_image="<img src='" . $this->logo->type[$type]->image . "?v=" . strtotime("now") . "' style='max-width: 290px'>";
    }
    function maintain_logo_initial() {
        $this->maintain_logo_display("upload");
        $this->maintain_logo_display("image");
    }
    function maintain_logo_upload() {
        $this->response->upload_status=$this->maintain_logo_upload_process();
    }
    function maintain_logo_upload_process() {
        switch (TRUE) {
          case ($_FILES['upload']['error'] != "0"):
            return "Error: {$_FILES['upload']['error']}";
           case ($_FILES['upload']['type'] != "image/png"):
            return "Error: not PNG";
        }
        $url=$this->logo->type['upload']->url;
        @unlink($url);
        move_uploaded_file($_FILES['upload']['tmp_name'], $url);
        $this->logo->name("upload", 0);
        $this->maintain_logo_display("upload");
        return "Upload success";
    }
    function maintain_logo_delete() {
        @unlink($this->logo->type[$this->type]->url);
        $this->logo->name($this->type, $this->id);
        $this->maintain_logo_display($this->type);
    }
    function maintain_js() {
        return <<< EOT
jQuery(document).ready(function() {
    maintain_logo('initial', '');
});

function maintain_logo(option, type) {
    jQuery("#logo_delete_" + type).prop("checked", false);
    if ( (option == "delete") && (!confirm("Delete image?")) ) return;
    jQuery.ajax({
        type: 'post',
        dataType: 'json',
        data: { action: 'maintain_logo', option: option, type: type, id: jQuery("#record_id").val() },
        success: function(response) { scscpq_fill(response) }
    })
}

function maintain_upload() {
    jQuery("#upload_status").html("Working ...");
    var form=new FormData();
    form.append("action", "maintain_logo");
    form.append("option", "upload");
    form.append("type", "upload");
    form.append("id", jQuery("#record_id").val());
    jQuery.each(jQuery("#upload")[0].files, function(i, file) {
        form.append("upload", file);
    });
    jQuery.ajax({
        dataType: 'json',
        method: 'post',
        data: form,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) { scscpq_fill(response) }
    });
}

function maintain_external() {
    if (jQuery("#external").prop("checked")) jQuery("#role").val("External");
}

function maintain_token() {
    jQuery.ajax({
        type: 'post',
        dataType: 'json',
        data: { action: 'maintain_token' },
        success: function(response) { jQuery('#token').val(response['token']) }
    })
};
EOT;
    }
    function member() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        if (!property_exists($this->options, "access")) $this->options->access="Administrator";
        $title=(strlen($this->options->title)) ? $this->options->title : "Registered Domain Members";
        $forms->title($title, key($this->scs_table_version(__FUNCTION__)));
        $this->member_input();
        $menu->head();
        $forms->message();
		print $this->js();
	    print $forms->open();
	    print $forms->hidden('action');
        switch (TRUE) {
          case ( ($this->input->action == "list") && (!sizeof($forms->error)) ):
            print implode("", $this->member_list());
            break;
          default:
            print implode("", $this->member_select());
        }
        print $forms->close();
        $menu->copyright();
    }
    function member_input() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->input->complete=$database->user->access($this->options->access, FALSE);
        $this->input->action=$_POST['action'];
        $this->input->cpqdomain_id=($this->input->complete) ? $_POST['cpqdomain_id'] : $_SESSION['user']->cpqdomain_id;
        $database->cpqdomain->read($this->input->cpqdomain_id);
        if ( ($this->input->action) && (!$this->input->cpqdomain_id) ) $forms->error("cpqdomain_id");
        if (sizeof($forms->error)) return;
        if (isset($this->variable)) $this->variable->load($database->cpqdomain->data->variable);
        if ($this->input->action == "update") $this->member_update();
        if (!$this->input->complete) $this->input->action="list";
    }
    function member_update() {
        global $database, $forms;
        $database->transaction("start");
        $fields=array();
        $fields[]="cpqdomain_id={$this->input->cpqdomain_id}";
        $fields[]="discount_pct={$database->cpqdomain->data->discount_pct}";
        if (isset($this->variable)) {
            $variable=array();
            foreach ($this->variable->fields as $field => $obj) {
                $variable[]="'$.{$field}', " . fn_escape($obj->value);
            }
            if (sizeof($variable)) $fields[]="variable=json_set(variable, " . implode(", " , $variable) . ")";
        }
        $query=array("update user set");
        $query[]="cpqdomain_id=0";
        $query[]="where cpqdomain_id=" . fn_escape($this->input->cpqdomain_id);
        $database->temp->query($query);
        $query_where=array();
        $query_where[]=new query_where("email", "like", "%@{$database->cpqdomain->data->domain}");
        $query=array("update user set");
        $query[]=implode(",\n", $fields);
        $query[]=$database->where($query_where);
        $database->temp->query($query);
        $items=array();
        foreach ($_POST as $key => $value) {
            list($code, $id)=fn_base64($key, FALSE);
            if ($code == "id") $items[]=$id;
        }
        if (sizeof($items)) {
            $query_where=array();
            $query_where[]=new query_where("id", "in", $items);
            $fields=array();
            $fields[]="role='Customer'";
            $fields[]="status='Reject'";
            $fields[]="discount_pct=0";
            $fields[]="admin=NULL";
            $fields[]="variable=NULL";
            $query=array("update user set");
            $query[]=implode(",\n", $fields);
            $query[]=$database->where($query_where);
            $database->temp->query($query);

        }
        $database->transaction("commit");
        $forms->message[]="Membership list updated";
    }
    function member_select() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $results=array();
        $results[]="<p>Domain: " . $database->cpqdomain->select($this->input->cpqdomain_id) . "</p>";
        $results[]="<p>" . $forms->button("Load Domain", array("onclick"=>"fn_action('list');")) . "</p>";
        return $results;
    }
    function member_list() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $query_where=array();
        $query_where[]=new query_where("email", "like", "%@{$database->cpqdomain->data->domain}");
        $query_where[]=new query_where("status", "<>", "CRON");
        $query=array("select");
        $query[]="case";
        $query[]="when status = 'Active' then 1";
        $query[]="when status = 'Pending' then 2";
        $query[]="else 3";
        $query[]="end as sort_key,";
        $query[]="if (email=" . fn_escape($_SESSION['user']->email) . ", 0, 1) as self,";
        $query[]="user.* from user";
        $query[]=$database->where($query_where);
        $query[]="order by sort_key, self, email";
        $database->user->query($query);
        $items=array();
        while ($database->user->fetch = $database->user->fetch_array()) {
            $database->user->fetch();
            if (!array_key_exists($database->user->data->status, $items)) $items[$database->user->data->status]=array();
            $items[$database->user->data->status][]=$database->user->data;
        }
        $results=array();
        $results[]=$forms->hidden("cpqdomain_id", $this->input->cpqdomain_id);
        $address=new address_class("cpqdomain", $database->cpqdomain->data);
        $results[]="<table class=noborder>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Domain</td>";
        $results[]="<td width='80%'>{$database->cpqdomain->data->domain}</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Registered Users</td>";
        $results[]="<td>" . number_format($database->user->meta->rows) . "</td>";
        $results[]="</tr>";
        $results[]=$address->output("table");
        if ( ($this->input->complete) || ($database->cpqdomain->data->discount_pct) ) {
            $results[]="<tr>";
            $results[]="<td>Discount</td>";
            $results[]="<td>" . number_format(($database->cpqdomain->data->discount_pct * 100), 2) . "%</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        $results[]="<table class=border>";
        $results[]="<tr>";
        $results[]="<th>Email</th>";
        $results[]="<th>Name</th>";
        if ($this->input->complete) $results[]="<th>Role</th>";
        $results[]="<th>Last Login</th>";
        $results[]="<th>Remove?</th>";
        $results[]="</tr>";
        $this->unassigned=FALSE;
        foreach ($items as $status => $status_items) {
            $results[]="<tr><td colspan=" . (($this->input->complete) ? 5 : 4)  . "><b>Status: {$status_items[0]->status}</b></td></tr>";
            foreach ($status_items as $obj) {
                if ($obj->cpqdomain_id == $this->input->cpqdomain_id) {
                    $bg="";
                  } else {
                    $bg=$forms->background->yellow;
                    $this->unassigned=TRUE;
                }
                $results[]="<tr {$bg}>";
                $results[]="<td>{$obj->email}</td>";
                $results[]="<td>{$obj->name}</td>";
                if ($this->input->complete) $results[]="<td>{$obj->role}</td>";
                $results[]="<td class=center>" . date("m/d/Y", $obj->login_timestamp) . "</td>";
                switch (TRUE) {
                  case ($obj->email == $_SESSION['user']->email):
                  case ($obj->status == "Reject"):
                    $text="";
                    break;
                  default:
                    $text=$forms->checkbox(fn_base64(array("id", $obj->id)), 1, 0);
                }
                $results[]="<td class=center>{$text}</td>";
                $results[]="</tr>";
            }
        }
        $results[]="</table>";
        $text=array();
        switch (TRUE) {
          case ( ($this->input->complete) && (!$database->user->meta->rows) ):
            $text[]="No matches found";
            break;
          case ($this->input->complete):
            if ($this->unassigned) {
                $text[]="<span {$forms->background->yellow}>Email match but not assigned to domain.</span>";
                $text[]="";
            }
            $text[]="Removing updates the user table:";
            $text[]="Status=Reject, Role=Customer, Discount: 0%";
            $text[]="Login from removed email address will be denied.";
            $text[]="This action can only be reversed Dashboard -> User -> Maintain";
            $text[]="";
            $text[]="User discounts will be synchronized";
            break;
          default:
            $text[]="Removed login will be denied";
            $text[]="This action can only be reversed by the system administator for {$database->registry->company->name}";
        }
        $results[]="<p>" . implode("<br>", $text) . "</p>";
        $text=array();
        if ($this->input->complete) $text[]=$forms->button("<< Back", array("onclick"=>"fn_action('');"));
        if ($database->user->meta->rows) $text[]=$forms->button("Update", array("onclick"=>"fn_action('update');"));
        $results[]="<p>" . implode(" ", $text) . "</p>";
        return $results;
    }
    function list() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        if (!property_exists($this->options, "access")) $this->options->access="Administrator";
        $title=(strlen($this->options->title)) ? $this->options->title : "Registered Domain Members";
        $forms->title($title, key($this->scs_table_version(__FUNCTION__)));
        $this->list_input();
        $menu->head();
        $forms->message();
		print $this->js();
	    print $forms->open();
	    print $forms->hidden('action');

        $this->tabs=new jquery_tab_class();
        $this->tabs->item("Enter", $this->list_enter());
        $this->tabs->item("Members", $this->list_members());
        $this->tabs->output();

        print $forms->close();
        $menu->copyright();
    }
    function list_input() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->input->complete=$database->user->access($this->options->access, FALSE);
        $this->input->file_type=".xlsx";
        $this->input->action=$_POST['action'];
        $this->input->cpqdomain_id=($this->input->complete) ? $_POST['cpqdomain_id'] : $_SESSION['user']->cpqdomain_id;
        $database->cpqdomain->read($this->input->cpqdomain_id);
        if ( ($this->input->action) && (!$this->input->cpqdomain_id) ) $forms->error("cpqdomain_id", "Cannot be blank");
        $this->input->export=intval($_POST['export']);
        switch (TRUE) {
          case (!$this->input->action):
          case (sizeof($forms->error)):
          case (!$this->input->export):
            return;
        }
        $this->map=new map_class("user",$this->input,"members");
        $this->map->export($this->list_query());
    }
    function list_query() {
        global $database;
        $query_where=array();
        $query_where[]=new query_where("email", "like", "%@{$database->cpqdomain->data->domain}");
        $query_where[]=new query_where("status", "<>", "reject");
        $query=array("select * from user");
        $query[]=$database->where($query_where);
        $query[]="order by email";
        return $query;
    }
    function list_enter() {
        global $database, $forms;
        $database->cpqdomain2=new cpqdomain_class();
        $results=array();
        $results[]="<table class=noborder>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Domain</td>";
        $text=array();
        if ($this->input->complete) {
            $text[]=$database->cpqdomain2->select($this->input->cpqdomain_id);
            if ($forms->error_text['cpqdomain_id']) $text[]=$forms->error_text['cpqdomain_id'];
            } else {
            $text[]="<b>{$_SESSION['cpqdomain']->domain}</b>";
        }
        $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Export?</td>";
        $results[]="<td width='80%'>" . $forms->checkbox("export", 1, $this->input->export) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>". $forms->button("Go", array("onclick"=>"fn_action('list');")) . "</p>";
        return $results;
    }
    function list_members() {
        global $database, $forms;
        if ( (!$this->input->action) || (sizeof($forms->error)) ) return;
        $this->tabs->landing_tab();

        $results=array();
        $results[]="<table class='border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr>";
        $results[]="<th>Email</th>";
        $results[]="<th>Name</th>";
        $results[]="<th>Address</th>";
        $results[]="<th>City</th>";
        $results[]="<th>State</th>";
        $results[]="<th>Zip</th>";
        $results[]="<th>Country</th>";
        $results[]="<th>Status</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        $database->user->query($this->list_query());
        while ($database->user->fetch = $database->user->fetch_array()) {
            $database->user->fetch();
            $results[]="<tr>";
            $results[]="<td>{$database->user->data->email}</td>";
            $results[]="<td>{$database->user->data->name}</td>";
            $results[]="<td>{$database->user->data->address}</td>";
            $results[]="<td>{$database->user->data->city}</td>";
            $results[]="<td>{$database->user->data->state}</td>";
            $results[]="<td>{$database->user->data->zip}</td>";
            $results[]="<td>{$database->user->data->country_code}</td>";
            $results[]="<td>{$database->user->data->country_code}</td>";
            $results[]="</tr>";
        }
        $results[]="</tbody>";
        $results[]="</table>";
        return $results;
    }

    function js() {
		$results=array();
        $results[]="<script>";
        $results[]=<<<EOT
function toolbox_action(action, record_id, text) {
	if (text.length > 0) {
        jQuery("#delete" + record_id).prop("checked", false);
	    if (!confirm(text)) return;
	}
	jQuery('#action').val(action);
	jQuery('#record_id').val(record_id);
	jQuery('#scs_form').submit();
}
function toolbox_continue(text) {
	if (!confirm(text)) return;
	jQuery('#scs_form').submit();
}
function fn_action(action) {
    if ( (action == "update") && (!confirm("Continue?")) ) return;
    jQuery("#action").val(action);
    jQuery("#scs_form").submit();
}
EOT;
        $function_js=$this->function . "_js";
        if (method_exists($this,$function_js)) $results[]=$this->$function_js();
		switch (TRUE) {
          case (!isset($this->options->js)):
          	break;
          case (is_array($this->options->js)):
          	$results[]=implode("\n",$this->options->js);
            break;
          default:
          	$results[]=$this->options->js;
            break;
		}
		$results[]="</script>";
        $results[]="";
        return implode("\n",$results);
    }
}
class maintain_logo_class {
    var $type=array();
    function __construct($id) {
        global $database;
        $this->name("upload", 0);
        if ($id) $this->name("image", $id);
    }
    function name($type, $id) {
        global $database;
        if (!$database->registry->cpqdomain->folder) return;
        $obj=new stdClass();
        $field_name="{$type}_name";
        $field_url="{$type}_url";
        $field_exists="{$type}_exists";
        $results=array();
        $results[]=$database->registry->cpqdomain->folder;
        $results[]="cpqdomain_logo";
        $results[]=$id;
        $results[]=".png";
        $obj->image=implode("", $results);
        $results=array();
        $results[]=$_SERVER['DOCUMENT_ROOT'];
        $results[]=$obj->image;
        $obj->url=implode("", $results);
        $obj->exists=@file_exists($obj->url);
        $this->type[$type]=$obj;
    }
    function save($id) {
        $this->name("image", $id);
        rename($this->type['upload']->url, $this->type['image']->url);
        return $this->type['image']->image;
    }
    function url($type) {
        global $database;
        switch (TRUE) {
          case (!$database->registry->cpqdomain->folder):
          case (!array_key_exists($type, $this->type)):
          case (!$this->type[$type]->exists):
            return;
          default:
            return $this->type[$type]->image;
        }
    }
}
?>