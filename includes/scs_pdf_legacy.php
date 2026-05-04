<?php
require_once "dompdf/dompdf_config.inc.php";
class scs_pdf_class {
    var $file;
    var $stream;
    var $paper;
    var $orientation;
	var $pdf;
    function scs_table_version() {
    	$results=array();
        $results['12/29/2017']="Initial release/Based on dompdf 0.7.0";
		$results['file']= __FILE__;
        return $results;
    }
    function __construct($html,$options=array()) {
		switch (TRUE) {
          case (!is_array($options)):
          	die("PDF options must be an array");
          case (!array_key_exists("file",$options)):
          	break;
          case (is_array($options["file"])):
          	$this->file=implode(DIRECTORY_SEPARATOR,$options["file"]);
            break;
          default:
          	$this->file=$options["file"];
		}
        if (!strlen($this->file)) $this->file=strtotime("now") . ".pdf";
        $this->stream=( (array_key_exists("stream",$options)) ? $options['stream'] : TRUE);
        $this->paper=( (array_key_exists("paper",$options)) ? $options['paper'] : "letter");
        $this->orientation=( (array_key_exists("orientation",$options)) ? $options['orientation'] : "portrait");
		$this->pdf=new DOMPDF();
    	$this->pdf->load_html( (is_array($html) ? implode("",$html) : $html));
        $this->pdf->render();
        if ($this->stream) {
	        $this->pdf->stream($this->file);
	        die();
		  } else {
			file_put_contents($this->file, $this->pdf->output());
		}
    }
}
?>