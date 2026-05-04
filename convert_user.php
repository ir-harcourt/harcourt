<?php
require_once "scs_header.php";
$database->user->access("Super User");
$forms->title("IPStack Conversion");
$forms->report['action']=$_POST['action'];
switch (TRUE) {
  case ($forms->report['action']):
	set_time_limit(0);
	$forms->constant->currency=array();
    $database->currency->query("select * from currency");
    while ($database->currency->fetch = $database->currency->fetch_array() ) {
		$database->currency->fetch();
		$forms->constant->currency[$database->currency->data->code]=$database->currency->data;
    }
	$items=array();
    $query_where=array();
    $query_where[]=new query_where("status","=","active");
    $query=array();
    $query[]="select * from user";
    $query[]="where status <> 'reject'";
    $query[]="and isnull(ipstack_json)";
    $query[]="order by id";
//    if (fn_development_server()) $query[]="limit 2";
	$query[]="limit 5000";
    $database->user->query($query);
    while ($database->user->fetch = $database->user->fetch_array() ) {
		$database->user->fetch();
    	$items[]=$database->user->data;
    }
	foreach ($items as $database->user->data) {
    	if (!$database->user->ipstack(FALSE)) continue;
		if ( (strlen($database->user->data->ipstack_json->currency->code)) && (!array_key_exists($database->user->data->ipstack_json->currency->code,$forms->constant->currency)) ) {
	        $database->currency->data=new currency_data_class();
            $database->currency->data->code=$database->user->data->ipstack_json->currency->code;
            $database->currency->data->name=$database->user->data->ipstack_json->currency->name;
            $database->currency->data->punctuate=0;
            $database->currency->data->active=TRUE;
            $database->currency->update(FALSE);
            $forms->constant->currency[$database->user->data->ipstack_json->currency->code]=$database->currency->data;
		}
    }
    $forms->message[]=number_format(sizeof($items)) . " User Records Updated";
}

$menu->head();
print $forms->message();

print $forms->open();
print $forms->hidden("action","update");

print $forms->submit("Go");


print $forms->close();
$menu->copyright();
?>