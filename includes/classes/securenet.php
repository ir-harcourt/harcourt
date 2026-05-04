<?php
class securenet_class {
	var $data;
    var $error;
    var $response;
    var $url;
    var $constant;
	function __construct($protocol) {
		set_time_limit(0);
    	$this->data=new securenet_data_class($protocol);
    	$this->constant=new securenet_constant_class();
    }
    function type_of_goods($data) {
		$this->data->type_of_goods=import_match($data,$this->constant->type_of_goods);
    }
    function authorize($amount) {
		$this->execute("Payments/Authorize",$this->data->load($amount));
    }
    function capture($amount) {
		$this->execute("Payments/Capture",$this->data->load($amount));
    }
    function charge($amount) {
		$this->execute("Payments/Charge",$this->data->load($amount));
    }
    function void($amount) {
		$this->execute("Payments/Void",$this->data->load($amount));
    }
    function refund($amount) {
		$this->execute("Payments/Refund",$this->data->load($amount));
    }
    function execute($action,$data) {
    global $database;
    	switch (TRUE) {
          case (!isset($database->registry->payments)):
          case (!array_key_exists("securenet",$database->registry->payments)):
          	$this->error="SecureNet not implemented";
            return FALSE;
		}
        $developer=array();
        $developer['developerId']=$database->registry->payments->securenet['developer_id'];
        $developer['version']=$database->registry->payments->securenet['version'];
        $data['developerApplication']=$developer;

		$this->url=$database->registry->payments->securenet[ $database->registry->payments->securenet['environment'] ] . $action;
        $json=json_encode($data);
        // headers to authenticate
        $headers=array();
        $headers[]="Authorization: Basic " . base64_encode($database->registry->payments->securenet['merchant_id'] . ':' . $database->registry->payments->securenet['password']);
        $headers[]="Content-Type: application/json";
        $headers[]="Content-Length: " . strlen($json);

        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($database->registry->payments->securenet['debug']) {
        	$fh=fopen("securenet.txt","w");
            fwrite($fh,date("m/d/Y h:i") . "\r\n");
	        curl_setopt($ch, CURLOPT_VERBOSE, true);
	        curl_setopt($ch, CURLOPT_STDERR, $fh);
		}
    	$this->response=new securenet_response_class($ch,curl_exec($ch));
        curl_close($ch);
        if ($database->registry->payments->securenet['debug']) {
        	fwrite($fh,"\r\nData: " . print_r($data,TRUE));
        	fwrite($fh,print_r($this->response,TRUE));
        	fclose($fh);
		}
    }
}
class securenet_data_class {
	var $results=array();
    var $protocol;

	var $name;
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country;
    var $phone;

    var $type_of_goods;
    var $po;
    var $customer;
    var $invoice_no=0;
    var $order_no=0;
    var $transaction_id;

/* credit cards */
    var $cc_account;
    var $cc_expiry=0;
    var $cc_cvv;

/* ach */
    var $ach_type;
	var $ach_routing;
    var $ach_account;
    var $ach_check;
    var $ach_source;
    var $ach_verification;

    function __construct($protocol) {
    	$this->protocol=$protocol;
    }
    function load($amount) {
		$this->results=array();
		$this->extended();

        $this->card();
        $this->ach();

		$results=array();
        if ($amount) $results['amount']=$amount;
        if ($this->transaction_id) $results['transactionId']=$this->transaction_id;
        if (strlen($this->customer)) $results['customerId']=$this->customer;
        if (sizeof($this->results['card'])) $results['card']=$this->results['card'];
        if (sizeof($this->results['ach'])) $results['check']=$this->results['ach'];
        if (sizeof($this->results['extended'])) $results['extendedInformation']=$this->results['extended'];
        return $results;
    }
    function card() {
		$this->results['card']=array();
		if (!in_array($this->protocol,array("ecommerce","moto"))) return;
        if (strlen($this->cc_account)) $this->results['card']['number']=$this->cc_account;
        if (strlen($this->cc_cvv)) $this->results['card']['cvv']=$this->cc_cvv;
        if (strlen($this->cc_expiry)) $this->results['card']['expirationDate']=date("m/y",strtotime($this->cc_expiry));
        if (strlen($this->name)) $this->results['card']['lastName']=$this->name;
		$this->address("card");
	}
    function ach() {
		$this->results['ach']=array();
		if (!in_array($this->protocol,array("ach"))) return;
        if (strlen($this->name)) $this->results['ach']['lastName']=$this->name;
        if (strlen($this->ach_type)) $this->results['ach']['accountType']=$this->ach_type;
        if (strlen($this->ach_source)) $this->results['ach']['checkType']=$this->ach_source;
        if (strlen($this->ach_routing)) $this->results['ach']['routingNumber']=$this->ach_routing;
        if (strlen($this->ach_account)) $this->results['ach']['accountNumber']=$this->ach_account;
        if (strlen($this->ach_check)) $this->results['ach']['checkNumber']=$this->ach_check;
        if (strlen($this->ach_verification)) $this->results['ach']['verification']=$this->ach_verification;
		$this->address("ach");
    }
    function extended() {
		$this->results['extended']=array();
        if ($this->invoice_no) $this->results['extended']['invoiceNumber']=$this->invoice_no;
        if (strlen($this->type_of_goods)) $this->results['extended']['typeOfGoods']=$this->type_of_goods;
        $this->leveltwo("extended");
    }
    function address($field) {
		$results=array();
        if (strlen($this->address)) $results['line1']=$this->address;
        if (strlen($this->city)) $results['city']=$this->city;
        if (strlen($this->state)) $results['state']=$this->state;
        if (strlen($this->zip)) $results['zip']=$this->zip;
        if (strlen($this->country)) $results['country']=$this->country;
        if (sizeof($results)) $this->results[$field]['address']=$results;
    }
    function leveltwo($field) {
		$results=array();
        if (strlen($this->po)) $results['purchaseOrder']=$this->po;
        if (sizeof($results)) $this->results[$field]['levelTwoData']=$results;
    }

}
class securenet_constant_class {
	var $protocol=array("ecommerce"=>"Ecommerce","moto"=>"Mail Order / Telephone Order","ach"=>"ACH (Check)");
	var $type_of_goods=array("PHYSICAL","DIGITAL","");
    var $avsCode=array();
    var $cardCodeCode=array();
	var $ach_type=array("BUSINESS_CHECKING","BUSINESS_SAVINGS","CHECKING","SAVINGS");
    var $ach_sec=array("TELEPHONE_INITIATED","WEB_INITIATED","POINT_OF_SALE","PREARRANGED_PAYMENT_OR_DEPOSIT","CORPORATE_CASH_DISBURSEMENT","POP");
    var $ach_verification=array("NONE","ACH_PROVIDER","CERTEGY");
    function __construct() {
	    $this->avsCode['0']="AVS data not provided.";
	    $this->avsCode['A']="Street address matches, zip code does not";
	    $this->avsCode['B']="Postal code not verified due to incompatible formats";
	    $this->avsCode['C']="Street address and postal code not verified due to incompatible formats.";
	    $this->avsCode['D']="Street address and postal code match";
	    $this->avsCode['E']="AVS data is invalid";
	    $this->avsCode['G']="Non-U.S. issuing bank does not support AVS";
	    $this->avsCode['I']="Address information not verified by international issuer";
	    $this->avsCode['M']="Customer name, billing address and zip match";
	    $this->avsCode['N']="Neither street address nor zip code match";
	    $this->avsCode['P']="Street address not verified due to incompatible format";
	    $this->avsCode['R']="Retry: issuer's system unavailable or timed out";
	    $this->avsCode['S']="U.S. issuing bank does not support AVS";
	    $this->avsCode['T']="Street address does not match, but 9-digit zip code matches";
	    $this->avsCode['U']="Address information is unavailable";
	    $this->avsCode['W']="9-digit zip matches, street address does not";
	    $this->avsCode['X']="Street address and 9-digit zip match";
	    $this->avsCode['Y']="Street address and 5-digit zip match";

	    $this->cardCodeCode['0']="CVV/CID not provided";
	    $this->cardCodeCode['M']="Match";
	    $this->cardCodeCode['N']="No match";
	    $this->cardCodeCode['P']="Not processed";
	    $this->cardCodeCode['S']="Data not present";
	    $this->cardCodeCode['U']="Issuer unable to process request";
	    $this->cardCodeCode['Y']="Card code matches (Amex only)";
    }
}
class securenet_response_class {
    var $message;
    var $approval_code;
    var $transaction_id;
    var $error_no=0;
    var $card_match;
	var $response;
    function __construct($ch,$response) {
		$this->error_no=curl_errno($ch);
        if ($this->error_no) {
			$this->message=curl_error($ch);
            return;
		}
        $this->response=json_decode($response);
        $this->message=$this->response->result;
        $this->approval_code=$this->response->transaction->authorizationCode;
        $this->transaction_id=$this->response->transaction->transactionId;
        $text=array();
        $text[]="AVS = " . $this->response->transaction->avsResult;
        $text[]="CVV = " . $this->response->transaction->cardCodeResult;
        $this->card_match=implode(" / ",$text);
    }
}
?>