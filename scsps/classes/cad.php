<?php
function partcommunity_server($url, $qa) {
    $qa=($_SESSION['user']->qa_server) ? 1 : intval($qa);
    $url_qa=intval(preg_match("/" . preg_quote(".qa.partcommunity.com","/") . "/", $url));
    switch (TRUE) {
      case ($qa == $url_qa):
      case ($url_qa):
        return $url;
      default:
        return str_replace(".partcommunity.com",".qa.partcommunity.com",$url);
    }
}
$database->cad=new cad_class();
class cad_class extends database_class {
	function __construct() {
    	$this->data=new cad_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new cad_constant_class();
    }
    function scs_table_version() {
    	$results=array();
        $results['09/11/2025']="Improve URL, parse_varset";
        $results['06/30/2025']="New URL interface";
        $results['03/21/2025']="Add CURLOPT_INTERFACE (Welker)";
        $results['03/05/2025']="Add parse_completeVarset";
        $results['09/16/2024']="Add zipfilename";
        $results['07/08/2024']="Improve parse_varset";
        $results['06/07/2024']="Add parse_varset";
        $results['03/16/2024']="PHP8 compatible";
        $results['06/25/2023']="Include user data on create";
        $results['09/28/2022']="Add parse_mident class";
        $results['08/17/2022']="Crashes on CURL HTTP Codes != 200";
        $results['06/02/2022']="Add qualifier/version";
        $results['07/30/2021']="MIDENT parsing bug corrected";
        $results['07/08/2021']="Add download";
        $results['02/11/2021']="Improve curl";
        $results['01/29/2021']="Improve mident_class";
        $results['11/16/2020']="Add generate";
        $results['04/23/2020']=array("Rewrite function select","Add partid_url");
        $results['03/15/2020']="Add mident_class";
        $results['08/19/2019']="Add reversemap";
        $results['08/09/2018']="New layout";
        $results['03/22/2018']="Add registry option";
        $results['09/28/2016']="Add version 2.0";
        $results['06/06/2016']="Add map";
        $results['09/22/2015']="Add function:url";
        $results['08/28/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cad_data_class();
        $this->data->id=$this->fetch['id'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->qualifier=$this->fetch['qualifier'];
	    $this->data->version=$this->fetch['version'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->instructions=$this->fetch['instructions'];
	    $this->data->native=$this->fetch['native'];
	    $this->data->macro=$this->fetch['macro'];
	    $this->data->direct=$this->fetch['direct'];
	    $this->data->active=$this->fetch['active'];
    }
    function read($id,$field="id") {
        $query=array("select * from cad where {$field}=" . fn_escape($id));
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new cad_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
	    $fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="code=" . fn_escape($this->data->code);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="qualifier=" . fn_escape($this->data->qualifier);
	    $fields[]="version=" . fn_escape($this->data->version);
	    $fields[]="type=" . fn_escape($this->data->type);
	    $fields[]="instructions=" . fn_escape($this->data->instructions);
	    $fields[]="native=" . fn_escape($this->data->native,TRUE);
	    $fields[]="macro=" . fn_escape($this->data->macro,TRUE);
	    $fields[]="direct=" . fn_escape($this->data->direct,TRUE);
	    $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update cad set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into cad set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
        if (( !$this->data->id) && (!$this->meta->error) ) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from cad where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare="",$select_options=array(),$forms_options=array()) {
        global $forms;
		$items=$this->select_picklist($select_options);
        if (!sizeof($items)) return;
		if (!is_array($select_options)) $select_options=array("field"=>$select_options);
		if (!array_key_exists("field",$select_options)) $select_options['field']="cad_code";
    	if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        return $forms->optgroup($select_options['field'],$items,$compare,$select_options['blank'],$forms_options);
    }
    function picklist($compare="", $select_options=array(), $forms_options=array()) {
        global $forms;
		$items=$this->select_picklist($select_options);
        if (!sizeof($items)) return;
		if (!array_key_exists("field",$select_options)) $select_options['field']="cad_code";
		if (!array_key_exists("columns",$select_options)) $select_options['columns']=4;
		if (!array_key_exists("gap",$select_options)) $select_options['gap']="10px";
        $results=array();
		foreach ($items as $type => $item) {
        	$results[]="<div><b>{$type}</b></div>";
            $text=array();
            foreach ($item as $code => $name) {
                $forms_options['id']=fn_base64(array($select_options['field'], $code));
				$text[]=$forms->radio($select_options['field'], $code, $compare, $forms_options) . " {$name}";
            }
            $style=array();
            $style[]="-webkit-column-count: " . $select_options['columns'] . ";";
            $style[]="-moz-column-count: " . $select_options['columns'] . ";";
            $style[]="column-count: " . $select_options['columns'] . ";";
            $style[]="-webkit-column-gap: " . $select_options['gap'] . ";";
            $style[]="-moz-column-gap: " . $select_options['gap'] . ";";
            $style[]="column-gap: " . $select_options['gap'] . ";";
            $results[]="<div style='" . implode(" ",$style) . "'>" . implode("<br>\n",$text) . "</div>";
        }
		return implode("",$results);
    }
    function select_picklist($options) {
        global $database;
        $query_where=array();
        $part2cad=(array_key_exists("part2cad", $options)) ? $options['part2cad'] : $database->registry->cad->part2cad;
        if (!$part2cad) $query_where[]=new query_where("direct", "=", 0);
	    $query=array("select");
        $query[]="case";
        $query[]="when direct=1 then 1";
        $query[]="when type='3D' then 2";
        $query[]="else 3";
        $query[]="end as sort_key,";
        $query[]="cad.* from cad";
        $query[]=$this->where($query_where);
        $query[]="order by sort_key, id";
	    $this->query($query);
        $results=array();
	    while ($this->fetch = $this->fetch_array()) {
	        $this->fetch();
            if (!array_key_exists($this->data->type,$results)) $results[$this->data->type]=array();
        	$results[$this->data->type][$this->data->code]=$this->data->name;
        }
        return $results;
    }
    function verify($table,$update=FALSE,$administrator=FALSE) {
        global $database,$forms,$menu;
		if ($update) {
			$database->$table->data->cad_code=$_POST['cad_code'];
            if ($administrator) {
	            $database->$table->data->cad_limit=$_POST['cad_limit'];
                if ($database->$table->data->cad_limit == "Limited") {
	       			$database->$table->data->cad_max=$_POST['cad_max'];
				  } else {
	       			$database->$table->data->cad_max=0;
				}
            }
          } else {
	        $results=array();
	        $results[]="<tr>";
	        $results[]="<td>CAD format</td>";
	        $results[]="<td>" . $this->select($database->$table->data->cad_code) . "</td>";
	        $results[]="</tr>";
            if ($administrator) {
	            $results[]="<tr>";
	            $results[]="<td>CAD limit</td>";
	            $results[]="<td>" . $forms->select("cad_limit",$this->constant->cad_limit,$database->$table->data->cad_limit,FALSE) . "</td>";
	            $results[]="</tr>";
	            $results[]="<tr>";
	            $results[]="<td>Maximum CAD downloads ";
	            if ($database->registry->cad->days) $results[]="(" . $database->registry->cad->days . " days)";
	            $results[]="</td>\n";
	            $results[]="<td>" . $forms->select_number("cad_max",$database->$table->data->cad_max,0,99);
	            if ($database->registry->cad->max) $results[]=" (0=Default of " . $database->registry->cad->max . ")";
	            $results[]="</td>";
	            $results[]="</tr>";
			}
			return implode("\n",$results);
		}
    }
    function prj_parse($prj) {
        $results=array();
        switch (TRUE) {
          case (preg_match("/^(info=)(.*)(&varset=)(.*)$/i",$prj, $matches)):
            if (strlen($matches[2])) $results['info']=$matches[2];
            if (strlen($matches[4])) $results['varset']=$matches[4];
            break;
          case (preg_match("/^(info=)(.*)$/i",$prj, $matches)):
            if (strlen($matches[2])) $results['info']=$matches[2];
            break;
          case (preg_match("/^(catalog\=)(.*)(&part=)(.*)$/i",$prj, $matches)):
            if (strlen($matches[2])) $results['catalog']=$matches[2];
            if (strlen($matches[4])) $results['part']=$matches[4];
            break;
        }
        return $results;
    }
    function prj_input($prj) {
        return urldecode(trim($prj));
    }
    function prj_verify($prj) {
        $results=array();
        foreach ($this->prj_parse($prj) as $key => $value) {
            $results[]="{$key}={$value}";
        }
        return ($prj == implode("&", $results)) ? 1 : 0;
    }
    function url($request=array()) {
        global $database;
        $url_query=array();
        switch (TRUE) {
          case (is_array($request['url_query'])):
            $url_query=$request['url_query'];
            break;
          case (strlen($request['info'])):
            $url_query['info']=$request['info'];
            if (strlen($request['varset'])) $url_query['varset']=$request['varset'];
            break;
          case (strlen($request['sku_prj'])):
            $url_query=$this->prj_parse($request['sku_prj']);
            break;
          default:
            $url_query=$this->prj_parse($request['prj']);
        }
        $hidePortlets=array();
        foreach ($request as $key => $value) {
            switch (TRUE) {
              case (in_array($key, array("languageIso", "partFixed"))):
                $url_query[$key]=$value;
                break;
              case ($key == "hidePortlets"):
                if (!is_array($value)) $value=array($value);
                foreach ($value as $item_key => $item_value) {
                    $hidePortlets[$item_key]="hidePortlets={$item_value}";
                }
                break;
            }
        }
        $url=fn_url(partcommunity_server($database->registry->cad->configurator, $request['staging']), $url_query);
        if (sizeof($hidePortlets)) {
            $url .= (sizeof($url_query)) ? "&" : "?";
            $url .= implode("&", $hidePortlets);
        }
        return $url;
    }
    function partid_prj($partid) {
        $parse_mident=new parse_mident($partid);
        $results=array();
        $results['info']=$parse_mident->info;
        $results['varset']=$parse_mident->varset;
		return $results;
    }
    function partid_url($partid, $options=array(), $runtime_options=array()) {
        global $database;
		$items=preg_split("/[()]/",$partid,2,PREG_SPLIT_NO_EMPTY);
        if (sizeof($items) != 2) return;
        if ( ($options['staging']) && ($database->registry->cad->staging) ) {
        	$url=$database->registry->cad->staging;
		  } else {
        	$url=$database->registry->cad->configurator;
        }
        if (!$database->registry->cad->configurator);
        $query=array();
        $query['info']=$items[0];
        $query['varset']=$items[1];
		return fn_url($url,array_merge($query,$runtime_options));
    }
    function user($id,$data=array()) {
        global $database;
		$database->country->read($data->country_code);
	    $item=array();
	    $item['email']=$data->email;
	    $text=array();
	    if ($data->name) $text[]=$data->name;
	    if ($id) $text[]=" | " . $id;
	    $item['name']=implode("",$text);
	    $item['company']=$data->company_name;
	    $item['street']=str_replace(array("\n","\r")," ",$data->address);
	    $item['city']=$data->city;
	    $item['state']=$data->state;
	    $item['zip']=$data->zip;
	    $item['country_iso3']=$database->country->data->ps_id;
	    $item['country_iso2']=$database->country->data->country;
	    $item['phone']=(isset($data->phone)) ? $data->phone : $data->phone_office;

		switch ($database->registry->cad->version) {
          case "3.0":
			$map=array("email"=>"email","name"=>"user","company"=>"userfirm","street"=>"street","city"=>"city","state"=>"state","zip"=>"zip","country_iso2"=>"country","phone"=>"phone");
          	break;
          case "2.0":
			$map=array("email"=>"email","name"=>"name","company"=>"company","street"=>"street","city"=>"city","state"=>"state","zip"=>"zip","country_iso3"=>"country","phone"=>"phone");
          	break;
          default:
			$map=array("email"=>"u_email","name"=>"u_name","company"=>"u_company","street"=>"u_street","city"=>"u_city","state"=>"u_state","zip"=>"u_zip","country_iso3"=>"u_country","phone"=>"u_phone");
		}
    	$results=array();
        foreach ($item as $field => $value) {
			$value=trim($value);
        	if ( (array_key_exists($field,$map)) && strlen($value) ) $results[ $map[$field] ]=$value;
        }
		return $results;
    }
    function pdf_content($items) { // addKeyValues=VARNAME1=Bla|VARNAME2=Blub
    	$results=array();
		foreach ($items as $key => $value) {
        	if (strlen($value)) $results[]="$key=" . urlencode($value);
        }
		return implode("|",$results);
    }
    function generate($sku, $cad_code, $options=array()) {
        global $database;
        $this->read($cad_code,"code");
        if (array_key_exists("mident", $options)) {
            $options['prj']=$this->partid_prj($options['mident']);
            $options['generate_sku']=FALSE;
        }
        $this->generate=new cad_generate_class($sku, $cad_code, $options);
    }
	function download($link) {
	    $ch=curl_init();
	    curl_setopt($ch, CURLOPT_URL, $link);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $content=curl_exec($ch);
        $curl_info=curl_getinfo($ch);
        if ($curl_info['http_code'] != "200") return "Link error: " . $curl_info['http_code'];
	    curl_close($ch);
	    header("Content-Disposition: attachment; filename=\"" . end(preg_split("/\//",$link)) . "\"");
	    header("Content-Type: " . $curl_info['content_type']);
	    header("Content-Length: " . strlen($content));
	    die($content);
    }
    function parse_varset($varset) {
        $results=array();
        $filter="[{*}],?";
        if (!preg_match("/{$filter}/",$varset)) return $results;
		$text=preg_split("/{$filter}/",$varset,0,PREG_SPLIT_NO_EMPTY);
        foreach ($text as $item) {
			$data=explode("=",$item,2);
            $results[trim($data[0])]=trim($data[1]);
        }
        return $results;
    }
    function parse_completeVarset($varset) {
//        $items=preg_split("/[{.*}]/", str_replace("\\\"", "\"", $varset), 0, PREG_SPLIT_NO_EMPTY);
        $items=preg_split("/[{*}](,?)/", str_replace('\\"', '"', $varset), 0, PREG_SPLIT_NO_EMPTY);
        $results=array();
        foreach ($items as $item) {
            if ($item == ",") continue;
            list($key, $value)=explode("=", $item, 2);
            $results[$key]=$value;
        }
        return $results;
    }
}
class cad_data_class {
	var $id=0;
	var $code;
    var $name;
    var $qualifier;
    var $version;
	var $type;
    var $instructions;
    var $native=0;
    var $macro=0;
    var $direct=0;
    var $active=1;
}
class cad_constant_class {
	var $type=array('3D','2D');
    var $cad_limit=array('Limited','Unlimited','Denied');
    var $hidePortlets=array();
    var $language=array();
    function __construct() {
        $this->hidePortlets['briefcase']="Generate CAD";
        $this->hidePortlets['preview']="3D Preview";
        $this->hidePortlets['searchHeader']="Search Header";
        $this->hidePortlets['navigation']="Enter Parameters";

// https://www.loc.gov/standards/iso639-2/php/code_list.php
        $this->language=array();
        $this->language['en']="English";
        $this->language['fr']="French";
        $this->language['de']="German";
        $this->language['it']="Italian";
        $this->language['es']="Spanish";
    }
}
class cad_lookup_class {
	var $sku;
    var $staging;
    var $hidePortlets=array();
    var $log;
    var $debug;
    var $error;
    var $json;
	var $reversemap_url;
    var $url_query=array();
    function __construct($sku, $options=array()) {
        global $database;
		$this->sku=$sku;
        $this->staging=(array_key_exists("staging", $options)) ?  $options['staging'] : 0;
        $this->log=(array_key_exists("log", $options)) ?  $options['log'] : 1;
        $this->debug=(array_key_exists("debug", $options)) ?  $options['debug'] : $database->registry->cad->debug;
        $this->error=$this->process();
        if ( (strlen($this->error)) && ($this->log) ) $database->log->update("Reverse Part# Error:Configurator", $this->error, 0, $this->sku);
    }
    private function process() {
        global $database;
        switch (TRUE) {
          case (!strlen($this->sku)):
            return "No SKU";
          case (!strlen($database->registry->cad->partserver_reversemap)):
            return "Reverse map URL missing";
          case (!strlen($database->registry->cad->catalog)):
            return "Catalog missing";
        }
	    $this->url_query['catalog']=$database->registry->cad->catalog;
	    $this->url_query['part']=$this->sku;
        $this->reversemap_url=fn_url(partcommunity_server($database->registry->cad->partserver_reversemap, $this->staging), $this->url_query);
	    $ch=curl_init();
	    curl_setopt($ch,CURLOPT_URL, $this->reversemap_url);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    if ($this->debug) {
	        $fh=fopen("cad_lookup.txt","w");
	        curl_setopt($ch, CURLOPT_VERBOSE, true);
	        curl_setopt($ch, CURLOPT_STDERR, $fh);
	    }
        $this->json=json_decode(curl_exec($ch), true);
	    $curl_error_no=intval(curl_errno($ch));
	    $curl_info=curl_getinfo($ch);
	    curl_close($ch);
	    if ($this->debug) fclose($fh);
        switch (TRUE) {
          case ($curl_error_no):
            return "CURL error: {$curl_error_no}";
          case (!is_array($this->json)):
            return "Invalid CURL response";
          case (strlen($this->json['error'])):
            return $this->json['error'];
        }
    }
}
class cad_generate_class {
	var $sku;
    var $cad_code;
    var $cad_name;
    var $generate;
    var $partserver_url;
    var $catalog;
    var $apikey;
    var $server_ip;
    var $prj=array();
    VAR $generate_sku;
    var $curl_data=array();
    var $user;
    var $source;
    var $log;
    var $zipfilename;
    var $debug;
    var $error;
    var $results=array();

    var $curl_ch;
    var $curl_fh;
    var $curl_file;
    var $curl_url;
    var $curl_error_no;
    var $curl_info;
    var $curl_response;
    function __construct($sku, $cad_code, $options) {
        global $database;
    	$this->curl_response=new stdClass();
	    $this->sku=$sku;
	    $this->cad_code=$cad_code;
        $this->cad_name=$database->cad->data->name;
        $this->generate_sku=(array_key_exists("generate_sku", $options)) ? $options['generate_sku'] : $database->registry->cad->generate_sku;
        $this->prj=( (array_key_exists("prj", $options)) && (is_array($options['prj'])) ) ? $options['prj'] : array();
        $this->generate=(array_key_exists("generate", $options)) ? $options['generate'] : FALSE;
        $this->zipfilename=(array_key_exists("zipfilename", $options)) ? $options['zipfilename'] : "";
        $this->log=(array_key_exists("log", $options)) ? $options['log'] : TRUE;
        $this->debug=(isset($options['debug'])) ? $options['debug'] : FALSE;
        $this->partserver_url=partcommunity_server($database->registry->cad->partserver_production, $options['staging']);
        $this->catalog=(array_key_exists("catalog", $options)) ? $options['catalog'] : $database->registry->cad->catalog;
        $this->apikey=(array_key_exists("apikey", $options)) ? $options['apikey'] : $database->registry->cad->apikey;
        $this->server_ip=(array_key_exists("server_ip", $options)) ? $options['server_ip'] : "";
        $this->results=new stdClass();
        switch (TRUE) {
          case (array_key_exists("user",$options)):
          	$this->user=$options['user'];
            break;
          case (isset($_SESSION['user'])):
           	$this->user=$_SESSION['user'];
            break;
          default:
          	$this->user=new user_data_class();
		}
        $this->error=$this->process();
    }
    function process() {
        global $database;
        $database->cad->read($this->cad_code, "code");
        switch (TRUE) {
          case (!strlen($this->sku)):
            return "SKU cannot blank";
          case (!strlen($this->cad_code)):
            return "Missing CAD Code";
          case (!strlen($this->cad_code)):
            return "Invalid CAD code: {$this->cad_code}";
          case (!strlen($this->partserver_url)):
            return "PARTserver URL Missing";
          case (!strlen($database->registry->cad->catalog)):
            return "PARTserver Catalog Missing";
          case ($this->generate_sku):
            break;
          case (!array_key_exists("info", $this->prj)):
            return "Field 'info' missing in inventory PRJ";
          case (!array_key_exists("varset", $this->prj)):
            return "Field 'varset' missing in inventory PRJ";
        }
        $this->curl_data['cgiaction']=( ($this->generate) ? "generate" : "download");
        $this->curl_data['part']=($this->generate_sku) ? "{PNO={$database->registry->cad->catalog},{$this->sku}}" : "{" . $this->prj['info'] . "}," . $this->prj['varset'];
        $this->curl_data['format']=$this->cad_code;
        $this->curl_data['firm']=urlencode($database->registry->cad->catalog);
        $this->curl_data['server_type']="SUPPLIER_EXTERNAL_" . $database->registry->cad->catalog;
		if ($database->registry->cad->apikey) $this->curl_data['apikey']=$database->registry->cad->apikey;
        $this->curl_data['language']="english";
        $this->curl_data['country']=$this->user->country_code;
		$this->curl_data['user']=$this->user->name;
        $this->curl_data['userfirm']=$this->user->company_name;
        $this->curl_data['street']=$this->user->address;
        $this->curl_data['zip']=$this->user->zip;
        $this->curl_data['city']=$this->user->city;
        $this->curl_data['state']=$this->user->state;
        $this->curl_data['phone']=$this->user->phone;
        $this->curl_data['email']=$this->user->email;
		$this->curl_data['downloadflags']=($this->cad_code == "PDFDATASHEET") ? "XML" : "zip";
        if (strlen($this->zipfilename)) $this->curl_data['zipfilename']=$this->zipfilename;
	    $this->curl_data['ok_url']=( ($this->generate) ? "" : "<%download_xml%>" );
	    $this->curl_data['ok_url_type']=( ($this->generate) ? "" : "text" );
		$this->curl_ch=curl_init();
	    curl_setopt($this->curl_ch, CURLOPT_URL, $this->partserver_url);
	    curl_setopt($this->curl_ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->curl_ch, CURLOPT_POST, 1);
	    curl_setopt($this->curl_ch, CURLOPT_POSTFIELDS, $this->curl_data);
	    curl_setopt($this->curl_ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->server_ip) curl_setopt($this->curl_ch, CURLOPT_INTERFACE, $this->server_ip);
		if ($this->debug) {
        	$this->curl_file=$_SERVER['DOCUMENT_ROOT'] . "/curl.txt";
        	$this->curl_fh=fopen($this->curl_file,"w");
        	curl_setopt($this->curl_ch, CURLOPT_VERBOSE, true);
        	curl_setopt($this->curl_ch, CURLOPT_STDERR, $this->curl_fh);
		}
	    $this->curl_url=curl_exec($this->curl_ch);
        $this->curl_error_no=intval(curl_errno($this->curl_ch));
        $this->curl_info=curl_getinfo($this->curl_ch);
	    curl_close($this->curl_ch);
        switch (TRUE) {
          case ($this->curl_error_no):
            $this->results->error="CURL error: " . $this->curl_error_no;
            break;
          case ($this->curl_info['http_code'] != "200"):
            $this->results->error="HTTP error: " . $this->curl_info['http_code'];
            break;
          case ($this->generate):
            $this->results->cad_link="SKU: " . $this->sku . " Format: " . $database->cad->data->name . " Emailed to: " . $this->user->email;
            break;
          case (!is_string($this->curl_url)):
            $this->results->error="No response from server";
            break;
          default:
            $this->curl_poll();
        }
        if ($this->debug) fclose($this->curl_fh);
        return $this->results->error;
    }
	private function curl_poll() {
        global $database;
		set_time_limit(120);
        $this->results->curl_count=0;
        $complete=FALSE;
	    do {
	        $complete=$this->curl_link();
	    } while (!$complete);
        if ( (strlen($this->results->error)) && ($this->log) ) $database->log->update("Reverse Part# Error:CAD", $this->results->error, 0, $this->sku);
    }
	private function curl_link() {
        $this->results->curl_count++;
        sleep(2);
	    $ch=curl_init($this->curl_url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->debug) {
        	curl_setopt($ch, CURLOPT_VERBOSE, true);
        	curl_setopt($ch, CURLOPT_STDERR, $this->curl_fh);
		}
	    $data=curl_exec($ch);
	    $http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);
        switch (TRUE) {
          case (!$http_code):
        	$this->error="Cannot connect to " . $this->curl_url;
            return TRUE;
          case ($http_code >= 400):
          	return;
		}
		$this->curl_response=json_decode(json_encode(simplexml_load_string($data)));
        $xml_errors=libxml_get_errors();
        switch (TRUE) {
          case (sizeof($xml_errors)):
          case (!is_object($this->curl_response)):
            $this->error="XML errors detected";
            return TRUE;
          case (isset($this->curl_response->MESSAGE)):
            $this->results->error=(is_array($this->curl_response->MESSAGE)) ? implode("<br>", $this->curl_response->MESSAGE) : $this->curl_response->MESSAGE;
            break;
          case (isset($this->curl_response->SECTION->ZIPFILE)):
            $this->results->cad_link=str_replace("download.xml",$this->curl_response->SECTION->ZIPFILE,$this->curl_url);
            break;
          case (isset($this->curl_response->SECTION->CONTENTS->PART->FILENAME)):
            $this->results->cad_link=str_replace("download.xml",$this->curl_response->SECTION->CONTENTS->PART->FILENAME,$this->curl_url);
            break;
          default:
            $this->results->error="Unknown CURL error";
        }
		$this->results->transaction_id=$this->curl_response->ORDERNO;
        $this->error=$this->results->error;
        return TRUE;
	}
    function url($options=array()) {
        if (!array_key_exists("title", $options)) $options['title']="Download CAD";
        if (!$this->generate->error) return fn_href("Download File ({$this->sku})",$this->results->cad_link,array(),$options);
    }
}
class parse_mident {
	var $mident;
    var $info;
    var $varset;
    var $fields=array();
    var $error;
    function __construct($mident) {
		$this->mident=$mident;
		if (!preg_match("/^[(](.*)[)][(](.*)[)]$/",$mident,$mident_matches)) {
        	$this->error="Malformed MIDENT";
            return;
        }
        $this->info=$mident_matches[1];
        $this->varset=$mident_matches[2];
        $filter="[\{\*\}\,?]";
        if (!preg_match("/{$filter}/",$this->varset)) {
        	$this->error="Malformed VARSET";
            return;
        }
		$varset=preg_split("/{$filter}/",$this->varset,0,PREG_SPLIT_NO_EMPTY);
        foreach ($varset as $item) {
			$data=explode("=",$item,2);
            $this->fields[trim($data[0])]=trim($data[1]);
        }
	}
}
/*
CREATE TABLE `cad` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `code` varchar(40) DEFAULT NULL,
  `name` varchar(80) DEFAULT '',
  `qualifier` varchar(60) DEFAULT '',
  `version` varchar(60) DEFAULT '',
  `type` varchar(20) DEFAULT '',
  `instructions` mediumtext,
  `macro` int(1) DEFAULT '0',
  `native` int(1) DEFAULT '0',
  `direct` int(1) DEFAULT '0',
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_key` (`code`)
) ENGINE=InnoDB AUTO_DEFAULT CHARSET=latin1 COMMENT='CAD formats (06/02/2022)'
*/
?>