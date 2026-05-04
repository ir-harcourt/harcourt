<?php
$database->user = new user_class();
class user_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/05/2024']="Add address info (hotfix)";
        $results['08/02/2022']="Add recaptcha_score";
        $results['07/11/2022']="Add IPstack";
        $results['11/28/2020']="Add domain";
        $results['10/21/2019']="Add city, state, revenue, employees";
        $results['03/11/2019']="Improve import/export";
        $results['09/11/2018']="Allow import adding";
        $results['06/16/2017']="Update pricing";
        $results['12/01/2016']="Add leadscore";
        $results['05/26/2016']="Improve access function";
        $results['03/10/2016']="Add industry_id";
        $results['11/23/2015']="Add ecommerce";
        $results['08/06/2015']="Revise ajax_tabs";
        $results['07/08/2015']="Add ajax_tabs";
        $results['08/25/2014']="Add jquery autocomplete";
        $results['10/30/2013']="Add language";
        $results['01/21/2013']="Add pricing";
        $results['10/29/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new user_data_class();
    	$this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new user_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new user_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->ip=$this->fetch['ip'];
	    $this->data->company_name=$this->fetch['company_name'];
        $this->data->address=$this->fetch['address'];
        $this->data->city=$this->fetch['city'];
	    $this->data->state=$this->fetch['state'];
        $this->data->zip=$this->fetch['zip'];
	    $this->data->country_code=$this->fetch['country_code'];
	    $this->data->status=$this->fetch['status'];
	    $this->data->type=$this->fetch['type'];
	    $this->data->last_login=$this->fetch['last_login'];
	    $this->data->login_document_id=$this->fetch['login_document_id'];
	    $this->data->landing_document_id=$this->fetch['landing_document_id'];
	    $this->data->comment=$this->fetch['comment'];
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->customer_code=$this->fetch['customer_code'];
	    $this->data->catalog=$this->fetch['catalog'];
	    $this->data->cad=$this->fetch['cad'];
	    $this->data->ecommerce=$this->fetch['ecommerce'];
	    $this->data->price_type=$this->fetch['price_type'];
	    $this->data->price_pct=$this->fetch['price_pct'];
	    $this->data->language_code=$this->fetch['language_code'];
	    $this->data->industry_id=$this->fetch['industry_id'];
	    $this->data->leadscore=$this->fetch['leadscore'];
	    $this->data->revenue=$this->fetch['revenue'];
	    $this->data->employees=$this->fetch['employees'];
	    $this->data->domain=$this->fetch['domain'];
	    $this->data->currency_code=$this->fetch['currency_code'];
        $json=json_decode($this->fetch['ipstack_json']);
        if (is_object($json)) $this->data->ipstack_json=$json;
	    $this->data->recaptcha_score=$this->fetch['recaptcha_score'];
    }
    function read($id, $field="id") {
		$query=array("select * from user");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new user_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$field=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="ip=" . fn_escape($this->data->ip);
	    $fields[]="company_name=" . fn_escape($this->data->company_name);
	    $fields[]="address=" . fn_escape($this->data->address, "null");
	    $fields[]="city=" . fn_escape($this->data->city, "null");
	    $fields[]="state=" . fn_escape($this->data->state, "null");
	    $fields[]="zip=" . fn_escape($this->data->zip, "null");
	    $fields[]="country_code=" . fn_escape($this->data->country_code,"null");
	    $fields[]="status=" . fn_escape($this->data->status);
	    $fields[]="type=" . fn_escape($this->data->type);
	    $fields[]="last_login=" . fn_escape($this->data->last_login,FALSE);
	    $fields[]="login_document_id=" . fn_escape($this->data->login_document_id,FALSE);
	    $fields[]="landing_document_id=" . fn_escape($this->data->landing_document_id,FALSE);
	    $fields[]="comment=" . fn_escape($this->data->comment);
	    $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
	    $fields[]="customer_code=" . fn_escape($this->data->customer_code);
	    $fields[]="catalog=" . fn_escape($this->data->catalog,FALSE);
	    $fields[]="cad=" . fn_escape($this->data->cad,FALSE);
	    $fields[]="ecommerce=" . fn_escape($this->data->ecommerce,FALSE);
	    $fields[]="price_type=" . fn_escape($this->data->price_type);
	    $fields[]="price_pct=" . fn_escape($this->data->price_pct,FALSE);
	    $fields[]="language_code=" . fn_escape($this->data->language_code);
	    $fields[]="currency_code=" . fn_escape($this->data->currency_code,"null");
	    $fields[]="industry_id=" . fn_escape($this->data->industry_id,FALSE);
	    $fields[]="leadscore=" . fn_escape($this->data->leadscore,FALSE);
	    $fields[]="employees=" . fn_escape($this->data->employees);
	    $fields[]="domain=" . fn_escape($this->data->domain,"null");
	    $fields[]="recaptcha_score=" . fn_escape($this->data->recaptcha_score,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update user set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into user set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) {
        	$this->data->id = $this->insert_id();
            $this->ipstack();
		}
    }
    function delete($id) {
	    $query=array("delete from user");
        $query[]="where id=" . fn_escape($id);
		$this->query($query);
    }
    function access($role_minimum,$deny=TRUE) {
    global $menu;
		$access=role_access($_SESSION['user']->type,$role_minimum);
        switch (TRUE) {
		  case ($access):
		  case (!$deny):
			return $access;
          default:
            fn_url_redirect($menu->page->home);
        }
    }
    function type_list() {
        $results=array();
        foreach ($this->constant->type as $type) {
            if ($this->access($type,FALSE)) $results[]=$type;
        }
        return $results;
    }
    function session() {
        if ($this->data->status != "Active") {
            $this->data->cad=0;
            $this->data->catalog=0;
            $this->data->ecommerce=0;
        }
        $_SESSION['user']=$this->data;
    }
    function recaptcha_score($score) {
        global $database;
    	$results=array($score);
        switch (TRUE) {
          case ($score < $database->registry->recaptcha->score_ignore):
          	$results[]="(Reject/Ignore)";
          	break;
          case ($score < $database->registry->recaptcha->score_spam):
          	$results[]="(Possible Spam)";
          	break;
          default:
          	$results[]="(OK)";
		}
        return implode(" ",$results);

    }
    function ipstack($currency=TRUE) {
    global $database, $forms, $menu;
		$obj=$this->ipstack_lookup($this->data->ip);
        if (is_object($obj)) $this->data->ipstack_json=$obj;
        if ( (is_object($obj)) && (strlen($this->data->ipstack_json->currency->code)) ) $this->data->currency_code=$this->data->ipstack_json->currency->code;
        $fields=array();
	    $fields[]="currency_code=" . fn_escape($this->data->currency_code,"null");
        $fields[]="ipstack_json=" . fn_escape(json_encode($this->data->ipstack_json));
        $query=array();
        $query[]="update user set";
        $query[]=implode(",\n",$fields);
        $query[]="where id=" . fn_escape($this->data->id);
		$this->query($query);
        if ( ($currency) && (strlen($this->data->ipstack_json->currency->code)) ) {
        	$database->currency->read($this->data->ipstack_json->currency->code);
            if (!$database->currency->meta->rows) {
	            $database->currency->data->code=$this->data->ipstack_json->currency->code;
	            $database->currency->data->name=$this->data->ipstack_json->currency->name;
			    $database->currency->data->plural=$this->data->ipstack_json->currency->plural;
/*
Too much work to do this one
Euro comes over as e282ac

$text['text']=hex2bin("e282ac");
$text['mb_convert_encoding']=bin2hex(mb_convert_encoding($text['text'], 'UTF-32', 'UTF-8' ));
$text['mb decimal']=hexdec($text['mb_convert_encoding']);

answer: &#8364;

http://www.endmemo.com/unicode/unicodeconverter.php

            	$database->currency->data->symbol=$this->data->ipstack_json->currency->symbol;
*/
	            $database->currency->data->active=TRUE;
	            $database->currency->update(FALSE);
			}
		}
        return TRUE;
    }
    function ipstack_lookup($items) {
        global $database;
// multiple not supports w/this version
        switch (TRUE) {
          case (is_array($items)):
          	$ip=array_filter($items);
            break;
          case (strlen($items)):
          	$ip=array($items);
          	break;
          default:
          	$ip=array();
		}
    	switch (TRUE) {
          case (!sizeof($ip)):
          case (!strlen($database->registry->ipstack->api_url)):
          	return;
		}
        $url=$database->registry->ipstack->api_url . implode(",",$ip);
        $url .= "?access_key=" . $database->registry->ipstack->api_key;
        $url .= "&format=1";
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response=curl_exec($ch);
		$curl_info=curl_getinfo($ch);
	    curl_close($ch);
        $obj=json_decode($response);
        $error=array();
        switch (TRUE) {
          case ($curl_info['http_code'] != "200"):
          	$error[]="HTTP Code: " . $curl_info['http_code'];
            break;
          case (!is_object($obj)):
          	$error[]="Invalid response:";
            $error[]=$response;
            break;
          case (property_exists($obj,"error")):
          	foreach ($obj->error as $key => $value) {
            	if (!is_object($value)) $error[]="{$key}: {$value}";
			}
			break;
		}
        if (sizeof($error)) {
        	$options=array();
            $options['comment']=$error;
            $database->log->update("IPStack:Error",$options);
            return;
		  } else {
            return $obj;
        }
    }
    function ajax($search_terms,$limit,$crap=array(),$options=array()) {
    	switch (TRUE) {
          case (!$options['source']):
          	break;
          case ($options['source'] == "company_name"):
             return $this->ajax_source_company_name($search_terms,$limit);
		}
	    $query_where=array();
        $query_where[]=new query_where("type","in",$this->type_list() );
        $query_where[]=new query_where("concat(ip,'\t',company_name,'\t',ifnull(domain,''))","like","%" . implode(" ", $search_terms) . "%");
	    $query=array("select * from user");
        $query[]=trim($this->where($query_where));
        $query[]="order by company_name, domain desc, id";
		if ($limit) $query[]="limit {$limit}";
		$this->query($query);
	    $results=array();
	    while($this->fetch = $this->fetch_array() ) {
			$this->fetch();
	        $results[]=$this->ajax_fill();
		}
		$this->free_result();
		return $results;
    }
    function ajax_source_company_name($search_terms,$limit) {
	    $query_where=array();
        $query_where[]=new query_where("not isnull(company_name)","","");
	    foreach ($search_terms as $item) {
	        $query_where[]=new query_where("company_name","like","%" . $item . "%");
	    }
        $query=array("select");
        $query[]="if (isnull(domain),1,0) as sort_key,";
        $query[]="company_name,";
        $query[]="domain";
        $query[]="from user";
        $query[]=$this->where($query_where);
        $query[]="group by company_name";
        $query[]="order by company_name, sort_key";
        $query[]="limit {$limit}";
        $this->query($query);
        $results=array();
        while ($this->fetch = $this->fetch_array() ) {
        	$this->fetch();
            $data=array();
        	$data['value']=$this->data->company_name;
            $results[]=$data;
        }
        return $results;
    }
    function ajax_fill($keys=array()) {
        global $menu;
		if (func_num_args()) {
	        $query_where=array();
	        $query_where[]=new query_where("id","=",$keys[0]);
	        $query_where[]=new query_where("type","in",$this->type_list() );
	        $query=array("select user.* from user");
	        $query[]=trim($this->where($query_where));
	        $this->query($query);
	        $this->fetch(TRUE);
	        $this->free_result();
            if (!$this->meta->rows) return;
		}
        $results=array();
        $text=array();
        $text[]=$this->data->ip;
        if ($this->data->company_name) $text[]="(" . $this->data->company_name . ")";
        if ($this->data->domain) $text[]="(" . $this->data->domain . ")";
        $text[]="(" . $this->data->type . ")";
		$results['value']=$this->data->id;
		$results['label']=implode(" ",$text);
        foreach ($this->data as $key => $value) {
	        switch (TRUE) {
              case ($key == "price_pct"):
	            $results[$key]=number_format($value * 100,2);
              	break;
	          case (is_object($value)):
	          case (is_array($value)):
	          case (array_key_exists($key,$results)):
	            break;
	          default:
	            $results[$key]=$value;
	        }
		}
        $results['pricebook_currency_code']=(array_key_exists($this->data->currency_code,$menu->currency)) ?  $this->data->currency_code : "USD";
        return $results;
    }
    function map($options) {
        global $database;
        $this->map=new table_map_class($options);
		$this->map->item("id",array("type"=>"id","required"=>TRUE));
	    $this->map->item("ip",array("required"=>TRUE));
	    $this->map->item("company_name",array("name"=>"Company Name"));
	    $this->map->item("address",array("name"=>"Address"));
	    $this->map->item("city",array("name"=>"City"));
	    $this->map->item("state",array("name"=>"State/Province","comment"=>"US/CA must be valid name or ISO-Alpha2 codes"));
	    $this->map->item("zip",array("name"=>"ZIP/Postal Cost","type"=>"uppercase"));
	    $this->map->item("country_code",array("type"=>"uppercase","name"=>"Country Code","comment"=>"Valid ISO-Alpha2"));
	    $this->map->item("status");
	    $this->map->item("type");
	    $this->map->item("last_login",array("type"=>"datetime","import"=>FALSE));
	    $this->map->item("customer_code",array("type"=>"uppercase"));
	    $this->map->item("catalog",array("type"=>"boolean"));
	    $this->map->item("cad",array("type"=>"boolean"));
		$this->map->item("leadscore",array("type"=>"boolean"));
	    $this->map->item("ecommerce",array("type"=>"boolean"));
	    $this->map->item("price_type",array());
	    $this->map->item("price_pct",array("type"=>"percent:4"));
	    $this->map->item("login_document_id",array("type"=>"id"));
	    $this->map->item("landing_document_id",array("type"=>"id"));
	    $this->map->item("portal_id",array("type"=>"decimal:0"));
	    $this->map->item("comment",array("type"=>"text"));
	    $this->map->item("language_code");
	    $this->map->item("currency_code",array("type"=>"uppercase"));
	    $this->map->item("industry_name");
	    $this->map->item("revenue");
	    $this->map->item("employees");

        $this->map->constant->currency=array();
		$database->temp->query("select code from currency");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
	        $this->map->constant->currency[]=$database->temp->fetch['code'];
	    }
		$database->temp->free_result();

        $this->map->constant->industry=array(0=>"");
		$database->temp->query("select id,name from industry");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->map->constant->industry[ $database->temp->fetch['id'] ]="^/" . preg_quote($database->temp->fetch['name'],"/") . "$/i";
			$this->map->constant->industry[ $database->temp->fetch['id'] ]=$database->temp->fetch['name'];
        }
		$database->temp->free_result();
        $this->map->constant->language=array();
		$database->temp->query("select code from language");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->map->constant->language[]=$database->temp->fetch['code'];
        }
		$database->temp->free_result();

        $this->map->constant->portal=array("0");
		$database->temp->query("select id from portal");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->map->constant->portal[]=$database->temp->fetch['id'];
        }
		$database->temp->free_result();

        $this->map->constant->document=array("0");
		$database->temp->query("select id from document where parent_id<>0 and active=1");
        while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->map->constant->document[]=$database->temp->fetch['id'];
        }
		$database->temp->free_result();

        $this->map->constant->type=array();
        foreach ($this->constant->type as $type) {
			$this->map->constant->type[]=$type;
            if ($type==$_SESSION['user']->type) break;
        }
    }
    function export_query() {
	    $query_where=array();
	    $query_where[]=new query_where("status","in",$this->map->options['status']);
	    $query_where[]=new query_where("type","in",$this->map->options['type']);
	    if ($this->map->options['industry_id']) $query_where[]=new query_where("industry_id","=",$this->map->options['industry_id']);
	    if ($this->map->options['login_date_from']) $query_where[]=new query_where("date_format(from_unixtime(last_login),'%Y/%m/%d')",">=",$this->map->options['login_date_from']);
	    if ($this->map->options['login_date_thru']) $query_where[]=new query_where("date_format(from_unixtime(last_login),'%Y/%m/%d')","<=",$this->map->options['login_date_thru']);
	    $query=array("select * from user");
        $query[]=$this->where($query_where);
        $query[]="order by ip";
	    return $query;
    }
    function export_data() {
		$this->data->industry_name=$this->map->constant->industry[ $this->data->industry_id ];
    }
    function import_update($data) {
        global $database;
        $this->read($data['id']);
        if ( ($data['id']) && (!$this->meta->rows) ) $this->map->error['id']="Not found: " .$data['id'];
		$this->data->ip=$data['ip'];
		$ip=ip2long($data['ip']);
        if ( (!intval($ip)) || ($this->data->ip != long2ip($ip)) ) $this->map->error['ip']="Invalid: " . $data['ip'];
        if (array_key_exists("company_name",$data)) {
        	$this->data->company_name=$data['company_name'];
            if (!strlen($data['company_name'])) $this->map->error['company_name']="Required";
        }
        if (array_key_exists("address",$data)) $this->data->address=$data['address'];
        if (array_key_exists("city",$data)) $this->data->city=$data['city'];
        if (array_key_exists("country_code",$data)) {
            if (!strlen($data['country_code'])) $data['country_code']="US";
            $this->data->country_code=$database->country->find_country($data['country_code']);
            if (!strlen($this->data->country_code)) $this->map->error['country_code']="Invalid: " . $data['country_code'];
        }
        if (array_key_exists("state",$data)) {
            $this->data->state=$database->country->find_state($this->data->country_code, $data['state']);
            if ( (strlen($data['state'])) && (!strlen($this->data->country_code)) )  $this->map->error['state']="Invalid: " . $data['state'];
        }
        if (array_key_exists("zip",$data)) $this->data->zip=$data['zip'];
        if (array_key_exists("status",$data)) {
			$this->data->status=import_match($data['status'],$this->constant->status);
            if (!in_array($this->data->status,$this->constant->status)) $this->map->error['status']="Invalid: " . $data['status'];
        }
        if (array_key_exists("type",$data)) {
			$this->data->type=import_match($data['type'],$this->constant->type);
            if (!in_array($this->data->type,$this->constant->type)) $this->map->error['type']="Invalid: " . $data['type'];
        }
        if (array_key_exists("customer_code",$data)) $this->data->customer_code=$data['customer_code'];
        if (array_key_exists("catalog",$data)) $this->data->catalog=$data['catalog'];
        if (array_key_exists("cad",$data)) $this->data->cad=$data['cad'];
        if (array_key_exists("ecommerce",$data)) $this->data->ecommerce=$data['ecommerce'];
        if (array_key_exists("price_type",$data)) {
        	$this->data->price_type=$data['price_type'];
            if (!array_key_exists($this->data->price_type,$this->constant->price_type)) $this->map->error['price_type']="Invalid: " . $data['price_type'];
		}
        if (array_key_exists("price_pct",$data)) $this->data->price_pct=floatval($data['price_pct']);
        if (array_key_exists("comment",$data)) $this->data->comment=$data['comment'];
        if (array_key_exists("login_document_id",$data)) {
			$this->data->login_document_id=$data['login_document_id'];
            if (!in_array($this->data->login_document_id,$this->map->constant->document)) $this->map->error['login_document_id']="Invalid: " . $data['login_document_id'];
        }
        if (array_key_exists("landing_document_id",$data)) {
			$this->data->landing_document_id=$data['landing_document_id'];
            if (!in_array($this->data->landing_document_id,$this->map->constant->document)) $this->map->error['landing_document_id']="Invalid: " . $data['landing_document_id'];
        }
        if (array_key_exists("portal_id",$data)) {
			$this->data->portal_id=$data['portal_id'];
            if (!in_array($this->data->portal_id,$this->map->constant->portal)) $this->map->error['portal_id']="Invalid: " . $data['portal_id'];
        }
        if (array_key_exists("language_code",$data)) {
			$this->data->language_code=import_match($data['language_code'],$this->map->constant->language);
            if (!in_array($this->data->language_code,$this->map->constant->language)) $this->map->error['language_code']="Invalid: " . $data['language_code'];
        }
        if (array_key_exists("currency_code",$data)) {
			$this->data->currency_code=$data['currency_code'];
            if (!in_array($this->data->currency_code,$this->map->constant->currency)) $this->map->error['currency_code']="Invalid: " . $data['currency_code'];
        }

        if (array_key_exists("language_code",$data)) {
			$this->data->language_code=import_match($data['language_code'],$this->map->constant->language);
            if (!in_array($this->data->language_code,$this->map->constant->language)) $this->map->error['language_code']="Invalid: " . $data['language_code'];
        }
        if (array_key_exists("industry_name",$data)) {
			$this->data->industry_id=import_match_id($data['industry_name'],$this->map->constant->industry);
            if ( (strlen($data['industry_name'])) && (!$this->data->industry_id) ) $this->map->error['industry_name']="Invalid: " . $data['industry_name'];
        }
		if ( (array_key_exists("catalog",$data)) || (array_key_exists("price_type",$data)) ) {
	        switch (TRUE) {
	          case ( (!$this->data->catalog) && ($this->data->price_type) ):
	            $this->map->error['pricing']="Pricing requires catalog access";
	            break;
	        }
        }
		if ( (array_key_exists("catalog",$data)) || (array_key_exists("cad",$data)) ) {
	        switch (TRUE) {
	          case ( (!$this->data->catalog) && ($this->data->cad) ):
	            $this->map->error['cad']="CAD requires catalog access";
	            break;
	        }
        }
		if ( (array_key_exists("portal_id",$data)) || (array_key_exists("login_document_id",$data)) || (array_key_exists("landing_document_id",$data)) ) {
			switch (TRUE) {
	          case (!$this->data->portal_id):
	            break;
	           case ($this->data->login_document_id):
	           case ($this->data->landing_document_id):
				$this->map->error['portal_id']="Landing documents not allowed when portal is selected";
            }
        }

        switch (TRUE) {
          case ( (!array_key_exists("price_type",$data)) && (!array_key_exists("price_pct",$data)) ):
          	break;
          case (!array_key_exists("price_type",$data)):
	        $this->map->error['price_type']="Required field";
	        break;
          case (!array_key_exists("price_pct",$data)):
	        $this->map->error['price_pct']="Required field";
	        break;
          case ($this->data->price_pct < 0):
	        $this->map->error['price_pct']="Less than 0%";
          	break;
          case ( (in_array($this->data->price_type,array("","base"))) && ($this->data->price_pct) ):
	        $this->map->error['price_pct']="Must be 0%";
          	break;
          case ( ($this->data->price_type == "discount") && (!$this->data->price_pct) ):
          case ( ($this->data->price_type == "discount") && ($this->data->price_pct >= 1.00) ):
	        $this->map->error['price_pct']="Must be > 0% and < 100%";
          	break;
          case ( ($this->data->price_type == "plus") && (!$this->data->price_pct) ):
          case ( ($this->data->price_type == "plus") && ($this->data->price_pct >= 5.00) ):
	        $this->map->error['price_pct']="Must be > 0% and < 500%";
          	break;
		};
        if (array_key_exists("revenue",$data)) $this->data->revenue=$data['revenue'];
        if (array_key_exists("employees",$data)) $this->data->employees=$data['employees'];

    	if ( ($this->map->update) && (!sizeof($this->map->error)) ) {
			$this->update($this->meta->rows);
            if ($this->meta->error) fn_pre($this->meta);
            if ($this->meta->error) $this->map->error['mysql']=$this->meta->error;
        }
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("user", "Registered users");
        $this->hotfix->version("05/06/2025", "Add domain_index");
        $this->hotfix->version("08/04/2024", "Add address info");
        $this->hotfix->version("08/02/2022", "Initial release");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20240804($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD COLUMN `address` VARCHAR(100) NULL AFTER `company_name`";
        $fields[]="CHANGE COLUMN `city` `city` VARCHAR(40) NULL AFTER `address`";
        $fields[]="CHANGE COLUMN `state` `state` VARCHAR(40) NULL AFTER `city`";
        $fields[]="ADD COLUMN `zip` VARCHAR(20) NULL AFTER `state`";
        $fields[]="ADD COLUMN `country_code` VARCHAR(2) NULL AFTER `zip`";
        $meta->count=$this->hotfix->alter($fields);
    }
    function scs_hotfix_20250506($meta) {
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD INDEX `domain_index` (`domain` ASC)";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class user_data_class {
	var $id=0;
	var $ip;
	var $company_name;
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country_code;
    var $status="Pending";
    var $type="Customer";
	var $last_login=0;
	var $login_document_id=0;
	var $landing_document_id=0;
	var $comment;
	var $portal_id=0;
	var $customer_code;
	var $catalog=0;
	var $cad=0;
	var $ecommerce=0;
	var $price_type;
	var $price_pct=0;
	var $language_code="EN";
    var $industry_id=0;
    var $leadscore=1;
    var $revenue;
    var $employees;
    var $domain;
    var $currency_code="USD";
    var $ipstack_json;
    var $recaptcha_score=1.0;
//* Internal
	var $remote=0;
	function __construct() {
        $this->ip=$_SERVER['REMOTE_ADDR'];
        $this->last_login=strtotime("now");
        $this->ipstack_json=new stdClass();
    }
}
class user_constant_class {
	var $status=array('Pending','Active','Denied');
	var $type=array('Customer','External','Internal','Executive','Administrator','Super User');
    var $price_type=array(""=>"No pricing","base"=>"Base price","discount"=>"Discount","plus"=>"Base plus");
}
/*
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `city` varchar(40) DEFAULT NULL,
  `state` varchar(40) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `type` varchar(20) DEFAULT 'Customer',
  `last_login` int(10) unsigned DEFAULT '0',
  `login_document_id` int(10) unsigned DEFAULT '0',
  `landing_document_id` int(10) unsigned DEFAULT '0',
  `comment` varchar(60) DEFAULT NULL,
  `portal_id` int(11) DEFAULT '0',
  `customer_code` varchar(12) DEFAULT NULL,
  `catalog` int(10) unsigned DEFAULT '0',
  `cad` int(10) unsigned DEFAULT '0',
  `ecommerce` int(11) DEFAULT '0',
  `price_pct` decimal(5,4) unsigned DEFAULT '0.0000',
  `language_code` varchar(2) NOT NULL DEFAULT 'EN',
  `industry_id` int(11) NOT NULL DEFAULT '0',
  `leadscore` int(11) NOT NULL DEFAULT '1',
  `tabs` mediumtext,
  `price_type` varchar(12) DEFAULT '',
  `revenue` varchar(20) DEFAULT '',
  `employees` varchar(20) DEFAULT '',
  `domain` varchar(80) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT 'USD',
  `ipstack_json` mediumtext,
  `recaptcha_score` decimal(2,1) DEFAULT '1.0',
  PRIMARY KEY (`id`),
  KEY `domain` (`domain`),
  KEY `domain_index` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Registered users (05/06/2025)'
*/
?>