<?php
// https://developer.authorize.net/api/reference/index.html
require_once "classes/authorizenet.php";
class authorizenet_toolbox_class {
	function scs_table_version() {
		$results=array();
        $results['03/08/2021']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($access, $local=TRUE) {
    global $database, $menu, $forms;
		if ($local) $database->user->access($access);
    	$this->input=new stdClass();
        $this->input->action=$_POST['action'];
        $this->input->environment=$_POST['environment'];
        if (!$this->input->action) {
          	$this->input->environment=$database->registry->authorizenet->environment;
            $this->input->charge_description=$database->registry->authorizenet->description;
		  } else {
          	$this->input->settled_from=fn_date($_POST['settled_from'],"mdy");
          	$this->input->settled_thru=fn_date($_POST['settled_thru'],"mdy");

            $this->input->charge_account=intval($_POST['charge_account']);
            $this->input->charge_expiry_mm=intval($_POST['charge_expiry_mm']);
            $this->input->charge_expiry_yyyy=intval($_POST['charge_expiry_yyyy']);
            $this->input->charge_cvv=intval($_POST['charge_cvv']);
            $this->input->charge_customer=fn_text($_POST['charge_customer']);
            $this->input->charge_email=fn_text($_POST['charge_email']);
            $this->input->charge_business=fn_text($_POST['charge_business']);
            $this->input->charge_company_name=fn_text($_POST['charge_company_name']);
            $this->input->charge_first_name=fn_text($_POST['charge_first_name']);
            $this->input->charge_last_name=fn_text($_POST['charge_last_name']);
            $this->input->charge_address=fn_text($_POST['charge_address']);
            $this->input->charge_city=fn_text($_POST['charge_city']);
            $this->input->charge_state=fn_text($_POST['charge_state'],TRUE);
            $this->input->charge_zip=fn_text($_POST['charge_zip'],TRUE);
            $this->input->charge_country=fn_text($_POST['charge_country'],TRUE);
            $this->input->charge_phone=fn_text($_POST['charge_phone'],TRUE);
            $this->input->charge_fax=fn_text($_POST['charge_fax'],TRUE);
            $this->input->charge_amount=fn_number($_POST['charge_amount'],2);
            $this->input->charge_invoice=fn_text($_POST['charge_invoice'],TRUE);
            $this->input->charge_description=fn_text($_POST['charge_description']);
            $this->input->charge_po=fn_text($_POST['charge_po'],TRUE);
            $this->input->charge_order=fn_text($_POST['charge_order'],TRUE);

            $this->input->sandbox_account=fn_text($_POST['sandbox_account']);
            $this->input->sandbox_solution=fn_text($_POST['sandbox_solution']);
            $this->input->sandbox_zip=fn_text($_POST['sandbox_zip']);
            $this->input->sandbox_cvv=fn_text($_POST['sandbox_cvv']);

            $this->input->transaction_id=fn_text($_POST['transaction_id'],TRUE);

            $this->input->batch_id=fn_text($_POST['batch_id'],TRUE);
        }
		$this->authorizenet=new authorizenet_class(array("environment"=>$this->input->environment));
        switch ($this->input->action) {
          case "transaction":
          	if (!strlen($this->input->transaction_id)) $forms->error("transaction_id","Cannot be blank");
            if (sizeof($forms->error)) break;
			$this->authorizenet->transaction_detail($this->input->transaction_id);
            break;
          case "batch":
          	if (!strlen($this->input->batch_id)) $forms->error("batch_id","Cannot be blank");
            if (sizeof($forms->error)) break;
			$this->authorizenet->batch_list($this->input->batch_id);
            break;
          case "settled":
          	if (!$this->input->settled_from) $forms->error("settled_from","Cannot be blank");
            switch (TRUE) {
              case (!$this->input->settled_thru):
              	$forms->error("settled_thru","Cannot be blank");
                break;
              case ($this->input->settled_from > $this->input->settled_thru):
              	$forms->error("settled_thru","From date > thru date");
                break;
			}
            if (sizeof($forms->error)) break;
			$this->authorizenet->settled($this->input->settled_from, $this->input->settled_thru);
            break;
          case "unsettled":
			$this->authorizenet->unsettled();
            break;
          case "charge":
          	switch (TRUE) {
              case (!strlen($this->input->charge_account)):
              	$forms->error('charge_account',"Cannot be blank");
                break;
              case ( ($this->input->environment == "sandbox") && (!isset($this->authorizenet->constant->sandbox_account[$this->input->charge_account])) ):
              	$forms->error('charge_account',"Not a sandbox account");
                break;
              case ( ($this->input->environment != "sandbox") && (isset($this->authorizenet->constant->sandbox_account[$this->input->charge_account])) ):
              	$forms->error('charge_account',"Sandbox account");
                break;
            }
            switch (TRUE) {
              case (!$this->input->charge_expiry_mm):
              	$forms->error('charge_expiry_mm',"Month not set");
                break;
              case ( ($this->input->charge_expiry_yyyy == date("Y")) && ($this->input->charge_expiry_mm < date("m")) ):
              	$forms->error('charge_expiry_mm',"Expired card");
                break;
			}
            switch (TRUE) {
              case (!$this->input->charge_expiry_yyyy):
              	$forms->error('charge_expiry_yyyy',"Year not set");
                break;
              case ($this->input->charge_expiry_yyyy < date("Y")):
              	$forms->error('charge_expiry_yyyy',"Expired card");
                break;
			}
            switch (TRUE) {
              case (strlen($this->input->charge_cvv) < 3):
              	$forms->error('charge_cvv',"Must be 3-4 digits");
                break;
              case ( ($this->input->environment == "sandbox") && (!isset($this->authorizenet->constant->sandbox_cvv[$this->input->charge_cvv])) ):
              	$forms->error('charge_cvv',"Not a sandbox CVV");
                break;
            }
            $email=new email_class($_POST['charge_email'],FALSE);
            $this->input->charge_email=$email->address;
            if ($email->error) $forms->error('charge_email',$email->error);
            switch (TRUE) {
              case ( ($this->input->charge_business) && (!strlen($this->input->charge_company_name)) ):
              	$forms->error('charge_company_name',"Cannot be blank");
                break;
              case ( (!$this->input->charge_business) && (!strlen($this->input->charge_last_name)) ):
              	$forms->error('charge_last_name',"Cannot be blank");
                break;
            }
          	switch (TRUE) {
              case (!strlen($this->input->charge_zip)):
              	$forms->error('charge_zip',"Cannot be blank");
                break;
              case ( ($this->input->environment == "sandbox") && (!isset($this->authorizenet->constant->sandbox_zip[$this->input->charge_zip])) ):
              	$forms->error('charge_zip',"Not a sandbox ZIP");
                break;
			}
            if ($this->input->charge_amount <= 0) $forms->error('charge_amount',"Must be > $0.00");
            if (!strlen($this->input->charge_invoice)) $forms->error('charge_invoice',"Cannot be blank");
            if (sizeof($forms->error)) break;
	        $this->authorizenet->customer(
            	$this->input->charge_customer,
                $this->input->charge_email,
                $this->input->charge_business
            );
	        $this->authorizenet->billto(
            	$this->input->charge_company_name,
                $this->input->charge_first_name,
                $this->input->charge_last_name,
                $this->input->charge_address,
                $this->input->charge_city,
                $this->input->charge_state,
                $this->input->charge_zip,
                $this->input->charge_country,
                $this->input->charge_phone,
                $this->input->charge_fax
            );
	        $this->authorizenet->order(
            	$this->input->charge_invoice,
                $this->input->charge_order,
                $this->input->charge_description
            );
	        $this->authorizenet->card(
            	$this->input->charge_account,
                $this->input->charge_expiry_mm,
                $this->input->charge_expiry_yyyy,
                $this->input->charge_cvv
            );

			$this->authorizenet->charge(
            	$this->input->charge_amount,
            	$this->input->charge_po
            );
			break;
        }
	    $forms->title("Authorize.net Toolbox");
        $forms->message[]="Version: " . key($this->scs_table_version());
	    $menu->head();
	    $forms->message();
        print "<script>";
        print "function fn_action(action) {";
        print "jQuery('#action').val(action);";
        print "jQuery('#scs_form').submit();";
        print "}";
        print "function fn_charge(field, value) {";
        print "jQuery('#charge_' + field).val(value);";
        print "}";
        print "function fn_batch_id(batch_id) {";
        print "jQuery('#batch_id').val(batch_id);";
        print "jQuery('#action').val('batch');";
        print "jQuery('#scs_form').submit();";
        print "}";
        print "function fn_transaction_id(transaction_id) {";
        print "jQuery('#transaction_id').val(transaction_id);";
        print "jQuery('#action').val('transaction');";
        print "jQuery('#scs_form').submit();";
        print "}";
        print "</script>";
	    print $forms->open();
        print $forms->hidden("action");
        $text=array($forms->select("environment",array("sandbox"=>"Sandbox","production"=>"Production"),$this->input->environment,FALSE));
        if ($forms->error_text['environment']) $text[]=$forms->error_text['environment'];
        print "<div>Environment: " . implode(" ",$text) . "</div>";
        if ($this->authorizenet->results->fatal_error) print "<p>Fatal error: <b>" . $this->authorizenet->results->fatal_error . "</b> in function <b>" . $this->authorizenet->results->fatal_source . "</b></p>";
	    $this->tabs=new jquery_tab_class();
	    $this->tabs->item("Merchant Details",$this->merchant());
	    $this->tabs->item("Charge",$this->charge());
	    $this->tabs->item("Sandbox",$this->sandbox());
	    $this->tabs->item("Settled",$this->settled());
	    $this->tabs->item("Unsettled",$this->unsettled());
	    $this->tabs->item("Batch List",$this->batch());
	    $this->tabs->item("Transaction",$this->transaction());
	    $this->tabs->item("Constants",$this->constants());
	    $this->tabs->output();
	    print $forms->close();
	    $menu->copyright();
    }
    function batch() {
    global $database, $menu, $forms;
		if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();
    	$results=array();
        $results[]="<table class='noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Batch ID</td>";
        $text=array($forms->text("batch_id",$this->input->batch_id,20,20));
        if ($forms->error_text['batch_id']) $text[]=$forms->error_text['batch_id'];
        $results[]="<td width='80%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        switch (TRUE) {
          case ($this->input->action != __FUNCTION__):
          case (sizeof($forms->error)):
          case ($this->authorizenet->results->fatal_error):
          	break;
          default:
            $results[]="<table class='standard border tablesorter'>";
            $results[]="<thead>";
            $results[]="<tr>";
            $results[]="<th>Transaction ID</th>";
            $results[]="<th>Timestamp</th>";
            $results[]="<th>Status</th>";
            $results[]="<th>Invoice#</th>";
            $results[]="<th>Name</th>";
            $results[]="<th>Card</th>";
            $results[]="<th>Settled Amount</th>";
            $results[]="</tr>";
            $results[]="</thead>";
            $results[]="<tbody>";
            $amount=0;
            foreach ($this->authorizenet->response->getTransactions() as $obj) {
            	$amount += $obj->getSettleAmount();
            	$transaction_id=$obj->getTransId();
	            $results[]="<tr>";
                $results[]="<td>" . fn_href($transaction_id,"javascript:fn_transaction_id('{$transaction_id}');") . "</td>";
                $results[]="<td class=center>" . $obj->getSubmitTimeLocal()->format("m/d/Y H:i") . "</td>";
                $results[]="<td>" . $obj->getTransactionStatus() . "</td>";
                $results[]="<td>" . $obj->getInvoiceNumber() . "</td>";
                $results[]="<td>" . $obj->getFirstName() . " " . $obj->getLastName() . "</td>";
                $results[]="<td>" . $obj->getAccountType() . " " . $obj->getAccountNumber() . "</td>";
                $results[]="<td class=right>" . number_format($obj->getSettleAmount(),2) . "</td>";
	            $results[]="</tr>";
            }
            $results[]="</tbody>";
            $results[]="<tfoot>";
            if ($this->authorizenet->response->getTotalNumInResultSet() > 1) {
	            $results[]="<tr>";
                $results[]="<td colspan=6><b>" . number_format($this->authorizenet->response->getTotalNumInResultSet()) . " Transactions</b></td>";
                $results[]="<td class=right><b>" . number_format($amount,2) . "</b></td>";
	            $results[]="</tr>";
            }
            $results[]="</tfoot>";
            $results[]="</table>";
		}
        return $results;
    }
    function transaction() {
    global $database, $menu, $forms;
		if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();
    	$results=array();
        $results[]="<table class='noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Transaction ID</td>";
        $text=array($forms->text("transaction_id",$this->input->transaction_id,20,20));
        if ($forms->error_text['transaction_id']) $text[]=$forms->error_text['transaction_id'];
        $results[]="<td width='80%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        switch (TRUE) {
          case ($this->input->action != __FUNCTION__):
          case (sizeof($forms->error)):
          case ($this->authorizenet->results->fatal_error):
          	break;
          default:
          	$transaction=$this->authorizenet->response->getTransaction();
            $items=array();
            foreach ($this->authorizenet->constant->transaction as $name => $field) {
            	$obj=$transaction->$field();
                switch (TRUE) {
                  case (is_object($obj)):
                    switch (get_class($obj)) {
                      case "DateTime":
                      	$items[$name]=$obj->format("m/d/Y H:i");
                      	break;
                      case "net\\authorize\\api\\contract\\v1\\orderex":
						$items[$name]=$this->authorizenet->constant->orderex($obj);
                      	break;
                      case "net\\authorize\\api\\contract\\v1\\PaymentMaskedType":
						$items[$name]=$this->authorizenet->constant->creditcardmasked($obj->getCreditCard());
                      	break;
                      case "net\\authorize\\api\\contract\\v1\\CustomerDataType";
						$items[$name]=$this->authorizenet->constant->customerdata($obj);
                      	break;
                      case "net\\authorize\\api\\contract\\v1\\CustomerAddressType";
						$items[$name]=$this->authorizenet->constant->customeraddress($obj);
                      	break;
                    }
                  	break;
                  case (method_exists($this->authorizenet->constant,$field)):
                	$items[$name]=$this->authorizenet->constant->$field($obj);
                  	break;
                  default:
                	$items[$name]=$obj;
				}
            }
	        $results[]="<table class='border'>";
	        $results[]="<tr>";
	        $results[]="<th width='20%'>Field</th>";
	        $results[]="<th width='80%'>Value</th>";
	        $results[]="<tr>";
			foreach ($items as $name => $value) {
            	switch (TRUE) {
                  case (is_object($value)):
                  	break;
                  case ( (is_string($value)) && (strlen($value)) ):
	                $results[]="<tr>";
	                $results[]="<td>{$name}</td>";
	                $results[]="<td>{$value}</td>";
	                $results[]="</tr>";
                    break;
                  case ( (is_array($value)) && (sizeof($value)) ):
                    foreach ($value as $name2 => $value2) {
                    	if (!is_string($value2)) continue;
	                    $results[]="<tr>";
	                    $results[]="<td>{$name}: {$name2}</td>";
	                    $results[]="<td>{$value2}</td>";
	                    $results[]="</tr>";
                    }
				}
            }
        	$results[]="</table>";
            break;
		}
        return $results;
    }
    function sandbox() {
    global $database, $menu, $forms;
		if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();
    	$results=array();
        $results[]="<table class='border'>";
        $results[]="<tr><td colspan=2><b>Card Number</b></td></tr>";
        $items=array();
        $items['Production']=array("");
        foreach ($this->authorizenet->constant->sandbox_account as $account => $card) {
        	if (!isset($items[$card])) $items[$card]=array();
            $items[$card][]=$account;
        }
        foreach ($items as $card => $accounts) {
	        $results[]="<tr>";
	        $results[]="<td width='20%'>{$card}</td>";
	        $text=array();
	        foreach ($accounts as $account) {
	            $text[]=$forms->radio("sandbox_account",$account,$this->input->sandbox_account,array("onclick"=>"fn_charge('account'," . fn_escape($account) . ");")) . " {$account}";
	        }
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
        $results[]="<tr><td colspan=2><b>Solution ID</b></td></tr>";
        $items=array();
        $items['Production']=array("");
        $items['Sandbox']=$this->authorizenet->constant->sandbox_solution;
        foreach ($items as $name => $codes) {
	        $results[]="<tr>";
	        $results[]="<td>{$name}</td>";
	        $text=array();
	        foreach ($codes as $code) {
	            $text[]=$forms->radio("sandbox_solution",$code,$this->input->sandbox_solution,array("onclick"=>"fn_charge('solution'," . fn_escape($code) . ");")) . " {$code}";
	        }
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
        $results[]="<tr><td colspan=2><b>Zip Codes</b></td></tr>";
        $items=array();
        $items['Production']=array(""=>"");
        $items['Sandbox']=$this->authorizenet->constant->sandbox_zip;
        foreach ($items as $name => $codes) {
	        $results[]="<tr>";
	        $results[]="<td>{$name}</td>";
	        $text=array();
	        foreach ($codes as $zip => $code) {
            	$avs_response=( (isset($this->authorizenet->constant->avs_response[$code])) ? $this->authorizenet->constant->avs_response[$code] : $code);
	            $text[]=$forms->radio("sandbox_zip",$zip,$this->input->sandbox_zip,array("onclick"=>"fn_charge('zip'," . fn_escape($zip) . ");")) . " {$zip}" . (($avs_response) ? " ({$avs_response})" : "");
	        }
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
        $results[]="<tr><td colspan=2><b>CVV</b></td></tr>";
        $items=array();
        $items['Production']=array(""=>"");
        $items['Sandbox']=$this->authorizenet->constant->sandbox_cvv;
        foreach ($items as $name => $codes) {
	        $results[]="<tr>";
	        $results[]="<td>{$name}</td>";
	        $text=array();
	        foreach ($codes as $cvv => $code) {
	            $text[]=$forms->radio("sandbox_cvv",$cvv,$this->input->sandbox_cvv,array("onclick"=>"fn_charge('cvv'," . fn_escape($cvv) . ");")) . " {$cvv}" . ( ($code) ? " (" . $this->authorizenet->constant->cvv_response[$code] . ")" : "");
	        }
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        return $results;
    }
    function charge() {
    global $database, $menu, $forms;
    	$results=array();
		if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();

		if ( ($this->input->action == __FUNCTION__) && (!sizeof($forms->error)) ) {
        	$text=array();
            $text[]="API status: " . $this->authorizenet->results->api_result_code;
            if ($this->authorizenet->results->api_result_code != "Ok") {
            	$text[]="API error: " . $this->authorizenet->results->api_message_error;
            	$text[]="API code: " . $this->authorizenet->results->api_message_code;
			}
            if (strlen($this->authorizenet->results->transaction_id)) $text[]="Transaction ID: " . $this->authorizenet->results->transaction_id;
            if (strlen($this->authorizenet->results->auth_code)) $text[]="Auth Code: " . $this->authorizenet->results->auth_code;
            if (strlen($this->authorizenet->results->avs_result)) $text[]="AVS: " . $this->authorizenet->results->avs_result;
            if (strlen($this->authorizenet->results->cvv_result)) $text[]="CVV: " . $this->authorizenet->results->cvv_result;
            if (strlen($this->authorizenet->results->message_text)) $text[]="Message: " . $this->authorizenet->results->message_text;
            if ($this->authorizenet->results->message_code > 1) $text[]="Message Code: " . $this->authorizenet->results->message_code;
            $results[]="<p>Response<br>" . implode("<br>",$text) . "</p>";
        }
        $results[]="<table class='noborder'>";
        $results[]="<tr><td colspan=2><b>Card Information</b></td></tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Card #</td>";
        $text=array($forms->text("charge_account",$this->input->charge_account,20,20));
        if ($forms->error_text['charge_account']) $text[]=$forms->error_text['charge_account'];
        $results[]="<td width='80%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Expiry</td>";
        $items=array($forms->select("charge_expiry_mm",array(1,2,3,4,5,6,7,8,9,10,11,12),$this->input->charge_expiry_mm,"Month"));
        $charge_expiry_yyyy=array();
        for ($i=-1; $i < 10; $i++) {
        	$charge_expiry_yyyy[]=date("Y",strtotime("+{$i} years"));
        }
        $items[]="/";
        $items[]=$forms->select("charge_expiry_yyyy",$charge_expiry_yyyy,$this->input->charge_expiry_yyyy,"Year");
        $text=array(implode(" ",$items));
        if ($forms->error_text['charge_expiry_mm']) $text[]=$forms->error_text['charge_expiry_mm'];
        if ($forms->error_text['charge_expiry_yyyy']) $text[]=$forms->error_text['charge_expiry_yyyy'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>CVV</td>";
        $items=array($forms->text("charge_cvv",$this->input->charge_cvv,4,4));
        $text=array(implode(" ",$items));
        if ($forms->error_text['charge_cvv']) $text[]=$forms->error_text['charge_cvv'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr><td colspan=2><b>Customer Information</b></td></tr>";
        $results[]="<tr>";
        $results[]="<td>Customer#</td>";
        $text=array($forms->text("charge_customer",$this->input->charge_customer,20,20));
        if ($forms->error_text['charge_customer']) $text[]=$forms->error_text['charge_customer'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Customer Email</td>";
        $text=array($forms->text("charge_email",$this->input->charge_email,60,60));
        if ($forms->error_text['charge_email']) $text[]=$forms->error_text['charge_email'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Business?</td>";
        $text=array($forms->checkbox("charge_business",1,$this->input->charge_business));
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr><td colspan=2><b>Bill To Information</b></td></tr>";
        $results[]="<tr>";
        $results[]="<td>Company Name</td>";
        $results[]="<td>" . $forms->text("charge_company_name",$this->input->charge_company_name,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>First Name</td>";
        $results[]="<td>" . $forms->text("charge_first_name",$this->input->charge_first_name,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Last Name</td>";
        $results[]="<td>" . $forms->text("charge_last_name",$this->input->charge_last_name,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Address</td>";
        $results[]="<td>" . $forms->text("charge_address",$this->input->charge_address,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>City</td>";
        $results[]="<td>" . $forms->text("charge_city",$this->input->charge_city,60,60) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>State</td>";
        $results[]="<td>" . $forms->text("charge_state",$this->input->charge_state,40,40) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Zip</td>";
        $text=array($forms->text("charge_zip",$this->input->charge_zip,20,20));
        if ($forms->error_text['charge_zip']) $text[]=$forms->error_text['charge_zip'];
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Country</td>";
        $results[]="<td>" . $forms->text("charge_country",$this->input->charge_country,20,20) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Phone</td>";
        $results[]="<td>" . $forms->text("charge_phone",$this->input->charge_phone,20,20) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Fax</td>";
        $results[]="<td>" . $forms->text("charge_fax",$this->input->charge_fax,20,20) . "</td>";
        $results[]="</tr>";
        $results[]="<tr><td colspan=2><b>Sale Information</b></td></tr>";
        $results[]="<tr>";
        $results[]="<td>Sale Amount</td>";
        $text=array($forms->text("charge_amount",$this->input->charge_amount,12,12,"decimal:2"));
        if ($forms->error_text['charge_amount']) $text[]=$forms->error_text['charge_amount'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Invoice#</td>";
        $text=array($forms->text("charge_invoice",$this->input->charge_invoice,20,20));
        if ($forms->error_text['charge_invoice']) $text[]=$forms->error_text['charge_invoice'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Order#</td>";
        $results[]="<td>" . $forms->text("charge_order",$this->input->charge_order,20,20) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>PO#</td>";
        $results[]="<td>" . $forms->text("charge_po",$this->input->charge_po,40,40) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Description</td>";
        $results[]="<td>" . $forms->text("charge_description",$this->input->charge_description,40,40) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        return $results;
    }
    function merchant() {
    global $database, $menu, $forms;
    	$results=array();
        switch (TRUE) {
          case ($this->input->action == __FUNCTION__):
        	$this->tabs->landing_tab();
        	$this->authorizenet->merchant();
            if ($this->authorizenet->results->fatal_error) break;
            $items=array();
            $items['Merchant Name']=$this->authorizenet->response->getMerchantName();
	        $items['Gateway ID']=$this->authorizenet->response->getGatewayId();
            $processor=array();
            foreach ($this->authorizenet->response->getProcessors() as $obj) {
            	$processor[]=$obj->getName();
            }
            $items['Processors']=$processor;
	        $items['Market Types']=$this->authorizenet->response->getMarketTypes();
	        $items['Product Codes']=$this->authorizenet->response->getProductCodes();
	        $items['Payment Methods']=$this->authorizenet->response->getPaymentMethods();
	        $items['Currencies']=$this->authorizenet->response->getCurrencies();
            $items['Test Mode']=$this->authorizenet->response->getIsTestMode();
			foreach ($items as $key => $value) {
	            $text=array("<b>{$key}</b>");
	            $text[]=( (is_array($value)) ? implode(", ",$value) : $value);
	            if (strlen($text[1])) $results[]="<p>" . implode("<br>",$text) . "</p>";
            }
		}
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        return $results;
    }
    function settled() {
    global $database, $menu, $forms;
        if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();
    	$results=array();
        $results[]="<table class='noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>From Date</td>";
        $text=array($forms->text("settled_from",$this->input->settled_from,10,10,"date"));
        if ($forms->error_text['settled_from']) $text[]=$forms->error_text['settled_from'];
        $results[]="<td width='80%'>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Thru Date</td>";
        $text=array($forms->text("settled_thru",$this->input->settled_thru,10,10,"date"));
        if ($forms->error_text['settled_thru']) $text[]=$forms->error_text['settled_thru'];
        $results[]="<td width='80%'>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        switch (TRUE) {
          case ($this->input->action != __FUNCTION__):
          case (sizeof($forms->error)):
          case ($this->authorizenet->results->fatal_error):
          	break;
		  default:
          	$items=$this->authorizenet->response->getBatchList();
            if (is_null($items)) {
          		$results[]="<p>No settled transactions</p>";
                break;
            }
            $title=array();
	        $title['Charge Amount']='getChargeAmount';
	        $title['Charge Count']='getChargeCount';
	        $title['Decline Count']='getDeclineCount';
	        $title['Error Count']='getErrorCount';
            $total=array();
	        foreach ($title as $field) {
	        	$total[$field]=0;
	        }
			$results[]="<table class='standard border tablesorter'>";
			$results[]="<thead>";
			$results[]="<tr>";
            $results[]="<th>Batch ID</th>";
            $results[]="<th>Timestamp</th>";
            $results[]="<th>Status</th>";
            $results[]="<th>Type</th>";
            $results[]="<th>Product</th>";
			foreach ($title as $name => $field) {
            	$results[]="<th>{$name}</th>";
            }
			$results[]="</tr>";
			$results[]="<thead>";
			$results[]="<tbody>";
            foreach ($items as $item) {
	            $subtotal=array();
	            foreach ($title as $field) {
	                $subtotal[$field]=0;
	            }
            	$batch_id=$item->getBatchId();
				$obj=$item->getStatistics();
                foreach ($obj as $stats) {
	                foreach ($title as $field) {
	                    $subtotal[$field]+=$stats->$field();
	                    $total[$field]+=$stats->$field();
	                }
                }
				$results[]="<tr>";
                $results[]="<td>" . fn_href($batch_id,"javascript:fn_batch_id('{$batch_id}');") . "</td>";
                $results[]="<td class=center>" . $item->getSettlementTimeLocal()->format("m/d/Y H:i") . "</td>";
                $results[]="<td>" . $item->getSettlementState() . "</td>";
                $results[]="<td>" . $item->getMarketType() . "</td>";
                $results[]="<td>" . $item->getProduct() . "</td>";
	            foreach ($title as $field) {
                    $results[]="<td class=right><b>" . number_format($subtotal[$field],(preg_match("/amount$/i",$field) ? 2 : 0)) . "</b></td>";
	            }
				$results[]="</tr>";
            }
			$results[]="</tbody>";
			$results[]="<tfoot>";
            if (sizeof($items) > 1) {
				$results[]="<tr>";
                $results[]="<td colspan=5><b>" . number_format(sizeof($items)) . " Batch(es)</b></td>";
	            foreach ($title as $field) {
                    $results[]="<td class=right><b>" . number_format($total[$field],(preg_match("/amount$/i",$field) ? 2 : 0)) . "</b></td>";
	            }
				$results[]="</tr>";
            }
			$results[]="</tfoot>";
			$results[]="</table>";
		}
/*
Get Transaction Details
*/
        return $results;
    }
    function unsettled() {
    global $database, $menu, $forms;
        if ($this->input->action == __FUNCTION__) $this->tabs->landing_tab();
    	$results=array();
        $results[]="<p>" . $forms->button("Go!",array("onclick"=>"fn_action('" . __FUNCTION__ . "');")) . "</p>";
        switch (TRUE) {
          case ($this->input->action != __FUNCTION__):
          case (sizeof($forms->error)):
          case ($this->authorizenet->results->fatal_error):
          	break;
          case (!$this->authorizenet->response->getTotalNumInResultSet()):
          	$results[]="<p>No unsettled transactions</p>";
            break;
          default:
          	$results[]="<p>" . number_format($this->authorizenet->response->getTotalNumInResultSet()) . " unsettled transactions</p>";
            $results[]="<table class='standard border tablesorter'>";
            $results[]="<tr>";
            $results[]="<th>Transaction ID</th>";
            $results[]="<th>Timestamp</th>";
            $results[]="<th>Status</th>";
            $results[]="<th>Card</th>";
            $results[]="<th>Account</th>";
            $results[]="<th>Invoice#</th>";
            $results[]="<th>Amount</th>";
            $results[]="</tr>";
            $items=$this->authorizenet->response->getTransactions();
            foreach ($items as $item) {
            	$transaction_id=$item->getTransId();
	            $results[]="<tr>";
                $results[]="<td>" . fn_href($transaction_id,"javascript:fn_transaction_id('{$transaction_id}');") . "</td>";
//                $timestamp=$item->getSubmitTimeLocal();
                $results[]="<td class=center>" . $item->getSubmitTimeLocal()->format("m/d/Y H:i") . "</td>";
                $results[]="<td>" . $item->getTransactionStatus() . "</td>";
                $results[]="<td>" . $item->getAccountType() . "</td>";
                $results[]="<td>" . $item->getAccountNumber() . "</td>";
                $results[]="<td>" . $item->getInvoiceNumber() . "</td>";
                $results[]="<td class=right>" . number_format($item->getSettleAmount(),2) . "</td>";
	            $results[]="</tr>";
            }
          	break;
        }
        $results[]="</table>";
        return $results;
    }
    function constants() {
    global $database, $menu, $forms;
    	$results=array();
        $results[]="<table class='border'>";
        $results[]="<tr><td colspan=2><b>Address Verification Service (AVS)</b></td></tr>";
        foreach ($this->authorizenet->constant->avs_response as $code => $name) {
        	$results[]="<tr>";
            $results[]="<td width='20%' class=center>{$code}</td>";
            $results[]="<td width='80%'>{$name}</td>";
        	$results[]="</tr>";
        }
        $results[]="<tr><td colspan=2><b>CVV Response</b></td></tr>";
        foreach ($this->authorizenet->constant->cvv_response as $code => $name) {
        	$results[]="<tr>";
            $results[]="<td width='20%' class=center>{$code}</td>";
            $results[]="<td width='80%'>{$name}</td>";
        	$results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
}
?>