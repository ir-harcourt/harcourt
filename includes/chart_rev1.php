<?php
class scs_chart_class {
	protected $width=800;
    protected $height=600;
    protected $label=array();
    protected $label_break=0;
    protected $item=array();
    protected $settings=array();
    protected $canvas_id;
    protected $data_id;
    protected $legend_id;
    protected $type="line";
    protected $color=array();
    protected $hover=array();
	function scs_table_version() {
		$results=array();
        $results['12/24/2018']="Add div function";
        $results['05/20/2015']="Add doughnut";
        $results['03/24/2015']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
    function __construct($id,$label,$options=array()) {
		$this->canvas_id="{$id}_canvas";
		$this->data_id="{$id}_data";
        if ($options['legend']) $this->legend_id="{$id}_legend";
		$this->label=$label;
		if ( (array_key_exists("width",$options)) && (intval($options['width'])) ) $this->width=intval($options['width']);
		if ( (array_key_exists("height",$options)) && (intval($options['height'])) ) $this->height=intval($options['height']);
		if ( (array_key_exists("type",$options)) && (in_array($options['type'],array("line","doughnut","pie"))) ) $this->type=$options['type'];
        $this->set_settings(array());
    }
    function set_settings($options=array()) {
//Line settings
        $this->settings['responsive']=FALSE;
        $this->settings['bezierCurve']=FALSE;
        $this->settings['pointDot']=FALSE;
        $this->settings['showTooltips']=TRUE;
        $this->settings['scaleBeginAtZero']=FALSE;
        $this->settings['scaleShowVerticalLines']=FALSE;
        foreach ($options as $key => $value) {
			switch (TRUE) {
			  case (array_key_exists($key,$this->settings)):
              case ($key == "legendTemplate"):
              	$this->settings[$key]=$value;
			}
        }
    }
    function get_settings() {
		if (!sizeof($this->settings)) return;
        $results=array();
        foreach ($this->settings as $key => $value) {
			switch (TRUE) {
              case (strlen($value)):
				$text="\"{$value}\"";
                break;
               case ($value):
            	$text="true";
				break;
               default:
				$text="false";
			}
			$results[]="$key: $text";
        }
        return "{ " . implode(", ",$results) . " }";
    }
    function load($name,$data,$decimal=0,$options=array()) {
        switch ($this->type) {
		  case "doughnut":
		  case "pie":
			foreach ($data as $key => $value) {
				$this->item[]=scs_chart_pie_class($key,$value,sizeof($this->item));
            }
            break;
		  default:
	        $results=array();
	        foreach ($data as $value) {
	            $results[]=round(floatval($value),$decimal);
	        }
			$this->item[]=new scs_chart_line_class($name,$results,$options);
		}
    }
    function div($canvas=TRUE,$options=array()) {
    	if (!array_key_exists("width",$options)) $options['width']=$this->width;
    	if (!array_key_exists("height",$options)) $options['width']=$this->height;
        if (!array_key_exists("class",$options)) $options['class']="chart-legend";
        switch (TRUE) {
          case ($canvas):
	    	return "<canvas id='" . $this->canvas_id . "' width=" . $this->width . " height=" . $this->height . "></canvas>";
          case ($this->legend_id):
			return "<div id='" . $this->legend_id . "' class='" . $options['class'] . "'></div>";
        }
    }
    function output() {
		switch ($this->type) {
		  case "pie":
		  case "doughnut":
			return $this->output_pie();
            break;
		  default:
			return $this->output_line();
        }
	}
    function output_pie() {
	    $results=array();
	    $results[]="<script language=javascript>";
	    $results[]="var " . $this->data_id . " = [";
        $results[]=implode(",\n",$this->item) . "\n";
	    $results[]="]";
		$results[]="var ctx=" . $this->canvas_id . ".getContext('2d');";
        $content=array();
        $content[]=$this->data_id;
        $content[]=$this->get_settings();
        if ($this->type == "doughnut") {
			$results[]="var chart=new Chart(ctx).Doughnut(" . implode(", ",$content) . ");";
		  } else {
			$results[]="var chart=new Chart(ctx).Pie(" . implode(", ",$content) . ");";
		}
	    if ($this->legend_id) $results[]="$('#" . $this->legend_id . "').html( chart.generateLegend() );";
	    $results[]="</script>";
        return implode("\n",$results);
    }
    function output_line() {
        $this->label_break=ceil($this->width / 20);
	    $mod=ceil(sizeof($this->label) / $this->label_break);
	    for ($i=0; $i < sizeof($this->label); $i++) {
	        if ($i % $mod) $this->label[$i]="";
	    }
        if (sizeof($this->label) > $this->label_break) $this->settings['showTooltips']=FALSE;
        $dataset=array();
        foreach ($this->item as $item) {
			$dataset[]=implode(",\n",$item->dataset);
        }
	    $results=array();
//	    $results[]="<canvas id='" . $this->canvas_id . "' width=" . $this->width . " height=" . $this->height . "></canvas>";
	    $results[]="<script language=javascript>";
	    $results[]="var " . $this->data_id . " = {";
	    $results[]="labels : ['" . implode("', '",$this->label) . "'],";
	    $results[]="datasets : [";
		$results[]="{" . implode("},\n{",$dataset) . "}";
	    $results[]="]";
	    $results[]="}";
	    $results[]="var ctx = " . $this->canvas_id . ".getContext('2d');";
        $content=array();
        $content[]=$this->data_id;
        $content[]=$this->get_settings();
	    $results[]="var chart = new Chart(ctx).Line(" . implode(", ",$content) . ");";
	    $results[]="</script>";
        return implode("\n",$results);
    }
}
class scs_chart_line_class {
	var $fields=array();
    var $dataset=array();
	function __construct($name, $data, $options=array()) {
		$this->fields["fillColor"]="rgba(220,220,220,0.2)";
		$this->fields["strokeColor"]="rgba(220,220,220,1)";
		$this->fields["pointColor"]="rgba(220,220,220,1)";
		$this->fields["pointStrokeColor"]="#fff";
		$this->fields["pointHighlightFill"]="#fff";
		$this->fields["pointHighlightStroke"]="rgba(220,220,220,1)";
		$this->dataset[]="label: " . fn_escape($name);
        foreach ($this->fields as $key => $value) {
			if (array_key_exists($key,$options)) $this->fields[$key]=$options[$key];
            $this->dataset[]="$key: " . fn_escape($this->fields[$key]);
        }
		$this->dataset[]="data : [" . implode(",",$data) . "]";
    }
}
function scs_chart_pie_class($name,$value,$item) {
	$color=array("#F7464A","#46BFBD","#FDB45C");

	$color=array(
    	"springgreen","steelblue","tan","tomato","violet","olive","orchid","rosybrown",
        "slategrey","forestgreen","indianred","dimgrey","dodgerblue","chocolate","blueviolet","coral",
        "darkorange","black","cyan","khaki","darkslategrey","cornflowerblue","goldenrod","lightseagreen");
	$results=array();
	$results[]="{";
	$results[]="value: $value,";
	$results[]="color:'" . $color[$item] . "',";
	$results[]="label: '$name'";
	$results[]="}";
    return implode("\n",$results);
}
?>