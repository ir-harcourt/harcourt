<?php
require_once "scs_header.php";
require_once "classes/reporting.php";
require_once "map_rev3.php";
require_once "classes/scsmail_rev1.php";
$database->user->access("Internal");
$obj=new pricebook_class();
class pricebook_class {
    var $input;
    var $items=array();
    function scs_table_version() {
    	$results=array();
        $results['06/05/2023']="Add subcategory_currency";
        $results['03/06/2023']="Ajax exchange/add rate, change date";
        $results['10/29/2022']="Initial release";
        return $results;
    }
    function __construct() {
    global $database, $forms, $menu;
		if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
        	$this->json();
		  } else {
          	$this->html();
		}
    }
    function json() {
    global $database, $forms, $menu;
		$response=new stdClass();
        switch ($_POST['action']) {
          case ("currency"):
	        $obj=(array_key_exists($_POST['currency_code'],$menu->currency)) ? $menu->currency[ $_POST['currency_code'] ] : $menu->currency['USD'];
	        $text=array();
	        if ($obj->code != "USD") {
	            $text[]="Revised: " . date("m/d/Y h:iA", $obj->update_timestamp);
	            $text[]="Exchange rate: " . number_format($obj->exchange_rate,4) . "%";
	            if ($obj->adder_pct) $text[]="Adder percent: " . number_format($obj->adder_pct * 100,4) . "%";
	        }
	        $response->currency_meta=implode("<br>",$text);
            break;
		}
        die(json_encode($response));
    }
    function html() {
    global $database, $forms, $menu;
        $this->input=new stdClass();
	    $forms->title("Price Book");
        $forms->message[]="Revised: " . key($this->scs_table_version());
	    $this->input->action=$_POST['action'];
        $this->input->subcategory=array();
        switch ($this->input->action) {
          case "":
            $this->input->title=$database->registry->local->pricebook_title;
            $this->input->price_type="base";
          	break;
		  default:
			$this->input->title=fn_text($_POST['title']);
            if (!strlen($this->input->title)) $forms->error("title","Cannot be blank");
          	$user_id=intval($_POST['ajax_temp_id']);
            $database->user->read($user_id);
          	if ($database->user->meta->rows) $this->input->user_id=$user_id;
			$this->input->company_name=fn_text($_POST['ajax_temp_company_name']);
			$this->input->name=fn_text($_POST['ajax_temp_name']);
            $this->input->price_type=$_POST['ajax_temp_price_type'];
	        $this->input->price_pct=(fn_number($_POST['ajax_temp_price_pct']) / 100);
	        switch ($this->input->price_type) {
	          case "":
	            $this->input->price_pct=0;
				$forms->error("ajax_temp_price_type","Not allowed");
	            break;
	          case "base":
	            if ($this->input->price_pct) $forms->error("ajax_temp_price_pct","Must be 0% for base price");
	            break;
	          case "discount":
	            if ( ($this->input->price_pct <= 0) || ($this->input->price_pct >= 1) ) $forms->error("ajax_temp_price_pct","Discount must be > 0% and < 100%");
	            break;
	          case "plus":
	            if ( ($this->input->price_pct <= 0) || ($this->input->price_pct >= 5) ) $forms->error("ajax_temp_price_pct","Price plus must be > 0% and < 500%");
	            break;
	        }
			$this->input->currency_code=$_POST['ajax_temp_pricebook_currency_code'];
			$this->input->qty_break=$_POST['qty_break'];
        	if ($database->registry->local->pricebook_mailform) {
	            $this->input->output=$_POST['output'];
	            $email=new email_class($_POST['ajax_temp_email'],($this->input->output == "Email") ? TRUE : FALSE);
	            $this->input->email=$email->address;
	            if ($email->error) $forms->error("ajax_temp_email",$email->error);
			}
            $this->input->message=fn_text($_POST['message']);
	        foreach ($_POST as $key => $value) {
	            list($field, $subcategory_id)=fn_base64($key,FALSE);
	            if ($field == "subcategory") $this->input->subcategory[]=$subcategory_id;
	        }
	        if (!sizeof($this->input->subcategory)) $forms->error("subcategory_id","No subcategories selected");
	        if (sizeof($forms->error)) break;
		    set_time_limit(120);
            $options=(array) $this->input;
            switch ($this->input->price_type) {
              case "discount":
            	$options['multiplier']=(1 - $this->input->price_pct);
                break;
              case "plus":
            	$options['multiplier']=(1 + $this->input->price_pct);
                break;
              default:
            	$options['multiplier']=1;
			}
            $options['exchange_rate']=$menu->currency[$this->input->currency_code]->exchange_rate;
            $options['adder_pct']=$menu->currency[$this->input->currency_code]->adder_pct;
            $options['report']="pricebook";
            $options['file_type']=".xlsx";
			$this->map=new map_class("reporting",$options);
            $query=array("select category_id, category_name from reporting");
            $query[]="group by category_id";
            $query[]="order by category_name";
            $database->temp->query($query);
            $codes=array();
            while ($database->temp->fetch = $database->temp->fetch_array() ) {
            	$codes[$database->temp->fetch['category_id']]=$database->temp->fetch['category_name'];
            }
            $options=array();
            $options['filename']="Price Book";
            $options['xls_multiple']=TRUE;
            $options['multiple_codes']=$codes;
            if ($this->input->output == "Email") $options['save']=1;

            $text=array();
            $text['Subject']=$this->input->title;
            $text['Website']="https://www.harcourt.co/";
            $text['Date']=date("l F j, Y");
            if (strlen($this->input->company_name)) $text['Company Name']=$this->input->company_name;
            if (strlen($this->input->name)) $text['Name']=$this->input->name;
			$text["Priced in " . $menu->currency[$this->input->currency_code]->plural ]="";
            if (strlen($this->input->message)) {
            	$text[""]="";
            	$text["Message"]=$this->input->message;
			}
            $options['coversheet']=$text;
            $query=array("select");
            $query[]="category_id as 'code', count(*) as 'count' from reporting";
            $query[]="group by category_id";
			$this->map->export($query,$options);

            if ($this->input->output != "Email") break;
            $options=array();
            $options['subject']=$this->input->title;
            $mail=new scsmail_class($database->registry->local->pricebook_mailform,$database->user->data,$options);
            $mail->user_id=$database->user->data->id;
            $mail->recipient($this->input->email,"");
            $mail->attachment($this->map->external_file);
            if (strlen($this->input->message)) $mail->html("<div>" . str_replace("\n","<br>",$this->input->message) . "</div>");
            $forms->message[]=$mail->send();
            unlink($this->map->external_file);
        }
	    $menu->head();
	    print $forms->message();
        print $this->js();
        print $forms->open();
	    print $forms->hidden("action","update");
	    $this->tabs=new jquery_tab_class();
	    $this->tabs->item("Input",$this->input());
	    $this->tabs->item("Subcategory",$this->subcategory());
	    $this->tabs->output();
	    print "<p>" . $forms->button("Create Price Book",array("onclick"=>"pricebook_create();")) . "</p>";
    }
    function js() {
		$response=<<<EOT

<script>
jQuery(document).ready(function() {
	fn_currency_code();
});
jQuery(function() {
    jQuery('#ajax_temp_id').autocomplete({
        source: '/scs_ajax.php?table=user',
        minLength: 2,
        html: true,
		select: function(event, ui) {
        	ajax_fill("temp",ui.item);
            fn_currency_code();
        },
        open: function(event, ui) { jQuery('.ui-autocomplete').css('z-index', 1000) }
    });
});
function fn_currency_code() {
	jQuery.ajax({
        type: "post",
	    dataType: "json",
		data: { action: "currency", currency_code: jQuery("#ajax_temp_pricebook_currency_code").val() },
	    success: function(response) {
	        for (var key in response) {
            	jQuery("#" + key).html(response[key]);
			}
        }
	});
}
function pricebook_create() {
if (!confirm('Create price book?')) return;
	jQuery('#scs_form').submit();
}
</script>
EOT;
		return $response;
    }
    function input() {
    global $database, $forms, $menu;
		$results=array();
        $results[]="<table class='noborder'>";

        $results[]="<tr>";
        $results[]="<td>Title</td>";
        $text=array($forms->text("title",$this->input->title,60,0,"",array("placeholder"=>"Mandatory")));
        if ($forms->error_text['title']) $text[]=$forms->error_text['title'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";

        $results[]="<tr>";
        $results[]="<td width='20%'>Prepared for user ID</td>";
        $results[]="<td width='80%'>" . $forms->text("ajax_temp_id",$this->input->user_id,10,0,"",array("placeholder"=>"Optional")) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Company Name</td>";
		$text=array();
        $text[]=$forms->text("ajax_temp_company_name",$this->input->company_name,40,0,"",array("placeholder"=>"Mandatory"));
        if ($forms->error_text['ajax_temp_company_name']) $text[]=$forms->error_text['ajax_temp_company_name'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Name</td>";
		$text=array();
        $text[]=$forms->text("ajax_temp_name",$this->input->name,40,0,"",array("placeholder"=>"Optional"));
        if ($forms->error_text['ajax_temp_name']) $text[]=$forms->error_text['ajax_temp_name'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
	    $results[]="<tr>";
	    $text=array( $forms->select("ajax_temp_price_type",$database->user->constant->price_type,$this->input->price_type,FALSE,array("onchange"=>"jQuery('#ajax_temp_price_pct').val('');")) );
	    if ($forms->error_text['ajax_temp_price_type']) $text[]=$forms->error_text['ajax_temp_price_type'];
	    $results[]="<td>" . implode("<br>",$text) . "</td>";
	    $text=array($forms->text("ajax_temp_price_pct",($this->input->price_pct * 100),6,6,"decimal:2") . "%");
	    if ($forms->error_text['ajax_temp_price_pct']) $text[]="<b>" . $forms->error_text['ajax_temp_price_pct'] . "</b>";
	    $results[]="<td>" . implode(" ",$text) . "</td>";
	    $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Currency</td>";
        $currency=array();
        foreach ($menu->currency as $key => $obj) {
        	$currency[$key]=$obj->name;
        }
        $text=array($forms->select("ajax_temp_pricebook_currency_code",$currency,$this->input->currency_code,FALSE,array("onchange"=>"fn_currency_code();")));
        $text[]="<div id=currency_meta></div>";
		$results[]="<td>" . implode("",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Include quantity break?</td>";
        $results[]="<td>" . $forms->checkbox("qty_break",1,$this->input->qty_break) . "</td>";
        $results[]="</tr>";
        if ($database->registry->local->pricebook_mailform) {
	        $results[]="<tr>";
	        $results[]="<td>Output: " . $forms->select("output",array("Download","Email"),$this->input->output,FALSE)  . "</td>";
	        $text=array();
	        $text[]=$forms->text("ajax_temp_email",$this->input->email,60,0,"",array("placeholder"=>"Mandatory if email output"));
	        if ($forms->error_text['ajax_temp_email']) $text[]=$forms->error_text['ajax_temp_email'];
	        $results[]="<td>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
		}
        $results[]="<tr>";
        $results[]="<td>Message</td>";
        $results[]="<td>" . $forms->textarea("message",$this->input->message,3,60,array("placeholder"=>"Optional")) . "</td>";
        $results[]="</tr>";
	    $results[]="</table>";
		return $results;
    }
    function subcategory() {
    global $database, $forms;
	    $query_where=array();
	    $query_where[]=new query_where("subcategory.output","<>","smart");
        $query_where[]=new query_where("category.active","=",1);
	    $query_where[]=new query_where("subcategory.status","=","Active");
		$query_where[]=new query_where("inventory.pricing","not in",array("","a:0:{}"));
	    $query=array("select");
	    $query[]="category.id as 'category.id',";
	    $query[]="category.name as 'category.name',";
	    $query[]="subcategory.id as 'subcategory.id',";
	    $query[]="subcategory.name as 'subcategory.name',";
	    $query[]="count(*) as 'count'";
	    $query[]="from inventory";
	    $query[]="left join category on category.id=inventory.category_id";
	    $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
	    $query[]=$database->where($query_where);
	    $query[]="group by category.id,subcategory.id";
	    $query[]="order by category.name, subcategory.name";
	    $database->temp->query($query);
	    while ($database->temp->fetch = $database->temp->fetch_array()) {
	        $category=$database->temp->fetch['category.id'] . "\t" . $database->temp->fetch['category.name'];
	        if (!array_key_exists($category,$this->items)) $this->items[$category]=array();
	        $this->items[$category][$database->temp->fetch['subcategory.id']]=$database->temp->fetch['subcategory.name'] . " (" . number_format($database->temp->fetch['count']) . ")";
	    }
    	$results=array();
        $results[]=$forms->hidden("subcategory_id");
		if ($forms->error_text['subcategory_id']) $results[]="<p><b>" . $forms->error_text['subcategory_id'] . "</b></p>";
        $results[]="<p>" . $forms->checkbox("all",1,0,array("onclick"=>"jQuery('.subcategory_all').prop('checked',this.checked);")) . " All categories/subcategories?</p>";
        $results[]="<table class='noborder'>";
	    foreach ($this->items as $category => $items) {
	        list($category_id, $category_name)=explode("\t",$category);
	        $classname="category_{$category_id}_class";
	        $results[]="<tr>";
	        $results[]="<td>" . $forms->checkbox("category_{$category_id}",1,0,array("class"=>"subcategory_all","onclick"=>"jQuery('." . $classname . "').prop('checked',this.checked);")) . " {$category_name}</td>";
	        $text=array();
	        foreach ($items as $subcategory_id => $item) {
	            $text[]=$forms->checkbox(fn_base64(array("subcategory",$subcategory_id)), 1, in_array($subcategory_id,$this->input->subcategory),array("class"=>"subcategory_all {$classname}")) . " {$item}";
	        }
	        $results[]="<td class=newspaper2>" . implode("<br>",$text) . "</td>";
	        $results[]="</tr>";
	    }
	    $results[]="</table>";
		return $results;
    }
}