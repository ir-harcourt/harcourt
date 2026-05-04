<?php
require_once "scs_header.php";
require_once "classes/currency.php";
$database->user->access("Administrator");
$forms->title("Currency Codes");
$forms->report['action']=$_POST['action'];
$forms->report['record_id']=$_POST['record_id'];
if ($forms->report['record_id']) {
	$database->currency->read($forms->report['record_id']);
  } else {
    $database->currency->data=new currency_data_class();
}
switch (TRUE) {
  case (!$forms->report['action']):
    break;
  case ($forms->report['action'] == "cancel"):
    $forms->message[]="Request cancelled";
    break;
  case ($forms->report['action'] =="maintain"):
    break;
  case ($forms->report['action'] == "delete"):
    $database->currency->delete($forms->report['record_id']);
    $forms->message[]="Currency " . $database->currency->data->code . " deleted";
    break;
  case ($forms->report['action'] == "update"):
	$database->currency->data->code=fn_text($_POST['code'],TRUE);
    switch (TRUE) {
      case (!strlen($database->currency->data->code)):
      	$forms->error('code',"Must be 3 characters");
	}
	$database->currency->data->name=trim($_POST['name']);
    if (!strlen($database->currency->data->name)) $forms->error('name',"Cannot be blank");
	$database->currency->data->plural=trim($_POST['plural']);
    if (!strlen($database->currency->data->plural)) $forms->error('plural',"Cannot be blank");
	$database->currency->data->symbol=trim($_POST['symbol']);
    if ($database->currency->data->code == "USD") {
		$database->currency->data->exchange_rate=1;
		$database->currency->data->addon_pct=0;
	  } else {
		$database->currency->data->exchange_rate=fn_number($_POST['exchange_rate'],4);
		if ($database->currency->data->exchange_rate < 0) $forms->error("exchange_rate","Must be >= 0.0000");
		$database->currency->data->adder_pct=fn_number($_POST['adder_pct'] . "%",2);
        switch (TRUE) {
          case ( (!$database->currency->data->exchange_rate) && ($database->currency->data->adder_pct) ):
          	$forms->error("adder_pct","Must be 0.00% if no exchange rate");
          	break;
          case ( ($database->currency->data->adder_pct <= -1) || ($database->currency->data->adder_pct >= 10) ):
          	$forms->error("adder_pct","Must between -100% and + 1000%");
            break;
		}

	}
	$database->currency->data->active=$_POST['active'];
	if (sizeof($forms->error)) break;
    $database->currency->update($forms->report['record_id']);
    switch (TRUE) {
      case (!$database->currency->meta->error):
		$forms->message[]="Record maintained";
        break;
      case (preg_match("/code/i",$database->currency->meta->error)):
		$forms->error('code',$database->currency->meta->error);
      	break;
      case (preg_match("/plural/i",$database->currency->meta->error)):
		$forms->error('plural',$database->currency->meta->error);
      	break;
      default:
		$forms->error('name',$database->currency->meta->error);
    }
	break;
}
$menu->head();
print $forms->message();
?>
<script>
jQuery(document).ready(function() {
	fn_exchange_rate();
});
function fn_exchange_rate() {
	if (jQuery("#code").val() == "USD") {
    	jQuery(".exchange_rate_tr").hide();
	  } else {
    	jQuery(".exchange_rate_tr").show();
    	jQuery("#exchange_rate_currency").html(jQuery("#code").val());
    }
}
function fn_action(obj,action,id,name) {
	if ( (action=='delete') && (!confirm("Delete: #" + id + " " + name)) ) {
	    obj.checked=false;
	    return;
    }
	jQuery("#action").val(action);
	jQuery("#record_id").val(id);
	jQuery("#scs_form").submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
print $forms->hidden("record_id");
switch (TRUE) {
  case ($forms->report['action'] == "maintain"):
  case (($forms->report['action'] == "update") && (sizeof($forms->error))):
	print "<table class='noborder'>";
    print "<tr>";
    print "<td width='30%'>Code</td>";
    $text=array( $forms->text("code",$database->currency->data->code,3,3) );
    if ($forms->error_text['code']) $text[]=$forms->error_text['code'];
    print "<td width='70%'>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Name</td>";
    $text=array( $forms->text("name",$database->currency->data->name,60, 60) );
    if ($forms->error_text['name']) $text[]=$forms->error_text['name'];
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr>";
    print "<td>Plural</td>";
    $text=array( $forms->text("plural",$database->currency->data->plural,60, 60) );
    if ($forms->error_text['plural']) $text[]=$forms->error_text['plural'];
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr>";
    $text=array("Currency Symbol");
    if (strlen($database->currency->data->symbol) > 1) $text[]=$database->currency->data->symbol;
    print "<td>" . implode(": ",$text) . "</td>";
    print "<td>" . $forms->text("symbol",$database->currency->data->symbol,20,20) . "</td>";
    print "</tr>";
    print "<tr class=exchange_rate_tr>";
    print "<td>Base Exchange Rate</td>";
    $text=array();
    $text[]="$1.00 USD = " . $forms->text("exchange_rate",$database->currency->data->exchange_rate,12, 12, "decimal:4") . " <span id=exchange_rate_currency></span>";
    if ($forms->error_text['exchange_rate']) $text[]=$forms->error_text['exchange_rate'];
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    print "<tr class=exchange_rate_tr>";
    print "<td>Adder</td>";
    $text=array();
    $text[]=$forms->text("adder_pct",($database->currency->data->adder_pct * 100),6,6,"decimal:2") . "%";
    if ($forms->error_text['adder_pct']) $text[]=$forms->error_text['adder_pct'];
    print "<td>" . implode("<br>",$text) .  "</td>";
    print "</tr>";
    if ($database->currency->data->update_timestamp) {
    	print "<tr>";
        print "<td>Revised</td>";
        print "<td>" . date("m/d/Y h:i A",$database->currency->data->update_timestamp) . "</td>";
        print "</tr>";
    }
	print "<tr>";
	print "<td>Active?</td>";
	print "<td>" . $forms->checkbox("active",1,$database->currency->data->active) . "</td>";
	print "</tr>";
	print "</table>";
    $buttons=array();
    $buttons[]=$forms->button("Update",array("onclick"=>"fn_action(this,'update'," . fn_escape($database->currency->data->id) . ",'');"));
    $buttons[]=$forms->button("Cancel",array("onclick"=>"fn_action(this,'cancel','','');"));
	print "<p class='standard'>" . implode("\n",$buttons) . "</p>\n";
  	break;
  default:
	print "<table class='standard border tablesorter'>";
    print "<caption>&nbsp;</caption>";
    print "<thead>";
    print "<tr>";
    print "<th>Code</th>";
    print "<th>Name</th>";
    print "<th>Symbol</th>";
    print "<th>Exchange Rate</th>";
    print "<th>Adder%</th>";
    print "<th>$1.00 USD Buys</th>";
    print "<th>Revised</th>";
    print "<th>Del?</th>";
    print "</tr>";
    print "</thead>";
    print "<tr>";
    print "<td colspan=8>" . fn_href("Add new","javascript:fn_action(this,'maintain','0','');") ."</td>";
    print "</tr>";
    print "<tbody>";
    $query=array("select");
    $query[]="case";
    $query[]="when code='USD' then 1";
    $query[]="when exchange_rate > 0 then 2";
    $query[]="else 3";
    $query[]="end  as sort_key,";
    $query[]="currency.* from currency";
    $query[]="order by sort_key, name";
    $database->currency->query($query);
    while ($database->currency->fetch = $database->currency->fetch_array()) {
	    $database->currency->fetch();
	    print "<tr>";
	    print "<td>" . fn_href($database->currency->data->code,"javascript:fn_action(this,'maintain'," . fn_escape($database->currency->data->id) . ",'');") . "</td>";
	    print "<td>" . $database->currency->data->name . "</td>";
	    print "<td class=center>" . $database->currency->data->symbol . "</td>";
        if ($database->currency->data->exchange_rate > 0) {
        	print "<td class=center>" . number_format($database->currency->data->exchange_rate,4) . "</td>";
        	print "<td class=center>" . number_format((100 * $database->currency->data->adder_pct),2) . "%</td>";
        	print "<td class=center>" . number_format($database->currency->data->exchange_net,4) . "</td>";
            print "<td class=center>" . date("m/d/Y",$database->currency->data->update_timestamp) . "</td>";
		  } else {
          	print "<td colspan=4>&nbsp;</td>";
		}
	    if ($database->currency->data->active) {
			$text="";
		  } else {
	        $text=$forms->radio("delete",$database->currency->data->id,0,array("onclick"=>"fn_action(this,'delete'," . fn_escape($database->currency->data->id) . "," . fn_escape($database->currency->data->name) . ");"));
	    }
	    print "<td class=center>{$text}</td>";
	    print "</tr>";
	}
    print "</tbody>";
	$database->currency->free_result();
	print "</table>";
    print "<p class=center>" . number_format($database->currency->meta->rows) . " Currency Codes Found</p>";
}
print $forms->close();
$menu->copyright();
?>