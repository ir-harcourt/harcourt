<?php
/* Dropped 09/01/2020 */
function address_data($table,$content) {
global $database;
    $results=new stdClass();
	if (isset($database->registry->address->{$table})) {
	    foreach ($content as $key => $value) {
			if ( (array_key_exists($key,$database->registry->address->{$table})) && (strlen($value)) ) $results->$key=$value;
	    }
	}
    if (isset($results->state)) {
		$database->country->read($results->country_code,$results->state);
        if ($database->country->meta->rows) $results->state_name=$database->country->data->name;
    }
    if (isset($results->country_code)) {
		$database->country->read($results->country_code,"");
        if ($database->country->meta->rows) $results->country_name=$database->country->data->name;
    }
    return $results;
}
function address_verify($table,$data,$update=FALSE,$mandatory=array(),$output=TRUE) {
global $database,$forms;
	if (!isset($database->registry->address->{$table})) return;
    if (!sizeof($mandatory)) {
        foreach ($database->registry->address->{$table} as $field => $value) {
            if ($value=="required") $mandatory[]=$field;
        }
    }
    if (array_key_exists("name_first",$data) && array_key_exists("name_last",$data)) {
		$first_last=TRUE;
	  } else {
      	$first_last=FALSE;
	}
    if ($update) {
        $data->country_code=fn_text($_POST['country_code']);
        switch ($data->country_code) {
          case "US":
            $state_field="state_us";
            break;
          case "CA":
            $state_field="state_ca";
            break;
          default:
            $state_field="state_other";
        }
        switch (TRUE) {
          case (!$database->registry->address->{$table}['name']):
			break;
          case ($first_last):
            $data->name_first=fn_text($_POST['name_first']);
            $data->name_last=fn_text($_POST['name_last']);
            $data->name=trim($data->name_first . " " . $data->name_last);
            if (in_array("name",$mandatory)) {
				if (!strlen($data->name_first)) $forms->error("name_first");
				if (!strlen($data->name_last)) $forms->error("name_last");
            }
			break;
          default:
            $data->name=fn_text($_POST['name']);
            if ( (in_array("name",$mandatory)) && (!strlen($data->name)) ) $forms->error("name");
        }
        if ($database->registry->address->{$table}['title']) {
            $data->title=fn_text($_POST['title']);
            if ( (in_array("title",$mandatory)) && (!strlen($data->title)) ) $forms->error("title");
        }
        if ($database->registry->address->{$table}['company_name']) {
            $data->company_name=fn_text($_POST['company_name']);
            if ( (in_array("company_name",$mandatory)) && (!strlen($data->company_name)) ) $forms->error("company_name");
        }
        if ($database->registry->address->{$table}['address']) {
            $data->address=fn_text($_POST['address']);
            if ((in_array("address",$mandatory)) && (!strlen($data->address)) ) $forms->error("address");
        }
        if ($database->registry->address->{$table}['city']) {
            $data->city=fn_text($_POST['city']);
            if ( (in_array("city",$mandatory)) && (!strlen($data->city)) ) $forms->error("city");
        }
        if ($database->registry->address->{$table}['state']) {
            $data->state=fn_text($_POST[$state_field]);
            if ( (in_array("state",$mandatory)) && (!strlen($data->state)) ) $forms->error($state_field);
        }
        if ($database->registry->address->{$table}['zip']) {
            $data->zip=fn_text($_POST['zip']);
            if ( (in_array("zip",$mandatory)) && (!strlen($data->zip)) ) $forms->error("zip");
        }

        if ($database->registry->address->{$table}['email']) {
			$email=new email_class($_POST['email'],(in_array("email",$mandatory)));
            $data->email=$email->address;
            if ($email->error) $forms->error("email",$email->error);
        }
        if ($database->registry->address->{$table}['phone']) {
            $data->phone=fn_text($_POST['phone']);
            if ( (in_array("phone",$mandatory)) && (!strlen($data->phone)) ) $forms->error("phone");
        }
        if ($database->registry->address->{$table}['fax']) {
            $data->fax=fn_text($_POST['fax']);
            if ( (in_array("fax",$mandatory)) && (!strlen($data->fax)) ) $forms->error("fax");
        }

      } else {
		$results=array();
        if ($database->registry->address->{$table}['name']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("name",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            if ($first_last) {
            	$results[]="First/Last Name";
			  } else {
            	$results[]="Name";
			}
            $results[]="</td>";
            $results[]="<td>";
            if ($first_last) {
				$results[]=$forms->text("name_first",$data->name_first,30,40);
				$results[]=$forms->text("name_last",$data->name_last,30,40);
			  } else {
				$results[]=$forms->text("name",$data->name,40,40);
			}
            $results[]="</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['title']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("title",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Title</td>";
            $results[]="<td>" . $forms->text("title",$data->title,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['company_name']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("company_name",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Company Name</td>";
            $results[]="<td>" . $forms->text("company_name",$data->company_name,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['address']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("address",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Address</td>";
            $results[]="<td>" . $forms->text("address",$data->address,40,100) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['city']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("city",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="City</td>";
            $results[]="<td>" . $forms->text("city",$data->city,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['state']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("state",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="<span id=state_text></span></td>";
            $results[]="<td>";
            $results[]=$database->country->select("US",$data->state,"state_us");
            $results[]=$database->country->select("CA",$data->state,"state_ca");
            $results[]=$forms->text("state_other",$data->state,40,40,"",array("id"=>"state_other"));
            $results[]="</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['zip']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("zip",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="<span id=zip_text></span></td>";
            $results[]="<td>" . $forms->text("zip",$data->zip,25,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['country_code']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("country_code",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Country</td>";
            $results[]="<td>" . $database->country->select("",$data->country_code,"country_code",array("onchange"=>"fn_country_address();"));
            $results[]="</td>";
            $results[]="</tr>";
         } else {
            $results[]=$forms->hidden("country_code","US");
        }
        if ($database->registry->address->{$table}['email']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("email",$mandatory)) $results[]=$forms->font("red","<b>*</b>");
            $results[]="Email</td>";
            $text=array( $forms->text("email",$data->email,60,60) );
            if ($forms->error_text['email']) $text[]="<b>" . $forms->error_text['email'] . "</b>";
            $results[]="<td>" . implode("<br>",$text) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['phone']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("phone",$mandatory)) $results[]=$forms->font("red","<b>*</b> ");
            $results[]="Phone</td>";
            $results[]="<td>" . $forms->text("phone",$data->phone,40,40) . "</td>";
            $results[]="</tr>";
        }
        if ($database->registry->address->{$table}['fax']) {
            $results[]="<tr>";
            $results[]="<td>";
            if (in_array("fax",$mandatory)) $results[]=$forms->font("red","<b>*</b> ");
            $results[]="Fax</td>";
            $results[]="<td>" . $forms->text("fax",$data->fax,40,40) . "</td>";
            $results[]="</tr>";
        }
        $results[]="<script type='text/javascript'>";
        $results[]="function fn_country_address() {";
        $results[]=" country_obj=document.getElementById('country_code');";
        $results[]=" switch (true) {";
        $results[]="   case (!country_obj):";
        $results[]="   return;";
        $results[]="  case (country_obj.value == 'US'):";
		$results[]="   state='State';";
		$results[]="   zip='ZIP Code';";
		$results[]="   div_show('state_us',1);";
		$results[]="   div_show('state_ca',0);";
		$results[]="   div_show('state_other',0);";
		$results[]="   break;";
		$results[]="  case (country_obj.value == 'CA'):";
		$results[]="   state='Province';";
		$results[]="   zip='Postal Code';";
		$results[]="    div_show('state_us',0);";
		$results[]="   div_show('state_ca',1);";
		$results[]="   div_show('state_other',0);";
		$results[]="   break;";
		$results[]="  default:";
		$results[]="   state='Province/State';";
		$results[]="   zip='Postal Code';";
		$results[]="   div_show('state_us',0);";
		$results[]="   div_show('state_ca',0);";
		$results[]="   div_show('state_other',1);";
		$results[]=" }";
		$results[]=" state_obj=document.getElementById('state_text');";
		$results[]=" if (state_obj) state_obj.innerHTML=state;";
		$results[]=" zip_obj=document.getElementById('zip_text');";
		$results[]=" if (zip_obj) zip_obj.innerHTML=zip;";
		$results[]="}";
		$results[]="fn_country_address();";
		$results[]="</script>";
	    if ($output) {
	        print implode("\n",$results);
	      } else {
	        return implode("\n",$results);
	    }
    }
}
function address_output($table, $content, $output="table") {
	$data=address_data($table,$content);
	$items=array();
    if (isset($data->name)) $items['Name']=$data->name;
    if (isset($data->title)) $items['Title']=$data->title;
    if (isset($data->company_name)) $items['Company Name']=$data->company_name;
    if (isset($data->address)) $items['Address']=str_replace("","<br>",$data->address);
	if (isset($data->city)) $items['City']=$data->city;
    switch (TRUE) {
      case (!isset($data->state)):
      	break;
      case ($data->country_code=="US"):
      	$items['State']=(isset($data->state_name) ? $data->state_name : $data->state);
        break;
      default:
        $items['Province']=(isset($data->state_name) ? $data->state_name : $data->state);
    }
    switch (TRUE) {
      case (!isset($data->zip)):
      	break;
      case ($data->country_code=="US"):
        $items['Zip Code']=$data->zip;
        break;
      default:
        $items['>Postal Code']=$data->zip;
    }
    if (isset($data->country_name)) $items['Country']=$data->country_name;
    if (isset($data->email)) $items['Email']=$data->email;
    if (isset($data->phone)) $items['Phone']=$data->phone;
    if (isset($data->fax)) $items['Fax']=$data->fax;
    $results=array();
	switch ($output) {
      case "array":
      	return $items;
      case "table":
      	foreach ($items as $key => $value) {
        	$results[]="<tr><td>{$key}</td><td>{$value}</td></tr>";
        }
        return implode("\n",$results);
      case "html":
      	foreach ($items as $key => $value) {
        	$results[]="{$key}: {$value}";
        }
    	return implode("<br>\n",$results);
      default:
      	die("Invalid output option {$output} in " . __METHOD__);
	}
}
function address_table($table,$content) {
	return address_output($table,$content,"table");
}
function address_html($table,$content) {
	return address_output($table,$content,"html");
}
?>