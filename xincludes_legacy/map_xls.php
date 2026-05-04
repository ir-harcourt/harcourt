<?php
require_once "map_rev0.php";
require_once "ExcelWriterXML/ExcelWriterXML.php";
class xls_class {
	var $workbook;
    var $worksheet;
    var $map=array();
    var $row=0;
	function __construct($file,$title="",$subject="") {
    global $database;
	    $this->workbook = new ExcelWriterXML($file);
	    $this->workbook->overwriteFile(TRUE);
	    $this->workbook->docTitle($title);
	    $this->workbook->docSubject($subject);
	    $this->workbook->docAuthor($_SESSION['user']->name);
	    $this->workbook->docCompany($database->registry->company->name);

	    $style=$this->workbook->addStyle('alphanumeric');
	    $style->alignWraptext();
	    $style->alignVertical('Top');

	    $style=$this->workbook->addStyle('integer');
	    $style->numberFormat("#0");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal0');
	    $style->numberFormat("#,##0");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal1');
	    $style->numberFormat("#,##0.0");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal2');
	    $style->numberFormat("#,##0.00");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal3');
	    $style->numberFormat("#,##0.000");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal4');
	    $style->numberFormat("#,##0.0000");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('decimal5');
	    $style->numberFormat("#,##0.00000");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Right');

	    $style=$this->workbook->addStyle('date');
	    $style->numberFormat("mm/dd/yyyy");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');

	    $style=$this->workbook->addStyle('time');
	    $style->numberFormat('hh:mm:ss');
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');

	    $style=$this->workbook->addStyle('datetime');
	    $style->numberFormat("mm/dd/yyyy hh:mm:ss");
	    $style->alignVertical('Top');
	    $style->alignHorizontal('Left');
    }
    function worksheet($name,$map) {
        $this->map=$map;
	    $this->worksheet=$this->workbook->addSheet($name);
		$count=0;
        foreach ($this->map as $field => $item) {
			if ($item->export) {
	            $count++;
                switch (TRUE) {
                  case ($item->xls->width < strlen($item->xls->name)):
					$width=strlen($item->xls->name);
                    break;
                  case ($item->xls->width > 100):
                  	$width=100;
                    break;
                  default:
                  	$width=$item->xls->width;
				}
	            $this->worksheet->columnWidth($count,($width * 6));
			}
        }
        $this->row=0;
		$data=array();
        foreach ($this->map as $field => $item) {
			if ($item->export) {
            	if ($item->xls->name) {
					$data[$field]=$item->xls->name;
				  } else {
					$data[$field]=$field;
				}
			}
        }
        $this->row($data,"db_header",$format="string");
    }
    function row($data,$style_override="") {
//integer, float, numeric, decimal:n percent:n date datetime timestamp uppercase lowercase boolean crlf map
		$this->row++;
		$count=0;
        foreach ($this->map as $field => $item) {
			if ($item->export) {
	            $count++;
                $text=$data[$field];
                if (is_array($text)) $text=implode("\n",$text);
                switch (TRUE) {
                  case ($style_override):
                  	$style=$style_override;
					break;
				  case ($item->type=="integer"):
                  	$style="integer";
                    $format="numeric";
                  	break;
                  case ($item->type=="decimal"):
                  	$style="decimal" . $item->decimal;
                    $format="numeric";
                  	break;
				  case ($item->type=="ymd"):
                  	$style="date";
                    $format="date";
                    $date=strtotime($text);
                    if ($date) {
						$text=date("Y-m-d",$date) . "T00:00:00.000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="date"):
                  	$style="date";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",$text) . "T00:00:00.000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="time"):
                  	$style="time";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",$text) . "T" . date("H:i:s",$text) . ".000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="datetime"):
				  case ($item->type=="timestamp"):
                  	$style="datetime";
                    $format="date";
                    if ($text) {
						$text=date("Y-m-d",$text) . "T" . date("H:i:s",$text) . ".000";
					  } else {
						$text="";
					}
                    break;
				  case ($item->type=="boolean"):
                  	$style="alphanumeric";
					if (in_array($text,array("1","Y","y"))) {
						$text="Y";
					  } else {
                      	$text="N";
                    }
                    $format="string";
                    break;
                  default:
                  	$style="alphanumeric";
                    $format="string";
                }
                switch ($format) {
				  case "numeric":
					$this->worksheet->writeNumber($this->row,$count,$text,$style);
                    break;
				  case "date":
					$this->worksheet->writeDateTime($this->row,$count,$text,$style);
                    break;
				  default:
					$this->worksheet->writeString($this->row,$count,$text,$style);
				}
			}
        }
    }
    function write() {
	    $this->workbook->sendHeaders();
	    $this->workbook->writeData();
    }
}
?>