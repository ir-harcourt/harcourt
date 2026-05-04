<?php
class cadorder_update_class {
	var $action;
    var $options=array();
    var $sku;
    var $order_id=0;
    var $user_id=0;
    var $firm;
    var $email;
    var $cad_code;
    var $response_url="";
    var $cadorder_update=FALSE;
    var $request=array();
    var $ps_request=array();
	var $status_code;
    var $status_internal;
    var $status_external;
    var $results=array();
    var $comment=array();
    var $html=array();
    var $endtime=0;
    function scs_table_version() {
    	$results=array();
        $results['11/25/2014']="Add zip purge";
        $results['04/29/2014']="Include html headers on output";
        $results['03/06/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($action,$options=array()) {
		$this->endtime=strtotime("now") + 25;
		if (is_array($options)) $this->options=$options;
		switch ($action) {
          case "":
        	break;
		  case "import":
			$this->import();
            break;
		  case "poll":
			$this->poll();
            break;
		  case "housekeeping":
			$this->housekeeping();
            break;
		  case "cancel":
			$this->cancel();
            break;
		  case "expire":
		  case "expired":
			$this->expired();
            break;
		  case "file":
			$this->file();
            break;
		  case "purge":
			$this->purge();
		  case "log":
			$this->log();
            break;
		  case "zip":
			$this->zip();
            break;
          default:
			print "unknown action: $action";
            die;
        }
    }
    function import() {
    global $database, $forms;
	    if ($_SERVER['REQUEST_METHOD'] == "GET") {
	        foreach ($_GET as $key => $value) {
	            $this->request[$key]=trim($value);
	        }
	      } else {
	        foreach ($_POST as $key => $value) {
	            $this->request[$key]=trim($value);
	        }
	    }
	    foreach ($this->request as $key => $value) {
	        $results[]="$key: $value";
	        switch (TRUE) {
	          case ($key == "scs_action"):
	            $this->action=strtolower(trim($value));
	            break;
	          case ($key == "scs_sku"):
	            $this->sku=$value;
	            break;
              case (preg_match("/scs_/i",$key)):
	            break;
	          default:
	            $this->ps_request[$key]=$value;
	        }
	    }
        switch (TRUE) {
		  case (!$this->action):
          	$this->status("Z03");
            break;
          case (!method_exists($this, $this->action)):
          	$this->status("Z04");
            break;
          default:
			$this->status( $this->{$this->action}() );
		}
        if ($this->status_external) $this->html[]=$this->status_external;
        if ($this->cadorder_update) {
			$database->cadorder->data->status_date=strtotime("now");
			$database->cadorder->data->status_code=$this->status_code;
			$database->cadorder->update(TRUE);
        }
        if ($this->status_internal) $this->comment[]=$this->status_internal;
        if (isset($database->registry->cadcart->log_ps)) $this->log_ps();
		$database->log->update("CAD Cart:" . $database->cadorder->constant->message[$this->status_code]->log_subtype,$this->comment,$this->user_id,$database->cadorder->data->sku);
        if ($this->status_code=="A00") {
	        $this->html[]="<script type='text/javascript'>";
            $this->html[]="function fn_action_go() {";
	        $this->html[]="document.ps_form.submit();";
            $this->html[]="}";
            $this->html[]="function fn_action_cancel() {";
	        $this->html[]="document.scs_form.submit();";
            $this->html[]="}";
			if (!isset($database->registry->cadcart->A00_html)) $this->html[]="fn_action_go();";
	        $this->html[]="</script>";
		}
        if (sizeof($this->html)) {
			$forms->html_open();
            print "<span style='font-family: Arial, Helvetica, sans-serif; font-size: 9pt; font-weight: normal;'>\n";
            print implode("\n",$this->html);
            print "</span>\n";
            $forms->html_close();
        }
    }
    function order() {
    global $database, $forms;
		$this->email=$this->ps_request['email'];
		$this->cad_code=$this->ps_request['format'];
	    $database->user->read($this->email,"email");
		$this->user_id=$database->user->data->id;
	    switch (TRUE) {
	      case (!$this->ps_request['email']):
	        return "A11";
	      case (!$database->user->meta->rows):
	        return "A21";
	      case ($database->user->data->status != "Active"):
	        return "A22";
	      case ($database->user->data->cad_limit == "Denied"):
	        return "A23";
	    }
        /*	Prevent downloading of same file */
        $query_where=array();
        $query_where[]=new query_where("status","in",array("Order","Pending","File"));
        $query_where[]=new query_where("user_id","=",$database->user->data->id);
        $query_where[]=new query_where("sku","=",$this->sku);
        $query_where[]=new query_where("cad_code","=",$this->cad_code);
        $query=
        	"select * from cadorder \n" .
            $database->where($query_where);
		$database->temp=new database_temp_class();
		$database->temp->query($query);
		$database->temp->free_result();
        if ($database->temp->meta->rows) return "A31";

		/*  Throttle number of unfilled orders */
        $query_where=array();
        $query_where[]=new query_where("status","in",array("Order"));
        $query_where[]=new query_where("user_id","=",$database->user->data->id);
        $query=
        	"select * from cadorder \n" .
            $database->where($query_where);
		$database->temp=new database_temp_class();
		$database->temp->query($query);
		$database->temp->free_result();
        if ($database->temp->meta->rows > $database->registry->cadcart->pending_limit) return "A41";

	    $database->cadorder->data=new cadorder_data_class();
	    $database->cadorder->data->firm=$this->request['firm'];
	    $database->cadorder->data->user_id=$database->user->data->id;
	    $database->cadorder->data->sku=$this->sku;
	    $database->cadorder->data->cad_code=$this->request['format'];
	    $database->cadorder->data->parameters=$this->ps_request;
	    $database->cadorder->data->status_date=$database->cadorder->data->initial_date;
	    $database->cadorder->data->status_code="A00";
		$database->cadorder->update(FALSE);
		$database->user->cadcart();
	    $this->comment[]="CAD Order #" . $database->cadorder->data->id;
	    $this->comment[]="CAD Format: " . $database->cadorder->data->cad_code;
		if (isset($database->registry->cadcart->A00_html)) {
        	$this->html[]=$forms->button("Go!",array("onclick"=>"fn_action_go();"));
        	$this->html[]=$forms->button("Cancel",array("onclick"=>"fn_action_cancel();"));
            $this->html[]="<div>" . $database->registry->cadcart->A00_html . "</div>";
		}
		$this->html[]=$forms->open();
        $this->html[]=$forms->hidden("scs_action","cancel_order");
        $this->html[]=$forms->hidden("scs_order",$database->cadorder->data->id);
        $this->html[]=$forms->close();
        $this->html[]=$forms->open( array("name"=>"ps_form","url"=>$database->registry->cadcart->ps_url,"method"=>"get") );
		$this->html[]="<input type=hidden name=ok_url value='" . $database->registry->cadcart->return_url . "?file=<%download_xml%>&scs_action=pending&scs_order=" . $database->cadorder->data->id . "'>\n";
        foreach ($this->ps_request as $key => $value) {
        	if (!in_array($key,array('ok_url','not_ok_url')) ) {
				$this->html[]=$forms->hidden($key,$value);
			}
        }
        $this->html[]=$forms->close();
		return "A00";
    }
    function pending() {
    global $database;
		if (isset($this->request['file'])) $this->response_url=$this->request['file'];
		if (isset($this->request['scs_order'])) $this->order_id=$this->request['scs_order'];
        $database->cadorder->read($this->order_id);
		$this->user_id=$database->cadorder->data->user_id;
        switch (TRUE) {
		  case (!$this->order_id):
          	return "B11";
		  case (!$database->cadorder->meta->rows):
          	return "B12";
		  case (!in_array($database->cadorder->data->status,array("Order","Pending","File","Error"))):
          	return "B13";
        }
        $this->cadorder_update=TRUE;
		$database->cadorder->reset();
        $database->cadorder->data->response_url=$this->response_url;
		if (!$database->cadorder->data->response_url) {
			$database->cadorder->data->status="Error";
			return "B21";
		}
		$database->cadorder->data->status="Pending";
        return "B00";
    }
    function cancel_order() {
	global $database;
    	$this->order_id=$this->request['scs_order'];
        $database->cadorder->read($this->order_id);
        $this->sku=$database->cadorder->data->sku;
        $this->user_id=$database->cadorder->data->user_id;
        switch (TRUE) {
		  case (!$database->cadorder->meta->rows):
			return "D11";
		  case ($database->cadorder->data->status != "Order"):
			return "D12";
		}
        $this->cadorder_update=TRUE;
		$database->cadorder->data->status="Cancel";
		return "D00";
    }
    function poll() {
    global $database;
	    foreach ($database->cadorder->constant->housekeeping as $key => $value) {
	        if (!$database->registry->read_id("cadcart","housekeeping_next",$key,TRUE)) continue;
			if ( (strtotime($database->registry->cadcart->housekeeping_next[$key])) > strtotime('now') ) continue;
            if (in_array($key,array("zip"))) {
            	$unit="days";
			  } else {
              	$unit="minutes";
			}
	        $database->registry->update_id(date("m/d/Y g:i:s A",strtotime("+" . $database->registry->cadcart->housekeeping_poll[$key] . " $unit")) );
			$this->$key();
	        if (strtotime("now") > $this->endtime) break;
	    }
    }
    function housekeeping() {
		$this->cancel();
        $this->expired();
        $this->purge();
        $this->file();
        $this->log();
        $this->zip();
    }
    function cancel() {
	global $database;
		$starttime=microtime(TRUE);
    	$query_where=array();
        $query_where[]=new query_where("status","=","order");
        $query_where[]=new query_where("initial_date","<=",strtotime("-5 minutes"));
        $query =
        	"select id as 'id' from cadorder \n" .
			$database->where($query_where) .
            "order by id";
		$database->temp->query($query,TRUE);
		while ($database->temp->fetch=$database->temp->fetch_array() ) {
			$this->results['cancel'][]=$database->temp->fetch['id'];
			$database->cadorder->read($database->temp->fetch['id']);
            $database->cadorder->data->status="Cancel";
            $database->cadorder->data->status_code="H11";
            $database->cadorder->data->status_date=strtotime("now");
			$database->cadorder->update(TRUE);
        }
		$database->temp->free_result();
        $this->log_poll('cancel',$starttime);
    }
    function expired() {
	global $database;
		$starttime=microtime(TRUE);
    	$query_where=array();
        $query_where[]=new query_where("status","in",array('Pending','File','Retrieve'));
        $query_where[]=new query_where("firm","=",$_SESSION['company']->firm);
        $query_where[]=new query_where("initial_date","<=",strtotime("-" . $database->registry->cadcart->expire_days . " days"));
        $query =
        	"select id as 'id' from cadorder \n" .
			$database->where($query_where) .
            "order by id";
		$database->temp->query($query);
		while ($database->temp->fetch=$database->temp->fetch_array() ) {
			$this->results['expired'][]=$database->temp->fetch['id'];
			$database->cadorder->read($database->temp->fetch['id']);
            $database->cadorder->data->status="Expired";
            $database->cadorder->data->status_code="E00";
            $database->cadorder->data->status_date=strtotime("now");
			$database->cadorder->update(TRUE);
            $database->cadorder->unlink();
        }
		$database->temp->free_result();
        $this->log_poll('expired',$starttime);
    }
    function purge() {
	global $database;
		$starttime=microtime(TRUE);
    	$query_where=array();
        $query_where[]=new query_where("status","in",array("cancel","error"));
        $query_where[]=new query_where("status_date","<=",strtotime("-" . $database->registry->cadcart->purge_days . " days"));
        $query =
        	"delete from cadorder \n" .
			$database->where($query_where) .
            "order by id";
		$database->temp->query($query);
        $this->log_poll('purge',$starttime);
    }
    function log() {
    global $database;
		$starttime=microtime(TRUE);
        $query_where=array();
        $query_where['type']=new query_where("type","=","CAD Cart");
		$query_where['date_time']=new query_where("date_time","<=",strtotime('-5 days'));
        $query_where['subtype']=array();
        $query_where['subtype'][]=new query_where("subtype","like","Poll%");
        $query_where['subtype'][]=new query_where("subtype","=","Partserver","or");
        $query=
        	"delete from log \n" .
            $database->where($query_where);
		$database->temp->query($query);
		$this->log_poll('log',$starttime);
    }
    function file() {
    global $database;
		$starttime=microtime(TRUE);
    	$query_where=array();
        $query_where[]=new query_where("status","=",'Pending');
	    $query_where[]=new query_where("status_date","<=",strtotime("-" . $database->registry->cadcart->partserver_delay . " minutes"));
	    if ($database->registry->cadcart->poll_limit) {
        	$query_limit="limit 0," . $database->registry->cadcart->poll_limit;
		  } else {
			$query_limit="";
        }
        $query =
        	"select id as 'id' from cadorder \n" .
			$database->where($query_where) .
            "order by status_date \n" .
            $query_limit;
		$database->temp->query($query);
		while ($database->temp->fetch = $database->temp->fetch_array() ) {
			$this->results['file'][]=$database->temp->fetch['id'];
			$database->cadorder->read($database->temp->fetch['id']);
            $database->cadorder->data->status_date=strtotime("now");
            $database->cadorder->data->status_code=$this->file_retrieve();
            switch ($database->cadorder->data->status_code) {
			  case "C00":
				$database->cadorder->data->status="File";
                break;
			  case "C01":
				$database->cadorder->data->status="Pending";
                break;
               default:
				$database->cadorder->data->status="Error";
			}
			$database->cadorder->update(TRUE);
        }
		$database->temp->free_result();
        $this->log_poll('file',$starttime);
	}
    function file_retrieve($file="") {
    global $database;
		$database->cadorder->reset(FALSE);
        if ($file) $database->cadorder->data->response_url=$file;
		$database->cadorder->reset(FALSE);
        if ($file) $database->cadorder->data->response_url=$file;
        libxml_use_internal_errors(TRUE);
        if (!$xml=simplexml_load_file($database->cadorder->data->response_url) ) {
			libxml_clear_errors();
        	return "C01";
		}

		$first_pass=TRUE;
	    foreach ($xml->children() as $element => $element_data) {
			switch ($element) {
              case "ORDERNO":
				$database->cadorder->data->ps_order=$element_data->__tostring();
				break;
			  case "MESSAGE":
				$database->cadorder->data->ps_message[]=$element_data->__tostring();
            	break;
              case "SECTION":
				foreach ($element_data as $section => $section_data) {
                	switch ($section) {
					  case "ZIPFILE":
						$database->cadorder->data->ps_zipfile=$section_data->__tostring();
                        break;
					  case "CONTENTS":
						foreach ($section_data as $part => $part_data) {
							if ($first_pass) {
                            	$first_pass=FALSE;
                                foreach ($part_data as $key => $value) {
                                	switch ($key) {
                                      case "NB":
                                    	$database->cadorder->data->sku=$value->__tostring();
                                    	break;
                                      case "NN":
                                    	$database->cadorder->data->sku_name=$value->__tostring();
                                    	break;
									}
                                }
                            }
							$database->cadorder->data->ps_contents[]=new cadorder_contents_class($part_data);
                        }
                        break;
					}
				}
				break;
            }
	    }
		if (!$database->cadorder->data->ps_zipfile) return "C11";
		$response=end(preg_split("/\//",$database->cadorder->data->response_url));
		$ps_zipfile=preg_replace("/$response/",$database->cadorder->data->ps_zipfile,$database->cadorder->data->response_url);
		if (!$contents=file_get_contents($ps_zipfile)) return "C12";
        $fh=fopen($database->cadorder->file_url(),"w");
        fwrite($fh,$contents);
        fclose($fh);
		return "C00";
    }
    function zip() {
	global $database;
		$starttime=microtime(TRUE);
        $prefix=$_SERVER['DOCUMENT_ROOT'] . $database->registry->cadcart->folder . "cad";
        $matches=array();
        $matches[]="/(" . preg_quote($prefix,"/") . ")/";
		$matches[]="/(" . preg_quote(".zip","/") . ")/";
		$files=glob($prefix . "*.zip");
        if (!sizeof($files)) return;
        foreach ($files as $file) {
	        $timestamp=hexdec(preg_replace($matches,"",$file));
			if ($timestamp > strtotime("-2 days")) break;
            $this->results[]=$file;
			unlink($file);
        }
	}
    function log_ps() {
    global $database;
		$results=array();
	    $results[]="Script: " . $_SERVER['PHP_SELF'] . " Revised: " . key( $this->scs_table_version() );
	    $results[]="Request method: " . $_SERVER['REQUEST_METHOD'];
	    $results[]="IP Address: " . $_SERVER['REMOTE_ADDR'];
	    foreach ($this->request as $key => $value) {
	        $results[]="$key: $value";
	    }
		$database->log->update("CAD Cart:Partserver",$results,0);
    }
    function log_poll($key,$starttime,$query="") {
    global $database;
		if (!isset($database->registry->cadcart->log_poll) ) return;
		$stoptime=microtime(TRUE);
		$results=array();
        if ($query) $results[]=$query;
        $results[]="Elapsed time: " . number_format(($stoptime - $starttime),4) . " seconds";
        $results[]="Orders processed: "  . $database->temp->meta->rows;
        if ($database->temp->meta->found_rows != $database->temp->meta->rows) $results[]="Orders remaining: "  . ($database->temp->meta->found_rows - $database->temp->meta->rows);
        $results[]="Next scheduled poll: " . $database->registry->cadcart->housekeeping_next[$key];
		$database->log->update("CAD Cart:Poll $key",$results,0);
    }
    function status($code="") {
    global $database;
		$pattern=array();
        $pattern[]="/%STATUS_CODE%/";
        $pattern[]="/%EMAIL%/";
        $pattern[]="/%SKU%/";
        $pattern[]="/%CAD_FORMAT%/";
        $pattern[]="/%SCS_ACTION%/";
        $pattern[]="/%ORDER_ID%/";
        $pattern[]="/%RESPONSE_URL%/";
        $pattern[]="/%USER_STATUS%/";
        $pattern[]="/%ORDER_STATUS%/";
        $pattern[]="/%PENDING_LIMIT%/";
        $pattern[]="/%CART_EXPIRE_DAYS%/";
		$replacement=array();
        $replacement[]=$code;
        $replacement[]=$this->email;
        $replacement[]=$this->sku;
        $replacement[]=$this->cad_code;
        $replacement[]=$this->action;
        $replacement[]=$this->order_id;
        $replacement[]=$this->response_url;
        $replacement[]=$database->user->data->status;
        $replacement[]=$database->cadorder->data->status;
        $replacement[]=$database->registry->cadcart->pending_limit;
        $replacement[]=$database->registry->cadcart->expire_days;
        switch (TRUE) {
          case (!$code):
          	$this->status_code="Z01";
            break;
          case (!array_key_exists($code,$database->cadorder->constant->message)):
          	$this->status_code="Z02";
            break;
          default:
          	$this->status_code=$code;
		}
	    $this->status_internal=preg_replace($pattern,$replacement,$database->cadorder->constant->message[$this->status_code]->internal);
	    $this->status_external=preg_replace($pattern,$replacement,$database->cadorder->constant->message[$this->status_code]->external);
    }
}
?>