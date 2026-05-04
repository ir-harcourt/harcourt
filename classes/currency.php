<?php
$database->currency=new currency_class();
class currency_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['09/23/2022']="Add adder_pct";
        $results['09/10/2022']="Add update_timestamp";
        $results['07/22/2022']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new currency_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new currency_constant_class();
        $this->fetch=array();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
	    $this->data=new currency_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->name=$this->fetch['name'];
        $this->data->plural=$this->fetch['plural'];
	    $this->data->symbol=$this->fetch['symbol'];
	    $this->data->exchange_rate=$this->fetch['exchange_rate'];
	    $this->data->adder_pct=$this->fetch['adder_pct'];
	    $this->data->update_timestamp=$this->fetch['update_timestamp'];
	    $this->data->active=$this->fetch['active'];
        $this->exchange_net();
    }
    function read($id,$field="id") {
        $query=array("select * from currency");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new currency_data_class();
		}
    }
    function update($update=FALSE) {
		$this->data->update_timestamp=($this->data->exchange_rate) ? strtotime("now") : 0;
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,FALSE);
        $fields[]="code=" . fn_escape($this->data->code);
        $fields[]="name=" . fn_escape($this->data->name);
        $fields[]="plural=" . fn_escape($this->data->plural);
        $fields[]="symbol=" . fn_escape($this->data->symbol,"null");
        $fields[]="exchange_rate=" . fn_escape($this->data->exchange_rate,FALSE);
        $fields[]="adder_pct=" . fn_escape($this->data->adder_pct,FALSE);
        $fields[]="update_timestamp=" . fn_escape($this->data->update_timestamp,FALSE);
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $this->exchange_net();
        $query=array();
    	if ($update) {
          	$query[]="update currency set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into currency set";
            $query[]=implode(",\n",$fields);
		}
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from currency");
        $query[]="where id=" . fn_escape($id);
		$this->query($query);
    }
    function select($compare="",$select_options=array(),$forms_options=array()) {
    global $database, $forms;
		if (!array_key_exists("field",$select_options)) $select_options['field']="currency_code";
		if (!array_key_exists("blank",$select_options)) $select_options['blank']=TRUE;
        $query_where=array();
		$query=array("select");
        $query[]="case";
        $query[]="when code='USD' then 1";
        $query[]="when exchange_rate > 0 then 2";
        $query[]="else 3";
        $query[]="end as sort_key,";
        $query[]="currency.* from currency";
        $query[]=$this->where($query_where);
        $query[]="order by name";
		$this->query($query);
		$results=array();
        $results['Priced']=array();
        $results['No Price']=array();
        while ($this->fetch = $this->fetch_array() ) {
			$this->fetch();
            if ($this->data->exchange_rate > 0) {
            	$results['Priced'][$this->data->code]=$this->data->name;
			  } else {
            	$results['No Price'][$this->data->code]=$this->data->name;
			}
        }
        $this->free_result();
        return $forms->optgroup($select_options['field'],$results,$compare,$select_options['blank'],$forms_options);
    }
    function exchange_net() {
    	$this->data->exchange_net=round($this->data->exchange_rate * (1 + $this->data->adder_pct),4);
    }
}
class currency_data_class {
	var $id=0;
	var $code;
	var $name;
    var $plural;
    var $symbol;
    var $exchange_rate=0;
    var $adder_pct=0;
    var $update_timestamp=0;
	var $active=1;
// Internal variables
    var $exchange_net=0;
}
class currency_constant_class {
}
/*
https://en.wikipedia.org/wiki/ISO_4217
http://cactus.io/resources/toolbox/html-currency-symbol-codes

ALTER TABLE `currency`
ADD COLUMN `adder_pct` DECIMAL(5,4) NULL DEFAULT NULL AFTER `exchange_rate`,
COMMENT = 'Currency Codes (09/23/2022)' ;

CREATE TABLE `currency` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `plural` varchar(60) DEFAULT NULL,
  `symbol` varchar(20) DEFAULT NULL,
  `exchange_rate` decimal(11,4) DEFAULT NULL,
  `adder_pct` decimal(6,4) DEFAULT NULL,
  `update_timestamp` bigint(10) DEFAULT '0',
  `active` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `plural_UNIQUE` (`plural`)
) ENGINE=InnoDB AUTO_DEFAULT CHARSET=latin1 COMMENT='Currency Codes (09/23/2022)'
*/
?>