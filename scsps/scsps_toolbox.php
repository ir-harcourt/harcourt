<?php
require_once "classes/cad.php";
class scsps_toolbox_class {
	var $options;
    var $trace=array();
	function scs_table_version($function="") {
		$results=array();
        switch ($function) {
		  case "lookup":
            $results['10/27/2025']="Correct Info/Varset Bug";
            $results['09/22/2025']="New interface";
            $results['07/01/2025']="Rewrite json_lookup_review";
            $results['02/27/2025']="Enhance flow, add CURL";
            $results['12/06/2024']="CAD download bug";
            $results['09/27/2024']="Standardize json";
            $results['08/27/2024']="Add user, stopwatch";
            $results['07/26/2024']="Add ipc";
            $results['07/20/2024']="Add source";
            $results['06/07/2024']="Add options: languageIso";
            $results['03/24/2024']="PHP 8 compatible";
        	$results['10/25/2022']="Initial release";
            break;
		  case "cad":
            $results['11/12/2025']="All importing PART2Cad formats";
            $results['09/11/2025']="Add qa_server";
            $results['07/30/2025']="Upload using CURL";
            $results['07/22/2025']="Add prj";
            $results['09/27/2024']="Add utility";
            $results['03/07/2024']="PHP 8 compatible";
        	$results['07/14/2023']="Add CRON";
        	$results['06/02/2022']="Initial release";
            break;
		}
        return $results;
    }
	function __construct($function, $role, $options=array()) {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
		if (!$forms->cron) $database->user->access($role);
        $this->input=new stdClass();
        $this->options=(is_array($options)) ? $options : array();
        $subroutine=(($forms->cron) ? "cron_" : "html_") . $function;
        switch (TRUE) {
          case (array_key_exists("user", $this->options)):
            $this->user=$this->options['user'];
            break;
          case (isset($_SESSION['user'])):
            $this->user=$_SESSION['user'];
            break;
        }
        switch (TRUE) {
          case (isset($_SERVER['HTTP_X_REQUESTED_WITH'])):
            $this->json();
            break;
          case (method_exists($this, $subroutine)):
            $this->$subroutine();
            break;
          default:
            http_response_code(401);
            die("Invalid function: {$subroutine}");
		}
	}
    function json() {
        $this->trace[]=__FUNCTION__;
        $this->response=new stdClass();
        $this->json_error="";
        $json_function="json_" . $_POST['function'];
        $this->stopwatch=new stopwatch_class($json_function);
        switch (TRUE) {
          case ($_POST['action'] == "curl_ipv4"):
            $this->json_curl_ipv4();
            break;
          case (method_exists($this,$json_function)):
            $this->response->toolbox_status=$this->$json_function();
            break;
          default:
            $this->response->toolbox_status="Function {$json_function} not found";
        }
        if (strlen($this->json_error)) $this->response->toolbox_status=$this->json_error;
        $this->response->trace=$this->trace;
        $this->response->stopwatch=$this->stopwatch->results(TRUE);
        die( json_encode($this->response) );
    }
    function json_utility() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $json_function="json_utility_" . $_POST['action'];
        if (method_exists($this,$json_function)) {
            return $this->$json_function();
          } else {
            return "Function {$json_function} not found";
        }
    }
    function json_utility_load() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->response->utility_configurator="";
        $this->response->utility_content="";
        $url=trim($_POST['input']['configurator_url']);
        if (!strlen($url)) return "URL cannot be blank";
        $parse=parse_url($url);
        switch (TRUE) {
          case (!array_key_exists("scheme", $parse)):
            return "Scheme missing (http|https)";
          case (!array_key_exists("host", $parse)):
            return "Host missing";
          case (!array_key_exists("path", $parse)):
            return "Path missing (try ending was / (slash)";
        }
        $portlets=array();
        foreach ($_POST['input'] as $key => $value) {
            list($code, $field)=fn_base64($key, FALSE);
            switch ($code) {
              case "hidePortlets":
                if (true_false($value)) $portlets[]="hidePortlets={$field}";
                break;
            }
        }
        $prj_url=$url;
        if (sizeof($portlets)) $prj_url .= ((strlen($parse['query'])) ? "&" : "?") . implode("&", $portlets);
        $this->response->parse=$parse;
        $this->response->utility_configurator="<iframe id=scscpq_configurator_iframe src='{$prj_url}' style='height: 1000px; width: 98%; frameborder: 0' onload='javascript:cad_listener(this.id);'></iframe>";
    }
    function json_utility_sku() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->configurator=$database->fetch_json($_POST['configurator']);
        if (!strlen($this->configurator->lina)) return;
        $this->response->configurator=$this->configurator;
        $results=array();
        $text=array("lina");
        if ($this->configurator->lina == $this->configurator->standardName)  $text[]="standardName";
        $results[]="<div class=utility_title>" . implode(" / ", $text) . "</div>";
        $results[]="<div class=utility_body>{$this->configurator->lina}</div>";
        if ($this->configurator->lina != $this->configurator->standardName) {
            $results[]="<div class=utility_title>standardName</div>";
            $results[]="<div class=utility_body>{$this->configurator->standardName}</div>";
        }
        $mident=new parse_mident($this->configurator->partId);
        $text=array();
        $text[]="<table class='standard border tablesorter'>";
        $text[]="<thead>";
        $text[]="<tr>";
        $text[]="<th width='10%'>Field</th>";
        $text[]="<th width='90%'>Value</th>";
        $text[]="</tr>";
        $text[]="</thead>";
        $text[]="<tbody>";
        foreach ($mident->fields as $field => $value) {
            $text[]="<tr>";
            $text[]="<td>{$field}</td>";
            $text[]="<td>{$value}</td>";
            $text[]="</tr>";
        }
        $text[]="</tbody>";
        $text[]="</table>";
        $results[]="<div class=utility_body>" . implode("", $text) . "</div>";
        $results[]="<div class=utility_title>PRJ</div>";
        $prj=$database->cad->partid_prj($this->configurator->partId);
        $text=array("info={$prj['info']}");
        if (strlen($prj['varset'])) $text[]="varset={$prj['varset']}";
        $results[]="<div>" . implode("&", $text) . "</div>";

        $results[]="<div class=utility_title>Complete Varset: Parse</div>";
        $text=array();
        foreach ( $database->cad->parse_completeVarset($this->configurator->completeVarset) as $key => $value) {
            $text[]="{$key}: {$value}";
        }
        $results[]="<div>" . implode("<br>", $text) . "</div>";
        $results[]="<div class=utility_title>Complete Varset: Value</div>";
        $results[]="<div>{$this->configurator->completeVarset}</div>";
        $this->response->utility_content=implode("", $results);
    }

    function json_curl_ipv4() {
        $url="https://scscpq.com/scs_ipv4.php";
		$this->curl_ch=curl_init();
	    curl_setopt($this->curl_ch, CURLOPT_URL, $url);
	    curl_setopt($this->curl_ch, CURLOPT_HEADER, FALSE);
	    curl_setopt($this->curl_ch, CURLOPT_POST, TRUE);
        curl_setopt($this->curl_ch, CURLOPT_POSTFIELDS, array("SERVER_ADDR" => $_SERVER['SERVER_ADDR'], "SERVER_NAME" => $_SERVER['SERVER_NAME']));
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) curl_setopt($this->curl_ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	    curl_setopt($this->curl_ch, CURLOPT_RETURNTRANSFER, TRUE);
	    $this->curl_response=curl_exec($this->curl_ch);
        $this->curl_error_no=intval(curl_errno($this->curl_ch));
        $this->curl_info=curl_getinfo($this->curl_ch);
	    curl_close($this->curl_ch);
        $text=array();
        if ($this->curl_info['http_code'] == "200") {
            $json=json_decode($this->curl_response);
            $text[]=$json->description;
          } else {
            $text[]="URL: {$url}";
            $text[]="HTTP Response: {$this->curl_info['http_code']}";
        }
        $this->response->curl_ipv4=implode("<br>", $text);
    }
    function json_lookup() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $obj=json_decode($_POST['configurator']);
        $this->configurator=(is_object($obj)) ? $obj : new stdClass();
        $this->response->configurator=$this->configurator;

        $this->input->source=trim($_POST['input']['source']);
        $this->input->sku=fn_text($_POST['input']['sku']);
        $this->input->prj=trim($_POST['input']['prj']);
        $this->input->info=trim($_POST['input']['info']);
        $this->input->varset=trim($_POST['input']['varset']);
        $this->input->partid=str_replace("\n","",trim($_POST['input']['partid']));
        $this->input->cad_code=trim($_POST['input']['cad_code']);
        $this->input->generate=true_false($_POST['input']['generate']);
        $this->input->staging=true_false($_POST['input']['staging']);
        $this->input->language=trim(strtolower($_POST['input']['language']));
        $this->input->debug=true_false($_POST['input']['debug']);
        $this->input->parse=true_false($_POST['input']['parse']);
        $this->input->log=true_false($_POST['input']['log']);

        if ($this->input->debug) {
            $database->registry->cad->configurator=trim($_POST['input']['configurator_url']);
            $database->registry->cad->catalog=trim($_POST['input']['catalog']);
            $database->registry->cad->apikey=trim($_POST['input']['apikey']);
          } else {
            $this->response->configurator_url=$database->registry->cad->configurator;
            $this->response->catalog=$database->registry->cad->catalog;
            $this->response->apikey=$database->registry->cad->apikey;
        }

        $this->response->input=$this->input;
        $this->response->curl="";
        $this->response->lookup_parse="";
        $this->response->varset_compare="";
        $json_function="json_lookup_" . $_POST['action'];
        if (method_exists($this,$json_function)) {
            return $this->$json_function();
          } else {
            return "Function {$json_function} not found";
        }
    }
    function json_lookup_load() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->lookup=new stdClass();
        $this->response->lookup_configurator="";
        $this->response->cad_url="";
        $this->response->reversemap_url="";
        return $this->json_lookup_process();
    }
    function json_lookup_review() {
        $this->trace[]=__FUNCTION__;
        $this->stopwatch->split(__FUNCTION__);
        $this->json_error=$this->json_lookup_review_items();
        if (!strlen($this->json_error)) return TRUE;
    }
    function json_lookup_review_items() {
        $this->trace[]=__FUNCTION__;
        global $database;
        switch (TRUE) {
          case ($_POST['function'] != "lookup");
          case ($this->input->source == "sku"):
          case ($this->input->source == "prj"):
            break;
          case ( ($this->input->source == "partid") && (!strlen($this->input->partid)) ):
          case ( ($this->input->source == "varset") && (!strlen($this->input->info)) ):
            return;
        }
        switch (TRUE) {
          case ($this->input->source == "sku"):
            if (!strlen($this->input->sku)) return "SKU cannot be blank";
            break;
          case ($this->input->source == "partid"):
            if (!strlen($this->input->partid)) return "PartID cannot be blank";
            break;
          case ($this->input->source == "prj"):
            switch (TRUE) {
              case (!strlen($this->input->prj)):
                return "PRJ cannot be blank";
              case (!$database->cad->prj_verify($this->input->prj)):
                return "Malformed PRJ";
            }
            break;
          case ($this->input->source == "varset"):
            if (!strlen($this->input->info)) return "Info cannot be blank";
            break;
        }
    }
    function json_lookup_process() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->stopwatch->split(__FUNCTION__);
        if (!$this->json_lookup_review()) return;
        $options=array();
        if ($this->input->source == "sku") {
            $lookup_options=array();
            $lookup_options['log']=$this->input->log;
            $lookup_options['staging']=$this->input->staging;
            $this->lookup=new cad_lookup_class($this->input->sku, $lookup_options);
            $this->response->lookup=$this->lookup;
            $this->response->reversemap_url=(strlen($this->lookup->reversemap_url)) ? fn_href($this->lookup->reversemap_url, $this->lookup->reversemap_url, array(), array("target"=>"_blank")) : "N/A";
            if (strlen($this->lookup->error)) return "Error: {$this->lookup->error}";
            $options['url_query']=$this->lookup->url_query;
          } else {
            switch ($this->input->source) {
              case "partid":
                $options['partid']=$this->input->partid;
                break;
              case "prj":
                $options['prj']=$this->input->prj;
                break;
              default:
                $options['url_query']=array();
                $options['url_query']['info']=$this->input->info;
                $options['url_query']['varset']=$this->input->varset;
            }
        }
        $options['staging']=$this->input->staging;
        $options['hidePortlets']=array();
        foreach ($_POST['input'] as $key => $value) {
            list($code, $field)=fn_base64($key, FALSE);
            switch ($code) {
              case "hidePortlets":
                if (true_false($value)) $options['hidePortlets'][]=$field;
                break;
              case "cad_options":
                $options[$field]=$value;
                break;
            }
        }
        if (strlen($this->input->language)) $options['languageIso']=$this->input->language;
        $url=$database->cad->url($options);
        $this->response->options=$options;
        $this->response->cad_options=$cad_options;
        $this->response->lookup_configurator="<iframe id=scscpq_configurator_iframe src='{$url}' style='height: 1000px; width: 100%; frameborder: 0' onload='javascript:cad_listener(this.id);'></iframe>";
        $this->response->cad_url=(strlen($url)) ? fn_href($url, $url, array(), array("target"=>"_blank")) : "";
    }
    function json_lookup_sku() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->stopwatch->split(__FUNCTION__);
        $prj=$database->cad->partid_prj($this->configurator->partId);
        $this->response->sku=$this->configurator->standardName;
        $this->response->partid=$this->configurator->partId;
        $this->response->prj="info=" . $prj['info'] ."&varset=" . $prj['varset'];
        $this->response->info=$prj['info'];
        $this->response->varset=$prj['varset'];

        $mident=new parse_mident($this->configurator->partId);
        $results=array();
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<thead>";
        $results[]="<tr><th colspan=2>partId</th></tr>";
        $results[]="<tr>";
        $results[]="<th width='10%'>Field</th>";
        $results[]="<th width='90%'>Value</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        $results[]="<tr><td>PRJ</td><td>{$mident->info}</td></tr>";
        foreach ($mident->fields as $key => $value) {
            $results[]="<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        $results[]="</table>";
        $results[]="<table class='standard border tablesorter'>";
        $results[]="<caption>&nbsp;</caption>";
        $results[]="<thead>";
        $results[]="<tr><th colspan=2>completeVarset</th></tr>";
        $results[]="<tr>";
        $results[]="<th width='10%'>Field</th>";
        $results[]="<th width='90%'>Value</th>";
        $results[]="</tr>";
        $results[]="</thead>";
        $results[]="<tbody>";
        foreach ($database->cad->parse_completeVarset($this->configurator->completeVarset) as $key => $value) {
            $results[]="<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        $results[]="</table>";
        $this->response->lookup_parse=implode("", $results);

    }
    function json_lookup_cad() {
        $this->trace[]=__FUNCTION__;
        $this->response->cad_working=$this->json_lookup_cad_process();
    }
    function json_lookup_cad_process() {
        global $database;
        $this->trace[]=__FUNCTION__;
        if (!$this->json_lookup_review()) return;
        $options=array();
        $options['generate']=$this->input->generate;
        $options['staging']=$this->input->staging;
        $options['generate_sku']=FALSE;
        if ($this->input->debug) $options['debug']=1;

        switch (TRUE) {
          case (!strlen($this->input->cad_code)):
            return "CAD Format not selected";
          case ($this->input->source == "prj"):
            $options['prj']=$this->input->prj;
            break;
          case ($this->input->source == "varset"):
            $prj=array();
            $prj['info']=$this->input->info;
            if (strlen($this->input->varset)) $prj['varset']=$this->input->varset;
            $options['prj']=$prj;
            break;
          case ($this->input->source == "partid"):
            $options['prj']=$database->cad->partid_prj($this->configurator->partId);
            break;
          default:
            $options['generate_sku']=TRUE;
            break;
        }

        $options['log']=$this->input->log;
        $options['user']=$this->user;
        if ($this->input->debug) {
            $options['catalog']=trim($_POST['input']['catalog']);
            $options['apikey']=trim($_POST['input']['apikey']);
        }
        $this->response->options=$options;
        $database->cad->generate($this->input->sku, $this->input->cad_code, $options);
        $this->response->cad_generate=$database->cad->generate->results;

        $curl=array();
        $curl[]="URL: {$database->cad->generate->partserver_url}";
        $curl[]="";
        $curl[]="CURL Data";
        foreach ($database->cad->generate->curl_data as $key => $value) {
            $curl[]="{$key}: {$value}";
        }
        $curl[]="";
        $curl[]=fn_href("CURL Log", "curl.txt", array(), array("target"=>"_blank"));
        $this->response->curl=implode("<br>", $curl);

        $results=array();
        switch (TRUE) {
          case ($database->cad->generate->error):
            $results[]=$database->cad->generate->error;
            break;
          case ($database->cad->generate->generate):
            $results[]=$database->cad->generate->results->cad_link;
            break;
          default:
            $results[]=fn_href("Download File ({$this->input->sku})",$database->cad->generate->results->cad_link,array(),array("target"=>"_blank"));
        }
        if (!strlen($database->cad->generate->error)) $results[]="Transaction ID: " .  $database->cad->generate->results->transaction_id;
        return implode("<br>", $results);
    }
    function json_lookup_varset_load() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->response->varset_field_error="";
        $text=array();
        foreach ($database->cad->parse_varset($this->input->varset) as $code => $value) {
            if (!strlen($value)) continue;
            $text[]="{$code}={$value}";
        }
        sort($text);
        $this->response->varset_field=implode("\n", $text);
    }
    function json_lookup_varset_save() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->response->varset_field_error="";
        $this->json_lookup_varset_review();
        if (sizeof($this->error)) {
            $this->response->varset_field_error=implode("<br>", $this->error);
            return;
        }
        $results=array();
        foreach ($this->varset as $key => $value) {
            $results[]="{{$key}={$value}}";
        }
        $this->response->varset=implode(",", $results);
    }
    function json_lookup_varset_review() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->varset=array();
        $this->error=array();
        foreach (explode("\n", $_POST['input']['varset_field']) as $text) {
            $text=trim($text);
            $code=explode("=", $text, 2);
            $key=trim(strtoupper($code[0]));
            $value=trim($code[1]);
            switch (TRUE) {
              case (!strlen($text)):
                break;
              case (sizeof($code) != 2):
                $this->error[]="Must be key=value format: {$text}:";
                break;
              case (!strlen($key)):
                $this->error[]="Key blank: {$text}:";
                break;
              case (array_key_exists($key, $this->varset)):
                $this->error[]="Duplicate key: {$text}";
                break;
              default:
                $this->varset[$key]=$value;
            }
        }
        ksort($this->varset);
        if (sizeof($this->error)) array_unshift($this->error,"<b>Correct " . sizeof($this->error) . " error(s)</b>");
    }
    function json_lookup_varset_compare() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->json_lookup_varset_review();
        if (sizeof($this->error)) {
            $this->response->varset_compare=implode("<br>", $this->error);
            return;
        }
        $compare=array();
        foreach ($database->cad->parse_completeVarset($_POST['input']['varset']) as $code => $value) {
            switch (TRUE) {
              case ($code == "NB"):
              case (!array_key_exists($code, $this->varset)):
              case ($this->varset[$code] == $value):
                break;
              default:
                $compare[$code]=$value;
            }
        }
        if (!sizeof($compare)) {
            $this->response->varset_compare="All fields match";
          } else {
            ksort($compare);
            $results=array("<table class='border'>");
            $results[]="<tr><th>Code</th><th>Sent</th><th>Received</th></tr>";
            foreach ($compare as $code => $value) {
                $results[]="<tr>";
                $results[]="<td>{$code}</td>";
                $results[]="<td>{$this->varset[$code]}</td>";
                $results[]="<td>{$value}</td>";
                $results[]="</tr>";
            }
            $results[]="</table>";
            $this->response->varset_compare=implode("", $results);
        }
    }
    function html_lookup() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
	    $forms->title("Configurator Toolbox (" . key($this->scs_table_version("lookup")) . ")");
	    $menu->head();
	    $forms->message();
        $this->input->action=$_GET['action'];
        switch ($this->input->action) {
          case "ipc":
            $this->input->source=$_GET['source'];
            $this->input->prj=$_GET['prj'];
            $this->input->sku=$_GET['sku'];
            break;
        }
        print $this->js();
        print $this->lookup_js();
		print $forms->open();
        print $forms->hidden("action","");
        print $forms->hidden("command","");
        if (array_key_exists("fixed", $this->options)) print $this->lookup_css();
        print "<table id=lookup_entry class='noborder'>";
        print "<tr>";
        print "<td width='60%'>";
            print "<table class='border'>";
            print "<tr><th colspan=2>Configurator</th></td>";
            print "<tr class=lookup_debug>";
            print "<td>Configurator</td>";
            print "<td>" . $forms->text("configurator_url", $database->registry->cad->configurator, 80, 0, "", array("class"=>"input")) . "</td>";
            print "</tr>";
            print "<tr class=lookup_debug>";
            print "<td>" . $forms->button("CURL IPV4",array("onclick"=>"cad_ajax('curl_ipv4');")) . "</td>";
            print "<td id=curl_ipv4></td>";
            print "</tr>";
            print "<td width='20%'>Source</td>";
            $options=array();
            if ($database->registry->cad->partserver_reversemap) $options["sku"]="Part#";
            $options["prj"]="PRJ";
            $options["varset"]="Info/Varset";
            $options["partid"]="Part ID";
            print "<td width='80%'>" . $forms->select("source", $options, $this->input->source, FALSE, array("class"=>"input", "onchange"=>"lookup_source();")) . "</td>";

            print "</tr>";
            print "<tr class=sku_class>";
            print "<td width='20%'>Part Number</td>";
            print "<td width='80%'>" . $forms->textarea("sku","",2,80,array("class"=>"input")) . "</td>";
            print "</tr>";

            print "<tr class=prj_class>";
            print "<td>PRJ</td>";
            print "<td>" . $forms->textarea("prj",$this->input->prj,8,60,array("class"=>"input")) . "</td>";
            print "</tr>";

            print "<tr class=varset_class>";
            print "<td>Info</td>";
            print "<td>" . $forms->textarea("info","",3,60,array("class"=>"input")) . "</td>";
            print "</tr>";
            print "<tr class=varset_class>";
            $text=($database->user->access("Super User",FALSE)) ? $forms->button("Edit Varset", array("onclick"=>"jQuery('#varset_values_tr').show();")) : "Varset";
            print "<td>{$text}</td>";
            print "<td>" . $forms->textarea("varset","",12,60,array("class"=>"input")) . "</td>";
            print "</tr>";

            print "<tr class=partid_class>";
            print "<td>PartId</td>";
            print "<td>" . $forms->textarea("partid","",12,60,array("class"=>"input")) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td id=lookup_button>" . $forms->button("Load Configurator",array("onclick"=>"cad_ajax('load');")) . "</td>";
            print "<td id=toolbox_status colspan=2></td>";
            print "</tr>";
            if (isset($this->user)) {
                print "<tr><th colspan=2>Generate CAD</th></tr>";
                print "<tr class=lookup_debug>";
                print "<td>Catalog</td>";
                print "<td>" . $forms->text("catalog", $database->registry->cad->catalog, 40, 40, "", array("class"=>"input"));
                print "</tr>";
                print "<tr class=lookup_debug>";
                print "<td>API Key</td>";
                print "<td>" . $forms->text("apikey", $database->registry->cad->apikey, 40, 40, "", array("class"=>"input"));
                print "</tr>";
                print "<tr>";
                print "<tr>";
                print "<td>CAD Format</td>";
                print "<td>" . $database->cad->select("PDFDATASHEET", array("blank"=>FALSE), array("class"=>"input")) . "</td>";
                print "</tr>";
                print "<tr>";
                print "<td>CAD Output</td>";
                print "<td>" . $forms->select("generate",array(0=>"Download",1=>"Email"),"",FALSE,array("class"=>"input"),array("raw"=>TRUE)) . "</td>";
                print "</tr>";
                print "<tr>";
                print "<td>" . $forms->button("Generate CAD",array("onclick"=>"cad_ajax('cad');")) . "</td>";
                print "<td id=cad_working></td>";
                print "</tr>";
            }
            print "</table>";
        print "</td>";
        print "<td width='40%'>";
            print "<table class='border'>";
            print "<tr><th colspan=2>Options</th></td>";
            print "<tr>";
            print "<td width='30%'>QA Server?</td>";
            print "<td width='70%'>" . $forms->checkbox("staging", 1, preg_match("/" . preg_quote(".qa.") . "/i", $database->registry->cad->configurator), array("class"=>"input")) . "</td>";
            print "</tr>";
            print "<tr>";
            print "<td>Language</td>";
            print "<td>" . $forms->select("language", $database->cad->constant->language, "", FALSE, array("class"=>"input")) . " (languageIso)</td>";
            print "</tr>";
            if ($database->user->access("Administrator",FALSE)) {
                print "<tr>";
                print "<td>Options</td>";
                $text=array();
                $text[]=$forms->checkbox(fn_base64(array("cad_options", "partFixed")),1,0,array("class"=>"input")) . " Fixed Part#? (partFixed)";
                print "<td>" . implode("<br>", $text) . "</td>";
                print "</tr>";
                print "<tr>";
                print "<td>Hide Portlets</td>";
                $text=array();
                foreach ($database->cad->constant->hidePortlets as $field => $name) {
                    $text[]=$forms->checkbox(fn_base64(array("hidePortlets", $field)),1,0,array("class"=>"input")) . " {$name}? ({$field})";
                }
                print "<td>" . implode("<br>", $text) . "</td>";
                print "</tr>";
                if (!array_key_exists("nolog", $this->options)) {
                    print "<tr>";
                    print "<td>Log Errors?</td>";
                    print "<td>" . $forms->checkbox("log",1,0,array("class"=>"input")) . "</td>";
                    print "</tr>";
                }
                print "<tr>";
                print "<td>Show Variables?</td>";
                print "<td>" . $forms->checkbox("parse",1,0,array("class"=>"input","onclick"=>"lookup_screen(this.checked);")) . "</td>";
                print "</tr>";
                print "<tr>";
                print "<td>Debug?</td>";
                print "<td>" . $forms->checkbox("debug",1,0,array("class"=>"input","onclick"=>"lookup_screen(this.checked);")) . "</td>";
                print "</tr>";
                print "<tr id=varset_values_tr>";
                $text=array();
                $items=array();
                $items[]=$forms->button("Varset -> Editor",array("onclick"=>"cad_ajax('varset_load');"));
                $items[]=$forms->button("Varset <- Editor",array("onclick"=>"cad_ajax('varset_save');"));
                $items[]=$forms->button("Compare varset",array("onclick"=>"cad_ajax('varset_compare');"));
                $text[]="<p>" . implode(" ", $items) . "</p>";
                $text[]="<div id=varset_field_error></div>";
                $text[]=$forms->textarea("varset_field", "", 20, 80, array("class"=>"input"));
                $text[]="<div id=varset_compare></div>";
                print "<td colspan=2>" . implode("" ,$text) . "</td>";
                print "</tr>";
             } else {
                print $forms->hidden(fn_base64(array("cad_options", "partFixed")), "false", "", "input");
            }
            print "</table>";

        print "</td>";
        print "</table>";
        print "<table class='border' class=lookup_debug>";
        print "<caption>&nbsp;</caption>";
        print "<tr>";
        print "<td width='10%'>Part URL</td>";
        print "<td width='90%' id=cad_url></td>";
        print "</tr>";
        if ($database->registry->cad->partserver_reversemap) {
            print "<tr>";
            print "<td>Reversemap URL</td>";
            print "<td id=reversemap_url></td>";
            print "</tr>";
        }
        print "<tr>";
        print "<td>Stopwatch</td>";
        print "<td id=stopwatch></td>";
        print "</tr>";
        print "<tr>";
        print "<td>CURL</td>";
        print "<td id=curl></td>";
        print "</tr>";
        print "</table>";
        if ( ($this->input->action == "ipc") && (strlen($this->input->sku)) ) print "<p>Lookup error detected for SKU: <b>{$this->input->sku}</b></p>";
        print "<table class='noborder'>";
        print "<tr>";
        print "<td width='60%' id=lookup_configurator></td>";
        print "<td width='40%' id=lookup_parse></td>";
        print "</tr>";
        print "</table>";
		print $forms->close();
		$menu->copyright();
    }
    function html_cad() {
        global $database, $forms, $menu;
        $this->input->action=$_POST['action'];
        $this->input->command=$_POST['command'];
        $this->input->record_id=intval($_POST['record_id']);
    	$this->command_list=array();
		$this->command_list["xml"]="Part Community Import";
		$this->command_list["list"]="CAD Code List";
		$this->command_list["utility"]="CAD Utility";
        $forms->title("CAD Toolbox", key($this->scs_table_version("cad")));
        switch (TRUE) {
          case ($this->input->command == "qa_update"):
            if (property_exists($_SESSION['user'], "qa_server")) {
                unset($_SESSION['user']->qa_server);
                $forms->message[]="Force QA Server";
              } else {
                $_SESSION['user']->qa_server=1;
                $forms->message[]="Force Production Server";
                fn_url_redirect($menu->page->home);
            }
            break;
          case (strlen($this->input->command)):
            $forms->message[]=$this->command_list[$this->input->command];
            break;
          default:
            $forms->message[]="Last import: " . ( ($database->registry->cad->import_timestamp) ? date("m/d/Y h:i A",$database->registry->cad->import_timestamp) : "Never");
        }
        switch (TRUE) {
		  case (!$database->user->access("Administrator", FALSE)):
          case (preg_match("/.qa./i", $database->registry->cad->configurator)):
            break;
          default:
            $this->command_list["qa"]=($_SESSION['user']->qa_server) ? "Force Production Server" : "Force QA Server";
        }

	    $menu->head();
	    $forms->message();
        print $this->js();
		print $forms->open();
        switch ($this->input->command) {
          case "xml":
          	$this->html_cad_xml();
            break;
          case "list":
          	$this->html_cad_list();
            break;
          case "utility":
            $this->html_cad_utility();
            break;
          case "qa":
            $this->html_cad_qa();
            break;
          default:
			$text=array();
	        foreach ($this->command_list as $key=>$value) {
	            $text[]=$forms->radio("commands",$key,$this->input->command,array("onclick"=>"cad_command(this.value,'');")) . " $value";
	        }
	        $text[]="";
	        print "<p class=standard>" . implode("<br>",$text) . "</p>";
		}
        print $forms->hidden_fields($this->input);
        print $forms->close();
	    $menu->copyright();
    }
    function html_cad_utility() {
        global $database, $forms;
        print $this->utility_css();
        print "<table class='standard noborder'>";
        print "<tr>";
        $text=array();
        $text[]=$forms->button("Load Configurator",array("placeholder"=>"Configurator URL", "onclick"=>"cad_ajax('load','');"));
        $text[]=$forms->button("Return",array("onclick"=>"cad_command('','');"));
        print "<td width='20%'>" . implode(" ",  $text) . "</td>";
        print "<td width='80%'>" . $forms->textarea("configurator_url", $database->registry->cad->configurator, 3, 80, array("class"=>"input")) . "</td>";
        print "</tr>";
        print "<tr>";
        print "<td>Hide Portlets</td>";
        $text=array();
        foreach ($database->cad->constant->hidePortlets as $field => $name) {
            $text[]=$forms->checkbox(fn_base64(array("hidePortlets", $field)),1,0,array("class"=>"input")) . " {$name}";
        }
        print "<td>" . implode("<br>", $text) . "</td>";
        print "</tr>";
        print "</table>";
        print "<div id=toolbox_status></div>";
        print "<div style='width: 100%; clear: both; display: table;'>";
        print "<div id=utility_configurator style='width: 70%; float: left;'></div>";
        print "<div id=utility_content style='width: 30%; float: right;'></div>";
        print "</div>";
    }
    function html_cad_qa() {
        global $database, $forms;
        $text=array();
        $text[]=$forms->button((property_exists($_SESSION['user'], "qa_server") ? "Force Production Server" : "Force QA Server"),array("id"=>"qa_button", "onclick"=>"cad_command('qa_update','');"));
        $text[]=$forms->button("Return",array("onclick"=>"cad_command('','');"));
        print "<p>" . implode(" ", $text) . "</p>";
    }
    function cron_cad() {
        global $database, $forms, $menu;
		$this->cad_process();
        die($this->cad_message);
    }
    function html_cad_xml() {
        global $database, $forms;
    	$this->cad_process();
		print "<p><b>{$this->cad_message}</b></p>";
		print "<p>" . $forms->button("Return",array("onclick"=>"cad_command('','');")) . "</p>";
    }

    function cad_process() {
        global $database, $forms;
        $this->cad_message=$this->cad_process_update();
        $comment=array();
        $comment[]=$this->cad_message;
        $comment[]="URL: {$this->cad_url}";
		$type=array();
		$type[]=($forms->cron) ? "CRON" : "Import";
		$type[]="CAD";
        $database->log->ipc($type, $comment);
    }
    function cad_process_update() {
        global $database;
        if (!function_exists("simplexml_load_string")) return "Program missing: simplexml_load_string";
        $options=array();
        if (strlen($database->registry->cad->portal)) {
            $options['portal']=$database->registry->cad->portal;
          } else {
            $options['firm']=$database->registry->cad->catalog;
        }
		$this->cad_url=fn_url("https://www.partcommunity.com/cadqualifier.asp", $options);
		$this->curl_ch=curl_init();
	    curl_setopt($this->curl_ch, CURLOPT_URL, $this->cad_url);
	    curl_setopt($this->curl_ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->curl_ch, CURLOPT_POST, FALSE);
	    curl_setopt($this->curl_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl_ch, CURLOPT_STDERR, $this->curl_fh);
	    $this->curl_response=curl_exec($this->curl_ch);
        $this->curl_error_no=intval(curl_errno($this->curl_ch));
        $this->curl_info=curl_getinfo($this->curl_ch);
	    curl_close($this->curl_ch);
        if ($this->curl_error_no != 0) {
            $text=array();
            if (is_array($this->curl_info)) $text[]="HTTP Code: {$this->curl_info['http_code']}";
            $text[]="CURL Error #{$this->curl_error_no}";
            return "Import Error: " . implode(", ", $text);
        }
        $xml=@simplexml_load_string($this->curl_response);
        switch (TRUE) {
          case (!is_object($xml)):
          case (!property_exists($xml,"format")):
            return "Malformed XML response";
        }
        $results=array();
        $xml_data=json_decode(json_encode($xml), TRUE);
        foreach ($xml_data['format'] as $item) {
            $data=new cad_data_class();
            $data->code=$item['qualifier'];
            $data->qualifier=$item['name'];
            $data->version=(is_string($item['version'])) ? $item['version'] : "";
            $data->name=trim($data->qualifier . " " .  $data->version);
            $data->instructions=$data->helplink;
            $data->native=( ($item['@attributes']['type'] == "native") ? 1 : 0);
            $data->macro=( ($item['macro'] == "true") ? 1 : 0);
            $data->direct=true_false($item['directintocad']);
            if ($data->direct) {
                $data->type="PART2CAD";
              } else {
                $data->type=strtoupper($item['@attributes']['cad']);
            }
            $results[$data->code]=$data;
        }
        if (!sizeof($results)) return "No records found";
		$query="drop table if exists cad";
        $database->temp->query($query);
        $query=array();
        $query[]="CREATE TABLE `cad` (";
        $query[]="`id` int(6) NOT NULL AUTO_INCREMENT,";
        $query[]="`code` varchar(40) DEFAULT NULL,";
        $query[]="`name` varchar(80) DEFAULT '',";
        $query[]="`qualifier` varchar(60) DEFAULT '',";
        $query[]="`version` varchar(60) DEFAULT '',";
        $query[]="`type` varchar(20) DEFAULT '',";
        $query[]="`instructions` mediumtext,";
        $query[]="`macro` int(1) DEFAULT '0',";
        $query[]="`native` int(1) DEFAULT '0',";
        $query[]="`direct` int(1) DEFAULT '0',";
        $query[]="`active` int(1) DEFAULT '1',";
        $query[]="PRIMARY KEY (`id`),";
        $query[]="UNIQUE KEY `code_key` (`code`)";
        $query[]=") ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='CAD formats (" . date("m/d/Y") . ")'";
        $database->temp->query($query);
        $database->cad->meta->error_abort=FALSE;
        foreach ($results as $database->cad->data) {
            $database->cad->update(FALSE);
        }
        $database->registry->update("cad","import_timestamp",strtotime("now"));
        return number_format(sizeof($results)) . " records processed";
    }
    function html_cad_list() {
        global $database, $forms;
        $database->cad->query("select * from cad order by id");
        print "<p><b>" . number_format($database->cad->meta->rows) . " Records Found</b></p>";
        print "<table class='border tablesorter'>";
        print "<thead>";
        print "<tr>";
        print "<th>Code</th>";
        print "<th>Qualifier</th>";
        print "<th>Version</th>";
        print "<th>2D/3D</th>";
        print "<th>Macro?</th>";
        print "<th>Native?</th>";
        print "<th>Direct to CAD?</th>";
        print "</tr>";
        print "</thead>";
        print "<tbody>";
        while ($database->cad->fetch = $database->cad->fetch_array()) {
            $database->cad->fetch();
            print "<tr>";
            print "<td>" . $database->cad->data->code . "</td>";
            print "<td>" . $database->cad->data->qualifier . "</td>";
            print "<td>" . $database->cad->data->version . "</td>";
            print "<td class=center>" . $database->cad->data->type . "</td>";
            print "<td class=center>" . $database->cad->data->macro . "</td>";
            print "<td class=center>" . $database->cad->data->native . "</td>";
            print "<td class=center>" . $database->cad->data->direct . "</td>";
            print "</tr>";
        }
        print "</tbody>";
        $database->cad->free_result();
        print "</table>";
		print "<p>" . $forms->button("Return",array("onclick"=>"cad_command('','');")) . "</p>";
    }
    function js() {
        return <<< EOT
<script>
var scscpq_function="utility";
var scscpq_action;
var scscpq_data;
var scscpq_id;
function cad_command(command, action) {
    if ( (command=='xml') &&  (!confirm('All existing records will be replaced')) ) return;
    jQuery("#command").val(command);
    jQuery("#action").val(action);
    jQuery("#scs_form").submit();
}
function cad_ajax(action, configurator) {
    field="#" + action + "_working";
    if (configurator == undefined) configurator=scscpq_data;
    jQuery(field).html("<b>Working ...</b>");
    jQuery.ajax({
        dataType: 'json',
        method: 'post',
        data: { function: scscpq_function, action: action, configurator: configurator, input: scscpq_class_data('input') },
        success: function(response) { scscpq_fill(response) },
        error: function (request, status, error) {
            jQuery(field).html("<b>Error Detected</b>"),
            console.log(error)
        }
    });
}
function cad_listener(id) {
    scscpq_id=id;
    scscpq_message('PCOM_INFO', "sku");
}
function scscpq_message(message, action) {
    try {
        jQuery("#" + scscpq_id)[0].contentWindow.postMessage(message, '*');
        scscpq_action=action;
    }
    catch(error) {
        action=scscpq_action;
    }
    scscpq_action=action;
}

jQuery(window).unbind('message');
jQuery(window).on('message', function(e) {
    var response=e.originalEvent.data;
    console.log(response);
    console.log(scscpq_action);
    switch (typeof(response)) {
    case "object":
        switch (true) {
        case ( (typeof(response.width) != "undefined") && (typeof(response.height) != "undefined") ):
            if (scscpq_action == "size") {
                if (scscpq_resize) {
                    if (response.height) document.getElementById(scscpq_id).style.height = (response.height + 30) + "px";
                    scscpq_resize=false;
                }
            }
            scscpq_message('PCOM_INFO', "sku");
            break;
        case (typeof(response.standardName) != "undefined"):
            switch (scscpq_action) {
            case "sku":
                if (JSON.stringify(response) == scscpq_data) break;
                scscpq_data=JSON.stringify(response);
                cad_ajax('sku');
                scscpq_action="";
                break;
            }
            break;
        }
      break;
    case "string":
        switch (response) {
        case "PCOM_PART_CHANGED":
            scscpq_message('PCOM_INFO', "sku");
            break;
        }
    }
});

</script>
EOT;
    }
    function lookup_js() {
        return <<< EOT
<script>
var scscpq_function="lookup";
jQuery(document).ready(function() {
    lookup_screen();
    lookup_source();
    switch (true) {
      case (typeof scscpq_class_data != "function" ):
        jQuery("#lookup_button").html("Missing function: scscpq_class_data");
        break;
      case (typeof scscpq_fill != "function" ):
        jQuery("#lookup_button").html("Missing function: scscpq_fill");
        break;
    }
});
function lookup_screen() {
    if (jQuery("#debug").prop('checked')) {
        jQuery(".lookup_debug").show();
      } else {
        jQuery(".lookup_debug").hide();
    }
    if (jQuery("#parse").prop('checked')) {
        jQuery("#lookup_parse").show();
      } else {
        jQuery("#lookup_parse").hide();
    }
}
function lookup_source() {
    show="." + jQuery("#source").val() + "_class";
    jQuery(".sku_class").hide();
    jQuery(".prj_class").hide();
    jQuery(".varset_class").hide();
    jQuery(".partid_class").hide();
    jQuery("#varset_values_tr").hide();
    jQuery("." + jQuery("#source").val() + "_class").show();
}
</script>
EOT;
    }
    function utility_css() {
        return <<< EOT

<style>
.utility_title {
    border-style: solid;
    padding: 2px;
    text-align: center;
    font-weight: bold;
    background-color: #ddd;
}
.utility_body {
    padding-top: 2px;
    padding-bottom: 5px;
}
</style>

EOT;
    }
    function lookup_css() {
        return <<< EOT
<style>
#lookup_entry { height: 400px; }
#lookup_configurator { height: 1050px; }
</style>
EOT;
    }
}
?>
