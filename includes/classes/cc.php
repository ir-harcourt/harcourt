<?php
//$cc=new cc_class();
class cc_class {
    var $data=array();
    var $compare=array();
    var $constant=array();
    var $error=array();
    function __construct($terms=FALSE,$forced=TRUE) {
        $this->data=new cc_data_class();
        $this->compare=new cc_data_class();
        $this->constant=new cc_constant_class($terms,$forced);
    }
    function scs_version() {
    	$results=array();
		$results['10/26/2014']="Expand to include unencoded accounts";
        $results['06/11/2014']="2.0 release";
        $results['10/13/2012']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function load($table,$id,$field="cc_data") {
    global $database;
	    $this->data=new cc_data_class();
	    $this->compare=new cc_data_class();
        $this->constant->database_table=$table;
        $this->constant->database_field=$field;
        $fields=array();
        if (!is_array($this->constant->database_field)) {
			$fields[]="des_decrypt(" . $field . ") as 'cc_data'";
		  } else {
			foreach ($this->constant->database_field as $internal => $external) {
				$fields[]="$external as '$internal'";
            }
        }
        $query=
        	"select \n" .
            implode(",\n",$fields) . "\n" .
            "from " . $table . "\n" .
            "where id=" . fn_escape($id);
        $database->temp->query($query);
		$database->temp->fetch();
        switch (TRUE) {
          case (!$id):
          case (!$database->temp->meta->rows):
          	break;
		  case (!is_array($this->constant->database_field)):
            $data=unserialize($database->temp->fetch['cc_data']);
			if (is_object($data)) {
            	$this->data=$data;
	            $this->compare=$data;
			}
            break;
		  default:
			foreach ($this->constant->database_field as $internal => $external) {
				$this->data->$internal=$database->temp->fetch[$internal];
			}
			list($year,$month,$day)=preg_split("/-/",$this->data->expiry);
            $this->data->expiry_year=intval($year);
            $this->data->expiry_month=intval($month);
            $this->data->expiry=substr("0000" . $this->data->expiry_year,-4) . "/" . substr("00" . $this->data->expiry_month, -2);
            $this->data->mask();
	        $this->compare=$this->data;
		}
    }
    function update_query() {
		$results=array();
		switch (TRUE) {
		  case (!$this->constant->database_table):
          case ($this->data == $this->compare):
          	break;
		  case (!is_array($this->constant->database_field)):
			$results[]=$this->constant->database_field . "=des_encrypt(" . fn_escape(serialize($this->data)) . ")";
            break;
		  default:
			foreach ($this->constant->database_field as $internal => $external) {
            	switch ($internal) {
                  case "expiry":
                  	$text=fn_escape($this->data->{$internal} . "/01",TRUE);
                  	break;
                  default:
                  	$text=fn_escape($this->data->{$internal});
				}
				$results[]="{$external}=$text";
            }
        }
        return implode(",\n",$results);
    }
    function verify($cvv=TRUE) {
    global $forms;
        if ($_POST['cc_debug']) $forms->error('cc_debug');
		$this->data=new cc_data_class();
		$this->data->card=$_POST['cc_card'];
		if ( ($this->constant->forced) && (!$this->data->card) ) $forms->error('cc_card');
        if (in_array($this->data->card,array("","Terms"))) return;
		$this->data->account=fn_text($_POST['cc_account']);
		switch (TRUE) {
          case ( ($this->data->card == $this->compare->card) && ($this->data->account == $this->compare->mask) ):
			$this->data->account=$this->compare->account;
            break;
          case (preg_match("/^example$/i",$this->data->account)):
			$this->data->account=$this->constant->card_list[$this->data->card]->example;
        	break;
        }
        $account=ereg_replace("[^0-9]","",$this->data->account);
		$_account_rev = strrev(ereg_replace("[^0-9]", "",$account));
	    $_multiplier=1;
	    $_sum=0;
	    for($i=0; $i < strlen($_account_rev); $i++) {
	        $_char = substr($_account_rev, $i, 1);
	        $_tmp=($_char * $_multiplier);
	        if ($_tmp > 9) $_tmp= (1 + ($_tmp - 10));
	        $_sum += $_tmp;
	        if ($_multiplier == 1) {
	            $_multiplier=2;
	          } else {
	            $_multiplier=1;
	        }
	    }
        switch (TRUE) {
		  case (!preg_match($this->constant->card_list[$this->data->card]->pattern,$account)):
          case ($_sum % 10 != 0):
			$forms->error('cc_account',"Invalid " . $this->data->card . " format");
            break;
		  default:
			$this->data->mask();
		}
        if ( ($cvv) && ($this->constant->card_list[$this->data->card]->cvv) ) {
            $this->data->cvv=trim($_POST['cc_cvv']);
            switch (TRUE) {
              case (!is_numeric($this->data->cvv)):
              case (strlen($this->data->cvv) != $this->constant->card_list[$this->data->card]->cvv):
                $forms->error('cc_cvv',"CVV must be " . $this->constant->card_list[$this->data->card]->cvv . " digits");
            }
        }
		$this->data->expiry_month=intval($_POST['cc_expiry_month']);
		$this->data->expiry_year=intval($_POST['cc_expiry_year']);
		if (!$this->data->expiry_month) $forms->error('cc_expiry_month');
		if (!$this->data->expiry_year) $forms->error('cc_expiry_year');
		if ( ($this->data->expiry_month) && ($this->data->expiry_year) ) {
			$this->data->expiry=$this->data->expiry_year . "/" . substr("00" . $this->data->expiry_month,-2);
            if ( ($this->constant->forced) && ($this->data->expiry < date("Y/m")) ) $forms->error('cc_expiry_year',"","Card has expired");
        }
    }
    function html_input($cvv=TRUE) {
    global $database, $forms;
	    if ( ($this->data->expiry_year) && (!array_key_exists($this->data->expiry_year,$this->constant->expiry_year)) ) {
	        $this->constant->expiry_year[$this->data->expiry_year]=$this->data->expiry_year;
	        ksort($this->constant->expiry_year);
	    }
		$forced=$forms->font("red","* ");
		$results=array();
	    $results[]="<tr>";
	    $results[]="<td>" . $forced . "Payment Type</td>";
	    $results[]="<td>" . $forms->select("cc_card",$this->constant->card,$this->data->card,TRUE) . "</td>";
	    $results[]="</tr>";
 	    $results[]="<tr>";
	    $results[]="<td>" . $forced . "Credit Card#</td>";
        if ( ($this->data->card == $this->compare->card) && ($this->data->account == $this->compare->account) ) {
            $account=$this->data->mask;
          } else {
            $account=$this->data->account;
        }
        $results[]="<td>" . $forms->text("cc_account",$account,20,20);
        if ($forms->error_text['cc_account']) $results[]=" " . $forms->error_text['cc_account'];
        $results[]="</td>";
        $results[]="</tr>";
        if ($cvv) {
	        $results[]="<tr>";
	        $results[]="<td>" . $forced . "CVV</td>";
	        $results[]="<td>" . $forms->text("cc_cvv",$this->data->cvv,4,4);
	        if ($forms->error_text['cc_cvv']) $results[]=" " . $forms->error_text['cc_cvv'];
	        $results[]="</td>";
	        $results[]="</tr>";
		}
	    $results[]="<tr>";
	    $results[]="<td>" . $forced . "Expires</td>";
        $results[]=
            "<td>" . $forms->select("cc_expiry_month",$this->constant->expiry_month,$this->data->expiry_month,"Month") .
            $forms->select("cc_expiry_year",$this->constant->expiry_year,$this->data->expiry_year,"Year") . "</td>";
        $results[]="</td>";
		$results[]="</tr>";

		if ($database->user->access("Super User",FALSE)) {
	        $results[]="<tr>";
	        $results[]="<td>SCS CC Debug? " . $forms->checkbox('cc_debug',1,$_POST['cc_debug']) . "</td>";
            $results[]="<td>";
            if ($_POST['cc_debug']) {
				$results[]="<table class='border'>";
                $results[]="<tr><td width='33%'>key</td><td width='33%'>data</td><td width='33%'>compare</td></tr>";
				foreach ($this->data as $key => $value) {
					$results[]="<tr><td>$key</td><td>$value</td><td>" . $this->compare->{$key} . "</td></tr>";
	            }
	            $results[]="</table>";
			}
			$results[]="</td>";
			$results[]="</tr>";
		}
		return implode("\n",$results);
    }
    function html($mask=TRUE) {
    global $database, $forms;
		$results=array();
	    $results[]="<tr>";
	    $results[]="<td>Payment Type</td>";
	    $results[]="<td>" . $this->data->card . "</td>";
	    $results[]="</tr>";
        switch (TRUE) {
          case ($input):
          case ($this->data->card != "Terms"):
 	        $results[]="<t>";
	        $results[]="<td>Credit Card#</td>";
            if ($mask) {
            	$text=$this->data->mask;
			  } else {
	            $text=$this->data->account;
	        }
	        $results[]="<td>$text</td>";
	        $results[]="</tr>";
            if (strlen($this->data->cvv)) {
	        	$results[]="<tr>";
	            $results[]="<td>CVV</td>";
	            $results[]="<td>" . $this->data->cvv . "</td>";
	        	$results[]="</tr>";
			}
	        $results[]="<tr>";
	        $results[]="<td>Expires</td>";
	        $results[]="<td>" . fn_date($this->data->expiry,"yymm") . "</td>";
	        $results[]="</td>";
	        $results[]="</tr>";
		}
		return implode("\n",$results);
    }
}
class cc_data_class {
    var $card="";
	var $account="";
    var $expiry;
    var $expiry_year=0;
    var $expiry_month=0;
    var $cvv="";
    var $name;
    var $address;
    var $city;
    var $state;
    var $zip;
    var $country_code;
    var $mask;
	function mask() {
		$this->mask="";
	    $account = ereg_replace("[^0-9]", "",$this->account);
	    if (strlen($account) > 8) $this->mask=substr_replace($account,"....",0,-4);
    }
}
class cc_constant_class {
	var $card=array();
    var $card_list=array();
    var $expiry_month=array();
    var $expiry_year=array();
    var $terms;
    var $forced;
    var $database_table;
    var $database_field;
	function __construct($terms,$forced) {
	global $database;
		$this->terms=$terms;
        $this->forced=$forced;
		$this->card_list["Terms"]=new cc_card_class("",0,"");
		$this->card_list["American Express"]=new cc_card_class("/^3[4|7][0-9]{13}$/",4,"3700 0000 0000 002");
		$this->card_list["Diners Club"]=new cc_card_class("/^30[0-5][0-9]{11}$|^3[6|8][0-9]{12}$/",0,"3000 0000 0000 04");
		$this->card_list["Discover"]=new cc_card_class("/^6011[0-9]{12}$/",3,"6011 0000 0000 0004");
		$this->card_list["JCB"]=new cc_card_class("/^3[0-9]{15}$|^[2131|1800][0-9]{11}$/",0,"2131 0000 0008");
		$this->card_list["MasterCard"]=new cc_card_class("/^5[1-5][0-9]{14}$/",3,"5500 0000 0000 0004");
		$this->card_list["Visa"]=new cc_card_class("/^4[0-9]{12}$|^4[0-9]{15}$/",3,"4000 0000 0000 0002");
		if (is_array($database->registry->cc->card)) {
        	foreach ($database->registry->cc->card as $card) {
				switch (TRUE) {
				  case (!array_key_exists($card,$this->card_list)):
                  case ((!$terms) && ($card == "Terms")):
                  	break;
				  default:
                  	$this->card[]=$card;
                }
            }
		}
//		$this->expiry_month['0']="Month";
        for ($i=1; $i < 13; $i++) {
			$this->expiry_month[$i]=date("F",strtotime($i . "/01/2010"));
        }
//		$this->expiry_year['0']="Year";
        $year=date("Y");
        for ($i=0; $i < 11; $i++) {
			$this->expiry_year[$year]=$year;
			$year++;
        }
    }
}
class cc_card_class {
	var $pattern;
    var $cvv;
	var $example;
    function __construct($pattern,$cvv,$example) {
		$this->pattern=$pattern;
        $this->cvv=$cvv;
        $this->example=$example;
    }
}
?>