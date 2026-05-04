<?php
class schema_class {
	var $schema;
    var $schemas=array(
		"Offer"=>"An offer to transfer some rights to an item or to provide a service-for example, an offer to sell tickets to an event, to rent the DVD of a movie, to stream a TV show over the internet, to repair a motorcycle, or to loan a book.",
		"ProductModel"=>"A datasheet or vendor specification of a product (in the sense of a prototypical description).",
		"Product"=>"A product is anything that is made available for sale-for example, a pair of shoes, a concert ticket, or a car. Commodity services, like haircuts, can also be represented using this type.");
    var $items=array();
    var $error=array();
	function scs_table_version() {
		$results=array();
        $results['04/13/2014']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($schema="") {
		if ($schema) $this->load($schema);
    }
    function load($schema) {
		foreach ($this->schemas as $schema_code => $schema_name) {
			if (preg_match("/^" . $schema_code . "$/i","$schema") ) {
				$this->schema=$schema_code;
                break;
            }
        }
        if (!$this->schema) {
			$this->error[]="Invalid schema: $schema";
			return;
        }
        switch ($this->schema) {
		 case "Offer":
			$this->load_offer();
			$this->load_thing();
            break;
		 case "Product":
			$this->load_product();
			$this->load_thing();
            break;
		 case "ProductModel":
			$this->load_productmodel();
			$this->load_product();
			$this->load_thing();
            break;
		}
    }
    function load_property($property,$type,$description) {
		if (!array_key_exists($property,$this->items)) $this->items[$property]=new schema_item_class($type,$description);
    }
    function load_thing() {
	    $this->load_property("additionalType","URL","An additional type for the item, typically used for adding more specific types from external vocabularies in microdata syntax. This is a relationship between something and a class that the thing is in. In RDFa syntax, it is better to use the native RDFa syntax - the 'typeof' attribute - for multiple types. Schema.org tools may have only weaker understanding of extra types, in particular those defined externally.");
	    $this->load_property("alternateName","Text","An alias for the item.");
	    $this->load_property("description","Text","A short description of the item.");
	    $this->load_property("image","URL","URL of an image of the item.");
	    $this->load_property("name","Text","The name of the item.");
	    $this->load_property("sameAs","URL","URL of a reference Web page that unambiguously indicates the item's identity. E.g. the URL of the item's Wikipedia page, Freebase page, or official website.");
	    $this->load_property("url","URL","URL of the item.");
	}
    function load_productmodel() {
	    $this->load_property("isVariantOf","ProductModel","A pointer to a base product from which this product is a variant. It is safe to infer that the variant inherits all product features from the base model, unless defined locally. This is not transitive.");
	    $this->load_property("predecessorOf","ProductModel","A pointer from a previous, often discontinued variant of the product to its newer variant.");
	    $this->load_property("successorOf","ProductModel","A pointer from a newer variant of a product to its previous, often discontinued predecessor.");
    }
    function load_product() {
	    $this->load_property("aggregateRating","AggregateRating","The overall rating, based on a collection of reviews or ratings, of the item.");
	    $this->load_property("audience","Audience","The intended audience of the item, i.e. the group for whom the item was created.");
	    $this->load_property("brand","Brand, Organization","The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.");
	    $this->load_property("color","Text","The color of the product.");
	    $this->load_property("depth","Distance, QuantitativeValue","The depth of the product.");
	    $this->load_property("gtin13","Text","The GTIN-13 code of the product, or the product to which the offer refers. This is equivalent to 13-digit ISBN codes and EAN UCC-13. Former 12-digit UPC codes can be converted into a GTIN-13 code by simply adding a preceeding zero.");
	    $this->load_property("gtin14","Text","The GTIN-14 code of the product, or the product to which the offer refers.");
	    $this->load_property("gtin8","Text","The GTIN-8 code of the product, or the product to which the offer refers. This code is also known as EAN/UCC-8 or 8-digit EAN.");
	    $this->load_property("height","Distance, QuantitativeValue","The height of the item.");
	    $this->load_property("isAccessoryOrSparePartFor","Product","A pointer to another product (or multiple products) for which this product is an accessory or spare part.");
	    $this->load_property("isConsumableFor","Product","A pointer to another product (or multiple products) for which this product is a consumable.");
	    $this->load_property("isRelatedTo","Product","A pointer to another, somehow related product (or multiple products).");
	    $this->load_property("isSimilarTo","Product","A pointer to another, functionally similar product (or multiple products).");
	    $this->load_property("itemCondition","OfferItemCondition","A predefined value from OfferItemCondition or a textual description of the condition of the product or service, or the products or services included in the offer.");
	    $this->load_property("logo","ImageObject, URL","A logo associated with an organization.");
	    $this->load_property("manufacturer","Organization","The manufacturer of the product.");
	    $this->load_property("model","Text, ProductModel","The model of the product. Use with the URL of a ProductModel or a textual representation of the model identifier. The URL of the ProductModel can be from an external source. It is recommended to additionally provide strong product identifiers via the gtin8/gtin13/gtin14 and mpn properties.");
	    $this->load_property("mpn","Text","The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers.");
	    $this->load_property("offers","Offer","An offer to transfer some rights to an item or to provide a service-for example, an offer to sell tickets to an event, to rent the DVD of a movie, to stream a TV show over the internet, to repair a motorcycle, or to loan a book.");
	    $this->load_property("productID","Text","The product identifier, such as ISBN. For example: <meta itemprop='productID' content='isbn:123-456-789'/>.");
	    $this->load_property("releaseDate","Date","The release date of a product or product model. This can be used to distinguish the exact variant of a product.");
	    $this->load_property("review","Review","A review of the item. Supercedes reviews.");
	    $this->load_property("sku","Text","The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers.");
	    $this->load_property("weight","QuantitativeValue","The weight of the product.");
	    $this->load_property("width","Distance, QuantitativeValue","The width of the item.");
    }
    function load_offer() {
	    $this->load_property("acceptedPaymentMethod","PaymentMethod","The payment method(s) accepted by seller for this offer.");
	    $this->load_property("addOn","Offer","An additional offer that can only be obtained in combination with the first base offer (e.g. supplements and extensions that are available for a surcharge).");
	    $this->load_property("advanceBookingRequirement","QuantitativeValue","The amount of time that is required between accepting the offer and the actual usage of the resource or service.");
	    $this->load_property("aggregateRating","AggregateRating","The overall rating, based on a collection of reviews or ratings, of the item.");
	    $this->load_property("availability","ItemAvailability","The availability of this item-for example In stock, Out of stock, Pre-order, etc.");
	    $this->load_property("availabilityEnds","DateTime","The end of the availability of the product or service included in the offer.");
	    $this->load_property("availabilityStarts","DateTime","The beginning of the availability of the product or service included in the offer.");
	    $this->load_property("availableAtOrFrom","Place","The place(s) from which the offer can be obtained (e.g. store locations).");
	    $this->load_property("availableDeliveryMethod","DeliveryMethod","The delivery method(s) available for this offer.");
	    $this->load_property("businessFunction","BusinessFunction","The business function (e.g. sell, lease, repair, dispose) of the offer or component of a bundle (TypeAndQuantityNode). The default is http://purl.org/goodrelations/v1#Sell.");
	    $this->load_property("category","Thing, Text","PhysicalActivityCategory","A category for the item. Greater signs or slashes can be used to informally indicate a category hierarchy.");
	    $this->load_property("deliveryLeadTime","QuantitativeValue","The typical delay between the receipt of the order and the goods leaving the warehouse.");
	    $this->load_property("eligibleCustomerType","BusinessEntityType","The type(s) of customers for which the given offer is valid.");
	    $this->load_property("eligibleDuration","QuantitativeValue","The duration for which the given offer is valid.");
	    $this->load_property("eligibleQuantity","QuantitativeValue","The interval and unit of measurement of ordering quantities for which the offer or price specification is valid. This allows e.g. specifying that a certain freight charge is valid only for a certain quantity.");
	    $this->load_property("eligibleRegion","Text, GeoShape","The ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, or the GeoShape for the geo-political region(s) for which the offer or delivery charge specification is valid.");
	    $this->load_property("eligibleTransactionVolume","PriceSpecification","The transaction volume, in a monetary unit, for which the offer or price specification is valid, e.g. for indicating a minimal purchasing volume, to express free shipping above a certain order volume, or to limit the acceptance of credit cards to purchases to a certain minimal amount.");
	    $this->load_property("gtin13","Text","The GTIN-13 code of the product, or the product to which the offer refers. This is equivalent to 13-digit ISBN codes and EAN UCC-13. Former 12-digit UPC codes can be converted into a GTIN-13 code by simply adding a preceeding zero.");
	    $this->load_property("gtin14","Text","The GTIN-14 code of the product, or the product to which the offer refers.");
	    $this->load_property("gtin8","Text","The GTIN-8 code of the product, or the product to which the offer refers. This code is also known as EAN/UCC-8 or 8-digit EAN.");
	    $this->load_property("includesObject","TypeAndQuantityNode","This links to a node or nodes indicating the exact quantity of the products included in the offer.");
		$this->load_property("inventoryLevel","QuantitativeValue","The current approximate inventory level for the item or items.");
	    $this->load_property("itemCondition","OfferItemCondition","A predefined value from OfferItemCondition or a textual description of the condition of the product or service, or the products or services included in the offer.");
	    $this->load_property("itemOffered","Product","The item being offered.");
		$this->load_property("mpn","Text","The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers.");
	    $this->load_property("price","Text,Number","The offer price of a product, or of a price component when attached to PriceSpecification and its subtypes.");
	    $this->load_property("priceCurrency","Text","The currency (in 3-letter ISO 4217 format) of the offer price or a price component, when attached to PriceSpecification and its subtypes.");
	    $this->load_property("priceSpecification","PriceSpecification","One or more detailed price specifications, indicating the unit price and delivery or payment charges.");
	    $this->load_property("priceValidUntil","Date","The date after which the price is no longer available.");
	    $this->load_property("review","Review","A review of the item. Supercedes reviews.");
	    $this->load_property("seller","Organization, Person","The organization or person making the offer.");
	    $this->load_property("serialNumber","Text","The serial number or any alphanumeric identifier of a particular product. When attached to an offer, it is a shortcut for the serial number of the product included in the offer.");
	    $this->load_property("sku","Text","The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers.");
	    $this->load_property("validFrom","DateTime","The date when the item becomes valid.");
	    $this->load_property("validThrough","DateTime","The end of the validity of offer, price specification, or opening hours data.");
	    $this->load_property("warranty","WarrantyPromise","The warranty promise(s) included in the offer.");
	}
    function json($debug=FALSE) {
		$results=array();
		$results[]="<script type='application/ld+json'>";
        $results[]="{";
	    $results[]=fn_escape("@context") . " : " . fn_escape("http://schema.org");
	    $results[]=fn_escape("@type") . " : " . fn_escape($this->schema);
		foreach ($this->items as $key => $data) {
			if (strlen($data->value)) $results[]=fn_escape("@$key") . " : " . fn_escape($data->value);
        }
        $results[]="}";
		$results[]="</script>";
        if ($debug) {
        	return $results;
		  } else {
			return "\n" . implode("\n",$results) . "\n";
		}
    }
    function item($item,$value,$type="") {
		switch (TRUE) {
          case (!array_key_exists($item,$this->items)):
			$this->error[]="$item not defined for " . $this->items['type']->value;
			break;
		  case (strlen($this->items[$item]->value)):
			$this->error[]="$item content already set";
            break;
		  default:
        	$this->items[$item]->value=trim($value);
			$this->items[$item]->value_type=$type;
		}
    }
    function html() {
    global $forms;
	    $results=array();
        $results[]="<p class=standard><b>Schema: " . $this->schema . "</b>";
        $results[]="<br>" . $this->schemas[$this->schema] . "</p>";
	    $results[]="<table class='small border'>";
	    $results[]="<tr>";
	    $results[]="<th width='15%'>Property</th>";
	    $results[]="<th width='15%'>Value</th>";
	    $results[]="<th width='15%'>Type(s)</th>";
	    $results[]="<th width='35%'>Description</th>";
	    $results[]="<th width='20%'>JSON</th>";
	    $results[]="</tr>";
	    $first_pass=TRUE;
	    foreach ($this->items as $property => $data) {
	        $results[]="<tr>";
	        $results[]="<td>" . $property . "</td>";
	        if ($data->value) {
	        	$bg=$forms->background->green;
	          } else {
	        	$bg="";
	        }
	        $results[]="<td $bg>" . $data->value . "</td>";
	        $results[]="<td>" . implode("<br>",$data->type) . "</td>";
	        $results[]="<td>" . htmlspecialchars($data->description, ENT_QUOTES) . "</td>";
	        if ($first_pass) {
				$first_pass=FALSE;
				$results[]="<td rowspan=" . sizeof($this->items) . " >" . str_replace("\n","<br>",htmlspecialchars(trim($this->json() ), ENT_QUOTES)) . "</td>";
	        }
	        $results[]="</tr>\n";
		}
	    $results[]="</table>";
        return implode("\n",$results);

    }
}
class schema_item_class {
    var $description;
    var $type=array();
    var $value;
    var $value_type;
    function __construct($type,$description) {
		$this->type=array_map("trim", explode(",",$type));
		$this->description=$description;
    }
}
?>