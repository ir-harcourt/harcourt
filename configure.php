<?php
require_once "scs_header.php";
require_once "classes/configure.php";
/*
Smart
HLSM-040A-0P-0600-3
HLF-5
L40-10 3/8-174-SS
L40-10_3/8-174-SS

Multiple break
SF32-34 5/16 .3125
*/
$database->user->access("Internal");
$obj=new local_class();

class local_class {
    function scs_table_version() {
        $results=array();
        $results['08/22/2024']="Initial release";
        return $results;
    }
    function __construct() {
        global $database, $forms, $menu;
        $forms->title("Pricing & CAD (" . key($this->scs_table_version()) . ")");
        $this->input();
        $menu->head();
        $forms->message();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action","list");
        $this->tabs=new jquery_tab_class('standard');
        if (isset($_SESSION['scscpq_wp'])) $this->tabs->meta->options->lineheight="auto";
        $this->tabs->item("Enter",$this->html_enter());
        if ( ($this->input->action) && ($this->configure->meta->source) ) {
            $this->tabs->item("Quantity Break",$this->html_break());
            $this->tabs->item("CAD", $this->html_cad());
            if ($this->input->debug) {
                $this->tabs->item("Data", $this->html_data());
                $this->tabs->item("Smart Pricing", $this->html_pricing());
            }
        }
        if ($this->input->debug) {
            $this->tabs->item("Constants", $this->html_constant());
            $this->tabs->item("Meta", $this->html_meta());
        }
        $this->tabs->output();
        print $forms->close();
        $menu->copyright();
    }
    function input() {
        global $database, $forms;
        $this->input=new stdClass();
        $this->input->action=$_POST['action'];
        $this->input->debug=$_POST['debug'];
        $this->input->sku=trim(strtoupper($_POST['sku']));
        $this->input->quantity=fn_number($_POST['quantity'],0);
        if ($this->input->quantity < 1) $this->input->quantity=1;
        switch ($this->input->action) {
          case "":
            $this->input->price_type=$_SESSION['user']->price_type;
            $this->input->price_pct=$_SESSION['user']->price_pct;
            $this->input->currency_code=$_SESSION['user']->currency_code;
            break;
          default:
            if (!strlen($this->input->sku)) $forms->error("sku","Cannot be blank");
            $this->input->price_type=$_POST['price_type'];
            $this->input->price_pct=fn_number($_POST['price_pct'] . "%",2);
            switch ($this->input->price_type) {
              case "":
                $this->input->price_pct=0;
                break;
              case "base":
                if ($this->input->price_pct) $forms->error("price_pct","Must be 0% for base price");
                break;
              case "discount":
                if ( ($this->input->price_pct <= 0) || ($this->input->price_pct >= 1) ) $forms->error("price_pct","Discount must be > 0% and < 100%");
                break;
              case "plus":
                if ( ($this->input->price_pct <= 0) || ($this->input->price_pct >= 5) ) $forms->error("price_pct","Price plus must be > 0% and < 500%");
                break;
            }
            $this->input->currency_code=$_POST['currency_code'];
            $options=array();
            $options['override']=TRUE;
            $options['price_type']=$this->input->price_type;
            $options['price_pct']=$this->input->price_pct;
            $options['currency_code']=$this->input->currency_code;
            $this->configure=new configure_class($this->input->sku,$this->input->quantity,$options);
            if ($this->configure->meta->error) $forms->error('sku',$this->configure->meta->error);
        }
    }
    function html_enter() {
        global $database, $forms, $menu;
        $results=array();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>SKU</td>";
        $text=array($forms->textarea("sku",$this->input->sku,3,90,array("placeholder"=>"Enter SKU")));
        if ($forms->error_text['sku']) $text[]=$forms->error_text['sku'];
        $results[]="<td width='80%'>" . implode("<br>",$text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>Quantity</td>";
        $results[]="<td>" . $forms->text("quantity",$this->input->quantity,6,6,"decimal:0") . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $text=array( $forms->select("price_type",$database->user->constant->price_type,$this->input->price_type,FALSE,array("onchange"=>"$('#price_pct').val('');")) );
        if ($forms->error_text['price_type']) $text[]=$forms->error_text['price_type'];
        $results[]="<td>" . implode("<br>",$text) . "</td>";
        $text=array($forms->text("price_pct",($this->input->price_pct * 100),6,6,"decimal:2") . "%");
        if ($forms->error_text['price_pct']) $text[]="<b>" . $forms->error_text['price_pct'] . "</b>";
        $results[]="<td>" . implode(" ",$text) . "</td>";
        $results[]="</tr>";
        if ($database->user->access("Administrator",FALSE)) {
            $results[]="<tr>";
            $results[]="<td>Currency</td>";
            $results[]="<td>" . $database->currency->select($this->input->currency_code,array("priced"=>TRUE,"blank"=>FALSE)) . "</td>";
            $results[]="</tr>";
            $results[]="<tr>";
            $results[]="<td>Debug?</td>";
            $results[]="<td>" . $forms->checkbox("debug",1,$this->input->debug) . "</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        $results[]="<p>" . $forms->submit("Enter",array("class"=>"configure_button")) . "</p>";
        switch (TRUE) {
          case (!$this->input->action):
          case (sizeof($forms->error)):
            break;
          case ($this->configure->meta->source == "smart"):
            $results[]=implode("",$this->html_price_smart());
            break;
          default:
            $results[]=implode("", $this->html_price_static());
        }
        return $results;
    }
    function html_price_smart() {
        global $database, $forms;
        if (!sizeof($forms->error)) $this->tabs->landing_tab();
        $results=array();
        $text=array();
        $text[]="Part number: " . $this->configure->meta->sku_url;
        $text[]="Category: " . $database->category->data->name;
        $text[]="Subcategory: " . $database->subcategory->data->name;
        switch (TRUE) {
          case ($database->subcategory->data->new_date >= $database->inventory->new_date()):
            $text[]="New Series!";
            break;
        }
        $results[]="<p>" . implode("<br>\n",$text) . "</p>";
        $results[]="<table class='standard configure'>";
        $results[]="<tr>";
        $results[]="<td width='66%'>";
        $results[]=$this->configure->output_results();
        $results[]="</td>";
        $results[]="<td width='33%'>";
        switch (TRUE) {
          case ($this->configure->module->pricing->error):
            $results[]="<p>" . $this->configure->module->pricing->error . "</p>";
            break;
          case ($this->configure->pricing->cf):
          case (!$this->configure->meta->price_type):
          case (!$this->configure->results->price):
            $results[]="<p>" . $database->registry->local->configure_contact . "</p>";
            break;
          default:
            $results[]=$this->configure->currency_name();
            $results[]="<table class='noborder standard'>";
            foreach ($this->configure->module->pricing->item as $field => $obj) {
                $results[]="<tr>";
                $text=array($obj->name);
                if ($obj->comment) $text[]="<li>" . $obj->comment . "</li>";
                $results[]="<td width='60%'>" . implode("<br>",$text) . "</td>";
                $results[]="<td width='40%' class=right>" . $this->configure->currency_price($obj->net_price) . "</td>";
                $results[]="</tr>";
            }
            $results[]="<tr>";
            $results[]="<td width='60%'><b>Price</b></td>";
            $results[]="<td width='40%' class=right><b>" . $this->configure->currency_price($this->configure->results->price) . "</b></td>";
            $results[]="</tr>";
            if ($this->configure->meta->quantity > 1) {
                $results[]="<tr>";
                $results[]="<td><b>" . number_format($this->configure->meta->quantity) . " @ " . $this->configure->currency_price($this->configure->results->price) . "</b></td>";
                $results[]="<td class=right><b>" . $this->configure->currency_price($this->configure->results->extended) . "</b></td>";
                $results[]="</tr>";
            }
            $results[]="</table>";
            if ($this->configure->meta->leadtime) $results[]="<p>Typical lead time: " . $this->configure->meta->leadtime . "</p>";
        }
        $results[]="</td>";
        $results[]="</tr>";
        $results[]="</table>";
        return $results;
    }
    function html_price_static() {
        global $database, $forms;
        if (!sizeof($forms->error)) $this->tabs->landing_tab();
        $results=array();
        $text=array();
        $text[]="Part number: " . $database->inventory->data->sku;
        $text[]="Category: " . $database->category->data->name;
        $text[]="Subcategory: " . $database->subcategory->data->name;
        switch (TRUE) {
          case ($database->inventory->data->new_date >= $database->inventory->new_date()):
            $text[]="New Item!";
            break;
          case ($database->subcategory->data->new_date >= $database->inventory->new_date()):
            $text[]="New Series!";
            break;
        }
        $results[]="<p>" . implode("<br>",$text) . "</p>";
        $results[]="<table class='standard configure'>";
        $results[]="<tr>";
        $text=array("<b>Specifications:</b>");
        foreach ($database->subcategory->data->parameters as $parameter) {
            $text[]=$parameter->name . ": <b>" . $database->inventory->data->parameter[$parameter->id] . "</b>";
        }
        $results[]="<td width='66%'>" . implode("<br>",$text) . "</td>";
        $results[]="<td width='33%'>";

        switch (TRUE) {
          case ($this->configure->meta->error):
          case (!$this->configure->meta->access):
          case ($this->configure->results->error):
            $results[]="<p>" . $database->registry->local->configure_contact . "</p>";
            break;
          default:
            $results[]=$this->configure->currency_name();
            $results[]="<table class='standard noborder'>";
            $results[]="<tr>";
            $results[]="<td width='60%'>Price</td>";
            $results[]="<td width='40%' class=right>" . $this->configure->currency_price($this->configure->results->price) . "</td>";
            $results[]="</tr>";
            if ($this->input->quantity > 1) {
                $results[]="<tr>";
                $results[]="<td width='60%'>" . number_format($this->input->quantity) . " @ " . $this->configure->currency_price($this->configure->results->price) . "</td>";
                $results[]="<td width='40%' class=right>" . $this->configure->currency_price($this->configure->results->extended) . "</td>";
                $results[]="</tr>";
            }
            $results[]="</tr>";
            $results[]="</table>";
            $results[]="</td>";
            $results[]="</tr>";
            $results[]="</table>";
        }
        if ($this->configure->meta->leadtime) $results[]="<p>Typical lead time: " . $this->configure->meta->leadtime . "</p>";
        return $results;
    }
    function html_break() {
        global $database, $forms, $menu;
        if ( ($this->configure->meta->error) || ($this->configure->meta->source != "inventory") ) return;
        $results=array();
        $currency_list=array();
        $currency_list[]=$this->configure->meta->currency_code;
        if ($this->configure->meta->currency_code != "USD") $currency_list[]="USD";
        $results[]="<table class='border'>";
        $results[]="<th rowspan=2>Quantity Break</th>";
        foreach ($currency_list as $currency_code) {
            $results[]="<th colspan=2>" . $menu->currency[$currency_code]->plural . "</th>";
        }
        $results[]="</tr>";
        $results[]="<tr>";
        foreach ($currency_list as $currency_code) {
            $results[]="<th>Price</th>";
            $results[]="<th>MSRP</th>";
        }
        $results[]="</tr>";
        foreach ($this->configure->results->qty_break as $qty_break => $obj) {
            $results[]="<tr>";
            $results[]="<td class=right>" . number_format($qty_break) . "</td>";
            foreach ($currency_list as $currency_code) {
                $results[]="<td class=right>" . number_format($obj[$currency_code]['price'],2) . "</td>";
                $results[]="<td class=right>" . number_format($obj[$currency_code]['msrp'],2) . "</td>";
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_cad() {
        global $database, $forms, $menu;
        $options=array();
        $options['prj']=$this->configure->meta->prj;
        $options['partFixed']="false";
        $options['languageIso']=strtolower($_SESSION['user']->language_code);
        $options['hidePortlets']=array("briefcase", "searchHeader");
        $url=$database->cad->url($options);
        return $forms->iframe("",$url,array("id"=>"scscpq_configurator_id","height"=>"660px","onload"=>"scscpq_initial();"),FALSE);
    }
    function html_data() {
        global $database, $forms;
        if ($this->configure->meta->source != "smart") return;
        $results=array();
        $results[]="<table class='standard border'>";
        foreach ($this->configure->module->data as $key => $value) {
            $results[]="<tr><td width='30%'>{$key}</td><td width='70%'>{$value}</td></tr>";
        }
        $results[]="</table>";
        return $results;
    }
    function html_pricing() {
        global $database, $forms;
        if ($this->configure->meta->source != "smart") return;
        $results=array();
        $results[]="<table class='standard border'>";
        $results[]="<tr>";
        $results[]="<th rowspan=2>Record Type</th>";
        $results[]="<th rowspan=2>Name</th>";
        $results[]="<th rowspan=2>Query</th>";
        $results[]="<th rowspan=2>Fields</th>";
        $results[]="<th colspan=10>Base Price</th>";
        $results[]="</tr>";
        $results[]="<tr>";
        for ($i=0; $i < 5; $i++) {
            $results[]="<th colspan=2>Price {$i}</th>";
        }
        $results[]="</tr>";
        foreach ($this->configure->module->pricing->item as $record_type => $item) {
            switch (TRUE) {
              case ( (!$item->required) && (!$item->found) ):
                $bg=$forms->background->khaki;
                break;
              case ( (!$item->required) && ($item->found) ):
                $bg=$forms->background->darkgrey;
                break;
              case ( ($item->required) && (!$item->found) && ($item->conditional) ):
                $bg=$forms->background->orange;
                break;
              case ( ($item->required) && (!$item->found) ):
                $bg=$forms->background->orangered;
                break;
              default:
                $bg=$forms->background->limegreen;
            }
            $results[]="<tr {$bg}>";
            $results[]="<td>{$record_type}</td>";
            $results[]="<td>" . $item->name . "</td>";
            $results[]="<td>" . $item->query . "</td>";
            $results[]="<td>" . implode(", ",array_keys($item->field)) . "</td>";
            for ($i=0; $i < 5; $i++) {
                if (strlen($item->price_name[$i])) {
                    $results[]="<td class=right>" . number_format($item->price[$this->configure->meta->currency_code]['price'][$i],4) . "</td>";
                    $results[]="<td>" . $item->price_name[$i] . "</td>";
                  } else {
                    $results[]="<td colspan=2>&nbsp;</td>";
                }
            }
            $results[]="</tr>";
        }
        $results[]="</table>";
        $legend=array();
        $legend["Required / Match"]=$forms->background->limegreen;
        $legend["Conditional / Not Found"]=$forms->background->orange;
        $legend["Not Required / Not Found"]=$forms->background->khaki;
        $legend["Not Required / Found"]=$forms->background->darkgrey;
        $legend["Required / Not Found"]=$forms->background->orangered;
        $text=array();
        foreach ($legend as $key => $value) {
            $text[]="&nbsp;&nbsp;<span $value>&nbsp;&nbsp;$key&nbsp;&nbsp;</span>";
        }
        $results[]="<p class='standard center'><b>Legend:</b>" . implode("",$text) . "</p>";
        return $results;
    }
    function html_constant() {
        global $database, $forms;
        if ($this->configure->meta->source != "smart") return;
        $results=array();
        $results[]="<pre>";
        $results[]=print_r($this->configure->module->constant,TRUE);
        $results[]="</pre>";
        return $results;
    }
    function html_meta() {
        global $database, $forms;
        $results=array();
        $results[]="<p>Configure meta</p>";
        $results[]="<table class='standard border'>";
        $results[]="<tr><th width='20%'>Field</th><th width='80%'>Value</th></tr>";
        foreach ($this->configure->meta as $key => $value) {
            $results[]="<tr>";
            $results[]="<td>{$key}</td>";
            switch (TRUE) {
              case (is_object($value)):
              case (is_array($value)):
                $text=parse_object($value);
                break;
              default:
                $text=$value;
            }
            $results[]="<td>{$text}</td>";
            $results[]="</tr>";
        }
        $results[]="</table>";
        if (strlen($this->configure->meta->series)) {
            $results[]="<p>Subcategory meta</p>";

            $results[]="<table class='standard border'>";
            $results[]="<tr><th width='20%'>Field</th><th width='80%'>Value</th></tr>";
            foreach ($this->configure->module->meta as $key => $value) {
                $results[]="<tr>";
                $results[]="<td>{$key}</td>";
                switch (TRUE) {
                  case (is_object($value)):
                  case (is_array($value)):
                    $text=parse_object($value);
                    break;
                  default:
                    $text=$value;
                }
                $results[]="<td>{$text}</td>";
                $results[]="</tr>";
            }
        }
        $results[]="<p><b>Trace</b><br>" . implode("<br>", $this->configure->trace) . "</p>";
        return $results;
    }
    function js() {
        return <<< EOT
<script>


</script>
EOT;
    }

}
?>