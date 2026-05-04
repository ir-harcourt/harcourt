<?php
require_once "classes/cad.php";
class ps_cad_class {
//  variables
	var $action;
    var $command;
    var $record_id=0;
// internal variables
	var $command_list=array();
    function scs_table_version() {
		$results=array();
        $results['04/11/2020']="Change access level";
        $results['08/09/2018']="Integrate w/registry";
        $results['06/06/2016']="Initial release";
        return $results;
    }
	function __construct($option) {
    global $database, $forms, $menu;
		switch (TRUE) {
		  case (!is_array($option)):
			$database->user->access($option);
          	break;
          case (!array_key_exists("administrator",$option)):
			fn_url_redirect($menu->page->home);
		}
		$this->command_list["xml"]="Part Community Import (Last import: " . ( ($database->registry->cad->import_timestamp) ? date("m/d/Y h:i A",$database->registry->cad->import_timestamp) : "Never") . ")";
		$this->command_list["entry"]="CAD Codes (manual entry)";
        $this->command_list["create"]="Create MySQL table";

        $this->action=$_POST['action'];
        $this->command=$_POST['command'];
        $this->record_id=intval($_POST['record_id']);

	    $forms->title("CAD Toolbox (" . key($this->scs_table_version()) . ")");
		$forms->message[]=$this->command_list[$this->command];

        switch ($this->command) {
          case "entry":
          	$this->entry();
            break;
          case "export":
          	$this->export();
            break;
          case "create":
          	$this->create();
            break;
          case "xml":
          	$this->xml();
            break;
          default:
			$this->command();
		}
    }
    function command() {
    global $database, $forms, $menu;
        $this->header();
		$text=array();
        foreach ($this->command_list as $key=>$value) {
        	$text[]=$forms->radio("commands",$key,$this->command,array("onclick"=>"fn_command(this.value,'');")) . " $value";
        }
        $text[]="";
        print "<p class=standard>" . implode("<br>",$text) . "</p>\n";
	    $this->copyright();
    }
    function xml() {
    global $database, $forms, $menu;
		switch ($this->action) {
          case "update":
			$this->xml_url=fn_text($_POST['xml_url']);
            $this->xml_catalog=fn_text($_POST['xml_catalog']);
            if (!($this->xml_url)) $forms->error('xml_url');
            if (!($this->xml_catalog)) $forms->error('xml_catalog');
            if (sizeof($forms->error)) break;
            $url=fn_url($this->xml_url, array("firm"=>$this->xml_catalog) );
			$xml=simplexml_load_file($url);
            if (!$xml) {
            	$forms->error('null',"","Cannot connect to server: $url");
            	break;
            }
            $this->mymap();
            $xml_data=json_decode(json_encode($xml), TRUE);
            $xml_map=array();
            $xml_results=array();
            $this->mymap();
   			$database->cad->meta->error_abort=FALSE;
			foreach ($xml_data['format'] as $item) {
            	$data=new stdClass();
				foreach ($item as $item_key => $item_value) {
                	if (is_array($item_value)) {
                    	foreach ($item_value as $key => $value) {
                        	$field=preg_replace("/\@/","",$item_key) . "_" . preg_replace("/\@/","",$key);
                            $data->$field=$value;
                        }
                      } else {
                      	$data->$item_key=$item_value;
                    }
                }
                foreach ($data as $key => $value) {
                	if (!in_array($key,$xml_map)) $xml_map[]=$key;
                }
                $xml_results[]=$data;
                $database->cad->data=new cad_data_class();
                $database->cad->data->code=$data->qualifier;
                $database->cad->data->name=trim($data->name . " " . $data->version);
                $database->cad->data->type=strtoupper($data->attributes_cad);
                $database->cad->data->instructions=$data->helplink;
                $database->cad->data->native=( ($data->attributes_type == "native") ? 1 : 0);
                $database->cad->data->macro=( ($data->macro == "true") ? 1 : 0);
                $database->cad->data->direct=( ($data->directintocad == "true") ? 1 : 0);
                $this->active=1;
				$database->cad->update(FALSE);
			}
            $forms->message[]="Update complete";
            $database->registry->update("cad","import_timestamp",strtotime("now"));
			break;
          default:
			$this->xml_url=( ($database->registry->cad->partcommunity_url) ? $database->registry->cad->partcommunity_url : "http://www.partcommunity.com/cadqualifier.asp");
            $this->xml_catalog=$database->registry->cad->catalog;
		}
        $this->header();
        $buttons=array();
        switch (TRUE) {
           case ( ($this->action=="update") && (!sizeof($forms->error)) ):
			print "<table class='small border tablesorter'>\n";
            print "<thead>\n";
            print "<tr>";
            foreach ($xml_map as $field) {
            	print "<th>$field</th>\n";
            }
            print "</tr>\n";
            print "</thead>\n";
            print "<tbody>\n";
            foreach ($xml_results as $data) {
	            print "<tr>";
	            foreach ($xml_map as $field) {
	                print "<td>" . $data->$field . "</td>\n";
	            }
	            print "</tr>\n";
			}
			print "</tbody>\n";
            print "</table>\n";
           	break;
           default:
	        print "<p>Part Communuity";
	        print "<br>URL: " . $forms->text('xml_url',$this->xml_url,60);
	        print "<br>Catalog: " . $forms->text('xml_catalog',$this->xml_catalog,60);
	        print "</p>\n";
			$buttons[]=$forms->button("Continue >>",array("onclick"=>"fn_command(" . fn_escape($this->command) . ",'update');"));
		}
        $buttons[]=$forms->button("Return",array("onclick"=>"fn_command('','');"));
        print "<p>" . implode(" ",$buttons) . "</p>\n";
        $this->copyright();
    }
    function create() {
    global $database, $forms, $menu;
		switch ($this->action) {
          case "update":
            $this->mymap();
            $forms->message[]="CAD table created";
			break;
		}
        $this->header();
        $buttons=array();
        switch (TRUE) {
           case ( ($this->action=="update") && (!sizeof($forms->error)) ):
           	break;
           default:
			$buttons[]=$forms->button("Continue >>",array("onclick"=>"fn_command(" . fn_escape($this->command) . ",'update');"));
		}
        $buttons[]=$forms->button("Return",array("onclick"=>"fn_command('','');"));
        print "<p>" . implode(" ",$buttons) . "</p>\n";
        $this->copyright();
    }
    function mymap() {
    global $database, $forms, $menu;
		$query="drop table if exists cad";
        $database->temp->query($query);
        $query=array();
        $query[]="CREATE TABLE `cad` (";
        $query[]="`id` int(6) NOT NULL AUTO_INCREMENT,";
        $query[]="`code` varchar(40) DEFAULT NULL,";
        $query[]="`name` varchar(80) DEFAULT '',";
        $query[]="`type` varchar(20) DEFAULT '',";
        $query[]="`instructions` mediumtext,";
        $query[]="`macro` int(1) DEFAULT '0',";
        $query[]="`native` int(1) DEFAULT '0',";
        $query[]="`direct` int(1) DEFAULT '0',";
        $query[]="`active` int(1) DEFAULT '1',";
        $query[]="PRIMARY KEY (`id`),";
        $query[]="UNIQUE KEY `code_key` (`code`)";
        $query[]=") ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='CAD formats (08/09/2018)'";
        $database->temp->query($query);
    }
    function entry() {
    global $database, $forms, $menu;
	    if ($this->record_id) {
	        $database->cad->read($this->record_id);
	      } else {
	        $database->cad->data=new cad_data_class();
	    }
	    switch ($this->action) {
	      case "cancel":
	        $forms->message[]="Request cancelled";
	        break;
	      case "delete":
	        $database->cad->delete($this->record_id);
	        $forms->message[]="CAD code " . $database->cad->data->code . " deleted";
	        break;
	      case "update":
	        $database->cad->data->code=trim($_POST['code']);
	        if (!$database->cad->data->code) $forms->error('code');
	        $database->cad->data->name=trim($_POST['name']);
	        if (!$database->cad->data->name) $forms->error('name');
	        $database->cad->data->type=$_POST['type'];
	        $database->cad->data->instructions=trim($_POST['instructions']);
	        $database->cad->data->macro=fn_number($_POST['macro']);
	        $database->cad->data->native=fn_number($_POST['native']);
	        $database->cad->data->direct=fn_number($_POST['direct']);
	        $database->cad->data->active=fn_number($_POST['active']);
	        if (sizeof($forms->error)) break;
	        $database->cad->update($database->cad->meta->rows);
	        if ($database->cad->meta->errno) {
	            $forms->error("code","",$database->cad->meta->error);
	          } else {
	            $forms->message[]="Record maintained";
	        }
	        break;
	    }
        $this->header();
	    switch (TRUE) {
	      case ($this->action == "maintain"):
	      case (($this->action == "update") && (sizeof($forms->error))):
	        print "<table class='standard noborder'>";
	        print "<tr>";
	        print "<td width='30%'>PartSolutions Code</td>";
	        print "<td width='70%'>" . $forms->text("code",$database->cad->data->code,40,40) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Name</td>";
	        print "<td>" . $forms->text("name",$database->cad->data->name,40,40) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Type</td>";
	        print "<td>" . $forms->select("type",$database->cad->constant->type,$database->cad->data->type,FALSE) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Instructions URL</td>";
	        print "<td>" . $forms->text("instructions",$database->cad->data->instructions,80) . "</td>";
	        print "</tr>";

	        print "<tr>";
	        print "<td>Macro?</td>";
	        print "<td>" . $forms->checkbox("macro",1,$database->cad->data->macro) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Native?</td>";
	        print "<td>" . $forms->checkbox("native",1,$database->cad->data->native) . "</td>";
	        print "</tr>";
	        print "<tr>";
	        print "<td>Direct to CAD?</td>";
	        print "<td>" . $forms->checkbox("direct",1,$database->cad->data->direct) . "</td>";
	        print "</tr>";

	        print "<tr>";
	        print "<td>Active?</td>";
	        print "<td>" . $forms->checkbox("active",1,$database->cad->data->active) . "</td>";
	        print "</tr>";
	        print "</table>";
            $buttons=array();
			$buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($this->record_id) . ",'');"));
            $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	        print "<p>" . implode(" ",$buttons) . "</p>";
	        break;
	      default:
	        print "<table class='small border tablesorter'>";
	        print "<thead>";
	        print "<tr>";
	        print "<th>Part-Solutions Code</th>";
	        print "<th>Name</th>";
	        print "<th>2D/3D</th>";
	        print "<th>Macro?</th>";
	        print "<th>Native?</th>";
	        print "<th>Direct to CAD?</th>";
	        print "<th>Del?</th>";
	        print "</tr>";
	        print "</thead>";
	        print "<tr><td colspan=5>" . fn_href("Add new","javascript:fn_action(this,'maintain','','');") . "</td></tr>";
	        print "<tbody>";
	        $query="select * from cad order by name";
	        $database->cad->query($query);
	        while ($database->cad->fetch = $database->cad->fetch_array()) {
	            $database->cad->fetch();
	            print "<tr>";
	            print "<td>" . fn_href($database->cad->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->cad->data->id) . ",'');") . "</td>";
	            print "<td>" . $database->cad->data->name . "</td>";
	            print "<td class=center>" . $database->cad->data->type . "</td>";
	            print "<td class=center>" . $database->cad->data->macro . "</td>";
	            print "<td class=center>" . $database->cad->data->native . "</td>";
	            print "<td class=center>" . $database->cad->data->direct . "</td>";
                if (!$database->cad->data->active) {
                  	$text=$forms->radio("delete",$database->cad->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->cad->data->id) . "," . fn_escape($database->cad->data->code) . ");"));
				  } else {
	                $text="&nbsp;";
				}
	            print "<td class=center>$text</td>";
	            print "</tr>";
	        }
	        print "</tbody>";
	        $database->cad->free_result();
	        print "</table>";
	        print "<p>" . $forms->button("Return",array("onclick"=>"fn_command('','');")) . "</p>\n";
	    }
		$this->copyright();
    }
	function header() {
    global $database, $forms, $menu;
	    $menu->head();
	    $forms->message();
    	$results=array();
		$results[]=$forms->open(array("data"=>TRUE));
		$results[]=$forms->hidden("command",$this->command);
		$results[]=$forms->hidden("action");
		$results[]=$forms->hidden("record_id");
		$results[]=$forms->hidden("page");
		$results[]=$forms->hidden("item");
		$results[]="<script type='text/javascript'>";
		$results[]="function fn_action(obj,action,id,name) {";
		$results[]="if (action=='delete') {";
		$results[]="if (!confirm('Delete: #' + id +' ' + name)) {";
		$results[]="obj.checked=false;";
		$results[]="return;";
		$results[]="}";
		$results[]="}";
		$results[]="document.scs_form.action.value=action;";
		$results[]="document.scs_form.record_id.value=id;";
		$results[]="document.scs_form.submit();";
		$results[]="}";
		$results[]="function fn_command(command,action) {";
		$results[]="if (action=='update') {";
		$results[]="if (!confirm('All existing records will be removed')) return;";
		$results[]="}";
		$results[]="document.scs_form.command.value=command;";
		$results[]="document.scs_form.action.value=action;";
		$results[]="document.scs_form.submit();";
		$results[]="}";
		$results[]="</script>";
        print implode("\n",$results);
    }
	function copyright() {
    global $database, $forms, $menu;
	    print $forms->close();
	    $menu->copyright();
    }
}
?>