<?php
$menu->geocode=new geocode_class();
class geocode_class {
	function scs_table_version() {
		$results=array();
        $results['03/13/2020']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct() {
    	$this->results=new geocode_results_class();
		libxml_use_internal_errors(TRUE);
    }
    function coordinates($address, $zip) {
    	$this->results=new geocode_results_class();
        $ch=curl_init();
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,True);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,True);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,15);
	    curl_setopt($ch, CURLOPT_TIMEOUT,15);
		$url_query=array();
        $url_query['street']=$address;
        if (strlen($zip)) $url_query['zip']=$zip;
        $url_query['format']="json";
        $url_query['benchmark']="4";
        $url_query['vintage']="4";
        $url_query['format']="json";
		$url=fn_url("https://geocoding.geo.census.gov/geocoder/locations/address",$url_query);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POST, FALSE);
	    $response=curl_exec($ch);
        $this->results->decode(json_decode($response), curl_errno($ch));
        return ( ($this->results->error) ? FALSE : TRUE);
    }
    function batch($file) {
/*
Unique ID, Street address, City, State, ZIP
10000 records per batch file

Example:
curl --form addressFile=@localfile.csv --form benchmark=9
https://geocoding.geo.census.gov/geocoder/locations/addressbatch --output geocoderesult.csv
*/
    }
}
class geocode_results_class {
	var $latitude=0;
	var $longitude=0;
    var $address;
    var $location;
    var $detail;
	var $error;
    function decode($content, $error_no) {
        switch (TRUE) {
          case (intval($error_no)):
        	$this->error="cURL error: {$error_no})";
            break;
          case (!is_object($content)):
          case (!is_object($content->result)):
          case (!is_array($content->result->addressMatches)):
        	$this->error="Invalid response";
            break;
          case (!sizeof($content->result->addressMatches)):
        	$this->error="Invalid address";
            break;
          default:
          	$this->address=$content->result->addressMatches[0]->matchedAddress;
            $this->longitude=$content->result->addressMatches[0]->coordinates->x;
            $this->latitude=$content->result->addressMatches[0]->coordinates->y;
            $results=array();
        	$results[]=$this->latitude;
        	$results[]=$this->longitude;
			$this->location=implode(",",$results);
            $results=array();
	        $results[]="Latitude:";
	        $results[]=number_format($this->latitude,5);
	        $results[]="Longitude:";
	        $results[]=number_format($this->longitude,5);
			$this->detail=implode(" ",$results);
        }
    }
}
?>