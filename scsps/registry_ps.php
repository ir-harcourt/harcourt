<?php
$registry->ps=new registry_ps_class($registry);

class registry_ps_class extends registry_entry_class {
	function scs_table_version() {
		$results=array();
        $results['11/12/2025']="Add PART2Cad";
        $results['09/23/2025']="Drop staging, enforce QA server for all instances";
        $results['02/28/2025']="Add cad:user_format";
        $results['01/29/2025']="Add cpqvariable";
        $results['11/20/2024']="Add cpq";
        $results['02/12/2024']="Add crmlog (copy from cad:statistics)";
        $results['02/07/2022']="Add cad:statistics";
        $results['07/06/2021']="Add cad:link_days";
        $results['06/28/2021']="Add function: crm";
        $results['03/15/2021']="Add apikey";
        $results['08/19/2019']="Add partserver_reversemap";
        $results['05/02/2019']="Add debug";
        $results['08/09/2018']="Add partcommunity_url";
        $results['06/28/2018']="Add partcommunity option";
        $results['03/22/2018']="Enforce options";
        $results['09/28/2016']="Add version";
        $results['06/04/2016']="Add direct";
        $results['12/09/2013']="Add cart";
        $results['10/25/2012']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct($registry) {
	require_once "classes/imap.php";
		$this->iframe=$registry->iframe;
		$this->checkbox=$registry->checkbox;
	    $this->imap=new imap_class();
    }
    function cpqvariable($update=FALSE) {
        global $database, $forms;
        $module_version=strtotime("01/29/2025");
        if ($update) {
            $database->registry->update(__FUNCTION__,"module_version",$module_version);
            $items=explode("\n", trim($_POST['cpqvariable_document']));
            $document=array();
            $errors=array();
            foreach ($items as $line) {
                trim($line);
                if (!strlen($line)) continue;
                list($code, $name)=explode("=", $line);
                $code=trim($code);
                $name=trim($name);
                switch (TRUE) {
                  case( (!strlen($code)) && (strlen($name)) ):
                    break;
                  case (!strlen($code)):
                  case (!strlen($code)):
                    $errors[]="Malformed line: <b>{$line}</b>";
                    break;
                  case (array_key_exists($code, $document)):
                    $errors[]="Duplicate code: <b>{$line}</b>";
                    break;
                  case (in_array($code, $document)):
                    $errors[]="Duplicate name: <b>{$line}</b>";
                    break;
                  default:
                    $document[$code]=$name;
                }
            }
            $database->registry->update(__FUNCTION__, "document", $document);
            if (!sizeof($document)) $errors[]="Cannot be empty";
            if (sizeof($errors)) $forms->error("cpqvariable_document", implode("<br>", $errors), "", "cpqvariable");

          } else {
            $document=array();
            if (is_array($database->registry->cpqvariable->document)) {
                foreach ($database->registry->cpqvariable->document as $code => $name) {
                    $document[]="{$code} = {$name}";
                }
            }
            $text=array("Format: CODE = NAME");
            $text[]=$forms->textarea("cpqvariable_document",$document, 8, 30);
            if ($forms->error_text['cpqvariable_document']) $text[]=$forms->error_text['cpqvariable_document'];
            $results[]="<tr>";
            $results[]="<td width='30%'>Documents</td>";
            $results[]="<td width='70%'>" . implode("<br>", $text) . "</td>";
            $results[]="</tr>";
            return $this->html(__FUNCTION__,"CPQ Variables", $results, $module_version);
        }
    }
    function cpq($update=FALSE) {
        global $database, $forms;
        $module_version=strtotime("10/24/2024");
        if ($update) {
            $database->registry->update(__FUNCTION__,"module_version",$module_version);
            $database->registry->update(__FUNCTION__,"css",$this->verify_file("cpq","cpq_css","css",FALSE));
            $database->registry->update(__FUNCTION__,"price",fn_text($_POST['cpq_price']));
            $database->registry->update(__FUNCTION__,"price_detail",fn_text($_POST['cpq_price_detail']));
            $database->registry->update(__FUNCTION__,"subtotal",fn_text($_POST['cpq_subtotal']));
            $database->registry->update(__FUNCTION__,"msrp",fn_text($_POST['cpq_msrp']));
            $database->registry->update(__FUNCTION__,"cf",fn_text($_POST['cpq_cf']));
            $database->registry->update(__FUNCTION__,"denied",fn_text($_POST['cpq_denied']));
            $database->registry->update(__FUNCTION__,"error",fn_text($_POST['cpq_error']));
            $database->registry->update(__FUNCTION__,"disclaimer",fn_text($_POST['cpq_disclaimer']));
            $database->registry->update(__FUNCTION__,"quantity",$_POST["cpq_quantity"]);
            $database->registry->update(__FUNCTION__,"datasheet",$_POST["cpq_datasheet"]);
            $database->registry->update(__FUNCTION__,"max_quantity",fn_number($_POST["cpq_max_quantity"],0));
            foreach ($database->quote->constant->type as $code => $name) {
                $field="{$code}_header";
                $database->registry->update(__FUNCTION__, $field, $_POST["cpq_{$field}"]);
                $field="{$code}_footer";
                $database->registry->update(__FUNCTION__, $field, $_POST["cpq_{$field}"]);
                $field="{$code}_prefix";
                $database->registry->update(__FUNCTION__, $field, strtoupper($_POST["cpq_{$field}"]));
                $field="{$code}_suffix";
                $database->registry->update(__FUNCTION__, $field, strtoupper($_POST["cpq_{$field}"]));
            }
            $database->registry->update(__FUNCTION__,"quote_expiry",fn_number($_POST['cpq_quote_expiry'],0));
            $database->registry->load(__FUNCTION__);
            if (!strlen($database->registry->cpq->msrp)) $forms->error("cpq_msrp","Cannot be blank","",__FUNCTION__);
            if (!strlen($database->registry->cpq->cf)) $forms->error("cpq_cf","Cannot be blank","",__FUNCTION__);
            if (!strlen($database->registry->cpq->denied)) $forms->error("cpq_denied","Cannot be blank","",__FUNCTION__);
            if (!strlen($database->registry->cpq->error)) $forms->error("cpq_error","Cannot be blank","",__FUNCTION__);
            if (!$database->registry->cpq->quote_expiry) $forms->error("cpq_quote_expiry","Must be from 1-365","",__FUNCTION__);
            if ( ($database->registry->cpq->max_quantity) && (!strlen($database->registry->cpq->quantity)) ) $forms->error("cpq_quantity","Cannot be blank","",__FUNCTION__);
            $error=scs_verify_folder($database->registry->cpq->datasheet,TRUE);
            if ($error) $forms->error("cpq_datasheet",$error,"",__FUNCTION__);
          } else {
            $results=array();
            $results[]="<tr>";
            $results[]="<td width='30%'>CSS</td>";
            $text=array($forms->text("cpq_css",$database->registry->cpq->css,60));
            if ($forms->error_text['cpq_css']) $text[]=$forms->error_text['cpq_css'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing</td>";
            $results[]="<td width='70%'>" . $forms->select("cpq_price",$database->user->constant->role,$database->registry->cpq->price,"Everyone") . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Detail</td>";
            $results[]="<td width='70%'>" . $forms->select("cpq_price_detail",$database->user->constant->role,$database->registry->cpq->price_detail,"Everyone") . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Subtotal</td>";
            $text=array( $forms->text("cpq_subtotal",$database->registry->cpq->subtotal,60,60) );
            if ($forms->error_text['cpq_subtotal']) $text[]=$forms->error_text['cpq_subtotal'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: MSRP</td>";
            $text=array( $forms->text("cpq_msrp",$database->registry->cpq->msrp,60,60) );
            if ($forms->error_text['cpq_msrp']) $text[]=$forms->error_text['cpq_msrp'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Consult Factory</td>";
            $text=array( $forms->text("cpq_cf",$database->registry->cpq->cf,60,60) );
            if ($forms->error_text['cpq_cf']) $text[]=$forms->error_text['cpq_cf'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Denied</td>";
            $text=array( $forms->text("cpq_denied",$database->registry->cpq->denied,60,60) );
            if ($forms->error_text['cpq_denied']) $text[]=$forms->error_text['cpq_denied'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Error</td>";
            $text=array( $forms->text("cpq_error",$database->registry->cpq->error,60,60) );
            if ($forms->error_text['cpq_error']) $text[]=$forms->error_text['cpq_error'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td width='30%'>Maxium Quantity</td>";
            $text=array( $forms->text("cpq_max_quantity",$database->registry->cpq->max_quantity,6, 6, "decimal:0") );
            if ($forms->error_text['cpq_max_quantity']) $text[]=$forms->error_text['cpq_max_quantity'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Quantity</td>";
            $text=array( $forms->text("cpq_quantity",$database->registry->cpq->quantity,60,60) );
            if ($forms->error_text['cpq_quantity']) $text[]=$forms->error_text['cpq_quantity'];
            $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td width='30%'>Pricing: Disclaimer</td>";
            $results[]="<td width='70%'>" . $forms->textarea("cpq_disclaimer",$database->registry->cpq->disclaimer,3,60) . "</td>";
            $results[]="</tr>";

            $results[]="<tr>";
            $results[]="<td>Datasheet Folder</td>";
            $text=array($forms->text("cpq_datasheet",$database->registry->cpq->datasheet,60));
            if ($forms->error_text['cpq_datasheet']) $text[]=$forms->error_text['cpq_datasheet'];
            $results[]="<td>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";

            foreach ($database->quote->constant->type as $code => $name) {
                $results[]="<tr><td colspan=2><hr></tr>";
                $results[]="<tr><td colspan=2><b>{$name} Information</td></tr>";
                $results[]="<tr>";
                $results[]="<td>{$name} Header</td>";
                $field="{$code}_header";
                $results[]="<td>" . $forms->textarea("cpq_{$field}", $database->registry->cpq->$field, 12, 120, array("placeholder"=>"Optional")) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>{$name} Footer</td>";
                $field="{$code}_footer";
                $results[]="<td>" . $forms->textarea("cpq_{$field}", $database->registry->cpq->$field, 12, 120, array("placeholder"=>"Optional")) . "</td>";
                $results[]="</tr>";
                $results[]="<tr>";
                $results[]="<td>Document</td>";
                $text=array();
                $text[]="Prefix:";
                $field="{$code}_prefix";
                $text[]=$forms->text("cpq_{$field}", $database->registry->cpq->$field, 6, 6);
                $text[]="Suffix:";
                $field="{$code}_suffix";
                $text[]=$forms->text("cpq_{$field}", $database->registry->cpq->$field, 6, 6);
                $results[]="<td>" . implode(" ", $text) . "</td>";
                $results[]="</tr>";
                if ($code == "quote") {
                    $results[]="<tr>";
                    $results[]="<td>Quote Expiry (1-365)</td>";
                    $field="{$code}_expiry";
                    $results[]="<td>" . $forms->text("cpq_{$field}", $database->registry->cpq->$field, 3, 3) . " Days</td>";
                    $results[]="</tr>";
                }
            }
            return $this->html(__FUNCTION__,"Configure/Price/Quote (CPQ)", $results, $module_version);
        }
    }
    function crmlog($update=FALSE) {
        global $database, $forms;
        $module_version=strtotime("02/12/2024");
        if ($update) {
	        $database->registry->update(__FUNCTION__,"module_version",$module_version);
            $database->registry->update(__FUNCTION__, "url", $_POST['crmlog_url']);
            $database->registry->update(__FUNCTION__, "login", $_POST['crmlog_login']);
            $database->registry->update(__FUNCTION__, "password", $_POST['crmlog_password']);
            $database->registry->update(__FUNCTION__, "template", $_POST['crmlog_template']);
            $database->registry->update(__FUNCTION__, "notify", $_POST['crmlog_notify']);
            $database->registry->update(__FUNCTION__, "last_cron", fn_date($_POST['crmlog_last_cron'],"mdy"));
            $database->registry->load(__FUNCTION__);
            $url=parse_url($database->registry->crmlog->url);
            switch (TRUE) {
              case (!strlen($database->registry->crmlog->url)):
              case (!$url['scheme']):
              case (!$url['host']):
              case (!$url['path']):
                $forms->error("crmlog_url","Malformed URL","",__FUNCTION__);
                break;
            }
            if (!strlen($database->registry->crmlog->login)) $forms->error("crmlog_login","Cannot be blank","",__FUNCTION__);
            if (!strlen($database->registry->crmlog->password)) $forms->error("crmlog_password","Cannot be blank","",__FUNCTION__);
            if (!strlen($database->registry->crmlog->template)) $forms->error("crmlog_template","Cannot be blank","",__FUNCTION__);
          } else {
            if ( (!$database->registry->crmlog->module_version) && (property_exists($database->registry->cad, "statistics")) ) {
                $database->registry->update(__FUNCTION__, "notify", "crm_cron");
                foreach ($database->registry->cad->statistics as $key => $value) {
                    $database->registry->update(__FUNCTION__, $key, $value);
                }
                $database->registry->load(__FUNCTION__);
            }
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>URL</td>";
            $text=array();
            $text[]="<i>Example: https://config.partcommunity.com/PARTcommunityAPI/Statistic</i>";
            $text[]=$forms->textarea("crmlog_url", $database->registry->crmlog->url, 2, 60);
            if ($forms->error_text['crmlog_url']) $text[]=$forms->error_text['crmlog_url'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Login</td>";
            $text=array();
            $text[]=$forms->text("crmlog_login", $database->registry->crmlog->login, 60);
            if ($forms->error_text['crmlog_login']) $text[]=$forms->error_text['crmlog_login'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Password</td>";
            $text=array();
            $text[]=$forms->text("crmlog_password",$database->registry->crmlog->password, 60);
            if ($forms->error_text['crmlog_password']) $text[]=$forms->error_text['crmlog_password'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Template</td>";
            $text=array();
            $text[]=$forms->text("crmlog_template",$database->registry->crmlog->template,60);
            if ($forms->error_text['crmlog_template']) $text[]=$forms->error_text['crmlog_template'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Last CRON Import (GMT)</td>";
	        $results[]="<td width='70%'>" . $forms->text("crmlog_last_cron",$database->registry->crmlog->last_cron,10,10,"date") . "</td>";
	        $results[]="</tr>";
            $results[]="<tr>";
	        $results[]="<td width='30%'>Notify email</td>";
            $results[]="<td width='70%'>" . $database->mailform->select($database->registry->crmlog->notify, array("field"=>"crmlog_notify", "internal"=>TRUE)) . "</td>";
	        $results[]="</tr>";
            return $this->html(__FUNCTION__,"CRMLOG Import",$results,$module_version);
        }
    }
    function cad($update=FALSE) {
        global $database, $forms;
		$module_version=strtotime("11/12/2025");
	    if ($update) {
            $database->registry->update(__FUNCTION__,"module_version",$module_version);
	        $database->registry->update(__FUNCTION__,"configurator",trim($_POST['cad_configurator']));
	        $database->registry->update(__FUNCTION__,"partserver_production",$_POST['cad_partserver_production']);
	        $database->registry->update(__FUNCTION__,"partserver_reversemap",$_POST['cad_partserver_reversemap']);
	        $database->registry->update(__FUNCTION__,"debug",trim($_POST['cad_debug']));
	        $database->registry->update(__FUNCTION__,"max",$_POST['cad_max']);
	        $database->registry->update(__FUNCTION__,"max",$_POST['cad_max']);
	        $database->registry->update(__FUNCTION__,"days",$_POST['cad_days']);
	        $database->registry->update(__FUNCTION__,"link_hours",$_POST['cad_link_hours']);
	        $database->registry->update(__FUNCTION__,"pending",$_POST['cad_pending']);
            $database->registry->update(__FUNCTION__,"user_format",$_POST['cad_user_format']);
	        $database->registry->update(__FUNCTION__,"version",$_POST['cad_version']);
	        $database->registry->update(__FUNCTION__,"partcommunity_url",$_POST['cad_partcommunity_url']);
	        $database->registry->update(__FUNCTION__,"catalog",$_POST['cad_catalog']);
            $database->registry->update(__FUNCTION__,"generate_sku",$_POST['cad_generate_sku']);
	        $database->registry->update(__FUNCTION__,"apikey",$_POST['cad_apikey']);
	        $database->registry->update(__FUNCTION__,"portal",$_POST['cad_portal']);
	        $database->registry->update(__FUNCTION__,"part2cad",intval($_POST['cad_part2cad']));
            $database->registry->load(__FUNCTION__);
            if (strlen($database->registry->cad->configurator)) {
                $qa=preg_match("/.qa./", $database->registry->cad->configurator);
                $text=($qa) ? "QA" : "Production";
                if ( (strlen($database->registry->cad->partserver_production)) && ( (preg_match("/.qa./", $database->registry->cad->partserver_production)) != $qa) ) $forms->error("cad_partserver_production", "Must be{$text} Server","",__FUNCTION__);
                if ( (strlen($database->registry->cad->partserver_reversemap)) && ( (preg_match("/.qa./", $database->registry->cad->partserver_reversemap)) != $qa) ) $forms->error("cad_partserver_reversemap", "Must be {$text} Server","",__FUNCTION__);
            }
            if (!strlen($database->registry->cad->catalog)) $forms->error("cad_catalog","","",__FUNCTION__);
	      } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Version</td>";
	        $results[]="<td width='70%'>" . $forms->select("cad_version",array(""=>"Initial/Default","2.0"=>"Responsive","3.0"=>"Partcommunity"),$database->registry->cad->version,FALSE) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr><td colspan=2>PARTsolutions URL</td></tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Configurator</td>";
            $text=array();
            $text[]=$forms->textarea("cad_configurator",$database->registry->cad->configurator,2,60);
            if ($forms->error_text['cad_configurator']) $text[]=$forms->error_text['cad_configurator'];
	        $results[]="<td width='70%'>" . implode("<br>", $text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Web assistants</td>";
            $text=array();
            $text[]="<br><i>Example: https://webassistants.partcommunity.com/cgi-bin/cgi2pview.exe</i>";
            $text[]=$forms->textarea("cad_partserver_production",$database->registry->cad->partserver_production,2,60);
            if ($forms->error_text['cad_partserver_production']) $text[]=$forms->error_text['cad_partserver_production'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Reverse map</td>";
            $text=array();
            $text[]="<br><i>Example: https://webapi.partcommunity.com/service/reversemap</i>";
            $text[]=$forms->textarea("cad_partserver_reversemap",$database->registry->cad->partserver_reversemap,2,60);
            if ($forms->error_text['cad_partserver_reversemap']) $text[]=$forms->error_text['cad_partserver_reversemap'];
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>CURL Debug?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cad_debug",1,$database->registry->cad->debug) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>User CAD download limit (0=Unlimited)</td>";
	        $results[]="<td>" . $forms->select_number("cad_max",$database->registry->cad->max,0,99) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td>Limit CAD days (0=Lifetime)</td>";
	        $results[]="<td>" . $forms->select_number("cad_days",$database->registry->cad->days,0,99) . " Days</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Pending user CAD?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cad_pending",1,$database->registry->cad->pending) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>User CAD format</td>";
	        $results[]="<td width='70%'>" . $forms->select("cad_user_format",array("select"=>"Select", "picklist"=>"Picklist"),$database->registry->cad->user_format) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>PARTcommunity URL</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("cad_partcommunity_url",$database->registry->cad->partcommunity_url,2,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Catalog</td>";
	        $results[]="<td width='70%'>" . $forms->text("cad_catalog",$database->registry->cad->catalog,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Generate CAD w/SKU?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cad_generate_sku",1,$database->registry->cad->generate_sku) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>API Key</td>";
	        $results[]="<td width='70%'>" . $forms->text("cad_apikey",$database->registry->cad->apikey,40,40) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Link Expires</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("cad_link_hours",$database->registry->cad->link_hours,0,48) . " Hours (0=Link not allowed)</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>CAD Format Import Portal</td>";
	        $results[]="<td width='70%'>" . $forms->text("cad_portal",$database->registry->cad->portal,40) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Auto grant PART2CAD Access?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cad_part2cad", 1, $database->registry->cad->part2cad) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Last Import</td>";
	        $results[]="<td width='70%'>" . ( ($database->registry->cad->import_timestamp) ? date("m/d/Y h:i A",$database->registry->cad->import_timestamp) : "Never") . "</td>";
	        $results[]="</tr>";
	        return $this->html(__FUNCTION__,"CAD & Configurator Options",$results,$module_version);
	    }
	}
    function crm($update=FALSE) {
        global $database,$forms;
		$module_version=strtotime("10/17/2023");
	    if ($update) {
	        $database->registry->update(__FUNCTION__, "module_version", $module_version);
	        $database->registry->update(__FUNCTION__, "imap_code", $_POST['crm_imap_code']);
	        $database->registry->update(__FUNCTION__, "imap_text", $_POST['crm_imap_text']);
            $results=array();
            foreach (explode("\n", trim($_POST['crm_blacklist_server_category'])) as $item) {
            	$item=trim($item);
                if ( (strlen($item)) && (!in_array($item, $results)) ) $results[]=$item;
            }
	        $database->registry->update(__FUNCTION__, "blacklist_server_category", $results);
            $results=array();
            foreach (explode("\n", trim($_POST['crm_blacklist_server_detail'])) as $item) {
            	$item=trim($item);
                if ( (strlen($item)) && (!in_array($item, $results)) ) $results[]=$item;
            }
	        $database->registry->update(__FUNCTION__, "blacklist_server_detail", $results);
	      } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>IMAP match code</td>";
	        $results[]="<td width='70%'>" . $forms->optgroup("crm_imap_code",$this->imap->constant->search(),$database->registry->crm->imap_code) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>IMAP match text</td>";
	        $results[]="<td width='70%'>" . $forms->text("crm_imap_text",$database->registry->crm->imap_text,60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr><td>Import Blacklist</td></tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Server Category</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("crm_blacklist_server_category", $database->registry->crm->blacklist_server_category, 3, 60) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Server Detail</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("crm_blacklist_server_detail", $database->registry->crm->blacklist_server_detail, 3, 60) . "</td>";
	        $results[]="</tr>";
	        return $this->html(__FUNCTION__,"CRM Import Options",$results,$module_version);
	    }
	}
    function cart_local($update=FALSE) {
        global $database, $forms;
        require_once "classes/cadorder.php";
	    if ($update) {
	        $database->registry->update("cadcart","ps_url",trim($_POST['cadcart_ps_url']));
	        $database->registry->update("cadcart","return_url",trim($_POST['cadcart_return_url']));
	        $database->registry->update("cadcart","pending_limit",trim($_POST['cadcart_pending_limit']));
	        $database->registry->update("cadcart","expire_days",trim($_POST['cadcart_expire_days']));
	        $database->registry->update("cadcart","purge_days",trim($_POST['cadcart_purge_days']));
	        $database->registry->update("cadcart","log_ps",$_POST['cadcart_log_ps']);
	        $database->registry->update("cadcart","log_poll",$_POST['cadcart_log_poll']);
	        $database->registry->update("cadcart","A00_html",trim($_POST['cadcart_A00_html']));
	        $database->registry->update("cadcart","B00_html",$_POST['cadcart_B00_html']);
	        $database->registry->update("cadcart","cart_html",trim($_POST['cadcart_cart_html']));
		  } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>PS Configurator URL</td>";
            $text=array("<i>Example: https://webassistants.partcommunity.com/cgi-bin/cgi2pview.cgi</i>");
            $text[]=$forms->textarea("cadcart_ps_url",$database->registry->cadcart->ps_url,3,60);
	        $results[]="<td width='70%'>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Successful Results URL</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("cadcart_return_url",$database->registry->cadcart->return_url,3,60) . "</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Maximum user pending CAD requests</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("cadcart_pending_limit",$database->registry->cadcart->pending_limit,1,30) . "</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>CAD expires after</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("cadcart_expire_days",$database->registry->cadcart->expire_days,1,10) . " days</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Confirm HTML (A00)</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("cadcart_A00_html",$database->registry->cadcart->A00_html,2,60);
            $results[]="</td>";
	        $results[]="</tr>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Download Successful HTML (B00)</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("cadcart_B00_html",$database->registry->cadcart->B00_html,2,60);
            $results[]="</td>";
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Cart Message HTML</td>";
	        $results[]="<td width='70%'>" . $forms->textarea("cadcart_cart_html",$database->registry->cadcart->cart_html,4,60);
            $results[]="</td>";
	        $results[]="</tr>";
	        return $this->html("cadcart","CAD Cart Local Options",$results);
        }
    }
    function cart_global($update=FALSE) {
        global $database,$forms;
        require_once "classes/cadorder.php";
	    if ($update) {
	        $database->registry->update("cadcart","folder",trim($_POST['cadcart_folder']));
            $error=scs_verify_folder($_POST['cadcart_folder'],TRUE);
			if ($error) $forms->error('cadcart_folder',$error,'','cadcart');
	        $database->registry->update("cadcart","purge_days",trim($_POST['cadcart_purge_days']));
	        $database->registry->update("cadcart","partserver_delay",trim($_POST['cadcart_partserver_delay']));
	        $database->registry->update("cadcart","log_ps",$_POST['cadcart_log_ps']);
	        $database->registry->update("cadcart","log_poll",$_POST['cadcart_log_poll']);
			$housekeeping_poll=array();
			$housekeeping_next=array();
            foreach ($database->cadorder->constant->housekeeping as $key => $value) {
				$housekeeping_poll[$key]=$_POST["cadcart_housekeeping_{$key}"];
                if (isset($database->registry->cadcart->housekeeping_next[$key])) {
                	$houskeeping_next[$key]=$database->registry->cadcart->housekeeping_next[$key];
				  } else {
                	$houskeeping_next[$key]=date("m/d/Y g:i:s A");
				}
            }
	        $database->registry->update("cadcart","housekeeping_poll",$housekeeping_poll);
			$database->registry->update("cadcart","housekeeping_next",$houskeeping_next);
		  } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td width='30%'>Upload file folder</td>";
	        $results[]="<td>" . $forms->text("cadcart_folder",$database->registry->cadcart->folder,60,60);
            if ($forms->error_text["cadcart_folder"]) $results[]="<br>" . $forms->error_text["cadcart_folder"];
            $results[]="</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Purge cancellations & errors</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("cadcart_purge_days",$database->registry->cadcart->purge_days,1,10) . " days</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Partserver retrieve delay</td>";
	        $results[]="<td width='70%'>" . $forms->select_number("cadcart_partserver_delay",$database->registry->cadcart->partserver_delay,1,15) . " minutes</td>";;
			$results[]="</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Partserver log?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cadcart_log_ps",1,$database->registry->cadcart->log_ps);
            $results[]="</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td width='30%'>Polling log?</td>";
	        $results[]="<td width='70%'>" . $forms->checkbox("cadcart_log_poll",1,$database->registry->cadcart->log_poll);
            $results[]="</td>";
	        $results[]="</tr>";

	        $results[]="<tr>";
	        $results[]="<td colspan=2><br>Housekeeping frequency (Minutes) Total housekeeping runtime limited to 30 seconds.</td>";
            $results[]="</td>";
	        $results[]="</tr>";
            foreach ($database->cadorder->constant->housekeeping as $key => $value) {
            	$results[]="<tr>";
                $results[]="<td>$value</td>";
                $results[]=
                	"<td>" . $forms->select_number("cadcart_housekeeping_{$key}",$database->registry->cadcart->housekeeping_poll[$key],1,15) .
                    " Next: " . $database->registry->cadcart->housekeeping_next[$key] . "</td>";
            	$results[]="</tr>";
            }
	        return $this->html("cadcart","CAD Cart Global Options",$results);
        }
    }
}
?>
