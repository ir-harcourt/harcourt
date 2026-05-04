<?php
class shipping_class {
	var $options;
    var $production=FALSE;
    var $url;
    var $xml;
    var $ship_date;
    var $address=array();
    var $transit=array();
    var $rate=array();
    var $error;
	function scs_table_version() {
		$results=array();
        $results['01/12/2016']="Initial release";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct($options=array()) {
    global $database;
    	$this->options=$options;
	    if (!array_key_exists("carrier",$this->options)) $this->options['carrier']="";
	    if (!array_key_exists("action",$this->options)) $this->options['action']="rate";
	    if (!array_key_exists("date",$this->options)) $this->options['date']=strtotime("now");
	    $this->meta=new stdClass();
	    $this->constant=new stdClass();
        $this->meta->xml="";
		$this->options['carrier']=strtolower($this->options['carrier']);
		$this->options['action']=strtolower($this->options['action']);
		$this->ship_date=ship_date( array("date"=>$this->options['date'],"days"=>$database->registry->shipping->days) );
        switch (TRUE) {
          case (!isset($database->registry->shipping)):
          	$this->error="Shipping module not implemented";
            return;
          case ($this->options['carrier'] == "ups"):
			$this->ups_initialize();
            break;
          default:
          	$this->error="Carrier not defined: " . $this->options['carrier'];
            return;
		}
        switch (TRUE) {
          case ( (in_array($this->options['action'],array("address","transit"))) && (func_num_args() <> 2) ):
          case ( (in_array($this->options['action'],array("rate"))) && (func_num_args() <> 3) ):
          	$this->error="Invalid number of arguments passes";
            return;
        }
        switch (TRUE) {
          case ( ($this->options['carrier']=="ups") && ($this->options['action']=="address") ):
          	$this->ups_address(func_get_arg(1));
            break;
          case ( ($this->options['carrier']=="ups") && ($this->options['action']=="transit") ):
          	$this->ups_transit(func_get_arg(1));
            break;
          case ( ($this->options['carrier']=="ups") && ($this->options['action']=="rate") ):
          	$this->ups_rate(func_get_arg(1),func_get_arg(2));
            break;
          default:
          	$this->error="Invalid action: " . $this->options['action'];
        }
        if (!$this->error) return TRUE;
    }
    private function ups_initialize() {
    global $database;
		if (!array_key_exists("access",$this->options)) $this->options['access']=$database->registry->shipping->ups['access'];
        if ($this->options['access']=="production") $this->production=TRUE;
        $this->constant->rate=array();
        $this->constant->rate['03']="UPS Ground";
        $this->constant->rate['01']="UPS Next Day Air";
        $this->constant->rate['14']="UPS Next Day Air Early";
        $this->constant->rate['13']="UPS Next Day Air Saver";
        $this->constant->rate['59']="UPS 2nd Day Air A.M.";
        $this->constant->rate['02']="UPS 2nd Day Air";
        $this->constant->rate['12']="UPS 3 Day Select";

        $this->constant->rate['11']="Standard";
        $this->constant->rate['07']="Worldwide Express";
        $this->constant->rate['54']="Worldwide Express Plus";
        $this->constant->rate['08']="Worldwide Expedited";
        $this->constant->rate['65']="Saver";
        $this->constant->rate['96']="UPS Worldwide Express Freight";

        $this->constant->transit=array();
        $this->constant->transit['1DM']="14";
        $this->constant->transit['1DA']="01";
        $this->constant->transit['1DP']="13";
        $this->constant->transit['2DM']="59";
        $this->constant->transit['2DA']="02";
        $this->constant->transit['3DS']="12";
        $this->constant->transit['GND']="03";
	}
    private function ups_execute($service,$service_xml) {
    global $database, $forms, $menu;
		$this->meta->ups_service=$service;
        $this->meta->xml=$this->ups_access() . $service_xml;
        if ($this->production) {
	        $this->url="https://onlinetools.ups.com/ups.app/xml/" . $service;
		  } else {
	        $this->url="https://wwwcie.ups.com/ups.app/xml/" . $service;
		}
        $header=array();
	    $header[]="Host: " . $_SERVER['SERVER_NAME'];
	    $header[]="MIME-Version: 1.0";
	    $header[]="Content-type: multipart/mixed; boundary=----doc";
	    $header[]="Accept: text/xml";
	    $header[]="Content-length: ".strlen($this->meta->xml);
	    $header[]="Cache-Control: no-cache";
	    $header[]="Connection: close \r\n";
	    $header[]=$this->meta->xml;
	    $ch =curl_init();
	    //Disable certificate check.
	    // uncomment the next line if you get curl error 60: error setting certificate verify locations
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    // uncommenting the next line is most likely not necessary in case of error 60
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_URL,$this->url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$results=json_decode(json_encode(simplexml_load_string(curl_exec($ch))),TRUE);
        switch (TRUE) {
          case (curl_errno($ch)):
            $this->error="Curl error #" . curl_errno($ch) . " " . curl_error($ch);
			return;
          case (!is_array($results)):
          case (!sizeof($results)):
          	$this->error="No data received";
            return;
          case (!array_key_exists("Response",$results)):
          	$this->error="No response element";
            return;
        }
        if ($results['Response']['ResponseStatusCode'] != "1") {
			$text=array();
        	if (array_key_exists("Error",$results['Response'])) {
            	foreach ($results['Response']['Error'] as $key => $value) {
                	$text[]="$key: $value";
                }
			  } else {
              	$text[]="Undefined error: " .  $results['Response']['ResponseStatusDescription'];
            }
            $this->error=implode(", ",$text);
			return;
        }
        switch ($this->options['action']) {
          case "address":
            switch (TRUE) {
              case (!array_key_exists("AddressKeyFormat",$results)):
              	$this->error="No addresses found";
                return;
              case (array_key_exists(0,$results['AddressKeyFormat'])):
            	$items=$results['AddressKeyFormat'];
				break;
              default:
              	$items=array($results['AddressKeyFormat']);
			}
            foreach ($items as $item) {
                $address=new shipping_address_class();
                $address->address=$item['AddressLine'];
                $address->city=$item['PoliticalDivision2'];
                $address->state=$item['PoliticalDivision1'];
                $address->zip=$item['PostcodePrimaryLow'];
                $address->country_code=$item['CountryCode'];
                $address->csz=$item['Region'];
                if ($item['AddressClassification']['Code']==1) $address->commercial=TRUE;
                $this->address[]=$address;
			}
			if ($results['AddressClassification']['Code']==1) {
            	$this->meta->ups_commercial=TRUE;
			  } else {
            	$this->meta->ups_commercial=FALSE;
			}
          	break;
          case "transit":
          	$this->meta->ups_disclaimer=$results['TransitResponse']['Disclaimer'];
          	$this->meta->ups_pickup=fn_date($results['TransitResponse']['PickupDate'],"mysql");
            foreach ($results['TransitResponse']['ServiceSummary'] as $item) {
                if (!array_key_exists($item['Service']['Code'],$this->constant->transit)) continue;
                $transit_code=$this->constant->transit[ $item['Service']['Code'] ];
            	$transit=new shipping_transit_class();
                $transit->service_code=$item['Service']['Code'];
                $transit->name=$item['Service']['Description'];
                if (preg_match("/y/i",$item['Guaranteed']['Code'])) $transit->guaranteed=TRUE;
                $transit->days=$item['EstimatedArrival']['BusinessTransitDays'];
                $transit->date=fn_date($item['EstimatedArrival']['Date'],"mysql");
                if ($item['EstimatedArrival']['Time'] <> "23:00:00") {
                	$transit->time=$item['EstimatedArrival']['Time'];
                    $transit->date_text=date("l F j, Y \b\y g:i A",strtotime($transit->date . " " . $transit->time));
				  } else {
                    $transit->date_text=date("l F j, Y",strtotime($transit->date));
                }
	            $transit->days_time="(" . $transit->days . " day";
	            if ($transit->days > 1) $transit->days_time .= "s";
	            if ($transit->time) $transit->days_time .= " by " . date("g:i A",strtotime($transit->time));
	            $transit->days_time .= ")";
    			$this->transit[ $transit_code ]=$transit;
            }
          	break;
          case "rate":
            switch (TRUE) {
              case (!array_key_exists("RatedShipment",$results)):
              	$this->error="No results found";
                return;
              case (array_key_exists(0,$results['RatedShipment'])):
            	$items=$results['RatedShipment'];
				break;
              default:
              	$items=array($results['RatedShipment']);
			}
            foreach ($items as $item) {
            	if (!array_key_exists($item['Service']['Code'],$this->constant->rate)) continue;
	            $rate=new shipment_rate_class();
	            $rate->rate=$item['TotalCharges']['MonetaryValue'];
                if (is_string($item['GuaranteedDaysToDelivery'])) $rate->days=$item['GuaranteedDaysToDelivery'];
                if (is_string($item['ScheduledDeliveryTime'])) $rate->time=$item['ScheduledDeliveryTime'];
                $rate->name=$this->constant->rate[ $item['Service']['Code'] ];
	            $rate->handling=$database->registry->shipping->ups['handling'];
                $rate->amount=$rate->rate + $rate->handling;
                if ($rate->days) {
                	$rate->days_time="(" . $rate->days . " day";
                    if ($rate->days > 1) $rate->days_time .= "s";
                	if ($rate->time) $rate->days_time .= " by " . $rate->time;
                    $rate->days_time .= ")";
					$rate->date=ship_date(array("date"=>$this->ship_date,"days"=>$rate->days));
                    $rate->date_text=date("l F j, Y",strtotime($rate->date));
                    if ($rate->time) $rate->date_text .= " by " . $rate->time;
				}
                $this->rate[ $item['Service']['Code'] ]=$rate;
			}
          	break;
        }
    }
    private function ups_access() {
    global $database;
	    $data=array();
	    $data['AccessLicenseNumber']=$database->registry->shipping->ups['license'];
	    $data['UserId']=$database->registry->shipping->ups['userid'];
	    $data['Password']=$database->registry->shipping->ups['password'];
        return $this->xml("AccessRequest",array($data),array("xml:lang"=>"en-US") );
    }
    private function ups_address($address_data) {
		$this->production=TRUE;
		$request=array();
        $request['TransactionReference']=array();
		$request['TransactionReference']['CustomerContext']="User id: " . intval($_SESSION['user']->id);
		$request['TransactionReference']['XpciVersion']="1.0";
        $request['RequestAction']="XAV";
		$request['RequestOption']="3";
		$address_data_results=array();
		if (strlen($address_data->address)) $address_data_results['AddressLine']=preg_replace("/\r|\n/"," ",$address_data->address);
		if (strlen($address_data->city)) $address_data_results['PoliticalDivision2']=$address_data->city;
		if (strlen($address_data->state)) $address_data_results['PoliticalDivision1']=$address_data->state;
        if (strlen($address_data->zip)) {
	        $zipcode=preg_split("/\-/",$address_data->zip);
	        if (strlen($zipcode[0])) $address_data_results['PostcodePrimaryLow']=$zipcode[0];
	        if (strlen($zipcode[1])) $address_data_results['PostcodeExtendedLow']=$zipcode[1];
		}
        $address_data_results['CountryCode']=$address_data->country_code;
	    $results=array();
	    $results['Request']=$request;
	    $results['AddressKeyFormat']=$address_data_results;
		$this->ups_execute("XAV",$this->xml("AddressValidationRequest",$results,array("xml:lang"=>"en-US") ) );
    }
    function ups_transit($address_data) {
    global $database;
    	$request=array();
		$request['TransactionReference']=array();
		$request['TransactionReference']['CustomerContext']="User id: " . intval($_SESSION['user']->id);
		$request['TransactionReference']['XpciVersion']="1.001";
        $request['RequestAction']="TimeInTransit";
        $ship_from=array();
        $ship_from['AddressArtifactFormat']=array();
        $ship_from['AddressArtifactFormat']['PoliticalDivision2']=$database->registry->shipping->city;
        $zip=preg_split("/\-/",$database->registry->shipping->zip);
        $ship_from['AddressArtifactFormat']['PostcodePrimaryLow']=$zip[0];
        $ship_from['AddressArtifactFormat']['CountryCode']=$database->registry->shipping->country_code;

        $ship_to=array();
        $ship_to['AddressArtifactFormat']=array();
        $ship_to['AddressArtifactFormat']['PoliticalDivision2']=$address_data->city;
        $ship_to['AddressArtifactFormat']['PoliticalDivision1']=$address_data->state;
        $zip=preg_split("/\-/",$address_data->zip);
        $ship_to['AddressArtifactFormat']['PostcodePrimaryLow']=$zip[0];
        $ship_to['AddressArtifactFormat']['CountryCode']=$address_data->country_code;

        $weight=array();
        $weight['UnitOfMeasurement']=array();
        $weight['UnitOfMeasurement']['Code']="LBS";
        $weight[]=array("Weight"=>1);
	    $results=array();
	    $results['Request']=$request;
	    $results['TransitFrom']=$ship_from;
	    $results['TransitTo']=$ship_to;
	    $results[]=array('PickupDate'=>preg_replace("/\//","",$this->ship_date));
		$this->ups_execute("TimeInTransit",$this->xml("TimeInTransitRequest",$results,array("xml:lang"=>"en-US") ) );
    }
    private function ups_rate($address_data,$shipment_data) {
    global $database;
		$this->ups_transit($address_data);

    	$request=array();
		$request['TransactionReference']=array();
		$request['TransactionReference']['CustomerContext']="User id: " . intval($_SESSION['user']->id);
		$request['TransactionReference']['XpciVersion']="1.0";
        $request['RequestAction']="Rate";
        $request['RequestOption']="Shop";

        $pickup_type=array();
        $pickup_type['Code']="01";
        $shipment=array();
        $shipment['Shipper']=array();
        $shipment['Shipper']['Name']=$database->registry->shipping->name;
        $shipment['Shipper']['PhoneNumber']=$database->registry->shipping->phone;
        $shipment['Shipper']['ShipperNumber']=$database->registry->shipping->ups['account'];
        $shipment['Shipper']['Address']=array();
        $shipment['Shipper']['Address']['AddressLine1']=preg_replace("/\n|\r/"," ",$database->registry->shipping->address);
        $shipment['Shipper']['Address']['City']=$database->registry->shipping->city;
        $shipment['Shipper']['Address']['StateProvinceCode']=$database->registry->shipping->state;
        $shipment['Shipper']['Address']['PostalCode']=$database->registry->shipping->zip;
        $shipment['Shipper']['Address']['CountryCode']=$database->registry->shipping->country_code;
        $shipment['ShipTo']=array();
        if (!$address_data->company_name) {
			$shipment['ShipTo']['CompanyName']=$address_data->name;
		  } else {
			$shipment['ShipTo']['CompanyName']=$address_data->company_name;
			$shipment['ShipTo']['AttentionName']=$address_data->name;
		}
        $shipment['ShipTo']['PhoneNumber']=$address_data->phone;
        $shipment['ShipTo']['Address']=array();
        $addresses=preg_split("/\r\n/",$address_data->address);
        $i=0;
        foreach ($addresses as $address) {
        	$address=trim($address);
        	switch (TRUE) {
              case ($i==3):
              	break;
              case (strlen($address)):
              	$i++;
				$shipment['ShipTo']['Address']['AddressLine' . $i]=$address;
                break;
            }
        }
	    $shipment['ShipTo']['Address']['City']=$address_data->city;
	    $shipment['ShipTo']['Address']['StateProvinceCode']=$address_data->state;
	    $shipment['ShipTo']['Address']['PostalCode']=$address_data->zip;
	    $shipment['ShipTo']['Address']['CountryCode']=$address_data->country_code;
        $shipment['ShipFrom']=array();
        $shipment['ShipFrom']['Name']=$database->registry->shipping->name;
        $shipment['ShipFrom']['PhoneNumber']=$database->registry->shipping->phone;
        $shipment['ShipFrom']['Address']=array();
        $shipment['ShipFrom']['Address']['AddressLine1']=preg_replace("/\n|\r/"," ",$database->registry->shipping->address);
        $shipment['ShipFrom']['Address']['City']=$database->registry->shipping->city;
        $shipment['ShipFrom']['Address']['StateProvinceCode']=$database->registry->shipping->state;
        $shipment['ShipFrom']['Address']['PostalCode']=$database->registry->shipping->zip;
        $shipment['ShipFrom']['Address']['CountryCode']=$database->registry->shipping->country_code;
        $shipment['PaymentInformation']=array();
        $shipment['PaymentInformation']['Prepaid']=array();
        $shipment['PaymentInformation']['Prepaid']['BillShipper']=array();
        $shipment['PaymentInformation']['Prepaid']['BillShipper']['AccountNumber']=$database->registry->shipping->ups['account'];
        $shipment['Package']=array();
        $shipment['Package']['PackagingType']=array();
        $shipment['Package']['PackagingType']['Code']="02";
        $shipment['Package']['PackagingType']['Description']="Package";
        $shipment['Package']['PackageWeight']=array();
        $shipment['Package']['PackageWeight']['UnitOfMeasurement']=array();
        $shipment['Package']['PackageWeight']['UnitOfMeasurement']['Code']="LBS";
        $shipment['Package']['PackageWeight']['Weight']=$shipment_data['weight'];
        $results=array();
        $results['Request']=$request;
        $results['PickupType']=$pickup_type;
        $results['Shipment']=$shipment;
		$this->ups_execute("Rate",$this->xml("RatingServiceSelectionRequest",$results,array("xml:lang"=>"en-US") ) );
	}
    function xml($name,$data=array(),$options=array()) {
		if (isset($this->xml)) $this->xml->flush();
	    $this->xml=new XMLWriter();
	    $this->xml->openMemory();
	    $this->xml->startDocument('1.0');
	    $this->xml->setIndent(4);
	    $this->xml->startElement($name);
        foreach ($options as $key => $value) {
			$this->xml->writeAttribute($key,$value);
        }
	    foreach ($data as $element_name => $element_data) {
			$this->xml_element($element_name,$element_data);
        }
	    $this->xml->endElement();
	    $this->xml->endDocument();
	    return $this->xml->outputMemory();
    }
    function xml_element($name,$data=array(),$options=array()) {
		if (!is_numeric($name)) {
        	$this->xml->startElement($name);
	        foreach ($options as $key => $value) {
	            $this->xml->writeAttribute($key,$value);
	        }
		}
        foreach ($data as $key => $value) {
			switch (TRUE) {
              case (is_array($value)):
              	$this->xml_element($key,$value);
            	break;
              default:
				$this->xml->writeElement($key,$value);
			}
        }
		if (!is_numeric($name)) $this->xml->endElement();
    }
}
class shipping_address_class {
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country_code;
    var $commercial=FALSE;
    var $csz;
}
class shipping_transit_class {
	var $service_code;
    var $name;
    var $guaranteed=FALSE;
    var $days=0;
    var $time;
    var $days_time;
    var $date;
    var $date_text;
}
class shipment_rate_class {
	var $name;
    var $rate=0;
    var $handling=0;
    var $amount=0;
    var $days=0;
    var $time;
    var $days_time;
    var $date;
    var $date_text;
}
?>