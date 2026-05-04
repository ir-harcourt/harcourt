<?php
require_once "braintree-php-3.8.0/lib/Braintree.php";
if (isset($database->registry->payments->braintree)) {
	Braintree\Configuration::environment($database->registry->payments->braintree['environment']);
	Braintree\Configuration::merchantId($database->registry->payments->braintree['merchant_id']);
	Braintree\Configuration::publicKey($database->registry->payments->braintree['public_key']);
	Braintree\Configuration::privateKey($database->registry->payments->braintree['private_key']);
}
/*
Credit Card #: 5105105105105100
Expiry: 05/2015
CVV: 123
https://developers.braintreepayments.com/reference/general/testing/php#test-amounts
*/
class braintree_class {
	var $available=FALSE;
	var $error;
    function __construct() {
    global $database;
		if (isset($database->registry->payments->braintree)) $this->available=TRUE;
	}
    function user_update($data) {
		if (!$this->customer_update($data)) return;
		if (!$this->address_update($data)) return;
        return TRUE;
    }
    function customer_find($id) {
        if (!$this->available) {
        	die("Braintree not implemented");
		}
		$this->error="";
	    try {
	    	$results=Braintree_Customer::find($id);
	      } catch (Exception $e) {
	        $this->error=$e->getMessage();
			return FALSE;
	    }
        $this->customer=$results;
        return TRUE;
    }
    function customer_update($data) {
    	$results=array();
		$results['id']=$data->id;
		$results['email']=$data->email;
		$results['firstName']=$data->name_first;
		$results['lastName']=$data->name_last;
		$results['company']=$data->company_name;
		$results['phone']=$data->phone;
		$this->error="";
        if ($this->customer_find($data->id)) {
	        try {
	            Braintree_Customer::update($data->id,$results);
	          } catch (Exception $e) {
	            $this->error=$e->getMessage();
	            return;
	        }
          } else {
	        try {
	            Braintree_Customer::create($results);
	          } catch (Exception $e) {
	            $this->error=$e->getMessage();
	            return;
	        }
        }
        return TRUE;
    }
    function address_update($data) {
    	switch (TRUE) {
          case (isset($this->customer)):
          	break;
          case (!$this->customer_find($data->id)):
          	return FALSE;
		}
	    $results=array();
	    $address=preg_split("/\r\n/",$data->address);
	    $results['customerId']=$data->id;
	    $results['firstName']=$data->name_first;
	    $results['lastName']=$data->name_last;
	    if ($data->company_name) $results['company']=$data->company_name;
	    if (strlen($address[0])) $results['streetAddress']=$address[0];
	    if (strlen($address[1])) $results['extendedAddress']=$address[1];
	    $results['locality']=$data->city;
	    $results['region']=$data->state;
	    $results['postalCode']=$data->zip;
	    $results['countryCodeAlpha2']=$data->country_code;
		$addresses=$this->customer->__get('addresses');
        if (sizeof($addresses)) {
        	unset($results['customerId']);
	        try {
	            Braintree_Address::update($data->id,$addresses[0]->__get('id'),$results);
	          } catch (Exception $e) {
	            $this->error=$e->getMessage();
	            return;
	        }
          } else {
	        try {
	            Braintree_Address::create($results);
	          } catch (Exception $e) {
	            $this->error=$e->getMessage();
	            return;
	        }
        }
    	return TRUE;
	}
    function token($data,$options=array()) {
		if (!$this->customer_find($data->id)) $this->user_update($data);
        $results=array();
		$results['customerId']=$data->id;
	    try {
			$token=Braintree_ClientToken::generate($results);
	      } catch (Exception $e) {
	        $this->error=$e->getMessage();
	      	return;
        }
    	return $token;
    }
    function checkout($data) {
    global $database, $forms, $menu;
    	$results=array();
		$results[]="<div id='braintree_div'></div>";
		$results[]="<script src='https://js.braintreegateway.com/v2/braintree.js'></script>";
		$results[]="<script>";
		$results[]="braintree.setup(" . fn_escape($menu->payment->token($data)) . ", 'dropin', { container: 'braintree_div', form: 'scs_form' });";
		$results[]="</script>";
        return implode("\n",$results);
    }
    function sale($data) {
		if (!$data->payment_details->nounce) return;
    	$options=array();
        $options['amount']=$data->total_amount;
        $options['orderId']=$data->id;
        if (strlen($data->po)) $options['purchaseOrderNumber']=$data->po;
        $options['paymentMethodNonce']=$data->payment_details->nounce;
        $options['options']=array();
        $options['options']['submitForSettlement']=TRUE;
	    try {
			$results=Braintree_Transaction::sale($options);
	      } catch (Exception $e) {
//	        $this->error=$e->getMessage();
	      	return;
        }
        if ($results->success) return $results->transaction->__get('id');
    }
    function payment_details($nounce) {
    	$results=new stdClass();
    	$results->nounce=$nounce;
        if (!strlen($nounce)) {
        	$results->error="Payment method not selected";
		  } else {
	        try {
	            $payment_method=Braintree_PaymentMethodNonce::find($nounce);
	          } catch (Exception $e) {
	            $results->error=$e->getMessage();
	        }
		}
        if (!isset($results->error)) {
	        $results->type=$payment_method->__get('type');
	        $results->details=$payment_method->__get('details');
	        $account=array();
	        switch (TRUE) {
	          case (array_key_exists("cardType",$results->details)):
	            $account[]=$results->details['cardType'];
	            switch (TRUE) {
	              case ( (array_key_exists("lastTwo",$results->details)) && (strlen($results->details['lastTwo'])) ):
	                $account[]=".. " . $results->details['lastTwo'];
	                break;
	              case ( (array_key_exists("dpanLastTwo",$results->details)) && (strlen($results->details['dpanLastTwo'])) ):
	                $account[]=".. " . $results->details['dpanLastTwo'];
	                break;
	            }
	            break;
	          case (array_key_exists("email",$results->details)):
	            $account[]=$results->details['email'];
	            break;
	        }
	        $results->account=implode(" ",$account);
	        switch (TRUE) {
	          case (array_key_exists("cardholderName",$results->details)):
	            $results->name=$this->details['cardholderName'];
	            break;
	          case (array_key_exists("email",$results->details)):
	            $results->name=$this->details['email'];
	            break;
	        }
        }
        return $results;
	}
}
?>