<?php
$registry->local=new registry_local_class($registry);
class registry_local_class extends registry_entry_class {
	function __construct($registry) {
		$this->iframe=$registry->iframe;
		$this->checkbox=$registry->checkbox;
    }
    function local($update=FALSE) {
        global $database,$forms,$menu;
		$module_version=strtotime("11/20/2025");
	    if ($update) {
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"thumbnail",$_POST['local_thumbnail']);
	        $database->registry->update(__FUNCTION__,"new_days",$_POST['local_new_days']);
	        $database->registry->update(__FUNCTION__,"track",$_POST['local_track']);
	        $database->registry->update(__FUNCTION__,"remote_days",intval($_POST['local_remote_days']));
	        $database->registry->update(__FUNCTION__,"configure_contact",$_POST['local_configure_contact']);
	        $database->registry->update(__FUNCTION__,"pricebook_title",$_POST['local_pricebook_title']);
	        $database->registry->update(__FUNCTION__,"pricebook_mailform",$_POST['local_pricebook_mailform']);
	        $database->registry->update(__FUNCTION__,"cc_checkout",$_POST['local_cc_checkout']);
	        $database->registry->update(__FUNCTION__,"cc_email",$_POST['local_cc_email']);
            $database->registry->update(__FUNCTION__,"profile_notice",$_POST['local_profile_notice']);
	        $database->registry->update(__FUNCTION__,"api_key",$_POST['local_api_key']);
	        $database->registry->update(__FUNCTION__,"related_message",$_POST['local_related_message']);
	      } else {
	        $results=array();
            $results[]=$this->js();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Thumbnail size</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("local_thumbnail",$database->registry->local->thumbnail,0,100) . " (Pixels)</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Inventory new</td>";
	        $results[]="<td width='70%'>" . $forms->select("local_new_days",array(0,30,60,90,120,180,360),$database->registry->local->new_days,FALSE) . " Days</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Event Tracking</td>";
	        $results[]="<td width='70%'>" . $forms->select("local_track",$database->log->constant->track,$database->registry->local->track,"Do not track") . "</td>";
	        $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Remote access expiry</td>";
            $results[]="<td width='70%'>" . $forms->text("local_remote_days",$database->registry->local->remote_days,3,3) . " days</td>";
            $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>No Price Message</td>";
	        $results[]="<td width='70%'>" . $forms->text("local_configure_contact",$database->registry->local->configure_contact,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Price Book Title</td>";
	        $results[]="<td width='70%'>" . $forms->text("local_pricebook_title",$database->registry->local->pricebook_title,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Price Book Email</td>";
	        $results[]="<td width='70%'>" . $database->mailform->select($database->registry->local->pricebook_mailform,array("field"=>"local_pricebook_mailform","internal"=>0)) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Credit Card Notice (Shopping Cart)</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("local_cc_checkout",$database->registry->local->cc_checkout,3,100) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Credit Card Notice (Email)</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("local_cc_email",$database->registry->local->cc_email,3,100) . "</td>";
	        $results[]="</tr>";
            $results[]="<tr>";
	        $results[]="<td width='30%'>Profile update notice</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("local_profile_notice",$database->registry->local->profile_notice,3,100) . "</td>";
	        $results[]="</tr>";
		    $results[]="<tr id=api_key_tr>";
	        $text=array("API Key");
	        $text[]=fn_href("(Create New)","javascript:local_api_key();");
	        $text[]=fn_href("(Clear)","javascript:jQuery('#local_api_key').val('');");
	        $results[]="<td>" . implode(" ",$text) . "</td>";
	        $text=array($forms->text("local_api_key",$database->registry->local->api_key,40,40,"",array("readonly"=>TRUE,"placeholder"=>"Must be generated")));
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Related SKU missing HTML</td>";
            $text=array("Format: beginning text %sku% remaining text");
            if ($forms->error_text['local_related_message']) $text[]=$forms->error_text['local_related_message'];
            $text[]=$forms->textarea("local_related_message", $database->registry->local->related_message, 3, 80);
	        $results[]="<td width='70%'>" . implode("<br>", $text) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Search Utility</td>";
	        $results[]="<td width='70%'>" . ( ($database->registry->local->search_timestamp) ? date("r", $database->registry->local->search_timestamp) : "Never") . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Image Filmstrip</td>";
	        $results[]="<td width='70%'>" . ( ($database->registry->local->filmstrip_timestamp) ? date("r", $database->registry->local->filmstrip_timestamp) : "Never") . "</td>";
	        $results[]="</tr>";
	        return $this->html(__FUNCTION__,"Local Settings",$results,$module_version);
	    }
    }
    function leadscore($update=FALSE) {
        global $database,$forms,$menu;
		$module_version=strtotime("12/02/2016");
	    if ($update) {
	        $database->registry->update("leadscore","module_version",$module_version);
	        $database->registry->update("leadscore","days",$_POST['leadscore_days']);
			$score=array();
            foreach ($_POST as $key => $value) {
            	$field=fn_base64($key,FALSE);
				if ($field[0]=="leadscore") {
                	$score[$field[1]]=fn_number($value,0);
                    if ($score[$field[1]] < 0) $forms->error($key,"","","leadscore");
                }
            }
	        $database->registry->update("leadscore","score",$score);
	      } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Days</td>";
            $results[]="<td width='70%'>" . $forms->select_number("leadscore_days",$database->registry->leadscore->days,1,99) . "</td>";
            $results[]="</tr>";
            foreach ($database->log->constant->type as $type => $subtypes) {
            	if (sizeof($subtypes)) {
	                $results[]="<tr>";
	                $results[]="<tr><td colspan=2>&nbsp;</td></tr>";
	                $results[]="<td>Type: <b>$type</b></td>";
	                $results[]="<td>Point Value</td>";
	                $results[]="</tr>";
                    foreach ($subtypes as $subtype) {
	                    $results[]="<tr>";
	                    $results[]="<td>$subtype</td>";
                        $field=strtolower($type . ":" . $subtype);
	                    $results[]="<td>" . $forms->text(fn_base64(array("leadscore",$field)),$database->registry->leadscore->score[$field],6,0,"decimal:0") . "</td>";
	                    $results[]="</tr>";
                    }
				}
			}
	        return $this->html("leadscore","Lead Score",$results,$module_version);
	    }
    }
    function motd($update=FALSE) {
        global $database,$forms,$menu;
		$module_version=strtotime("05/16/2019");
	    if ($update) {
	        $database->registry->update("motd","module_version",$module_version);
	        $database->registry->update("motd","message",$_POST['motd_message']);
	        $database->registry->update("motd","foreground",fn_text($_POST['motd_foreground'],TRUE));
	        $database->registry->update("motd","background",fn_text($_POST['motd_background'],TRUE));
			$database->registry->load("motd");
		  } else {
	        $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>Message</td>";
            $results[]="<td width='70%'>" . $forms->text("motd_message",$database->registry->motd->message,60) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Foreground color (default: FFFFFF)</td>";
            $results[]="<td width='70%'>#" . $forms->text("motd_foreground",$database->registry->motd->foreground,6,6) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Background color (default: DC2536)</td>";
            $results[]="<td width='70%'>#" . $forms->text("motd_background",$database->registry->motd->background,6,6) . "</td>";
            $results[]="</tr>";
	        return $this->html("motd","Message of the day",$results,$module_version);
		}
	}
    function ipstack($update=FALSE) {
        global $database, $forms;
		$module_version=strtotime("07/11/2022");
        if ($update) {
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"api_url",$_POST['ipstack_api_url']);
	        $database->registry->update(__FUNCTION__,"api_key",$_POST['ipstack_api_key']);
            $database->registry->load(__FUNCTION__);
            $parse=parse_url($database->registry->ipstack->api_url);
            switch (TRUE) {
              case (!strlen($database->registry->ipstack->api_url)):
              	break;
              case (!preg_match("/^(http|https)$/i",$parse['scheme'])):
              	$forms->error("ipstack_api_url","Scheme must be http/https","",__FUNCTION__);
                break;
              case (!strlen($parse['host'])):
              	$forms->error("ipstack_api_url","Host missing","",__FUNCTION__);
                break;
              case ($parse['path'] != "/"):
              	$forms->error("ipstack_api_url","Path must be /","",__FUNCTION__);
                break;
			}
		  } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>API URL</td>";
            $text=array($forms->text("ipstack_api_url",$database->registry->ipstack->api_url,60));
            if ($forms->error_text['ipstack_api_url']) $text[]=$forms->error_text['ipstack_api_url'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>API Key</td>";
            $text=array($forms->text("ipstack_api_key",$database->registry->ipstack->api_key,60));
            if ($forms->error_text['ipstack_api_key']) $text[]=$forms->error_text['ipstack_api_key'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        return $this->html(__FUNCTION__,"IPstack.com",$results,$module_version);
        }
    }
    function js() {
        $results=<<<EOT

<script>
function local_api_key() {
    jQuery.ajax({
      type: 'post',
      dataType: 'json',
      data: { action: 'local_api_key' },
      success: function(response) { jQuery('#local_api_key').val(response['local_api_key']) }
    });
}
</script>

EOT;
        return $results;
    }
}
?>