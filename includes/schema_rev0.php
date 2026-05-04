<?php
require_once "classes/schema.php";
$schema=new schema_class();
$forms->title("Schema Tag Generator (Revised " . key($schema->scs_table_version()) . ")");
$forms->report['action']=$_POST['action'];
$forms->report['schema']=$_POST['schema'];
if ($forms->report['schema']) $schema->load($forms->report['schema']);
switch ($forms->report['action']) {
  case "":
	break;
  case "data":
	foreach ($schema->items as $property => $data) {
		$text=trim($_POST['property_' . $property]);
		if (strlen($text)) {
			$type_field="type_" . $property;
        	$schema->item($property,$text,$_POST[$type_field]);
            if (!$data->value_type) $forms->error($type_field);
		}
    }
	break;
}
$menu->head();
$forms->message();
?>
<script language=javascript>
function fn_action(action) {
    document.scs_form.action.value=action;
    document.scs_form.submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
switch (TRUE) {
  case ($forms->report['action']=="data"):
  case ($forms->report['action']=="schema"):
	print $forms->hidden("schema",$forms->report['schema']);
	print "<p class=standard><b>Schema: " . $forms->report['schema'] . "</b>";
	print "<br>" . $schema->schemas[ $forms->report['schema'] ] . "</p>\n";
	print "<table class='small border'>";
	print "<tr>";
	print "<th width='15%'>Property</th>";
	print "<th width='15%'>Value</th>";
	print "<th width='15%'>Type(s)</th>";
	print "<th width='35%'>Description</th>";
//	print "<th width='20%'>JSON</th>";
	print "</tr>";
	$first_pass=TRUE;
	foreach ($schema->items as $property => $data) {
	    print "<tr>";
	    print "<td>" . $property . "</td>";
	    print "<td>" . $forms->text("property_" . $property,$data->value,30) . "</td>";
        if (sizeof($data->type) > 1) {
        	$text=$forms->select("type_" . $property,$data->type,$data->value_type);
		  } else {
          	$text=$data->type[0];
            $text .= $forms->hidden("type_" . $property,$data->type[0]);
		}
	    print "<td>" .$text  . "</td>\n";
	    print "<td>" . htmlspecialchars($data->description, ENT_QUOTES) . "</td>";
	    print "</tr>\n";
	}
	print "</table>";
    $button=array();
    $button[]=$forms->button("<< Back",array("onclick"=>"fn_action('');"));
    $button[]=$forms->button("Next >>",array("onclick"=>"fn_action('data');"));
    print "<p>" . implode(" ",$button) . "</p>\n";
	break;
  default:
	print "<table class='standard border'>\n";
	print "<tr>\n";
	print "<th width='5%'>Schema?</th>\n";
	print "<th width='15%'>Property</th>\n";
	print "<th width='80%'>Descripton</th>\n";
	foreach ($schema->schemas as $schema_code => $schema_name) {
	    print "<tr>\n";
	    print "<td class=center>" . $forms->radio("schema",$schema_code,$forms->report['schema'],array("onclick"=>"fn_action('schema');")) . "</td>\n";
	    print "<td>" . $schema_code . "</td>\n";
	    print "<td>" . $schema_name . "</td>\n";
	    print "</tr>\n";
	}
	print "</table>\n";
}
print $forms->close();
$database->disconnect();
$menu->copyright();
?>