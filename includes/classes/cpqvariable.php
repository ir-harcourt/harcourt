<?php
$database->cpqvariable=new cpqvariable_class();
class cpqvariable_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['02/22/2025']="Add type:email";
        $results['01/29/2025']="Document list in registry";
        $results['12/30/2024']="Add output_pdf";
        $results['12/19/2024']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new cpqvariable_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new cpqvariable_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cpqvariable_data_class();
	    $this->data->id=$this->fetch['id'];
        $this->data->prompt=$this->fetch['prompt'];
	    $this->data->document=$this->fetch_json($this->fetch["document"], TRUE);
	    $this->data->rank=intval($this->fetch['rank']);
        $this->data->type=$this->fetch['type'];
        $this->data->mandatory=intval($this->fetch['mandatory']);
        $this->data->output=$this->fetch['output'];
        $this->data->output_pdf=$this->fetch['output_pdf'];
        $this->data->list=$this->fetch_json($this->fetch["list"], TRUE);
        $this->data->active=intval($this->fetch['active']);
    }
    function read($id,$field="id") {
        $query=array("select * from cpqvariable");
        $query[]="where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new cpqvariable_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id,TRUE);
        $fields[]="prompt=" . fn_escape($this->data->prompt);
        $fields[]="document=" . fn_escape(json_encode($this->data->document));
        $fields[]="rank=" . fn_escape($this->data->rank,TRUE);
        $fields[]="type=" . fn_escape($this->data->type);
        $fields[]="mandatory=" . fn_escape($this->data->mandatory,FALSE);
        $fields[]="output=" . fn_escape($this->data->output);
        $fields[]="output_pdf=" . fn_escape($this->data->output_pdf,TRUE);
        $fields[]="list=" . fn_escape(json_encode($this->data->list));
        $fields[]="active=" . fn_escape($this->data->active,FALSE);
        $query=array();
    	if ($update) {
          	$query[]="update cpqvariable set";
            $query[]=implode(",\n",$fields);
            $query[]=" where id=" . fn_escape($this->data->id);
		  } else {
        	$query[]="insert into cpqvariable set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from cpqvariable where id=" . fn_escape($id);
		$this->query($query);
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("cpqvariable", "CPQ Variables");
        $this->hotfix->version("12/13/2024", "Initial release");
        $this->hotfix->version("12/19/2024", "Add output");
        $this->hotfix->version("12/30/2024", "Add output_pdf");
        $this->hotfix->process($execute);
    }
    function scs_hotfix_20241219($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="ADD COLUMN `output` VARCHAR(12) DEFAULT '' AFTER `mandatory`";
        $meta->count=$this->hotfix->alter($fields);
    }
    function scs_hotfix_20241230($meta) {
        global $database;
        $this->hotfix->trace[]=__FUNCTION__;
        $fields=array();
        $fields[]="DROP COLUMN `code`";
        $fields[]="ADD COLUMN `output_pdf` int(1) DEFAULT '0' AFTER `output`";
        $meta->count=$this->hotfix->alter($fields);
    }
}
class cpqvariable_data_class {
    var $id=0;
    var $prompt;
    var $rank=0;
    var $type;
    var $mandatory=1;
    var $output;
    var $output_pdf=0;
    var $list=array();
    var $active=1;
    function __construct() {
    }
}
class cpqvariable_constant_class {
    var $type=array();
    var $document=array();
    var $output=array();
    function __construct() {
        $this->type["uppercase"]="Uppercase";
        $this->type["alphanumeric"]="Alpha/Numeric";
        $this->type["decimal:0"]="Integer";
        $this->type["decimal:2"]="2 decimal place number";
        $this->type["currency"]="Currency";
        $this->type["date"]="Date";
        $this->type["email"]="Email";
        $this->type["checkbox"]="Checkbox";
        $this->type["list"]="List";
        $this->output['']="Enter";
        $this->output['display']="Display only";
        $this->output['hide']="Hide";
    }
}
class cpqvariable_input_class {
    var $document;
    var $prefix;
    var $fields=array();
    var $map=array();
    function __construct($document, $prefix="") {
        global $database;
        $this->document=$document;
        $this->prefix=$prefix;
        $query_where=array();
        $query_where[]=new query_where("document", "like", "%\"{$document}\"%");
        $query_where[]=new query_where("active", "=", 1);
        $query=array("select * from cpqvariable");
        $query[]=$database->where($query_where);
        $query[]="order by rank, prompt";
        $database->cpqvariable->query($query);
        while ($database->cpqvariable->fetch = $database->cpqvariable->fetch_array()) {
            $database->cpqvariable->fetch();
            $field="cpqvariable{$database->cpqvariable->data->id}";
            $obj=$database->cpqvariable->data;
            $obj->field="{$this->prefix}{$field}";
            switch ($obj->type) {
              case "uppercase":
                $obj->map_type="uppercase";
                $obj->map_width=40;
                break;
              case "email":
                $obj->map_type="email";
                $obj->map_width=60;
                break;
              case "decimal:0":
              case "decimal:2":
              case "date":
                $obj->map_type=$obj->type;
                break;
              case "currency":
                $obj->map_type="decimal:2";
                break;
              case "checkbox":
                $obj->map_type="boolean";
                break;
              default:
                $obj->map_type="alphanumeric";
                $obj->map_width=40;
            }
            $this->fields[$field]=$obj;
            $this->map[$obj->field]=$field;
        }
    }
    function load($data, $options=array()) {
        global $database, $forms;
        $clear=(array_key_exists("clear", $options)) ? $options['clear'] : FALSE;
        $replace=(array_key_exists("replace", $options)) ? $options['replace'] : TRUE;
        $prefix=(array_key_exists("prefix", $options)) ? $options['prefix'] : $this->prefix;
        if ($clear) {
            foreach ($this->fields as $field => $obj) {
                unset($obj->value, $obj->display);
            }
        }
        foreach ($data as $key => $value) {
            $field=(!strlen($prefix)) ? $key : $this->map[$key];
            if ( (!strlen($field)) || (!array_key_exists($field, $this->fields)) ) continue;
            $obj=$this->fields[$field];
            if ( (!$replace) && (strlen($obj->display)) ) continue;
            $text=fn_text($value);
            switch ($obj->type) {
              case "uppercase":
                $obj->value=strtoupper($text);
                $obj->display=$obj->value;
                break;
              case "decimal:0":
              case "decimal:2":
                $decimal=substr($obj->type, -1);
                $obj->value=fn_number($text, $decimal);
                if ($obj->value) $obj->display=number_format($obj->value, $decimal);
                break;
              case "currency":
                $obj->value=fn_number($text, 2);
                if ($obj->value) $obj->display="$" . number_format($obj->value, 2);
                break;
              case "date":
                $obj->value=(is_object($data)) ? $text : fn_date($text, "mdy");
                $obj->display=fn_date($obj->value, "ymd");
                break;
              case "checkbox":
                $obj->value=true_false($text);
                $obj->display=($obj->value) ? "Yes" : "No";
                break;
              default:
                $obj->value=$text;
                $obj->display=$obj->value;
            }
        }
        foreach ($this->fields as $key => $obj) {
            if ( ($obj->type == "checkbox") && (!property_exists($obj, "value")) ) $obj->value=0;
        }
    }
    function input($internal, $input_options=array()) {
        global $database, $forms;
        $tr_class=($input_options['tr_class']) ? "class='{$input_options['tr_class']}'" : "";
        $results=array();
        foreach ($this->fields as $field => $obj) {
            if ( (!$internal) && ($obj->output == "hide") ) continue;
            $field_name="{$this->prefix}{$field}";
            $options=array();
            if ($input_options['td_class']) $options['class']=$input_options['td_class'];
            $options['placeholder']=($obj->mandatory) ? "Required" : "Optional";
            $results[]="<tr {$tr_class}>";
            $results[]="<td>{$obj->prompt}</td>";
            $text=array();
            switch (TRUE) {
              case ( (!$internal) && ($obj->output == "display") ):
                $text[]=$obj->display;
                break;
              case ($obj->type == "date"):
                $text[]=$forms->text($field_name, $obj->value, 10, 10, "date", $options);
                break;
              case ($obj->type == "decimal:0"):
              case ($obj->type == "decimal:2"):
                $text[]=$forms->text($field_name, $obj->value, 12, 12, $obj->type, $options);
                break;
              case ($obj->type == "currency"):
                $text[]="$" . $forms->text($field_name, $obj->value, 14, 14, "decimal:2", $options);
                break;
              case ($obj->type == "checkbox"):
                $text[]=$forms->checkbox($field_name, 1, $obj->value, $options);
                break;
              case ($obj->type == "list"):
                $text[]=$forms->select($field_name, $obj->list, $obj->value, (($obj->mandatory) ? "Required" : "Optional"), $options);
                break;
              case ($obj->type == "email"):
                $text[]=$forms->text($field_name, $obj->value, 60, 60, "", $options);
                break;
              default:
                $text[]=$forms->text($field_name, $obj->value, 40, 40, "", $options);
            }
            $results[]="<td>" . implode("<br>", $text) . "</td>";
            $results[]="</tr>";
        }
        return implode("", $results);
    }
    function verify($internal, $tabs_name="") {
        global $database, $forms;
        foreach ($this->fields as $field => $obj) {
            switch (TRUE) {
              case ($obj->map_type == "email"):
                $email=new email_class($obj->display, $obj->mandatory);
                $obj->value=$email->address;
                $obj->display=$email->address;
                $obj->error=$email->error;
                if ($obj->error) $forms->error($field, $obj->error, "", $tabs_name);
                break;
              case (!$obj->mandatory):
              case ($obj->type == "checkbox"):
              case ( (!$internal) && ($obj->output) ):
              case (strlen($obj->display)):
                break;
              default:
                $obj->error="Cannot be blank";
                $forms->error($field, "Cannot be blank", "", $tabs_name);
            }
        }
    }
    function json($json) {
        if (!is_object($json)) $json=new stdClass();
        foreach ($this->fields as $field => $obj) {
            $json->$field=$obj->value;
        }
    }
    function search_initialize($internal) {
        foreach ($this->fields as $field => $obj) {
            switch (TRUE) {
              case ( (!$internal) && ($obj->output) ):
                unset($this->fields[$field]);
                break;
              default:
                $obj->mandatory=FALSE;
            }
        }
    }
    function search_input($internal, $input_options=array()) {
        $this->search_initialize($internal);
        if (!sizeof($this->fields)) return;
        $results=array();
        $results[]="<tr><td colspan=2><b>Parametric Search</b></td></tr>";
        $results[]=$this->input($internal, $input_options);
        return implode("", $results);
    }
    function search_verify($internal, $tabs_name="") {
        $this->search_initialize($internal);
        $this->verify($internal);
    }
    function query($table, $options) {
        global $database;
        $fields=(array_key_exists("fields", $options)) ? $options["fields"] : array();
        $join=(array_key_exists("join", $options)) ? $options["join"] : array();
        $where=(array_key_exists("where", $options)) ? $options["where"] : array();
        $having=(array_key_exists("having", $options)) ? $options["having"] : array();
        $order=(array_key_exists("order", $options)) ? $options["order"] : "";
        foreach ($this->fields as $field => $obj) {
            $fields[]="JSON_UNQUOTE(JSON_EXTRACT({$table}.variable, '$.{$field}')) as $field";
            switch (TRUE) {
              case (!$obj->display):
              case ( ($obj->type == "checkbox") && (!$obj->value) ):
                continue;
              case ($obj->type == "decimal:0"):
              case ($obj->type == "decimal:2"):
              case ($obj->type == "currency"):
              case ($obj->type == "date"):
                $having[]=new query_where($field, ">=", $obj->value);
                break;
              case ($obj->type == "list"):
              case ($obj->type == "checkbox"):
                $having[]=new query_where($field, "=", $obj->value);
                break;
              default:
                $having[]=new query_where($field, "like", "{$obj->value}%");
            }

        }
        $query=array("select");
        if (sizeof($fields)) $query[]=implode(",\n", $fields) . ",\n";
        $query[]="{$table}.* from {$table}";
        $query[]=implode("\n", $join);
        $query[]=$database->where($where);
        if (sizeof($having)) $query[]=$database->where($having, "having");
        $query[]=$order;
        return $query;
    }
    function list($internal, $data) {
        $results=array();
        $options['clear']=TRUE;
        $options['prefix']="";
        $this->load($data, $options);
        foreach ($this->fields as $field => $obj) {
            switch (TRUE) {
              case (!strlen($obj->display)):
              case ( (!$internal) && ($obj->output == "hide") ):
                break;
              default:
                $results[]="{$obj->prompt}: {$obj->display}";
            }
        }
        return implode("<br>", $results);
    }
    function pdf() {
        $results=array();
        foreach ($this->fields as $field => $obj) {
            switch (TRUE) {
              case (!strlen($obj->display)):
              case ($obj->output == "hide"):
              case (!$obj->output_pdf):
                break;
              default:
                $results[$obj->prompt]=$obj->display;
            }
        }
        return $results;
    }
    function map($map) {
        $internal=( ($_SESSION['cpqdomain']->id) && ($_SESSION['cpqdomain']->external)  ) ? FALSE : TRUE;
        foreach ($this->fields as $field => $obj) {
            if ( (!$internal) && ($obj->output == "hide") ) continue;
            $options=array();
            $options['name']=$obj->prompt;
            $options['type']=$obj->map_type;
            if ($obj->map_width) $options['width']=$obj->map_width;
            $map->item($field, $options);
        }
    }
}
/*
CREATE TABLE `cpqvariable` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `prompt` varchar(120) DEFAULT NULL,
  `document` varchar(256) DEFAULT NULL,
  `rank` int(2) DEFAULT '0',
  `type` varchar(20) DEFAULT NULL,
  `mandatory` int(1) DEFAULT '1',
  `output` varchar(12) DEFAULT '',
  `output_pdf` int(1) DEFAULT '0',
  `list` mediumtext,
  `active` int(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `prompt_UNIQUE` (`prompt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='CPQ Variables (12/30/2024)'
*/
?>