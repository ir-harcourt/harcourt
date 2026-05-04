<?php
/*
http://localhost:8085//image_filmstrip.php?api_key=67bb9773abba2e7561c2b16719afd67e493d2b92
*/
require_once "scs_header.php";
$obj=new local_class($forms->cron);
class local_class {
	var $offset=0;
	var $dimension_x=80;
	var $dimension_y=80;
	var $css=array();
	var $url="scs_scripts/catalog_images.png";
    var $cron=0;
    var $timestamp=0;
	function scs_table_version() {
		$results=array();
        $results['09/29/2025']="Add cron output";
        $results['11/28/2023']="Add CRON";
        $results['10/24/2023']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct($cron) {
        global $database;
        $this->cron=$cron;
        $this->timestamp=strtotime("now");
		$this->input=new stdClass();
        switch (TRUE) {
          case ($this->cron):
            die( $this->process() );
          case (!$database->user->access("Administrator",FALSE)):
            die("Access denied");
          case (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])):
        	$this->json();
            break;
          default:
          	$this->html();
		}
    }
    function json() {
        global $database, $forms;
    	$this->response=new stdClass();

        die(json_encode($this->response));
    }
    function html() {
        global $database, $forms, $menu;
        switch (TRUE) {
          case ($_POST['action']):
            $this->process();
            break;
        }
	    $forms->title("Image Strip (Beta)");
        $forms->message[]="Revised " . key($this->scs_table_version());
        $forms->message[]="Last Update: " . ( ($database->registry->local->filmstrip_timestamp) ? date("r", $database->registry->local->filmstrip_timestamp) : "Never");
	    $menu->head();
	    print $forms->message();
        print $this->js();
        print $forms->open();
        print $forms->hidden("action","update");
        if ($_POST['action']) {
            print "<p>Items processed: " . number_format($this->offset + 1) . "</p>";
		  } else {
        	print "<p id=submit_button>" . $forms->button("Go",array("onclick"=>"fn_action();") ) . "</p>";
        }
        print $forms->close();
        $menu->copyright();
    }
    function process() {
    global $database;
    	$this->css[]="/* Generated: " . date("r", $this->timestamp) . " */";
        $this->css[]="";
		$this->css[]=".catalog_subcategory_list span {";
        $this->css[]="	width: {$this->dimension_x}px;";
        $this->css[]="	height: {$this->dimension_x}px;";
        $this->css[]="	display: block;";
        $this->css[]="	background-image: url(\"/" . $this->url . "?ver=" . $this->timestamp . "\");";
        $this->css[]="	background-position-y: center;";
		$this->css[]="}";
        $this->css[]="";
        $query=array();
        $query[]="select * from subcategory";
        $query[]="order by id";
        $database->subcategory->query($query);

        $this->img=imagecreatetruecolor($database->subcategory->meta->rows * $this->dimension_x, $this->dimension_y);
	    $transparent = imagecolorallocatealpha($this->img, 0, 0, 0, 127 );
	    imagefill( $this->img, 0, 0, $transparent );
        while ($database->subcategory->fetch = $database->subcategory->fetch_array()) {
        	$database->subcategory->fetch();
 			$this->process_css($this->process_item());
		}
	    imagealphablending($this->img, false);
	    imagesavealpha($this->img,true);

		imagepng($this->img, $this->url);
		imagedestroy($this->img);
        file_put_contents("scs_scripts/catalog.css",implode("\n",$this->css));
        $options=array();
        $options['comment']="Items processed: " . number_format($this->offset + 1);
        if ($this->cron) $options['cron']=$this->cron;
        $database->log->update("cron:Filmstrip", $options);
        $database->registry->update("local", "filmstrip_timestamp", $this->timestamp);
        $database->registry->load("local");
        return $options['comment'];
    }
    function process_item() {
    global $database;
        $image_url=$_SERVER['DOCUMENT_ROOT'] . $database->subcategory->data->thumbnail_url;
        switch (TRUE) {
          case (!strlen($database->subcategory->data->thumbnail_url)):
          case (!file_exists($image_url)):
            return 0;
		}
        switch (exif_imagetype($image_url)) {
          case (IMAGETYPE_GIF):
          	$thumbnail=imagecreatefromgif($image_url);
            break;
          case (IMAGETYPE_JPEG):
          	$thumbnail=imagecreatefromjpeg($image_url);
            break;
          case (IMAGETYPE_PNG):
          	$thumbnail=@imagecreatefrompng($image_url);
            break;
		  default:
            return 0;
        }
        $this->offset++;
		imagecopy($this->img, $thumbnail, ($this->offset * $this->dimension_x), 0, 0, 0, $this->dimension_x, $this->dimension_y);
        imagedestroy($thumbnail);
		return $this->offset;
    }
    function process_css($offset) {
    global $database;
	    $text=(!$offset) ? "0px 0" : "-" . ($offset * $this->dimension_x) . "px";
	    $this->css[]=".subcat" . $database->subcategory->data->id . " { background-position: {$text}; }";
    }
    function js() {
        $results=<<<EOT

<script>
function fn_action() {
    jQuery("#submit_button").html("Working ...");
    jQuery("#scs_form").submit();
}
</script>

EOT;
        return $results;
    }
}
?>