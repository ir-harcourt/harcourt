<?php
$database->orderln = new orderln_class();
class orderln_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['08/26/2019']="Add smart SKU";
        $results['08/22/2014']="Initial release";
        return $results;
    }
	function __construct() {
    	$this->data=new orderln_data_class();
        $this->fetch=array();
    	$this->meta=new database_meta_class();
        $this->constant=new orderln_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new orderln_data_class();
        $this->data->id=$this->fetch['id'];
	    $this->data->order_id=$this->fetch['order_id'];
	    $this->data->line_no=$this->fetch['line_no'];
	    $this->data->sku=$this->fetch['sku'];
	    $this->data->description=$this->fetch['description'];
	    $this->data->quantity=floatval($this->fetch['quantity']);
	    $this->data->price=floatval($this->fetch['price']);
	    $this->data->extended=floatval($this->fetch['extended']);
	    $this->data->comment=$this->fetch['comment'];
    }
    function read($id) {
        $query =
            "select * from orderln " .
            "where id=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
        	$this->fetch(TRUE);
		  } else {
			$this->data=new orderln_data_class();
		}
        $this->free_result();
    }
    function update($update=FALSE) {
    	$fields=array();
	    $fields[]="id=" . fn_escape($this->data->id,FALSE);
	    $fields[]="order_id=" . fn_escape($this->data->order_id,FALSE);
	    $fields[]="line_no=" . fn_escape($this->data->line_no,FALSE);
	    $fields[]="sku=" . fn_escape($this->data->sku);
	    $fields[]="description=" . fn_escape($this->data->description);
	    $fields[]="quantity=" . fn_escape($this->data->quantity,FALSE);
 	    $fields[]="price=" . fn_escape($this->data->price,FALSE);
	    $fields[]="extended=" . fn_escape($this->data->extended,FALSE);
	    $fields[]="comment=" . fn_escape($this->data->comment);
        $query=array();
    	if ($update) {
          	$query[]="update orderln set";
            $query[]=implode(",\n",$fields);
            $query[]="where id=" . fn_escape($this->data->id);
		  } else {
          	$query[]="insert into orderln set";
            $query[]=implode(",\n",$fields);
        }
		$this->query($query);
		if ((!$this->data->id) && (!$this->meta->error)) $this->data->id = $this->insert_id();
    }
    function delete($id) {
	    $query="delete from orderln where id=" . fn_escape($id);
		$this->query($query);
    }
    function cart($options=array()) {
	global $database, $forms, $menu;
		switch (TRUE) {
          case (array_key_exists("cart",$options)):
          	$database->orderhd->currency_code();
            break;
          case (array_key_exists("order_id",$options)):
			$otpions['cart']=array();
	        $query=array("select");
	        $query[]="orderln.* from orderln";
	        $query[]="where orderln.order_id = " . fn_escape($options['order_id']);
	        $query[]="order by orderln.line_no";
			$this->query($query);
	        while ($this->fetch = $this->fetch_array() ) {
	            $this->fetch();
                $otpions['cart'][$this->data->sku]=$this->data;
			}
			$this->free_result();
            break;
          default:
			$otpions['cart']=$menu->cart;
          	$database->orderhd->currency_code();
		}
        if (!array_key_exists("entry",$options)) $options['entry']=FALSE;
        if (!sizeof($otpions['cart'])) return;
        $total_amount=0;
        $total_missing=FALSE;
        $img_query=array();
        $img_query['width']=$database->registry->local->thumbnail;
        $img_query['height']=$database->registry->local->thumbnail;
        $results[]="<br>";
        $colspan=4;
        $results[]="<table class='cart' id='scs_cart'>";
        $results[]="<tr>";
        if ($options['entry']) {
        	$results[]="<th>Delete</th>";
            $colspan++;
		}
        $results[]="<th>Part #</th>";
        $results[]="<th colspan=2>Description</th>";
        $results[]="<th>Quantity</th>";
        $results[]="<th>Unit Price</th>";
        $results[]="<th>Extended</th>";
        $results[]="</tr>";
        foreach ($otpions['cart'] as $this->data) {
			$query=
            	"select subcategory.thumbnail_url from inventory \n" .
                "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
                "where inventory.sku=" . fn_escape($this->data->sku);
			$database->temp->query($query);
            $database->temp->fetch();
			$field_row=str_replace("=","",fn_base64(array("row",$this->data->sku)));
			$field_quantity=fn_base64(array("quantity",$this->data->sku));
			$field_comment=fn_base64(array("comment",$this->data->sku));
	        $results[]="<tr style='border-bottom: 2px solid #444;' id=$field_row>";
			if ($options['entry']) $results[]="<td class=center>" . $forms->button("X",array("class"=>"cart_delete","onclick"=>"cart_delete(" . fn_escape($this->data->sku) . "," . fn_escape($field_row) . ");")) . "</td>";
	        $results[]="<td class=nobr>" . $this->data->sku . "</td>";
	        $results[]="<td class=center>" . $forms->img($database->temp->fetch['thumbnail_url'],$img_query) . "</td>";
            $content=array();
            $content[]=str_replace("\n","<br>",$this->data->description);
            switch (TRUE) {
              case ($options['entry']):
				$content[]="<br>" . $forms->text($field_comment,$this->data->comment,40,60,"",array("placeholder"=>"Comment (optional)"));
                break;
              case ($this->data->comment):
				$content[]="<br>Comment: " . $this->data->comment;
                break;
			}
			$results[]="<td>" . implode("\n",$content) . "</td>";
            $results[]="</td>";
            if ($options['entry']) {
				$results[]="<td class=center>" . $forms->text($field_quantity,$this->data->quantity,6,8,"decimal:0") . "</td>";
			  } else {
				$results[]="<td class=right>" . number_format($this->data->quantity) . "</td>";
			}
            if ($this->data->price) {
            	$total_amount += $this->data->extended;
	            $results[]="<td class=right>" . $database->orderhd->currency_price($this->data->price) . "</td>";
	            $results[]="<td class=right>" . $database->orderhd->currency_price($this->data->extended) . "</td>";
			  } else {
              	$total_missing=TRUE;
              	$results[]="<td class=center colspan=2>Pricing available upon request</td>";
			}
	        $results[]="</tr>";
        }
        $results[]="<tr>";
        $results[]="<td colspan=$colspan>" . $database->orderhd->currency_name(FALSE) . "</td>";
        if ($total_missing) {
        	$results[]="<td class=center colspan=2>Pricing available upon request</td>";
		  } else {
	        $results[]="<td class=right>Total:</td>";
	        $results[]="<td class=right><span id=cart_extended>" . $database->orderhd->currency_price($total_amount) . "</span></td>";
		}
        $results[]="</tr>";
		$results[]="</table>";
        if ($options['entry']) {
        	$results[]="<table class='noborder'>";
	        $results[]=$forms->caption();
	        $results[]="</tr>";
        	$buttons=array();
	        $buttons[]=$forms->button($menu->language("cart_update"),array("onclick"=>"fn_action('update');","class"=>"cart_button"));
            $buttons[]=$forms->button($menu->language("cart_clear"),array("onclick"=>"fn_action('clear');","class"=>"cart_button"));
	        $results[]="<td class=left>" . implode("\n",$buttons) . "</td>";
	        $results[]="<td class=right>" . $forms->button($menu->language("cart_checkout") . " >>",array("onclick"=>"fn_action('checkout');","class"=>"cart_button")) . "</td>";
	        $results[]="</tr>";
	        $results[]="</table>";
        }
        return implode("\n",$results);
    }
}
class orderln_data_class {
	var $id=0;
	var $order_id=0;
	var $line_no=0;
	var $sku;
	var $description;
	var $quantity=0;
	var $price=0;
	var $extended=0;
	var $comment;
    function __construct($sku="") {
		$this->sku=$sku;
    }
}
class orderln_constant_class {

}
/*
CREATE TABLE `orderln` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `line_no` int(6) unsigned NOT NULL DEFAULT '0',
  `sku` varchar(100) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT '0',
  `price` decimal(11,5) NOT NULL DEFAULT '0.00000',
  `extended` decimal(11,2) NOT NULL DEFAULT '0.00',
  `comment` mediumtext,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `line_key` (`order_id`,`line_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Order Line Items (08/22/2014)'
*/
?>