<?php
$override_landing_page=TRUE;
include_once "/Webpages/Header_Footer/scs_header.php";
include_once "classes/category.php";
include_once "classes/subcategory.php";
include_once "classes/document.php";
include_once "classes/inventory.php";
include_once "classes/inventory_xref.php";
include_once "classes/log.php";
switch (TRUE) {
  case (!$_SESSION['user']->id):
  case ($_SESSION['user']->status == "Pending"):
  case ($_SESSION['user']->status == "Denied"):
  case (!in_array(149,$_SESSION['document'])):
	fn_url_redirect($menu->page['home']);
}
$database->connect();
$menu->title="Inventory Catalog";
$menu->search=TRUE;
$report['action']=$_REQUEST['action'];
$report['page']=intval($_REQUEST['page']);
if (!$report['page']) $report['page']=1;
$report['item']=intval($_REQUEST['item']);
$report['initial']=intval($_REQUEST['initial']);
$report['search_term']=trim($_REQUEST['search_term']);
$report['category']=intval($_REQUEST['category']);
$report['subcategory']=intval($_REQUEST['subcategory']);
$report['source']=$_REQUEST['source'];

switch (TRUE) {
  case ($_COOKIE['email']):
	break;
  case ($report['action'] != "email"):
  	$_SESSION['catalog']=$report;
	break;
  default:
	$database->document->login_verify();
    if ($_COOKIE['email']) $report=$_SESSION['catalog'];
}

switch (TRUE) {
  case (!$_COOKIE['email']):
	break;
  case ($report['action']=="list"):
	$query_where=array();
    $query_where[]=new query_where("inventory.active","=","1");
	if ($report['category']) $query_where[]=new query_where("inventory.category_id","=",$report['category']);
	if ($report['subcategory']) $query_where[]=new query_where("inventory.subcategory_id","=",$report['subcategory']);
	if ($report['item']) {
      	$query_limit=$database->item_limit($report['item']);
	  } else {
      	$query_limit="";
	}
	$query =
    	"select \n" .
        "category.name as 'category_name', \n" .
        "category.documents as 'category_documents', \n" .
        "category.image_url as 'category_image_url', \n" .
        "subcategory.name as 'subcategory_name', \n" .
        "subcategory.documents as 'subcategory_documents', \n" .
        "subcategory.image_url as 'subcategory_image_url', \n" .
        "subcategory.sketch_url as 'subcategory_sketch_url', \n" .
        "inventory.* from inventory \n" .
        "left join category on category.id=inventory.category_id \n" .
        "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
		$database->where($query_where) .
        "order by category.name,subcategory.name,inventory.sku" .
        $query_limit;
	$database->inventory->query($query,TRUE);
    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
		$database->inventory->fetch();
		$search_list[$database->inventory->data->category_id][$database->inventory->data->subcategory_id][]=new catalog_inventory_class($database->inventory);
    }
	$database->inventory->free_result();
    break;
  case ($report['action']=="search"):
	$menu->search_term=$report['search_term'];
	$search_array=split(" ",str_replace(array("+","-",">","<","(",")","*","~","^","!","&",",","'","\""),"",$menu->search_term));
    if ((!$menu->search_term) || (!$search_array[0])) break;
    $menu->messages[]="Search term: <b>" . $menu->search_term . "</b>\n";
	if (($report['source']) && ($report['item'])) {
      	$query_limit=$database->item_limit($report['item']);
	  } else {
      	$query_limit="";
	}
	$query =
    	"select \n" .
        "category.name as 'category_name', \n" .
        "category.documents as 'category_documents', \n" .
        "category.image_url as 'category_image_url', \n" .
        "subcategory.name as 'subcategory_name', \n" .
        "subcategory.documents as 'subcategory_documents', \n" .
        "subcategory.image_url as 'subcategory_image_url', \n" .
        "subcategory.sketch_url as 'subcategory_sketch_url', \n" .
        "inventory.* from inventory \n" .
        "left join category on category.id=inventory.category_id \n" .
        "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
        "left join inventory_xref on inventory_xref.sku=inventory.sku \n" .
        "where inventory.active=1 \n" .
        " and ( \n" .
        "  inventory.sku like " . fn_escape($menu->search_term . "%") . " \n" .
        "  or inventory_xref.competitor_sku like " . fn_escape($menu->search_term . "%") . " \n" .
        ") \n" .
        "group by inventory.sku \n" .
        "order by category.name,subcategory.name,inventory.sku \n" .
        $query_limit;
	$database->inventory->query($query,TRUE);
    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
		$database->inventory->fetch();
		$search_list[$database->inventory->data->category_id][$database->inventory->data->subcategory_id][]=new catalog_inventory_class($database->inventory);
    }
	$database->inventory->free_result();
    if ($database->inventory->meta->rows) {
		$report['source']="inventory";
	  } else {
		$search_term="";
        foreach ($search_array as $key => $value) {
        	if ($key) $search_term .= " ";
            $search_term .= "+" . $value . "*";
        }
        $query_where=array();
        $query_where[]=new query_where("inventory.active","=",1);
        if ($report['subcategory']) $query_where[]=new query_where("inventory.subcategory_id","=",$report['subcategory']);
	    if ($report['item']) {
	        $query_limit=$database->item_limit($report['item']);
	      } else {
	        $query_limit="";
	    }
	    $query =
	        "select \n" .
	        "category.name as 'category_name', \n" .
	        "category.documents as 'category_documents', \n" .
	        "category.image_url as 'category_image_url', \n" .
	        "subcategory.name as 'subcategory_name', \n" .
	        "subcategory.documents as 'subcategory_documents', \n" .
	        "subcategory.image_url as 'subcategory_image_url', \n" .
	        "subcategory.sketch_url as 'subcategory_sketch_url', \n" .
	        "match(category.name,category.documents) against (" . fn_escape($search_term) . " IN BOOLEAN MODE) as category_match, \n" .
	        "match(subcategory.name,subcategory.search_words,subcategory.documents) against (" . fn_escape($search_term) . " IN BOOLEAN MODE) as subcategory_match, \n" .
            "inventory.* \n" .
	        "from inventory \n" .
	        "left join category on category.id=inventory.category_id \n" .
	        "left join subcategory on subcategory.id=inventory.subcategory_id \n" .
			$database->where($query_where) .
            "having category_match > 0 \n" .
            " or subcategory_match > 0 \n" .
	        "order by category.name,subcategory.name,inventory.sku \n" .
			$query_limit;
	    $database->inventory->query($query,TRUE);
	    while ($database->inventory->fetch = mysql_fetch_array($database->inventory->meta->handle)) {
			$database->inventory->fetch();
			$search_list[$database->inventory->data->category_id][$database->inventory->data->subcategory_id][]=new catalog_inventory_class($database->inventory);
	    }
	    $database->inventory->free_result();
		if ($database->inventory->meta->rows) $report['source']="search";
	}
    if ($database->inventory->meta->rows) {
    	$menu->messages[]=number_format($database->inventory->meta->found_rows) . " item(s) found";
	  } else {
    	$menu->messages[]=" No items found";
	}
    break;
}
if (($report['action']=="search") && ($report['initial'])) $database->log->create("Search",$menu->search_term);
$menu->head();
$menu->message();
?>
<script langauge=javascript>
function configurator(prj,email) {
	obj=document.getElementById('configurator_iframe');
	obj.src="http://www.harcourtind.com/psconfig/v1/harcourt2/configurator.php?uemail=" + email + "&prj=" + prj;
    obj.style.height=550;
    obj.focus();
}
</script>
<?php
if (!$_COOKIE['email']) $database->document->login_document();
print "<form name=scs_form method=post action='" . $_SERVER['PHP_SELF'] . "'>\n";
print "<input type=hidden name=action>\n";
if (sizeof($search_list)) $category_id=key($search_list);
if ($category_id) $subcategory_id=key($search_list[$category_id]);
$url_query_base=array();
$url_query_base['action']=$report['action'];
$url_query_base['search_term']=$menu->search_term;
$url_query_base['source']=$report['source'];
$url_query_base['category']=$report['category'];
$url_query_base['subcategory']=$report['subcategory'];
switch (TRUE) {
  case (!$_COOKIE['email']):
  	break;
  case (!sizeof($search_list)):
    $url_query=array();
	$url_query['action']="list";
	$query =
    	"select \n" .
        "count(*) as 'count', \n" .
        "category.* \n" .
        "from inventory \n" .
        "left join category on category.id=inventory.category_id \n" .
        "where inventory.active='1' \n" .
        "group by inventory.category_id \n" .
        "order by category.name";
    $database->category->query($query);
    $catalog_list=array();
    while ($database->category->fetch = mysql_fetch_array($database->category->meta->handle)) {
    	$database->category->fetch();
		$catalog_item=new catalog_list_class();
        $catalog_item->count=$database->category->fetch['count'];
        $catalog_item->id=$database->category->data->id;
        $catalog_item->name=$database->category->data->name;
        $catalog_item->image_url=$database->category->data->image_url;
		$url_query['category']=$database->category->data->id;
        $catalog_item->url($url_query);
        $catalog_list[]=$catalog_item;
    }
    $database->category->free_result();
    catalog_list($catalog_list);
    break;
  case ($report['item']):
	$database->category->read($database->inventory->data->category_id);
	$database->subcategory->read($database->inventory->data->subcategory_id);
	print "<table class='large noborder'>\n";
    print "<tr>\n";
    if ($database->subcategory->data->sketch_url) {
    	$image_url=$database->subcategory->data->sketch_url;
	  } else {
    	$image_url=$database->subcategory->data->image_url;
	}
    print "<td width=400>";
    print $menu->image($image_url,$database->subcategory->data->name,400);
	print "</td>\n";
    print "<td width=50%><p>";
    print "Part number : <b>" . $database->inventory->data->sku . "</b>\n";
    if ($database->inventory->data->name) print "<br>" . $database->inventory->data->sku;
    print "<br>Category : <b>" . $database->category->data->name . "</b>\n";
    print "<br>Subcategory : <b>" . $database->subcategory->data->name . "</b>\n";
    print "</p>\n";
    if ($_SESSION['user']->status == "Administrator") {
		$query=
        	"select * from inventory_xref \n" .
            "where sku=" . fn_escape($database->inventory->data->sku) . " \n" .
            "order by competitor";
		$database->inventory_xref->query($query);
        if ($database->inventory_xref->meta->rows) {
			print "<table class='noborder'>\n";
            print "</tr>\n";
            print "<td><b>Competitor</b></td>\n";
            print "<td><b>Part#</b></td>\n";
            print "</tr>\n";
	        while ($database->inventory_xref->fetch = mysql_fetch_array($database->inventory_xref->meta->handle)) {
	            $database->inventory_xref->fetch();
                print "<tr>\n";
                print "<td>" . $database->inventory_xref->data->competitor . "</td>\n";
                print "<td>" . $database->inventory_xref->data->competitor_sku . "</td>\n";
                print "</tr>\n";
	        }
			$database->inventory_xref->free_result();
			print "</table>\n";
        }
    }
    if ($database->inventory->data->prj) {
      	$prj=$database->inventory->data->prj;
	  } else {
      	$prj=$database->subcategory->data->prj;
	}
	if ($prj) print "<p><input type=button value='Configurator/CAD' onclick=\"javascript:configurator('$prj','" . $_COOKIE['email'] . "');\"></p>\n";
    print "</td>\n";
    print "<td width=50%><b>Specifications</b>\n";
    $parameters=array();
    if (sizeof($database->subcategory->data->parameters)) {
    	foreach ($database->subcategory->data->parameters as $key => $value) {
        	if ($database->inventory->data->parameters[$key]) print "<br>$value: <b>" . $database->inventory->data->parameters[$key] . "</b>\n";
        }
    }
    print "</td>\n";
    print "</tr>\n";
    print "</table>\n";
    if (($report['item']) && ($database->inventory->meta->found_rows > 1)) {
		$url_query=$url_query_base;
	    $nav_url=array();
        if ($report['item'] != 1) {
        	$url_query['item']=1;
        	$nav_url['first']=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query);
        	$url_query['item']=($report['item'] - 1);
        	$nav_url['previous']=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query);
		}
		$nav_url['return']=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query_base);
        if ($report['item'] != $database->inventory->meta->found_rows) {
        	$url_query['item']=($report['item'] + 1);
        	$nav_url['next']=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query);
        	$url_query['item']=$database->inventory->meta->found_rows;
        	$nav_url['last']=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query);
		}
        $menu->nav_field($nav_url,$report['item'],$database->inventory->meta->found_rows);
    }
	if ($prj) print "<iframe id=configurator_iframe style=\"height: 0px; width: 1050px; border: 0px; frameborder: 0px;\" frameBorder=\"No\"></iframe>\n";
	break;
  case ($report['source']=="inventory"):
  case (((sizeof($search_list)==1)) && ((sizeof($search_list[$category_id])==1))):
	$url_query=$url_query_base;
	$item=1;
	foreach ($search_list as $category_id => $category_list) {
		foreach ($category_list as $subcategory_id => $subcategory_list) {
	        $database->subcategory->read($subcategory_id);
	        $parameter_list=array();
	        foreach ($subcategory_list as $inventory) {
	            for ($i=0; $i < sizeof($database->subcategory->data->parameters); $i++) {
	                if ($inventory->data->parameters[$i]) {
	                    $parameter_list[$i][$inventory->data->parameters[$i]]++;
	                }
	            }
	        }
	        print "<table class='large noborder'>\n";
	        print "<tr>\n";
	        if ($database->subcategory->data->sketch_url) {
	            $image_url=$database->subcategory->data->sketch_url;
	          } else {
	            $image_url=$database->subcategory->data->image_url;
	        }
	        print "<td width=100%>";
	        print "<h2>" . $subcategory_list[0]->category_name . "<br>" . $database->subcategory->data->name . "</h2>\n";
	        print $database->subcategory->data->documents['description']->html . "</td>\n";
	        print "<td width=400>" . $menu->image($image_url,$database->subcategory->data->name,400) . "</td>\n";
	        print "</table>\n";
	        print "<table class='standard catalog'>\n";
	        print "<tr>\n";
	        print "<th>Part#</th>\n";
	        for ($i=0; $i < sizeof($database->subcategory->data->parameters); $i++) {
	            if (isset($parameter_list[$i])) print "<th>" . $database->subcategory->data->parameters[$i] . "</th>\n";
	        }
	        print "</tr>\n";
	        foreach ($subcategory_list as $key => $inventory) {
	            $url_query["item"]=$item;
	            print "<tr>\n";
	            print "<td class=nobr><a href='" . fn_url($_SERVER['PHP_SELF'],FALSE,$url_query) . "'>" . $inventory->data->sku . "</a></td>\n";
	            for ($i=0; $i < sizeof($database->subcategory->data->parameters); $i++) {
	                switch (TRUE) {
	                  case (!isset($parameter_list[$i])):
	                    break;
	                  case ((sizeof($parameter_list[$i]) == 1) && ($key==0)):
	                    print "<td rowspan=" . sizeof($subcategory_list) . ">" . $inventory->data->parameters[$i] . "</td>\n";
	                    break;
	                  case (sizeof($parameter_list[$i]) == 1):
	                    break;
	                  default:
	                    print "<td>" . $inventory->data->parameters[$i] . "</td>\n";
	                }
	            }
	            print "</tr>\n";
	            $item++;
	        }
	        print "</table>\n";
		}
	}
    if (isset($url_query['subcategory'])) {
    	$url_query=$url_query_base;
        unset($url_query['subcategory']);
	    $nav_url=array("return"=>fn_url($_SERVER['PHP_SELF'],FALSE,$url_query));
	    $menu->nav_field($nav_url);
	}
	break;
  default:
	foreach ($search_list as $category_id => $category_list) {
		$database->category->read($category_id);
        $url_query=$url_query_base;
    	$catalog_list=array();
        foreach ($category_list as $subcategory_id => $subcategory_list) {
			$catalog_item=new catalog_list_class();
			$catalog_item->count=sizeof($subcategory_list);
	        $catalog_item->id=$subcategory_id;
	        $catalog_item->name=$subcategory_list[0]->subcategory_name;
            if ($subcategory_list[0]->subcategory_image_url) {
	            $catalog_item->image_url=$subcategory_list[0]->subcategory_image_url;
			  } else {
	            $catalog_item->image_url=$subcategory_list[0]->subcategory_sketch_url;
			}
            $url_query['subcategory']=$subcategory_id;
            $catalog_item->url($url_query);
			$catalog_list[]=$catalog_item;
		}
		catalog_category($catalog_list,$url_query);
    }
    $url_query=$url_query_base;
    switch (TRUE) {
      case (isset($url_query['subcategory'])):
        unset($url_query['subcategory']);
		break;
	  default:
      	$url_query=array();
    }
    $nav_url=array("return"=>fn_url($_SERVER['PHP_SELF'],FALSE,$url_query));
    $menu->nav_field($nav_url);
}
print "</form>\n";
$database->disconnect();
$menu->copyright();
function catalog_category($catalog_list,$base_url_query=array()) {
	global $database, $menu;
	print "<table class='large noborder'>\n";
    print "<caption>&nbsp;</caption>\n";
    print "<tr>\n";
    print "<td width=200>" . $menu->image($database->category->data->image_url,$database->category->data->name,200) . "</td>\n";
    print "<td width=100%>\n";
    print "<h1>" . $database->category->data->name . "</h1>\n";
    print $database->category->data->documents['description']->html;
    print "</td>\n";
	print "</tr>\n";
	print "</table>\n";
	catalog_list($catalog_list);
}

function catalog_list($catalog_list) {
global $menu;
	print "<table class='standard noborder'>\n";
    $cols=6;
    $width=" width=" . floor(100 / $cols) . "%";
    $rows=ceil(sizeof($catalog_list)/$cols);
    for ($row=0; $row < ceil(sizeof($catalog_list)/$cols); $row++) {
        print "<tr>\n";
        for ($col=0; $col < $cols; $col++) {
            $cell=($row * $cols) + $col;
            if (!isset($catalog_list[$cell])) break;
            print "<td class=center $width>";
            print "<a href='" .$catalog_list[$cell]->href . "'>";
            print $menu->image($catalog_list[$cell]->image_url,$catalog_list[$cell]->name,89,89);
            print "</a>\n";
            print "</td>\n";
        }
        print "</tr>\n";
        print "<tr>\n";
        for ($col=0; $col < $cols; $col++) {
            $cell=($row * $cols) + $col;
            if (!isset($catalog_list[$cell])) break;
            print "<td class=center $width>";
            print "<a href='" .$catalog_list[$cell]->href . "'>";
            print $catalog_list[$cell]->name;
            print "</a>\n";
            print "</td>\n";
        }
        print "</tr>\n";
        print "<tr><td colspan=$cols>&nbsp;</td></tr>\n";
    }
    print "</table>\n";
}

class catalog_inventory_class {
	var $data;
    var $category_name;
    var $category_html;
    var $category_image_url;
    var $category_match;
    var $subcategory_name;
    var $subcategory_html;
    var $subcategory_image_url;
    var $subcategory_sketch_url;
    var $subcategory_match;
    function __construct ($inventory) {
    	$this->data=$inventory->data;
        $this->category_name=$inventory->fetch['category_name'];
        $documents=unserialize($inventory->fetch['category_documents']);
		$this->category_html=$documents['description']->html;
        $this->category_image_url=$inventory->fetch['category_image_url'];
        $this->category_match=$inventory->fetch['category_match'];
        $this->subcategory_name=$inventory->fetch['subcategory_name'];
        $documents=unserialize($inventory->fetch['subcategory_documents']);
		$this->subcategory_html=$documents['description']->html;
        $this->subcategory_image_url=$inventory->fetch['subcategory_image_url'];
        $this->subcategory_sketch_url=$inventory->fetch['subcategory_sketch_url'];
        $this->subcategory_match=$inventory->fetch['subcategory_match'];
    }
}
class catalog_list_class {
	var $count;
    var $id;
    var $name;
    var $image_url;
    var $href;
    function url($url_query) {
        $this->name .= " (" . $this->count . ")";
		$this->href=fn_url($_SERVER['PHP_SELF'],FALSE,$url_query);
    }
}
?>