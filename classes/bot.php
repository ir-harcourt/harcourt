<?php
$database->bot = new bot_class();
class bot_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['12/02/2016']="Incorporate new logging";
        $results['03/08/2016']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new bot_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new bot_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new bot_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->code=$this->fetch['code'];
	    $this->data->pattern=$this->fetch['pattern'];
	    $this->data->name=$this->fetch['name'];
	    $this->data->portal_id=$this->fetch['portal_id'];
	    $this->data->catalog=$this->fetch['catalog'];
	    $this->data->comment=$this->fetch['comment'];
	    $this->data->status=$this->fetch['status'];
    }
    function read($id,$field="id") {
        $query =
            "select * from bot " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
			$this->free_result();
		  } else {
			$this->data=new bot_data_class();
		}
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="code=" . fn_escape($this->data->code);
	    $fields[]="pattern=" . fn_escape($this->data->pattern);
	    $fields[]="name=" . fn_escape($this->data->name);
	    $fields[]="portal_id=" . fn_escape($this->data->portal_id,FALSE);
	    $fields[]="catalog=" . fn_escape($this->data->catalog,FALSE);
	    $fields[]="comment=" . fn_escape($this->data->comment);
	    $fields[]="status=" . fn_escape($this->data->status);
    	if ($update) {
          	$query=
            	"update bot set \n" .
				implode(",\n",$fields) . "\n" .
				"where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
            	"insert into bot set \n" .
				implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from bot where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function load_bot() {
		$this->constant->bot=array();
	    $this->query("select * from bot");
	    while ($this->fetch = $this->fetch_array() ) {
	        $this->fetch();
	        $this->constant->bot[$this->data->id]=$this->data;
	    }
		$this->free_result();
    }
    function match_bot($ip) {
		if (!isset($this->constant->bot)) $this->load_bot();
        $dns=gethostbyaddr($ip);
        $results=new bot_data_class();
	    foreach ($this->constant->bot as $bot) {
        	if (preg_match($bot->pattern,$dns)) {
            	$results=$bot;
            	break;
            }
	    }
        $results->dns=$dns;
		return $results;
    }
    function session($bot,$impersonate=FALSE) {
    global $database,$menu;
	    $database->user->data=new user_data_class();
	    $database->user->data->id=($bot->id * -1);
	    $database->user->data->company_name=$bot->name;
	    $database->user->data->status=$bot->status;
	    $database->user->data->portal_id=$bot->portal_id;
	    $database->user->data->catalog=$bot->catalog;
	    $database->user->data->bot_id=$bot->id;
	    $database->user->data->bot_code=$bot->code;
	    $database->user->data->language_code=$menu->request_language();
        $_SESSION['user']=$database->user->data;
		$database->log->update("BOT: " . $bot->status);
        switch (TRUE) {
          case ($bot->status != "Active"):
          case ($impersonate):
          	fn_url_redirect("/index.php");
	    }
    }
}
class bot_data_class {
	var $id=0;
    var $code;
    var $pattern;
    var $name;
    var $portal_id=0;
    var $catalog=1;
    var $comment;
    var $status;
}
class bot_constant_class {
	var $status=array("Active","Denied");
}
/*
CREATE TABLE `bot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL DEFAULT '',
  `pattern` varchar(256) NOT NULL DEFAULT '',
  `name` varchar(60) NOT NULL DEFAULT '',
  `portal_id` int(11) NOT NULL DEFAULT '0',
  `catalog` int(1) NOT NULL DEFAULT '0',
  `comment` mediumtext,
  `status` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_index` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bots (03/08/2016)'
*/
?>