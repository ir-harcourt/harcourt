<?php
require_once "scs_header.php";
require_once "map_rev3.php";
require_once "classes/category.php";
require_once "classes/subcategory.php";
require_once "classes/inventory.php";
require_once "classes/partcommunity.php";
require_once "classes/configure.php";
$obj=new local_class();
class local_class {
    var $error;
    var $trace=array();
    function scs_table_version() {
        $results=array();
        $results['09/26/2025']="Add trace";
        $results['12/06/2024']="Remove CAD link";
        $results['09/17/2024']="Improve quick_cad";
        $results['08/18/2024']="Add profile";
        $results['02/21/2024']="Updates";
        $results['09/10/2022']="Initial release";
        return $results;
    }
    function __construct() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
          } else {
            $this->html();
        }
    }
    function html() {
        $function="html_" . trim($_REQUEST['action']);
        if (method_exists($this, $function)) {
            $this->error=$this->$function();
          } else {
            die("function: {$function}");
            $this->error="Function not found";
        }
        if ($this->error) die("Error detected {$this->error}");
    }
    function html_download() {
        global $database;
        $url=urldecode($_REQUEST['url']);
        $parse=parse_url($url);
        switch (TRUE) {
          case (!strlen($url)):
            return "Empty URL";
          case (!array_key_exists("scheme", $parse)):
          case (!array_key_exists("host", $parse)):
          case (!array_key_exists("path", $parse)):
            return "Malformed URL";
        }
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data=curl_exec($ch);
        $curl_errno=curl_errno($ch);
        $curl_info=curl_getinfo($ch);
        curl_close($ch);
        switch (TRUE) {
          case ($curl_error):
            return "CURL error: {$curl_errno}";
          case ($curl_info['http_code'] != 200):
            return "CURL HTTP: {$curl_info['http_code']}";
          case (!strlen($data)):
            return "Empty file";
        }
        $parse_path=explode("/", $parse['path']);
        $filename="harcourt_" . end($parse_path);
        header("Content-type: {$curl_info['$curl_info']}");
        header("Content-Disposition: attachment; filename={$filename}");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $data;
        die();
    }
    function json() {
        global $database;
        $this->trace[]=__FUNCTION__;
        $this->response=new stdClass();
        switch ($_POST['action']) {
          case "sku":
            $this->json_sku();
            break;
          case "cad":
            $this->json_cad();
            break;
          case "profile":
            $this->json_profile();
            break;
          case "quick_cad":
            $this->json_quick_cad();
            break;
        }
        if ($database->user->access("Super User", FALSE)) $this->response->trace=$this->trace;
        die( json_encode($this->response,JSON_UNESCAPED_UNICODE) );
    }
    function configurator() {
        $this->trace[]=__FUNCTION__;
        if ( (!array_key_exists("data", $_POST)) || (!is_array($_POST['data'])) ) return;
        $this->configure=new configure_class($_POST['data']['lina'], 1);
        $this->response->data=$_POST['data'];
        $this->response->meta=$this->configure->meta;
        return TRUE;
    }
    function json_sku() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        if (!$this->configurator()) return;

        $results=array();
        if ($this->configure->meta->error) {
	        $results[]="<div>Part number<br>{$this->configure->meta->sku_url}</div>";
	        $results[]="<div>{$database->registry->local->configure_contact}</div>";
		  } else {
            $results[]="<div>{$database->category->data->name}: {$database->subcategory->data->name}</div>";
            $results[]="<div>{$this->configure->meta->sku_url}</div>";
            $results[]="<div>" . $this->configure->output_verbose() . "</div>";
	        if ($_SESSION['user']->ecommerce) {
	            $text=array();
	            $field_sku=fn_base64(array("sku_cad",$this->configure->meta->sku));
	            $field_list=fn_base64(array("list_cad",$this->configure->meta->sku));
	            $text[]=$forms->text($field_sku,"",6,6,"",array("class"=>"products_sku_quantity"));
 	            $text[]=$forms->button("Add to Cart",array("class"=>"cart_button","onclick"=>"cart_update(this," . fn_escape($field_sku) . "," . fn_escape($field_list) . ");"));
	            $results[]="<p>" . implode(" ",$text) . "</p>";
	        }
		}
        if ($database->profile->quick_cad_access()) $results[]="<p>" . $forms->button("Download CAD",array("title"=>"Download CAD", "class"=>"quick_cad_button", "onclick"=>"quick_cad('', " . fn_escape($this->configure->meta->sku) . ",1);")) . "</p>";
	    $this->response->html=implode("<br>",$results);
    }
    function json_cad() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
    }
    function json_profile() {
        global $database, $forms;
        $this->trace[]=__FUNCTION__;
        $this->response->success_url="";
        $this->response->profile_tab="";
        $this->response->html=new stdClass();
        $this->response->error=new stdClass();
        $database->user->read($_SESSION['user']->id);
        $database->profile->address();
        foreach ($database->profile->address->options->omit as $field) {
            $database->profile->address->input["profile_{$field}"]=$database->profile->data->$field;
        }
        $database->profile->address->verify();
        $database->profile->data->generate=true_false($_POST['profile_generate']);
        $database->profile->data->cad_code=$_POST['cad_code'];
        if (!strlen($database->profile->data->cad_code)) $forms->error("cad_code");
        foreach ($database->profile->address->input as $field => $value) {
            $this->response->error->$field=(array_key_exists($field, $forms->error)) ? 1 : 0;
        }
        $this->response->error->cad_code_error=(array_key_exists("cad_code", $forms->error)) ? 1 : 0;
        if (sizeof($forms->error)) {
            $this->response->html->profile_message="Correct " . sizeof($forms->error) . " error(s)";
          } else {
            $this->json_profile_update();
        }
    }
    function json_profile_update() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $database->user->data->company_name=$database->profile->data->company_name;
        $database->user->data->address=$database->profile->data->address;
        $database->user->data->city=$database->profile->data->city;
        $database->user->data->state=$database->profile->data->state;
        $database->user->data->zip=$database->profile->data->zip;
        $database->user->data->country_code=$database->profile->data->country_code;
        $database->user->data->remote=$_SESSION['user']->remote;
        $database->cad->read($database->profile->data->cad_code, "code");
        $database->profile->data->cad_name=$database->cad->data->name;
        $database->user->update(TRUE);
        $menu->cookie($database->profile->data->email);
        $_SESSION['user']=$database->user->data;
        $database->profile->cookie();

        $this->response->html->quick_cad_status=$database->profile->quick_cad_message();
        $this->response->html->cad_name=$database->cad->data->name;
        $this->response->html->profile_message="Profile Updated";
        switch (TRUE) {
          case (strlen($_POST['success_url'])):
            $this->response->success_url=$_POST['success_url'];
            break;
          default:
            $this->response->profile_tab=(strlen($_POST['profile_tab'])) ? $_POST['profile_tab'] : "";
            $this->response->html->profile_tab="";
            break;
          }
    }
    function json_quick_cad() {
        global $database;
        $this->trace[]=__FUNCTION__;
        $database->profile->load();
        $this->response->html=new stdClass();
        $this->response->quick_cad_status=$this->json_quick_cad_process();
    }
    function json_quick_cad_process() {
        global $database, $forms, $menu;
        $this->trace[]=__FUNCTION__;
        $database->cad->read($database->profile->data->cad_code, "code");
        if (!$database->cad->meta->rows) return "Invalid CAD code: {$database->profile->data->cad_code}";
        $options=array();
        $options['user']=$database->profile->data;
        $options['log']=0;
        $options['generate']=intval($database->profile->data->generate);
        if (array_key_exists("configurator", $_POST)) {
            $json=json_decode($_POST['configurator']);
            $sku=$json->lina;
            $mident=new parse_mident($json->partId);
            $database->subcategory->read("info={$mident->info}", "prj");
            $database->category->read($database->subcategory->data->category_id);
            $options['prj']=http_build_query(array("info"=>$mident->info, "varset"=>$mident->varset));
          } else {
            $sku=$_REQUEST['sku'];
            $lookup=new cad_lookup_class($sku, array("log"=>FALSE));
            if (strlen($lookup->error)) return "Quick CAD not available for {$sku}";
            $mident=preg_split("/[{}]/", $lookup->json['mident'], 2, PREG_SPLIT_NO_EMPTY);
            $prj="info=" . substr($mident[0], strpos($mident[0], "{$database->registry->cad->catalog}/"));
            $database->subcategory->read($prj, "prj");
            $database->category->read($database->category->data->category_id);
            parse_str(parse_url($lookup->part_url, PHP_URL_QUERY), $prj);
            $options['url_query']=$lookup->url_query;
        }
        $options['zipfilename']="{$database->registry->cad->catalog}_{$sku}.zip";
        $database->cad->generate($sku, $database->profile->data->cad_code, $options);

//        if (fn_development_server())) $this->response->generate=$database->cad->generate;

        $options=array();
        $options['sku']=$sku;
        $options['subcategory_id']=$database->subcategory->data->id;
        $comment=array();
        if ($database->subcategory->data->id) $comment[]="{$database->category->data->name}: {$database->subcategory->data->name}";
        $comment[]="CAD Format: " . $database->cad->data->name;

        if ($database->cad->generate->error) {
            $type=array('PARTSolutions','Quick CAD Error');
            $comment[]=$database->cad->generate->error;
            $options['comment']=implode("\n", $comment);
            $database->log->update($type, $options);
            return "CAD Generator Error";
        }

        if (!$database->profile->data->generate) {
            $options=array();
            $options['action']="download";
            $options['url']=$database->cad->generate->results->cad_link;
            $this->response->url=fn_url($_SERVER['PHP_SELF'], $options);
        }
		$type=array('PARTSolutions','Quick CAD');
        $comment[]=($database->profile->data->generate) ? "Emailed to user" : $database->cad->generate->url(array("target"=>"_blank"));
        $comment[]="Transaction ID: {$database->cad->generate->results->transaction_id}";
        $options['comment']=implode("\n", $comment);
        $database->log->update($type, $options);
        return ($database->profile->data->generate) ? $database->cad->generate->results->cad_link : "CAD File Downloaded Successfully";
    }
}
?>