<?php
require_once "classes/cadorder_update.php";
$database->cadorder=new cadorder_class();
class cadorder_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['03/10/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
    	$this->data=new cadorder_data_class();
        $this->constant=new cadorder_constant_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new cadorder_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->firm=$this->fetch['firm'];
	    $this->data->user_id=$this->fetch['user_id'];
	    $this->data->sku_name=$this->fetch['sku_name'];
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->cad_code=$this->fetch['cad_code'];
	    $this->data->parameters=unserialize($this->fetch['parameters']);
	    $this->data->response_url=$this->fetch['response_url'];
	    $this->data->ps_order=$this->fetch['ps_order'];
	    $this->data->ps_zipfile=$this->fetch['ps_zipfile'];
	    $this->data->ps_message=unserialize($this->fetch['ps_message']);
	    $this->data->ps_contents=unserialize($this->fetch['ps_contents']);
	    $this->data->initial_date=$this->fetch['initial_date'];
	    $this->data->status=$this->fetch['status'];
	    $this->data->status_date=$this->fetch['status_date'];
	    $this->data->status_code=$this->fetch['status_code'];
    }
    function read($id,$field="id") {
        $query =
            "select * from cadorder " .
            "where $field=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new cadorder_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
		$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="firm=" . fn_escape($this->data->firm);
	    $fields[]="user_id=" . fn_escape($this->data->user_id,FALSE);
	    $fields[]="sku=" . fn_escape($this->data->sku);
	    $fields[]="sku_name=" . fn_escape($this->data->sku_name);
	    $fields[]="cad_code=" . fn_escape($this->data->cad_code);
	    $fields[]="parameters=" . fn_escape(serialize($this->data->parameters));
	    $fields[]="response_url=" . fn_escape($this->data->response_url);
	    $fields[]="ps_order=" . fn_escape($this->data->ps_order);
	    $fields[]="ps_zipfile=" . fn_escape($this->data->ps_zipfile);
	    $fields[]="ps_message=" . fn_escape(serialize($this->data->ps_message));
	    $fields[]="ps_contents=" . fn_escape(serialize($this->data->ps_contents));
	    $fields[]="initial_date=" . fn_escape($this->data->initial_date,FALSE);
	    $fields[]="status=" . fn_escape($this->data->status);
	    $fields[]="status_date=" . fn_escape($this->data->status_date,FALSE);
	    $fields[]="status_code=" . fn_escape($this->data->status_code);
    	if ($update) {
          	$query=
            	"update cadorder set \n" .
                implode(",\n",$fields) . "\n" .
				"where id=" . fn_escape($this->data->id);
		  } else {
        	$query=
        		"insert into cadorder set \n" .
                implode(",\n",$fields);
		}
		$this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query =
	        "delete from cadorder where " .
	        "id=" . fn_escape($id);
		$this->query($query);
    }
    function file_url($raw=TRUE) {
    global $database;
		switch (TRUE) {
          case (!$this->data->ps_zipfile):
          case (!isset($database->registry->cadcart->folder)):
			return "";
          default:
			return $_SERVER['DOCUMENT_ROOT'] . $database->registry->cadcart->folder . $this->data->ps_zipfile;
		}
    }
    function unlink() {
		$filename=$this->file_url();
		if ( ($filename) && (file_exists($filename)) ) unlink($filename);
    }
    function reset($pending=TRUE) {
		$this->unlink();
		if ($pending) {
	        $this->data->response_url="";
	        $this->data->ps_order="";
        }
	    $this->data->ps_zipfile="";
	    $this->data->ps_message=array();
	    $this->data->ps_contents=array();
    }
}
class cadorder_data_class {
	var $id=0;
	var $firm;
	var $user_id=0;
    var $sku;
    var $sku_name;
    var $cad_code;
    var $parameters=array();
    var $response_url;
    var $ps_order;
    var $ps_zipfile;
    var $ps_message=array();
    var $ps_contents=array();
	var $initial_date=0;
	var $status="Order";
	var $status_date=0;
    var $status_code;
    function __construct() {
		$this->initial_date=strtotime("now");
        $this->status_date=$this->initial_date;
        $this->firm=$_SESSION['company']->firm;
    }
}
class cadorder_constant_class {
	var $status=array('Order','Pending','File','Retrieve','Expired','Cancel','Error');
	var $status_description=array(
    	'Order'=>'Sent to PARTsolutions',
        'Pending'=>'Order received by PARTsolutions',
        'File'=>'File uploaded from PARTsolutions',
        'Retrieve'=>'Order Retrieved by Customer',
        'Cancel'=>'Order Cancelled',
        'Expired'=>'Order Expired',
        'Error'=>'Error in transmission');
	var $housekeeping=array();
    var $mesasge=array();
    var $log_comment=array();
    function __construct() {
    }
    function load_message() {
    global $database;
		$this->housekeeping['cancel']="Cancel unfilled orders (after 5 minutes)";
		$this->housekeeping['expired']="Expire old orders (" . $database->registry->cadcart->expire_days . " days)";
		$this->housekeeping['purge']="Remove cancel/error orders (" . $database->registry->cadcart->purge_days . " days)";
		$this->housekeeping['file']="Retrieve completed orders";
		$this->housekeeping['log']="Remove debugging log entries (after 5 days)";
		$this->housekeeping['zip']="Remove orphan zip files (Days)";

		/* Order Errors */
		$this->create("A00","Order","","Order Created","Processing, please wait.");
		$this->create("A11","Order","Denied","Email address required","Login required to download CAD");
		$this->create("A21","Order","Denied","%EMAIL% not a registered user","A problem was encountered while processing your request");
		$this->create("A22","Order","Denied","%EMAIL% not an active user (%USER_STATUS%)","A problem was encountered while processing your request");
		$this->create("A23","Order","Denied","%EMAIL% CAD access denied","A problem was encountered while processing your request");
		$this->create("A31","Order","Denied","%EMAIL% Pending CAD request for SKU: %SKU% CAD Format: %CAD_FORMAT% already exists","Pending CAD request for SKU: %SKU% already exists");
		$this->create("A41","Order","Denied","%EMAIL% CAD pending limit of %PENDING_LIMIT% exceded","Previous items have not been processed, please try again");
		$this->create("A99","Order","Error","An internal error has occurred","Processing, please wait.");

        /* Pending Errors */
        if ($database->registry->cadcart->B00_html) {
        	$text=$database->registry->cadcart->B00_html;
          } else {
          	$text="Request has been accepted.";
        }
		$this->create("B00","Pending","","PARTsolutions accepted order %RESPONSE_URL%",$text);
		$this->create("B11","Pending","Error","Field not included in response: scs_order","A problem was encountered while processing your request");
		$this->create("B12","Pending","Error","Order# %ORDER_ID% not found","A problem was encountered while processing your request");
		$this->create("B13","Pending","Error","Order# %ORDER_ID% already processed, status %ORDER_STATUS%","A problem was encountered while processing your request");
		$this->create("B21","Pending","Error","Field not included in response: response_url","A problem was encountered while processing your request");
		$this->create("B99","Pending","Error","An internal error has occurred","A problem was encountered while processing your request");

		/* File Errors */
		$this->create("C00","File","","PARTsolutions upload complete from %RESPONSE_URL%","");
		$this->create("C01","File","Poll","File: %RESPONSE_URL% not ready","");
		$this->create("C11","File","Error","Zip file not created (see message)","");
		$this->create("C99","File","Error","An internal error has occurred","A problem was encountered while processing your request");

        /* Cancel Order */
		$this->create("D00","Cancel","Customer","CAD Order #%ORDER_ID% for SKU %SKU% cancelled by customer","Cancelled CAD Order for SKU %SKU%");
		$this->create("D01","Cancel","Automatic","CAD Order #%ORDER_ID% for SKU %SKU% cancelled automatically, not Partserver response in 5 minutes.","");
		$this->create("D11","Cancel","Error","Order #%ORDER_ID% not on file","Cancelled CAD Order for SKU %SKU%");
		$this->create("D12","Cancel","Error","Order #%ORDER_ID% fully processed, Status: %ORDER_STATUS%","Cancelled CAD Order for SKU %SKU%");

        /* Expired Order */
		$this->create("E00","Expired","","Order expired after %CART_EXPIRE_DAYS% days","");

        /* Internal Errors */
		$this->create("Z01","Internal","Error","Function: %SCS_ACTION% did not return a status code","Processing, please wait.");
		$this->create("Z02","Internal","Error","Function: %SCS_ACTION% return invalid status code: %STATUS_CODE%","Processing, please wait.");
		$this->create("Z03","Internal","Error","Mandatory field not populated: scs_action","Processing, please wait.");
		$this->create("Z04","Internal","Error","Incorrect Action %SCS_ACTION%","Processing, please wait.");
    }
    function create($code,$status,$error,$internal,$external) {
		$log_subtype=$status;
		if ($error) $log_subtype .= " $error ($code)";
        $external .= " ($code)";
        $this->message[$code]=new cadorder_message_class($status,$log_subtype,$internal,$external);
    }
}
class cadorder_message_class {
	var $status;
    var $log_subtype;
	var $internal;
    var $external;
    function __construct($status,$log_subtype,$internal,$external) {
    	$this->status=$status;
        $this->log_subtype=$log_subtype;
		$this->internal=$internal;
		$this->external=$external;
    }
}
 class cadorder_contents_class {
	var $view;
    var $view_description;
    var $file;
    var $cad_code;
    var $cad_version;
	function __construct($contents) {
		foreach ($contents as $key => $value) {
			switch ($key) {
              case "NN":
              case "NB":
				continue;
              case "VIEW":
				$this->view=$value->__tostring();
                break;
              case "VIEWDESC":
				$this->view_description=$value->__tostring();
                break;
              case "FILENAME":
				$this->file=$value->__tostring();
                break;
              case "CADFORMAT":
				$this->cad_code=$value->__tostring();
                break;
              case "CADVERSION":
				$this->cad_version=$value->__tostring();
                break;
			}
        }
    }
}
/*
CREATE TABLE `cadorder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm` varchar(40) NOT NULL DEFAULT '',
  `user_id` int(10) NOT NULL DEFAULT '0',
  `sku` varchar(255) NOT NULL DEFAULT '',
  `sku_name` varchar(255) NOT NULL DEFAULT '',
  `cad_code` varchar(255) NOT NULL DEFAULT '',
  `parameters` mediumtext,
  `response_url` mediumtext,
  `ps_order` varchar(20) NOT NULL DEFAULT '',
  `ps_zipfile` varchar(255) NOT NULL DEFAULT '',
  `ps_message` mediumtext,
  `ps_contents` mediumtext,
  `initial_date` bigint(14) NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT 'Order',
  `status_date` bigint(14) NOT NULL DEFAULT '0',
  `status_code` varchar(12) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1000 DEFAULT CHARSET=latin1 COMMENT='CAD order (03/10/2014)'
*/
?>