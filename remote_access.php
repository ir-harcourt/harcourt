<?php
require_once "scs_header.php";
switch (TRUE) {
  case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
  	$results=array();
	switch ($_POST['action']) {
      case "load":
      	$results['company_error']="&nbsp;";
      	$results['domain_div']="&nbsp;";
      	$results['results_div']="&nbsp;";
      	$results['error_div']="&nbsp;";
        $company_name=trim($_POST['company_name']);
        if (!strlen($company_name)) {
        	$results['company_error']="Cannot be blank";
            break;
        }
        $query_where=array();
        $query_where[]=new query_where("status","=","active");
        $query_where[]=new query_where("company_name","=",$company_name);
        $query_where[]=new query_where("last_login","<>",0);
        $query=array("select * from user");
        $query[]=$database->where($query_where);
        $query[]="order by domain desc,last_login desc,ip";
		$database->user->query($query);
        if (!$database->user->meta->rows) {
        	$results['company_error']="No {$company_name} users found";
            break;
        }
        $domain="";
	    $items=array();
	    while ($database->user->fetch = $database->user->fetch_array()) {
	        $database->user->fetch();
	        if ($database->user->data->domain) $domain=$database->user->data->domain;
	        if (!array_key_exists($database->user->data->ip,$items)) {
            	$obj=new stdClass();
                $obj->id=$database->user->data->id;
                $obj->last_login=$database->user->data->last_login;
                $obj->type=$database->user->data->type;
                $obj->primary=(strlen($database->user->data->domain) ? 1 : 0);
            	$items[$database->user->data->ip]=$obj;
			}
	    }
        $text=array();
        $text[]=$forms->hidden("local_company",$company_name);
        $text[]="<table class='standard border tablesorter'>";
        $text[]=$forms->caption("&nbsp");
        $text[]="<thead>";
        $text[]="<tr>";
        $text[]="<th width='10%'>Primary?</th>";
        $text[]="<th width='20%'>IP Address</th>";
        $text[]="<th width='50%'>Host Name</th>";
        $text[]="<th width='10%'>Role</th>";
        $text[]="<th width='10%'>Last Login</th>";
		$text[]="</tr>";
        $text[]="</thead>";
        $text[]="<tr>";
        $text[]="<td class=center>" . $forms->radio("access","0.0.0.0",(!$domain) ? "0.0.0.0" : "",array("class"=>"remote_access")) . "</td>";
        $text[]="<td colspan=3>No remote access</td>";
        $text[]="</tr>";
        $text[]="</tr>";
        $text[]="<tbody>";
        foreach ($items as $ip => $obj) {
            $text[]="<tr>";
            $text[]="<td class=center>" . $forms->radio("access",$ip,($obj->primary) ? $ip : "") . "</td>";
            $text[]="<td>" . $ip . "</td>";
            $field=fn_base64(array("ip",$ip));
            $text[]="<td id={$field}>" . fn_href("Resolve Host","javascript:fn_resolve_host('{$field}');") . "</td>";
            $text[]="<td>" .  $obj->type . "</td>";
            $text[]="<td class=center>" . date("m/d/Y",$obj->last_login) . "</td>";
            $text[]="</tr>";
        }
        $text[]="</tbody>";
        $text[]="</table>";
        $text[]="<script>";
        $text[]="jQuery('.tablesorter').tablesorter('destroy');";
        $text[]="jQuery('.tablesorter').tablesorter();";
        $text[]="</script>";
      	$results['results_div']=implode("",$text);
        $text=array("Domain:");
        $text[]=$forms->text("domain",$domain,40,40);
        $text[]=$forms->button("Update domain",array("onclick"=>"fn_update();"));
        $text[]="<span id=domain_status><span>";
        $results['domain_div']=implode(" ",$text);
        break;
      case "host":
      	$ip=fn_base64($_REQUEST['id'],FALSE);
		$results[$_REQUEST['id']]=gethostbyaddr($ip[1]);
		break;
      case "update":
      	$company_name=trim($_POST['company_name']);
      	$domain=trim(strtolower($_POST['domain']));
      	$ip=trim($_POST['ip']);
        $database->user->read($domain,"domain");
        switch (TRUE) {
          case ( (!strlen($company_name)) || (!strlen($_POST['ip'])) ):
			$results['domain_status']="Company name/IP missing";
            break;
          case ( (!strlen($domain)) && ($ip != '0.0.0.0') ):
			$results['domain_status']="Domain name missing";
            break;
          case (!strlen($domain)):
          	break;
          case (!preg_match("/^(?:[a-z0-9\-]+\.)+[a-z0-9\-]{2,63}$/i",$domain)):
			$results['domain_status']="Domain format must be domain.com";
            break;
          case ($ip == '0.0.0.0'):
			$results['domain_status']="Domain name must be blank";
            break;
		  case ( ($database->user->meta->rows) && (!preg_match("/^" . preg_quote($company_name,"$/") . "$/i",$database->user->data->company_name)) ):
			$results['domain_status']="Domain {$domain} already assigned to " . $database->user->data->company_name;
            break;
		}
        if ($results['domain_status']) break;
        $query=array("update user set domain=null");
        $query[]="where company_name=" . fn_escape($company_name);
        $database->temp->query($query);
        $text=array("Company name: {$company_name}");
        if (!$domain) {
        	$text[]="Remote access denied";
		  } else {
        	$query=array("update user set domain=" . fn_escape($domain));
        	$query[]="where ip=" . fn_escape($ip);
            $query[]="limit 1";
            $database->temp->query($query);
            $text[]="Domain: {$domain}";
            $text[]="IP Address: {$ip}";
        }
		$results['domain_status']=implode(" ",$text);
      	break;
	}
  	die(json_encode($results));
}
$database->user->access("Administrator");
$forms->title("Company Remote Access");
$menu->head();
print $forms->message();
?>
<script>
function fn_domain_load() {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: "load", company_name: jQuery("#company_name").val() },
	    success: function(response) {
	        for (var key in response) {
           		jQuery("#" + key).html(response[key]);
            }
            scscpq_ipc("scscpq_wp_iframe", "resize", jQuery("#scs_content").height());
        },
        error: function(xhr, response) {
			jQuery("#error_div").html(xhr.responseText);
        }
	});
}
function fn_resolve_host(id) {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: "host", id: id },
	    success: function(response) {
        	jQuery("#" + id).html(response[id]);
        },
        error: function(xhr, response) {
			jQuery("#error_div").html(xhr.responseText);
        }
	});
}
function fn_update() {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: "update", company_name: jQuery("#local_company").val(), domain: jQuery("#domain").val(), ip: jQuery("input[name=access]:checked").val() },
	    success: function(response) {
			jQuery("#error_div").html("&nbsp;");
	        for (var key in response) {
           		jQuery("#" + key).html(response[key]);
            }
        },
        error: function(xhr, response) {
			jQuery("#error_div").html(xhr.responseText);
        }
	});
}
jQuery(function() {
    jQuery('#company_name').autocomplete({
        source: '/scs_ajax.php?table=user&source=company_name',
        minLength: 2,
        select: function(event, ui) {
        },
        html: true,
        open: function(event, ui) {
        	jQuery('.ui-autocomplete').css('z-index', 1000);
        }
    });
});
</script>
<?php
$text=array();
$text=array($forms->text("company_name","",40,80));
$text[]=$forms->button("Load",array("onclick"=>"fn_domain_load();"));
$text[]="<span id=company_error></span>";
$text[]="<span id=ajax_user_ip></span>";
print "<p>Company Name: " . implode(" ",$text) . "</p>";
print "<div id=error_div></div>";
print "<div id=domain_div></div>";
print "<div id=results_div></div>";
print "<div style='height: 800px;'>&nbsp;</div>";
$menu->copyright();
?>