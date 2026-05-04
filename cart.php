<?php
require_once "scs_header.php";
require_once "classes/orderhd.php";
require_once "classes/orderln.php";
require_once "classes/configure.php";
require_once "classes/inventory.php";
$forms->title("Shopping Cart");
$forms->report['action']=$_POST['action'];
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	if (!$_SESSION['user']->ecommerce) die();
    $results=array();
    $results['json_code']=$_REQUEST['json_code'];
	$data=preg_split("/\t/",base64_decode($_REQUEST['json_code']));
    $results['constant']=$data[0];
    $results['sku']=$data[1];
    $results['quantity']=abs(fn_number($_REQUEST['json_quantity'],0));
    if (!$results['quantity']) {
        unset($menu->cart[ $results['sku'] ]);
		$results['status']=$results['sku'] . " removed from cart";
	  } else {
		$results['status']=cart_update($results['sku'],$results['quantity']);
	}
	$_SESSION['cart']=$menu->cart;
    $results['cart_size']=sizeof($menu->cart);
    $extended=0;
    $total_missing=FALSE;
    foreach ($menu->cart as $item) {
    	if ($item->price) {
        	$extended += $item->extended;
		  } else {
          	$total_missing=TRUE;
            break;
        }
    }
    if ($total_missing) {
    	$results['cart_extended']="Pricing available upon request.";
      } else {
    	$results['cart_extended']="$" . number_format($extended,2);
    }
    die( json_encode($results) );
}
if (!$_SESSION['user']->ecommerce) fn_url_redirect($menu->page->home);
switch ($forms->report['action']) {
  case "checkout":
	fn_url_redirect($menu->page->checkout);
  case "clear":
	$menu->cart=array();
    $forms->message[]="Shopping Cart Cleared";
    break;
  case "update":
    foreach ($_SESSION['cart'] as $sku => $item) {
        $field_quantity=fn_base64(array("quantity",$sku));
        $field_comment=fn_base64(array("comment",$sku));
        cart_update($sku,$_REQUEST[$field_quantity],$_REQUEST[$field_comment]);
    }
    $forms->message[]="Shopping Cart Updated";
	$_SESSION['cart']=$menu->cart;
}
$menu->head();
print $forms->message();
?>
<script>
function fn_action(action) {
    if (action=='clear') {
        if (!confirm('Clear Shopping Cart?')) return;
    }
    document.scs_form.action.value=action;
    document.scs_form.submit();
}
</script>
<?php
print $forms->open();
print $forms->hidden("action");
if (!sizeof($menu->cart)) {
	print "<p>Your shopping cart is empty</p>";
  } else {
	print $database->orderln->cart(array("entry"=>TRUE));
}
print $forms->close();
$menu->copyright();
function cart_update($sku, $quantity) {
global $database, $forms, $menu;
	$menu->configure=new configure_class($sku,$quantity);
    if (!$menu->configure->meta->source) return "{$sku} invalid";
    $item=( (array_key_exists($sku,$menu->cart)) ? $menu->cart[$sku] : new orderln_data_class($sku) );
    $item->description=$database->category->data->name . ": " . $database->subcategory->data->name;
	if (func_num_args()==3) $item->comment=func_get_arg(2);
    $item->quantity=$quantity;
    $item->price=$menu->configure->results->price;
    $item->extended=$menu->configure->results->extended;
	$menu->cart[$sku]=$item;
	return "{$sku} cart updated";
}
?>